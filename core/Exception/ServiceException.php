<?php

namespace Core\Exception;

/**
 * Exception spécifique aux services
 */
class ServiceException extends Exception
{
    /**
     * Erreurs de validation
     */
    protected array $errors = [];

    /**
     * Type de service concerné
     */
    protected string $serviceType;

    /**
     * Action qui a échoué
     */
    protected string $action;

    /**
     * Constructeur étendu
     *
     * @param string $message Message d'erreur
     * @param int $code Code d'erreur
     * @param string $serviceType Type de service (ex: 'UserService', 'AuthService')
     * @param string $action Action qui a échoué (ex: 'create', 'update')
     * @param array $errors Erreurs de validation
     * @param \Throwable|null $previous Exception précédente
     * @param array $context Contexte supplémentaire
     */
    public function __construct(
        string $message = "",
        int $code = 0,
        string $serviceType = "",
        string $action = "",
        array $errors = [],
        ?\Throwable $previous = null,
        array $context = []
    ) {
        $this->errors = $errors;
        $this->serviceType = $serviceType;
        $this->action = $action;

        // Ajout des informations au contexte
        $context['service_type'] = $serviceType;
        $context['action'] = $action;
        $context['validation_errors'] = $errors;

        parent::__construct($message, $code, $previous, $context);
    }

    /**
     * Récupère les erreurs de validation
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Ajoute une erreur de validation
     */
    public function addError(string $field, string $message): self
    {
        $this->errors[$field] = $message;
        $this->context['validation_errors'] = $this->errors;
        return $this;
    }

    /**
     * Récupère le type de service
     */
    public function getServiceType(): string
    {
        return $this->serviceType;
    }

    /**
     * Récupère l'action qui a échoué
     */
    public function getAction(): string
    {
        return $this->action;
    }

    /**
     * Vérifie si une erreur existe pour un champ
     */
    public function hasError(string $field): bool
    {
        return isset($this->errors[$field]);
    }

    /**
     * Récupère le message d'erreur pour un champ
     */
    public function getError(string $field): ?string
    {
        return $this->errors[$field] ?? null;
    }

    /**
     * Surcharge de toArray pour inclure les informations spécifiques
     */
    public function toArray(): array
    {
        $data = parent::toArray();
        $data['service_type'] = $this->serviceType;
        $data['action'] = $this->action;
        $data['errors'] = $this->errors;
        return $data;
    }

    /**
     * Crée une exception pour une erreur de validation
     */
    public static function validationError(
        string $serviceType,
        array $errors,
        string $message = "Validation failed"
    ): self {
        return new self(
            $message,
            400,
            $serviceType,
            'validate',
            $errors
        );
    }

    /**
     * Crée une exception pour une entité non trouvée
     */
    public static function notFound(
        string $serviceType,
        int $id,
        string $message = "Entity not found"
    ): self {
        return new self(
            $message,
            404,
            $serviceType,
            'get',
            [],
            null,
            ['entity_id' => $id]
        );
    }
}