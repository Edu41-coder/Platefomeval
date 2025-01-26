<?php

namespace Core\Http;

class Request
{
    public array $get;
    private array $post;
    private array $server;
    private array $files;
    private array $cookies;
    private ?string $rawBody;
    private array $headers;
    private array $routeParams = [];

    public function __construct()
    {
        $this->get = $_GET;
        $this->post = $_POST;
        $this->server = $_SERVER;
        $this->files = $_FILES;
        $this->cookies = $_COOKIE;
        $this->rawBody = file_get_contents('php://input');
        $this->headers = $this->parseHeaders();
        $this->routeParams = [];
    }

    /**
     * Définit les paramètres de route
     */
    public function setRouteParams(array $params): void
    {
        $this->routeParams = $params;
    }

    /**
     * Récupère une valeur GET ou un paramètre de route
     */
    public function get(string $key, $default = null)
    {
        // Vérifier d'abord dans $_GET
        if (isset($this->get[$key])) {
            return $this->get[$key];
        }
        
        // Vérifier dans les paramètres de route
        if (isset($this->routeParams[$key])) {
            return $this->routeParams[$key];
        }
        
        return $default;
    }

    /**
     * Récupère une valeur POST
     */
    public function post(string $key = null, $default = null)
    {
        if ($key === null) {
            return $this->post;
        }
        return $this->post[$key] ?? $default;
    }

    /**
     * @deprecated Utiliser getJson() à la place
     */
    public function json(): array
    {
        return $this->getJson();
    }

    /**
     * Récupère les données de la requête (JSON ou POST)
     */
    public function getJson(): array
    {
        // Si c'est une requête JSON
        if ($this->isJson()) {
            error_log('Request is JSON, parsing raw body');
            if (empty($this->rawBody)) {
                error_log('Raw body is empty, returning empty array');
                return [];
            }
            
            $data = json_decode($this->rawBody, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                error_log('JSON decode error: ' . json_last_error_msg());
                return [];
            }
            
            error_log('JSON data parsed: ' . print_r($data, true));
            return $data;
        }
        
        // Si c'est une requête POST standard
        if ($this->isPost()) {
            error_log('Request is POST, returning $_POST data');
            error_log('POST data: ' . print_r($this->post, true));
            return $this->post;
        }
        
        error_log('Request is neither JSON nor POST, returning empty array');
        return [];
    }

    /**
     * Vérifie si la requête contient des données JSON
     */
    private function isJson(): bool
    {
        $contentType = $this->getHeader('Content-Type');
        error_log('Content-Type header: ' . ($contentType ?? 'null'));
        return !empty($contentType) && strpos($contentType, 'application/json') !== false;
    }

    /**
     * Récupère une valeur SERVER
     */
    public function server(string $key = null, $default = null)
    {
        if ($key === null) {
            return $this->server;
        }
        return $this->server[$key] ?? $default;
    }

    /**
     * Récupère un fichier uploadé
     */
    public function file(string $key = null)
    {
        error_log("Request::file() called with key: " . ($key ?? 'null'));
        error_log("FILES array content: " . print_r($this->files, true));

        if ($key === null) {
            return $this->files;
        }
        error_log("Returning file for key '$key': " . ($this->files[$key] ? "Found" : "Not found"));
        return $this->files[$key] ?? null;
    }

    /**
     * Récupère un cookie
     */
    public function cookie(string $key = null, $default = null)
    {
        if ($key === null) {
            return $this->cookies;
        }
        return $this->cookies[$key] ?? $default;
    }

    /**
     * Gestion de la session
     */
    public function getSession(string $key = null, $default = null)
    {
        if ($key === null) {
            return $_SESSION;
        }
        return $_SESSION[$key] ?? $default;
    }

    public function setSession(string $key, $value): void
    {
        $_SESSION[$key] = $value;
    }

    public function hasSession(string $key): bool
    {
        return isset($_SESSION[$key]);
    }

    public function removeSession(string $key): void
    {
        unset($_SESSION[$key]);
    }

    /**
     * Vérifie si la requête est en AJAX
     */
    public function isAjax(): bool
    {
        return (
            isset($_SERVER['HTTP_X_REQUESTED_WITH']) &&
            strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest'
        );
    }

    /**
     * Récupère la méthode HTTP
     */
    public function getMethod(): string
    {
        return strtoupper($this->server['REQUEST_METHOD']);
    }

    /**
     * Vérifie la méthode HTTP
     */
    public function isMethod(string $method): bool
    {
        return $this->getMethod() === strtoupper($method);
    }

    public function isPost(): bool
    {
        return $this->isMethod('POST');
    }

    public function isGet(): bool
    {
        return $this->isMethod('GET');
    }

    public function isPut(): bool
    {
        return $this->isMethod('PUT');
    }

    public function isDelete(): bool
    {
        return $this->isMethod('DELETE');
    }

    /**
     * Récupère l'URI de la requête
     */
    public function getUri(): string
    {
        return $this->server['REQUEST_URI'] ?? '';
    }

    /**
     * Récupère l'URL complète
     */
    public function getFullUrl(): string
    {
        $protocol = $this->isSecure() ? 'https' : 'http';
        return $protocol . '://' . $this->server['HTTP_HOST'] . $this->getUri();
    }

    /**
     * Vérifie si la requête est en HTTPS
     */
    public function isSecure(): bool
    {
        return (!empty($this->server['HTTPS']) && $this->server['HTTPS'] !== 'off')
            || $this->server['SERVER_PORT'] == 443;
    }

    /**
     * Récupère l'IP du client
     */
    public function getIp(): string
    {
        return $this->server['REMOTE_ADDR'];
    }

    /**
     * Récupère un en-tête spécifique
     */
    public function header(string $key, $default = null)
    {
        return $this->headers[$key] ?? $default;
    }

    /**
     * Récupère tous les en-têtes
     */
    public function headers(): array
    {
        return $this->headers;
    }

    /**
     * Parse les en-têtes HTTP depuis $_SERVER
     */
    private function parseHeaders(): array
    {
        $headers = [];
        foreach ($this->server as $key => $value) {
            if (strpos($key, 'HTTP_') === 0) {
                $name = str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($key, 5)))));
                $headers[$name] = $value;
            } elseif (in_array($key, ['CONTENT_TYPE', 'CONTENT_LENGTH', 'CONTENT_MD5'])) {
                $name = str_replace('_', '-', $key);
                $headers[$name] = $value;
            }
        }
        return $headers;
    }

    /**
     * Vérifie si la requête accepte un type de contenu spécifique
     */
    public function accepts(string $contentType): bool
    {
        $acceptHeader = $this->header('Accept', '');
        if (empty($acceptHeader)) {
            return false;
        }
        return strpos($acceptHeader, $contentType) !== false;
    }

    /**
     * Vérifie si la requête attend une réponse JSON
     */
    public function wantsJson(): bool
    {
        $acceptHeader = $this->header('Accept', '');
        return strpos($acceptHeader, 'application/json') !== false || $this->isAjax();
    }

    public function getHeader(string $name): ?string
    {
        $headers = getallheaders();
        return $headers[$name] ?? null;
    }

    /**
     * Récupère les paramètres de route
     */
    public function getRouteParams(): array
    {
        return $this->routeParams;
    }
}