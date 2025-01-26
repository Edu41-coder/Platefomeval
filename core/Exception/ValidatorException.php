<?php

namespace Core\Exception;

class ValidatorException extends \Exception
{
    /**
     * Tableau des erreurs de validation
     */
    private array $errors;

    /**
     * Constructeur étendu pour les erreurs de validation
     */
    public function __construct(
        array $errors,
        string $message = '',
        int $code = 422,
        ?\Throwable $previous = null
    ) {
        $this->errors = $errors;
        parent::__construct($message, $code, $previous);
    }

    /**
     * Récupère toutes les erreurs de validation
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Récupère les erreurs pour un champ spécifique
     */
    public function getErrorsFor(string $field): array
    {
        return $this->errors[$field] ?? [];
    }

    /**
     * Vérifie si un champ a des erreurs
     */
    public function hasError(string $field): bool
    {
        return isset($this->errors[$field]);
    }

    /**
     * Récupère la première erreur pour un champ
     */
    public function getFirstError(string $field): ?string
    {
        return $this->errors[$field][0] ?? null;
    }

    /**
     * Ajoute une erreur pour un champ
     */
    public function addError(string $field, string $message): self
    {
        if (!isset($this->errors[$field])) {
            $this->errors[$field] = [];
        }
        $this->errors[$field][] = $message;
        return $this;
    }

    /**
     * Vérifie s'il y a des erreurs
     */
    public function hasErrors(): bool
    {
        return !empty($this->errors);
    }

    /**
     * Compte le nombre total d'erreurs
     */
    public function countErrors(): int
    {
        return array_reduce($this->errors, function ($carry, $messages) {
            return $carry + count($messages);
        }, 0);
    }

    /**
     * Formate les erreurs pour l'affichage
     */
    public function formatErrors(): array
    {
        $formatted = [];
        foreach ($this->errors as $field => $messages) {
            $formatted[$field] = implode(', ', $messages);
        }
        return $formatted;
    }

    /**
     * Conversion en chaîne pour le logging
     */
    public function __toString(): string
    {
        return sprintf(
            "[%d] %s\nValidation Errors:\n%s",
            $this->code,
            $this->message,
            json_encode($this->errors, JSON_PRETTY_PRINT)
        );
    }
}