<?php

namespace Core\Http;

class JsonResponse extends Response
{
    /**
     * Codes HTTP standards
     */
    public const HTTP_OK = 200;
    public const HTTP_CREATED = 201;
    public const HTTP_NO_CONTENT = 204;
    public const HTTP_BAD_REQUEST = 400;
    public const HTTP_UNAUTHORIZED = 401;
    public const HTTP_FORBIDDEN = 403;
    public const HTTP_NOT_FOUND = 404;
    public const HTTP_CONFLICT = 409;
    public const HTTP_UNPROCESSABLE_ENTITY = 422;
    public const HTTP_INTERNAL_ERROR = 500;

    /**
     * Options JSON par défaut
     */
    protected const JSON_OPTIONS = JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES;

    /**
     * Constructeur avec données JSON
     * 
     * @param mixed $data Les données à encoder en JSON
     * @param int $status Code HTTP de la réponse
     * @param array $headers Headers additionnels
     */
    public function __construct($data = null, int $status = self::HTTP_OK, array $headers = [])
    {
        // Ensure we have proper JSON headers
        $defaultHeaders = [
            'Content-Type' => 'application/json; charset=UTF-8',
            'X-Content-Type-Options' => 'nosniff'
        ];
        $headers = array_merge($defaultHeaders, $headers);

        // Convert data to JSON if it's not already a string
        if (!is_string($data)) {
            $jsonContent = json_encode($data, self::JSON_OPTIONS);
            if ($jsonContent === false) {
                throw new \RuntimeException('Failed to encode response to JSON: ' . json_last_error_msg());
            }
        } else {
            // Validate if the string is valid JSON
            json_decode($data);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \RuntimeException('Invalid JSON string provided to JsonResponse');
            }
            $jsonContent = $data;
        }

        parent::__construct($jsonContent, $status, $headers);
    }

    /**
     * Crée une réponse de succès
     * 
     * @param mixed $data Données de la réponse
     * @param int $status Code HTTP
     * @return self
     */
    public static function success($data = null, int $status = self::HTTP_OK): self
    {
        $response = [
            'success' => true,
            'data' => $data
        ];

        if (is_string($data)) {
            $response['message'] = $data;
            $response['data'] = null;
        }

        return new self($response, $status);
    }

    /**
     * Crée une réponse d'erreur
     * 
     * @param string $message Message d'erreur
     * @param int $status Code HTTP
     * @param array|null $errors Erreurs détaillées
     * @param array|null $debug Informations de debug
     * @return self
     */
    public static function error(
        string $message,
        int $status = self::HTTP_BAD_REQUEST,
        ?array $errors = null,
        ?array $debug = null
    ): self {
        $response = [
            'success' => false,
            'message' => $message,
            'code' => $status
        ];

        if ($errors !== null) {
            $response['errors'] = $errors;
        }

        if ($debug !== null && ($_ENV['APP_DEBUG'] ?? false) === true) {
            $response['debug'] = $debug;
        }

        return new self($response, $status);
    }
    /**
     * Crée une réponse de création réussie
     * 
     * @param mixed $data Données de la ressource créée
     * @return self
     */
    public static function created($data = null): self
    {
        return self::success($data, self::HTTP_CREATED);
    }

    /**
     * Crée une réponse sans contenu
     * 
     * @return self
     */
    public static function noContent(): self
    {
        return new self(null, self::HTTP_NO_CONTENT);
    }

    /**
     * Crée une réponse non autorisée
     * 
     * @param string $message Message personnalisé
     * @return self
     */
    public static function unauthorized(string $message = 'Non autorisé'): self
    {
        return self::error($message, self::HTTP_UNAUTHORIZED);
    }

    /**
     * Crée une réponse interdite
     * 
     * @param string $message Message personnalisé
     * @return self
     */
    public static function forbidden(string $message = 'Accès interdit'): self
    {
        return self::error($message, self::HTTP_FORBIDDEN);
    }

    /**
     * Crée une réponse non trouvée
     * 
     * @param string $message Message personnalisé
     * @return self
     */
    public static function notFound(string $message = 'Ressource non trouvée'): self
    {
        return self::error($message, self::HTTP_NOT_FOUND);
    }

    /**
     * Crée une réponse d'erreur de validation
     * 
     * @param array $errors Liste des erreurs de validation
     * @param string $message Message général
     * @return self
     */
    public static function validationError(
        array $errors,
        string $message = 'Erreurs de validation'
    ): self {
        return self::error($message, self::HTTP_UNPROCESSABLE_ENTITY, $errors);
    }

    /**
     * Crée une réponse d'erreur serveur
     * 
     * @param string $message Message d'erreur
     * @param array|null $debug Informations de debug
     * @return self
     */
    public static function serverError(
        string $message = 'Erreur interne du serveur',
        ?array $debug = null
    ): self {
        return self::error($message, self::HTTP_INTERNAL_ERROR, null, $debug);
    }

    /**
     * Crée une réponse de conflit
     * 
     * @param string $message Message personnalisé
     * @return self
     */
    public static function conflict(string $message = 'Conflit détecté'): self
    {
        return self::error($message, self::HTTP_CONFLICT);
    }


    /**
     * Envoie la réponse au client
     * 
     * @return void
     */
    public function send(): void
    {
        if (!headers_sent()) {
            http_response_code($this->getStatusCode());

            foreach ($this->getHeaders() as $name => $value) {
                header(sprintf('%s: %s', $name, $value), true);
            }
        }

        if ($this->getStatusCode() !== self::HTTP_NO_CONTENT) {
            echo $this->getContent();
        }

        exit;
    }
    /**
     * @param string $message Message d'erreur
     * @param array|null $errors Erreurs détaillées
     * @return Response
     */
    public static function badRequest(string $message, ?array $errors = null): Response
    {
        return self::error($message, self::HTTP_BAD_REQUEST, $errors);
    }
}
