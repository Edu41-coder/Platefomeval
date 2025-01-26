<?php

namespace Core\Middleware;

/**
 * Exception spécifique pour les erreurs de middleware
 */
class MiddlewareException extends \Exception
{
    /**
     * Codes d'erreur spécifiques aux middlewares
     */
    public const ERROR_UNAUTHORIZED = 401;
    public const ERROR_FORBIDDEN = 403;
    public const ERROR_INVALID_TOKEN = 498;
    public const ERROR_RATE_LIMIT = 429;

    /**
     * Crée une exception pour un accès non autorisé
     *
     * @param string $reason Raison du refus
     * @return self
     */
    public static function unauthorized(string $reason): self
    {
        return new self($reason, self::ERROR_UNAUTHORIZED);
    }

    /**
     * Crée une exception pour un accès interdit
     *
     * @param string $reason Raison du refus
     * @return self
     */
    public static function forbidden(string $reason): self
    {
        return new self($reason, self::ERROR_FORBIDDEN);
    }

    /**
     * Crée une exception pour un token invalide
     *
     * @param string $reason Raison de l'invalidité
     * @return self
     */
    public static function invalidToken(string $reason): self
    {
        return new self($reason, self::ERROR_INVALID_TOKEN);
    }

    /**
     * Crée une exception pour une limite de taux dépassée
     *
     * @param string $reason Détails sur la limite
     * @return self
     */
    public static function rateLimit(string $reason): self
    {
        return new self($reason, self::ERROR_RATE_LIMIT);
    }
}