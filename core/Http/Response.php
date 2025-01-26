<?php

namespace Core\Http;

class Response
{
    /** @var string|array */
    private $content = '';
    private int $statusCode = 200;
    private array $headers = [];
    private bool $headersSent = false;

    /**
     * Codes de statut HTTP courants
     */
    public const HTTP_OK = 200;
    public const HTTP_CREATED = 201;
    public const HTTP_NO_CONTENT = 204;
    public const HTTP_MOVED_PERMANENTLY = 301;
    public const HTTP_FOUND = 302;
    public const HTTP_BAD_REQUEST = 400;
    public const HTTP_UNAUTHORIZED = 401;
    public const HTTP_FORBIDDEN = 403;
    public const HTTP_NOT_FOUND = 404;
    public const HTTP_METHOD_NOT_ALLOWED = 405;
    public const HTTP_CONFLICT = 409;
    public const HTTP_UNPROCESSABLE_ENTITY = 422;
    public const HTTP_INTERNAL_SERVER_ERROR = 500;

    /**
     * @param string|array $content Le contenu de la réponse
     * @param int $status Code HTTP
     * @param array $headers En-têtes HTTP
     */
    public function __construct($content = '', int $status = 200, array $headers = [])
    {
        $this->content = $content;
        $this->statusCode = $status;
        $this->headers = array_merge([
            'Content-Type' => 'text/html; charset=UTF-8'
        ], $headers);
    }

    /**
     * @param string|array $content
     * @return self
     */
    public function setContent($content): self
    {
        $this->content = $content;
        return $this;
    }

    /**
     * @return string|array
     */
    public function getContent()
    {
        return $this->content;
    }

    public function setStatusCode(int $statusCode): self
    {
        $this->statusCode = $statusCode;
        return $this;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function setHeader(string $name, string $value): self
    {
        $this->headers[$name] = $value;
        return $this;
    }

    public function setHeaders(array $headers): self
    {
        $this->headers = array_merge($this->headers, $headers);
        return $this;
    }

    public function getHeaders(): array
    {
        return $this->headers;
    }

    public function setCookie(
        string $name,
        string $value = "",
        array $options = []
    ): self {
        $defaults = [
            'expires' => 0,
            'path' => '/',
            'domain' => '',
            'secure' => true,
            'httponly' => true,
            'samesite' => 'Lax'
        ];

        $options = array_merge($defaults, $options);
        setcookie($name, $value, $options);
        
        return $this;
    }
        /**
     * Redirige vers une autre URL
     */
    public function redirect(string $url, int $status = 302): self
    {
        $this->setHeader('Location', $url);
        $this->setStatusCode($status);
        return $this;
    }
    
    private function sendHeaders(): void
    {
        if ($this->headersSent || headers_sent()) {
            return;
        }

        http_response_code($this->statusCode);

        foreach ($this->headers as $name => $value) {
            header($name . ': ' . $value, true);
        }

        $this->headersSent = true;
    }

    public function send(): void
    {
        try {
            // Disable error output
            if (ini_get('display_errors')) {
                ini_set('display_errors', '0');
            }

            $this->sendHeaders();

            if ($this->getStatusCode() !== self::HTTP_NO_CONTENT) {
                if (is_array($this->content) || is_object($this->content)) {
                    $json = json_encode($this->content, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
                    if ($json === false) {
                        throw new \RuntimeException('Failed to encode response to JSON: ' . json_last_error_msg());
                    }
                    echo $json;
                } else if (is_string($this->content)) {
                    if (strpos($this->headers['Content-Type'], 'application/json') !== false) {
                        // Validate that it's valid JSON if we're sending JSON
                        json_decode($this->content);
                        if (json_last_error() !== JSON_ERROR_NONE) {
                            throw new \RuntimeException('Invalid JSON string in response');
                        }
                    }
                    echo $this->content;
                }
            }

            if (function_exists('fastcgi_finish_request')) {
                fastcgi_finish_request();
            }
        } catch (\Throwable $e) {
            // Log the error
            error_log('Response error: ' . $e->getMessage());
            
            // Send a JSON error response
            http_response_code(500);
            header('Content-Type: application/json; charset=UTF-8');
            echo json_encode([
                'success' => false,
                'message' => 'Une erreur est survenue lors du traitement de la réponse'
            ]);
        }
        exit;
    }

    public static function json($data, int $status = self::HTTP_OK, array $headers = []): self
    {
        $headers['Content-Type'] = 'application/json; charset=UTF-8';
        return new static($data, $status, $headers);
    }

    public static function file(string $path, string $filename = null): self
    {
        if (!file_exists($path)) {
            return static::notFound('File not found');
        }

        $content = file_get_contents($path);
        $mime = mime_content_type($path);
        $headers = [
            'Content-Type' => $mime,
            'Content-Length' => filesize($path)
        ];

        if ($filename) {
            $headers['Content-Disposition'] = 'attachment; filename="' . $filename . '"';
        }

        return new static($content, self::HTTP_OK, $headers);
    }

    /**
     * @param string|array $content
     */
    public static function ok($content = ''): self
    {
        return new static($content, self::HTTP_OK);
    }

    /**
     * @param string|array $content
     */
    public static function created($content = ''): self
    {
        return new static($content, self::HTTP_CREATED);
    }

    public static function noContent(): self
    {
        return new static('', self::HTTP_NO_CONTENT);
    }

    public static function badRequest(string $message, ?array $errors = null): self
    {
        $data = ['message' => $message];
        if ($errors !== null) {
            $data['errors'] = $errors;
        }
        return new static($data, self::HTTP_BAD_REQUEST);
    }

    public static function unauthorized(string $message = 'Unauthorized'): self
    {
        return new static(['message' => $message], self::HTTP_UNAUTHORIZED);
    }

    public static function forbidden(string $message = 'Forbidden'): self
    {
        return new static(['message' => $message], self::HTTP_FORBIDDEN);
    }

    public static function notFound(string $message = 'Not Found'): self
    {
        return new static(['message' => $message], self::HTTP_NOT_FOUND);
    }

    public static function methodNotAllowed(string $message = 'Method Not Allowed'): self
    {
        return new static(['message' => $message], self::HTTP_METHOD_NOT_ALLOWED);
    }

    public static function conflict(string $message = 'Conflict'): self
    {
        return new static(['message' => $message], self::HTTP_CONFLICT);
    }

    public static function unprocessableEntity(string $message = 'Unprocessable Entity'): self
    {
        return new static(['message' => $message], self::HTTP_UNPROCESSABLE_ENTITY);
    }

    public static function serverError(string $message = 'Internal Server Error'): self
    {
        return new static(['message' => $message], self::HTTP_INTERNAL_SERVER_ERROR);
    }

    public static function html(string $content, int $status = self::HTTP_OK): self
    {
        return new static($content, $status, ['Content-Type' => 'text/html; charset=UTF-8']);
    }

    public static function text(string $content, int $status = self::HTTP_OK): self
    {
        return new static($content, $status, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }
}