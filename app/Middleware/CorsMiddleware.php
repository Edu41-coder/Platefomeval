<?php

namespace App\Middleware;

use Core\Middleware\MiddlewareInterface;
use Core\Middleware\MiddlewareException;
use Core\Http\Request;
use Core\Http\Response;
use Core\Http\JsonResponse;

/**
 * Middleware de gestion des CORS (Cross-Origin Resource Sharing)
 */
class CorsMiddleware implements MiddlewareInterface
{
    /**
     * Configuration CORS
     */
    protected array $config;

    /**
     * En-têtes par défaut
     */
    protected const DEFAULT_HEADERS = [
        'Content-Type',
        'Authorization',
        'X-Requested-With',
        'Accept',
        'Origin',
        'X-CSRF-TOKEN',
        'Cache-Control',
        'If-Match',
        'If-None-Match'
    ];

    /**
     * Méthodes HTTP valides
     */
    protected const VALID_METHODS = [
        'GET',
        'POST',
        'PUT',
        'DELETE',
        'OPTIONS',
        'PATCH',
        'HEAD'
    ];

    /**
     * Durée maximale du cache en secondes (24h)
     */
    protected const MAX_CACHE_AGE = 86400;

    /**
     * Constructeur
     * @throws MiddlewareException Si la configuration ne peut pas être chargée
     */
    public function __construct()
    {
        $configPath = $_SERVER['DOCUMENT_ROOT'] . '/Plateformeval/app/config/config.php';
        
        if (!file_exists($configPath)) {
            throw new MiddlewareException('Configuration CORS introuvable');
        }

        $this->config = require $configPath;
        $this->config = $this->config['cors'] ?? [];
    }

    /**
     * Gère les en-têtes CORS
     * 
     * @param Request $request Requête HTTP
     * @param callable $next Fonction suivante dans la chaîne des middlewares
     * @return Response Réponse HTTP
     * @throws MiddlewareException Si une erreur CORS se produit
     */
    public function handle(Request $request, callable $next): Response
    {
        try {
            // Vérifier si CORS est activé
            if (!($this->config['enabled'] ?? true)) {
                return $next($request);
            }

            // Démarrer la session si elle n'est pas déjà démarrée
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }

            $this->setCorsHeaders($request);

            // Répondre directement aux requêtes OPTIONS (preflight)
            if ($request->getMethod() === 'OPTIONS') {
                return new JsonResponse(null, 204);
            }

            // Obtenir la réponse du prochain middleware
            $response = $next($request);

            // Réappliquer les en-têtes CORS
            $this->setCorsHeaders($request);

            return $response;

        } catch (\Exception $e) {
            error_log('CORS Middleware Error: ' . $e->getMessage());
            throw new MiddlewareException('Erreur CORS: ' . $e->getMessage());
        }
    }

    /**
     * Configure les en-têtes CORS
     * 
     * @param Request $request Requête HTTP
     * @throws MiddlewareException Si une erreur se produit lors de la configuration des en-têtes
     */
    protected function setCorsHeaders(Request $request): void
    {
        // Origin
        $origin = $_SERVER['HTTP_ORIGIN'] ?? '*';
        
        // Permettre toutes les origines en développement
        header('Access-Control-Allow-Origin: ' . $origin);
        header('Access-Control-Allow-Credentials: true');

        // Méthodes
        $allowedMethods = ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS', 'PATCH'];
        header('Access-Control-Allow-Methods: ' . implode(', ', $allowedMethods));

        // En-têtes
        $allowedHeaders = array_merge(self::DEFAULT_HEADERS, [
            'Content-Type',
            'X-XSRF-TOKEN',
            'X-Requested-With',
            'Accept',
            'Origin',
            'Authorization',
            'X-CSRF-TOKEN',
            'csrf-token'
        ]);
        header('Access-Control-Allow-Headers: ' . implode(', ', $allowedHeaders));

        // En-têtes exposés
        $exposedHeaders = [
            'X-RateLimit-Limit',
            'X-RateLimit-Remaining',
            'X-RateLimit-Reset',
            'X-XSRF-TOKEN',
            'X-CSRF-TOKEN'
        ];
        header('Access-Control-Expose-Headers: ' . implode(', ', $exposedHeaders));

        // Augmenter la durée du cache pour les requêtes preflight
        header('Access-Control-Max-Age: ' . self::MAX_CACHE_AGE);
        
        // Ajouter le SameSite=Lax pour permettre les requêtes cross-origin avec credentials
        header('Set-Cookie: PHPSESSID=' . session_id() . '; SameSite=Lax; Path=/');
        
        header('Vary: Origin');
    }

    /**
     * Configure les en-têtes de sécurité
     */
    protected function setSecurityHeaders(): void
    {
        header('X-Content-Type-Options: nosniff');
        header('X-XSS-Protection: 1; mode=block');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        
        // Désactiver X-Frame-Options pour le développement
        // header('X-Frame-Options: DENY');
        
        // CSP plus permissif pour le développement
        if (!($this->config['disable_csp'] ?? false)) {
            $csp = "default-src 'self' *; " .
                   "script-src 'self' 'unsafe-inline' 'unsafe-eval' *; " .
                   "style-src 'self' 'unsafe-inline' *; " .
                   "img-src 'self' data: *; " .
                   "font-src 'self' *; " .
                   "connect-src 'self' *;";
            
            header("Content-Security-Policy: " . $csp);
        }
    }

    /**
     * Vérifie si une origine est autorisée
     * 
     * @param string|null $origin
     * @return bool
     */
    protected function isOriginAllowed(?string $origin): bool
    {
        if (!$origin) {
            return false;
        }

        $allowedOrigins = $this->config['allowed_origins'] ?? ['*'];

        if (in_array('*', $allowedOrigins)) {
            return true;
        }

        // Validation plus stricte des origines
        $origin = filter_var($origin, FILTER_SANITIZE_URL);
        if (!$origin || !parse_url($origin, PHP_URL_HOST)) {
            return false;
        }

        return in_array($origin, $allowedOrigins);
    }
}