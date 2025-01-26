<?php

namespace App\Security;

class CsrfToken
{
    private static array $tokenCache = [];
    private const TOKEN_LIFETIME = 3600;

    /**
     * Génère un nouveau token CSRF
     *
     * @return string
     */
    public static function generate(): string
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $token = bin2hex(random_bytes(32));
        
        $_SESSION['csrf_token'] = $token;
        $_SESSION['csrf_token_time'] = time();
        
        $sessionId = session_id();
        self::$tokenCache[$sessionId] = [
            'token' => $token,
            'time' => time()
        ];
        
        return $token;
    }

    /**
     * Récupère le token CSRF actuel ou en génère un nouveau
     *
     * @return string
     */
    public static function getToken(): string
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $sessionId = session_id();
        
        // Vérifier d'abord le cache
        if (isset(self::$tokenCache[$sessionId])) {
            $cached = self::$tokenCache[$sessionId];
            if (time() - $cached['time'] < self::TOKEN_LIFETIME) {
                return $cached['token'];
            }
        }

        // Vérifier ensuite la session
        if (isset($_SESSION['csrf_token'])) {
            $tokenTime = $_SESSION['csrf_token_time'] ?? 0;
            if (time() - $tokenTime < self::TOKEN_LIFETIME) {
                $token = $_SESSION['csrf_token'];
                self::$tokenCache[$sessionId] = [
                    'token' => $token,
                    'time' => $tokenTime
                ];
                return $token;
            }
        }

        // Générer un nouveau token seulement si nécessaire
        return self::generate();
    }

    /**
     * Vérifie si le token fourni est valide
     *
     * @param string|null $token
     * @return bool
     */
    public static function verify(?string $token): bool
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!$token) {
            error_log('CSRF: Token manquant');
            return false;
        }

        $sessionId = session_id();

        // Vérifier d'abord dans le cache
        if (isset(self::$tokenCache[$sessionId])) {
            $cached = self::$tokenCache[$sessionId];
            if (time() - $cached['time'] < self::TOKEN_LIFETIME) {
                return hash_equals($cached['token'], $token);
            }
        }

        // Vérifier ensuite dans la session
        if (isset($_SESSION['csrf_token'])) {
            $isValid = hash_equals($_SESSION['csrf_token'], $token);
            if ($isValid) {
                self::$tokenCache[$sessionId] = [
                    'token' => $token,
                    'time' => $_SESSION['csrf_token_time'] ?? time()
                ];
            }
            return $isValid;
        }

        error_log('CSRF: Aucun token valide trouvé');
        return false;
    }
} 