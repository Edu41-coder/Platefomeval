<?php

namespace Core\Exception;

class Exception extends \Exception
{
    /**
     * Données supplémentaires liées à l'exception
     */
    protected array $context = [];

    /**
     * Constructeur étendu
     */
    public function __construct(
        string $message = "",
        int $code = 0,
        ?\Throwable $previous = null,
        array $context = []
    ) {
        $this->context = $context;
        parent::__construct($message, $code, $previous);
    }

    /**
     * Récupère le contexte de l'exception
     */
    public function getContext(): array
    {
        return $this->context;
    }

    /**
     * Ajoute des données au contexte
     */
    public function addContext(string $key, $value): self
    {
        $this->context[$key] = $value;
        return $this;
    }

    /**
     * Récupère une donnée spécifique du contexte
     */
    public function getContextData(string $key)
    {
        return $this->context[$key] ?? null;
    }

    /**
     * Formate l'exception pour le logging
     */
    public function __toString(): string
    {
        return sprintf(
            "[%s] %s in %s:%d\nStack trace:\n%s\nContext: %s",
            $this->code,
            $this->message,
            $this->file,
            $this->line,
            $this->getTraceAsString(),
            json_encode($this->context, JSON_PRETTY_PRINT)
        );
    }

    /**
     * Crée une représentation JSON de l'exception
     */
    public function toArray(): array
    {
        return [
            'message' => $this->message,
            'code' => $this->code,
            'file' => $this->file,
            'line' => $this->line,
            'context' => $this->context
        ];
    }
}