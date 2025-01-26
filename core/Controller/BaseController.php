<?php

namespace Core\Controller;

use Core\View\Template;
use Core\Http\Request;
use Core\Http\Response;
use Core\Http\JsonResponse;
use App\Security\CsrfToken;
use Core\Session\FlashMessage;
use App\Interfaces\Controller\ControllerInterface;
use App\Traits\Controller\RoleCheckTrait;

abstract class BaseController implements ControllerInterface
{
    use RoleCheckTrait;

    protected string $viewPath;
    protected array $sharedViewData = [];
    protected Request $request;
    protected Response $response;
    protected Template $view;
    protected array $loadedAssets = [
        'css' => [],
        'js' => []
    ];
    protected ?string $layout = 'default';
    protected FlashMessage $flash;

    public function __construct()
    {
        $this->flash = new FlashMessage();

        // Initialize request and response
        $this->request = new Request();
        $this->response = new Response();

        // Get existing CSRF token or generate new one only if needed
        $csrfToken = CsrfToken::getToken();

        // Get flash messages before they're marked as displayed
        $flashMessages = FlashMessage::getAll();

        // Initialize shared view data
        $this->sharedViewData = [
            'site_title' => $_ENV['APP_NAME'] ?? 'Plateforme Eval',
            'csrfToken' => $csrfToken,
            'user' => $this->getUser(),
            'flash_messages' => $flashMessages,
            'assets' => $this->loadedAssets
        ];

        $this->viewPath = dirname(__DIR__, 2) . '/app/views';
        $this->initialize();
    }

    public function initialize(): void
    {
        $this->viewPath = dirname(__DIR__, 2) . '/app/views';
    }

    protected function setViewPath(): void
    {
        $className = get_class($this);
        $classPath = str_replace(['App\\Controllers\\', 'Controller'], '', $className);
        $viewPath = strtolower(str_replace('\\', '/', $classPath));
        $this->viewPath = dirname(__DIR__, 2) . '/app/views';
    }

    protected function getView(string $path): Template
    {
        $fullPath = $this->viewPath . '/' . $path . '.php';
        
        if (!file_exists($fullPath)) {
            throw new \RuntimeException("View file not found: {$fullPath}");
        }
        
        return new Template($fullPath);
    }

    public function render(string $view, array $data = [], ?string $layout = null): Response
    {
        try {
            // Le layout passé en paramètre a la priorité sur le layout de classe
            $finalLayout = $layout ?? $this->layout;
            
            // Always include flash messages in the shared data
            $this->sharedViewData['flash'] = FlashMessage::getAll();
            
            // Merge avec les données partagées (data takes precedence over shared data)
            $finalData = array_merge($this->sharedViewData, $data);

            // Create new Template instance
            $template = $this->getView($view);

            // Add data and layout
            $template->withData($finalData);
            if ($finalLayout !== null) {
                $template->layout($finalLayout);
            }

            // Configure response
            $response = new Response();
            $this->setSecurityHeaders($response);

            // Render view
            $content = $template->render();
            
            $response->setContent($content);
            return $response;

        } catch (\Throwable $e) {
            // Keep only critical error logging
            error_log('Error rendering view: ' . $e->getMessage());
            throw $e;
        }
    }

    protected function setSecurityHeaders(Response $response): void
    {
        $response->setHeader('X-Frame-Options', 'DENY');
        $response->setHeader('X-Content-Type-Options', 'nosniff');
        $response->setHeader('X-XSS-Protection', '1; mode=block');
        $response->setHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
    }

    public function handleError(\Throwable $e): Response
    {
        error_log($e->getMessage());
        error_log($e->getTraceAsString());

        if (filter_var($_ENV['APP_DEBUG'] ?? false, FILTER_VALIDATE_BOOLEAN)) {
            throw $e;
        }

        return $this->renderError();
    }

    protected function renderError(int $code = 500): Response
    {
        try {
            $errorView = dirname(__DIR__, 2) . '/app/views/errors/' . $code . '.php';
            if (!file_exists($errorView)) {
                $errorView = dirname(__DIR__, 2) . '/app/views/errors/500.php';
            }

            $template = new Template($errorView);
            $content = $template->withData($this->sharedViewData)->render();

            return new Response($content, $code);
        } catch (\Throwable $e) {
            error_log('Erreur lors du rendu de la page d\'erreur: ' . $e->getMessage());
            return new Response('Une erreur est survenue', 500);
        }
    }

    /**
     * Redirige vers une URL ou un chemin
     * 
     * @param string $urlOrPath URL ou chemin de redirection
     * @param int $status Code de statut HTTP
     * @return Response
     */
    public function redirect(string $urlOrPath, int $status = 302): Response
    {
        // Make sure session is saved before redirect
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }

        // If it's already a full URL, use it as is
        if (preg_match('~^(https?:)?//~i', $urlOrPath)) {
            return new Response('', $status, ['Location' => $urlOrPath]);
        }

