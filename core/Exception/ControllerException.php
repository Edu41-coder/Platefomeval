<?php

namespace Core\Exception;

/**
 * Exception spécifique pour les erreurs de contrôleur
 */
class ControllerException extends \Exception
{
    /**
     * Codes d'erreur spécifiques aux contrôleurs
     */
    public const ERROR_BAD_REQUEST = 400;
    public const ERROR_UNAUTHORIZED = 401;
    public const ERROR_FORBIDDEN = 403;
    public const ERROR_NOT_FOUND = 404;
    public const ERROR_CONFLICT = 409;
    public const ERROR_VALIDATION = 422;
    public const ERROR_SERVER = 500;

    /**
     * Crée une exception pour une requête invalide
     *
     * @param string $message Message d'erreur
     * @return self
     */
    public static function badRequest(string $message): self
    {
        return new self($message, self::ERROR_BAD_REQUEST);
    }

    /**
     * Crée une exception pour un accès non autorisé
     *
     * @param string $message Message d'erreur
     * @return self
     */
    public static function unauthorized(string $message): self
    {
        return new self($message, self::ERROR_UNAUTHORIZED);
    }

    /**
     * Crée une exception pour un accès interdit
     *
     * @param string $message Message d'erreur
     * @return self
     */
    public static function forbidden(string $message): self
    {
        return new self($message, self::ERROR_FORBIDDEN);
    }

    /**
     * Crée une exception pour une ressource non trouvée
     *
     * @param string $message Message d'erreur
     * @return self
     */
    public static function notFound(string $message): self
    {
        return new self($message, self::ERROR_NOT_FOUND);
    }

    /**
     * Crée une exception pour un conflit
     *
     * @param string $message Message d'erreur
     * @return self
     */
    public static function conflict(string $message): self
    {
        return new self($message, self::ERROR_CONFLICT);
    }

    /**
     * Crée une exception pour une erreur de validation
     *
     * @param string $message Message d'erreur
     * @return self
     */
    public static function validation(string $message): self
    {
        return new self($message, self::ERROR_VALIDATION);
    }

    /**
     * Crée une exception pour une erreur serveur
     *
     * @param string $message Message d'erreur
     * @return self
     */
    public static function serverError(string $message): self
    {
        return new self($message, self::ERROR_SERVER);
    }
}