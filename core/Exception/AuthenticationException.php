<?php

namespace Core\Exception;

use Exception;

/**
 * Exception spécifique pour les erreurs d'authentification
 */
class AuthenticationException extends Exception
{
    /**
     * Codes d'erreur spécifiques à l'authentification
     */
    public const INVALID_CREDENTIALS = 401;
    public const ACCOUNT_LOCKED = 402;
    public const ACCOUNT_NOT_VERIFIED = 403;
    public const ACCOUNT_DISABLED = 404;
    public const TOKEN_EXPIRED = 405;
    public const TOKEN_INVALID = 406;
    public const PASSWORD_EXPIRED = 407;
    public const EMAIL_ALREADY_EXISTS = 409;
    public const INVALID_PASSWORD_FORMAT = 422;
    public const TOO_MANY_ATTEMPTS = 429;
    public const REGISTRATION_FAILED = 500;

    /**
     * Constructeur personnalisé pour AuthenticationException
     */
    public function __construct(
        string $message = "",
        int $code = self::INVALID_CREDENTIALS,
        ?Exception $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }

    /**
     * Crée une exception pour des identifiants invalides
     */
    public static function invalidCredentials(string $message = "Identifiants invalides"): self
    {
        return new self($message, self::INVALID_CREDENTIALS);
    }

    /**
     * Crée une exception pour un compte verrouillé
     */
    public static function accountLocked(string $message = "Compte verrouillé"): self
    {
        return new self($message, self::ACCOUNT_LOCKED);
    }

    /**
     * Crée une exception pour un compte non vérifié
     */
    public static function accountNotVerified(string $message = "Compte non vérifié"): self
    {
        return new self($message, self::ACCOUNT_NOT_VERIFIED);
    }

    /**
     * Crée une exception pour un compte désactivé
     */
    public static function accountDisabled(string $message = "Compte désactivé"): self
    {
        return new self($message, self::ACCOUNT_DISABLED);
    }

    /**
     * Crée une exception pour un token expiré
     */
    public static function tokenExpired(string $message = "Token expiré"): self
    {
        return new self($message, self::TOKEN_EXPIRED);
    }

    /**
     * Crée une exception pour un token invalide
     */
    public static function tokenInvalid(string $message = "Token invalide"): self
    {
        return new self($message, self::TOKEN_INVALID);
    }

    /**
     * Crée une exception pour un mot de passe expiré
     */
    public static function passwordExpired(string $message = "Mot de passe expiré"): self
    {
        return new self($message, self::PASSWORD_EXPIRED);
    }

    /**
     * Crée une exception pour un email déjà existant
     */
    public static function emailAlreadyExists(string $message = "Email déjà utilisé"): self
    {
        return new self($message, self::EMAIL_ALREADY_EXISTS);
    }

    /**
     * Crée une exception pour un format de mot de passe invalide
     */
    public static function invalidPasswordFormat(string $message = "Format de mot de passe invalide"): self
    {
        return new self($message, self::INVALID_PASSWORD_FORMAT);
    }

    /**
     * Crée une exception pour trop de tentatives
     */
    public static function tooManyAttempts(string $message = "Trop de tentatives, veuillez réessayer plus tard"): self
    {
        return new self($message, self::TOO_MANY_ATTEMPTS);
    }

    /**
     * Crée une exception pour un échec d'inscription
     */
    public static function registrationFailed(string $message = "Échec de l'inscription"): self
    {
        return new self($message, self::REGISTRATION_FAILED);
    }

    /**
     * Vérifie si l'erreur est liée aux identifiants
     */
    public function isCredentialsError(): bool
    {
        return in_array($this->code, [
            self::INVALID_CREDENTIALS,
            self::ACCOUNT_LOCKED,
            self::ACCOUNT_NOT_VERIFIED,
            self::ACCOUNT_DISABLED
        ]);
    }

    /**
     * Vérifie si l'erreur est liée au token
     */
    public function isTokenError(): bool
    {
        return in_array($this->code, [
            self::TOKEN_EXPIRED,
            self::TOKEN_INVALID
        ]);
    }

    /**
     * Vérifie si l'erreur est liée à la sécurité
     */
    public function isSecurityError(): bool
    {
        return in_array($this->code, [
            self::TOO_MANY_ATTEMPTS,
            self::PASSWORD_EXPIRED
        ]);
    }
}