        // Clean the path from any existing prefixes
        $urlOrPath = trim($urlOrPath, '/');
        
        // If the path is 'home', convert it to empty string for root path
        if ($urlOrPath === 'home') {
            $urlOrPath = '';
        }
        
        // Use the url() helper to generate the correct URL
        $finalUrl = url($urlOrPath);
        
        return new Response('', $status, ['Location' => $finalUrl]);
    }

    public function json($data, int $status = 200): Response
    {
        return new JsonResponse($data, $status);
    }

    public function getUser(): ?array
    {
        return $_SESSION['user'] ?? null;
    }

    public function isAuthenticated(): bool
    {
        return isset($_SESSION['user']);
    }

    public function getRequest(): Request
    {
        return $this->request;
    }

    public function setRequest(Request $request): self
    {
        $this->request = $request;
        return $this;
    }

    public function getResponse(): Response
    {
        return $this->response;
    }

    public function setResponse(Response $response): self
    {
        $this->response = $response;
        return $this;
    }

    public function isAjax(): bool
    {
        return $this->request->isAjax();
    }

    public function wantsJson(): bool
    {
        return $this->request->wantsJson();
    }

    public function hasRole(string $role): bool
    {
        return $this->checkRole(function($userRole) use ($role) {
            return $userRole->getName() === $role;
        });
    }

    public function hasPermission(string $permission): bool
    {
        return $this->isAdmin();
    }

    public function validate(array $rules): array
    {
        //  implémenter selon vos besoins
        return [];
    }

    protected function addFlash(string $type, string $message): void
    {
        FlashMessage::add($type, $message);
        // Update shared view data immediately
        $this->sharedViewData['flash'] = FlashMessage::getAll();
    }

    protected function getFlashMessages(): array
    {
        $messages = FlashMessage::getAll();
        return $messages;
    }

    protected function validateCsrf(): bool
    {
        $token = null;
        
        // Check POST data first, then JSON data
        $postToken = $this->request->post('csrf_token');
        $jsonData = $this->request->json();
        $jsonToken = $jsonData['csrf_token'] ?? null;
        
        // Use the first available token
        $token = $postToken ?? $jsonToken ?? null;
        
        return CsrfToken::verify($token);
    }

    protected function generateCsrfToken(): string
    {
        return CsrfToken::generate();
    }

    protected function addAsset(string $type, string $path): void
    {
        // Clean the path from any prefixes
        $path = preg_replace('#^(assets/|/assets/|Plateformeval/public/assets/)+#', '', $path);
        
        // Add to loaded assets if not already present
        if (!in_array($path, $this->loadedAssets[$type])) {
            $this->loadedAssets[$type][] = $path;
            // Update shared view data
            $this->sharedViewData['assets'] = $this->loadedAssets;
        }
    }

    protected function addAssets(array $assets): void
    {
        foreach ($assets as $type => $paths) {
            foreach ((array)$paths as $path) {
                $this->addAsset($type, $path);
            }
        }
    }

    /**
     * Génère une URL pour un asset
     */
    protected function asset(string $path): string
    {
        return asset($path);
    }

    abstract public function index(Request $request, Response $response): Response;

    /**
     * Méthode appelée avant l'exécution de chaque action
     *
     * @return void
     */
    protected function beforeAction(): void
    {
        try {
            if (!$this->validateCsrf()) {
                // Log l'erreur
                error_log('CSRF verification failed in beforeAction');
                
                if ($this->request->wantsJson()) {
                    header('Content-Type: application/json');
                    http_response_code(422);
                    echo json_encode([
                        'success' => false,
                        'message' => 'Token CSRF invalide'
                    ]);
                    exit;
                }
                
                // Pour les requêtes non-AJAX, redirige vers une page d'erreur
                http_response_code(403);
                die('Invalid CSRF token');
            }
        } catch (\Exception $e) {
            // Log l'erreur
            error_log('CSRF verification exception: ' . $e->getMessage());
            
            if ($this->request->wantsJson()) {
                header('Content-Type: application/json');
                http_response_code(422);
                echo json_encode([
                    'success' => false,
                    'message' => 'Erreur de vérification CSRF'
                ]);
                exit;
            }
            
            // Pour les requêtes non-AJAX, redirige vers une page d'erreur
            http_response_code(403);
            die('Invalid CSRF token');
        }
    }

    /**
     * Génère une URL
     */
    protected function url(string $path = ''): string
    {
        return url($path);
    }

    protected function setLayout(?string $layout): void
    {
        $this->layout = $layout;
    }

    protected function generateUrl(string $route, array $params = []): string
    {
        $url = $route;
        if (!empty($params)) {
            $queryString = http_build_query($params);
            $url .= '?' . $queryString;
        }
        return $this->url($url);
    }

    protected function redirectToRoute(string $route, array $params = [], int $status = 302): Response
    {
        return $this->redirect($this->generateUrl($route, $params), $status);
    }
}

