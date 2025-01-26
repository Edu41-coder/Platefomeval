<?php

namespace Core\Exception;

/**
 * Exception spécifique pour les erreurs liées aux repositories
 */
class RepositoryException extends \Exception
{
    /**
     * Codes d'erreur spécifiques aux repositories
     */
    public const ERROR_NOT_FOUND = 1;
    public const ERROR_CREATE = 2;
    public const ERROR_UPDATE = 3;
    public const ERROR_DELETE = 4;
    public const ERROR_QUERY = 5;
    public const ERROR_VALIDATION = 6;
    public const ERROR_DUPLICATE = 7;

    /**
     * @var array<string,mixed>|null Données supplémentaires liées à l'erreur
     */
    protected ?array $context;

    /**
     * Constructeur
     *
     * @param string $message Message d'erreur
     * @param int $code Code d'erreur
     * @param array<string,mixed>|null $context Données supplémentaires
     * @param \Throwable|null $previous Exception précédente
     */
    public function __construct(
        string $message = "",
        int $code = 0,
        ?array $context = null,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
        $this->context = $context;
    }

    /**
     * Récupère le contexte de l'erreur
     *
     * @return array<string,mixed>|null
     */
    public function getContext(): ?array
    {
        return $this->context;
    }

    /**
     * Crée une exception pour une entité non trouvée
     *
     * @param string $entity Nom de l'entité
     * @param string|int $identifier Identifiant recherché
     * @return self
     */
    public static function notFound(string $entity, $identifier): self
    {
        return new self(
            sprintf("L'entité %s avec l'identifiant %s n'a pas été trouvée", $entity, (string)$identifier),
            self::ERROR_NOT_FOUND,
            ['entity' => $entity, 'identifier' => $identifier]
        );
    }

    /**
     * Crée une exception pour une erreur de création
     *
     * @param string $entity Nom de l'entité
     * @param string $reason Raison de l'échec
     * @param array<string,mixed>|null $data Données qui ont causé l'erreur
     * @return self
     */
    public static function createError(string $entity, string $reason, ?array $data = null): self
    {
        return new self(
            sprintf("Impossible de créer l'entité %s : %s", $entity, $reason),
            self::ERROR_CREATE,
            ['entity' => $entity, 'reason' => $reason, 'data' => $data]
        );
    }

    /**
     * Crée une exception pour une erreur de mise à jour
     *
     * @param string $entity Nom de l'entité
     * @param string|int $identifier Identifiant de l'entité
     * @param string $reason Raison de l'échec
     * @return self
     */
    public static function updateError(string $entity, $identifier, string $reason): self
    {
        return new self(
            sprintf("Impossible de mettre à jour l'entité %s (%s) : %s", $entity, (string)$identifier, $reason),
            self::ERROR_UPDATE,
            ['entity' => $entity, 'identifier' => $identifier, 'reason' => $reason]
        );
    }

    /**
     * Crée une exception pour une erreur de suppression
     *
     * @param string $entity Nom de l'entité
     * @param string|int $identifier Identifiant de l'entité
     * @param string $reason Raison de l'échec
     * @return self
     */
    public static function deleteError(string $entity, $identifier, string $reason): self
    {
        return new self(
            sprintf("Impossible de supprimer l'entité %s (%s) : %s", $entity, (string)$identifier, $reason),
            self::ERROR_DELETE,
            ['entity' => $entity, 'identifier' => $identifier, 'reason' => $reason]
        );
    }
    /**
     * Crée une exception pour une erreur de requête
     *
     * @param string $entity Nom de l'entité
     * @param string $operation Nom de l'opération
     * @param string $reason Raison de l'échec
     * @param array<string,mixed>|null $context Contexte supplémentaire
     * @return self
     */
    public static function queryError(
        string $entity,
        string $operation,
        string $reason,
        ?array $context = null
    ): self {
        return new self(
            sprintf("Erreur lors de l'opération %s sur l'entité %s : %s", $operation, $entity, $reason),
            self::ERROR_QUERY,
            ['entity' => $entity, 'operation' => $operation, 'reason' => $reason, 'context' => $context]
        );
    }
}
