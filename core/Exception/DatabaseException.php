<?php

namespace Core\Exception;

class DatabaseException extends Exception
{
    // Codes d'erreur personnalisés
    public const CONNECTION_ERROR = 1;
    public const QUERY_ERROR = 2;
    public const TRANSACTION_ERROR = 3;

    /**
     * Constructeur étendu pour les erreurs de base de données
     */
    public function __construct(
        string $message = "",
        int $code = self::CONNECTION_ERROR,
        ?\Throwable $previous = null,
        array $context = []
    ) {
        // Ajout d'informations supplémentaires au contexte
        $context['sql_state'] = $previous?->getCode();
        $context['driver_error'] = $previous?->getMessage();
        
        if (isset($context['query'])) {
            $context['parameters'] = $context['parameters'] ?? [];
        }

        parent::__construct($message, $code, $previous, $context);
    }

    /**
     * Récupère la requête SQL si disponible
     */
    public function getQuery(): ?string
    {
        return $this->getContextData('query');
    }

    /**
     * Récupère les paramètres de la requête si disponibles
     */
    public function getParameters(): array
    {
        return $this->getContextData('parameters') ?? [];
    }

    /**
     * Récupère le code d'erreur SQL si disponible
     */
    public function getSqlState(): ?string
    {
        return $this->getContextData('sql_state');
    }

    /**
     * Récupère l'erreur du driver si disponible
     */
    public function getDriverError(): ?string
    {
        return $this->getContextData('driver_error');
    }
}