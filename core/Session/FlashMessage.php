<?php

namespace Core\Session;

class FlashMessage
{
    private const FLASH_KEY = 'flash_messages';

    /**
     * Ajoute un message flash
     */
    public static function add(string $type, string $message): void
    {
        if (!isset($_SESSION[self::FLASH_KEY]) || !is_array($_SESSION[self::FLASH_KEY])) {
            $_SESSION[self::FLASH_KEY] = [];
        }
        
        $_SESSION[self::FLASH_KEY][] = [
            'type' => $type,
            'message' => $message,
            'displayed' => false
        ];
    }

    /**
     * Récupère tous les messages flash
     */
    public static function getAll(): array
    {
        if (!isset($_SESSION[self::FLASH_KEY]) || !is_array($_SESSION[self::FLASH_KEY])) {
            return [];
        }

        $messages = $_SESSION[self::FLASH_KEY];
        
        return array_filter($messages, function($message) {
            return !($message['displayed'] ?? false);
        });
    }

    /**
     * Marque les messages comme affichés
     */
    public static function markAsDisplayed(): void
    {
        if (!isset($_SESSION[self::FLASH_KEY]) || !is_array($_SESSION[self::FLASH_KEY])) {
            return;
        }

        foreach ($_SESSION[self::FLASH_KEY] as &$message) {
            if (!$message['displayed']) {
                $message['displayed'] = true;
            }
        }
        unset($message); // Break the reference
    }

    /**
     * Vérifie s'il y a des messages flash
     */
    public static function has(string $type = null): bool
    {
        $messages = $_SESSION[self::FLASH_KEY] ?? [];
        
        if ($type === null) {
            return !empty(array_filter($messages, function($message) {
                return !($message['displayed'] ?? false);
            }));
        }

        return !empty(array_filter($messages, function($message) use ($type) {
            return $message['type'] === $type && !($message['displayed'] ?? false);
        }));
    }

    /**
     * Récupère les messages d'un type spécifique
     */
    public static function get(string $type): array
    {
        $messages = $_SESSION[self::FLASH_KEY] ?? [];
        
        $typeMessages = array_filter($messages, function($message) use ($type) {
            return $message['type'] === $type && !($message['displayed'] ?? false);
        });

        // Marquer les messages de ce type comme affichés
        foreach ($_SESSION[self::FLASH_KEY] as &$message) {
            if ($message['type'] === $type) {
                $message['displayed'] = true;
            }
        }

        return $typeMessages;
    }

    /**
     * Nettoie tous les messages flash
     */
    public static function clear(): void
    {
        $_SESSION[self::FLASH_KEY] = [];
    }

    /**
     * Nettoie les messages affichés à la fin de la requête
     */
    public static function clearDisplayed(): void
    {
        if (!isset($_SESSION[self::FLASH_KEY])) {
            return;
        }

        $_SESSION[self::FLASH_KEY] = array_filter($_SESSION[self::FLASH_KEY], function($message) {
            return !($message['displayed'] ?? false);
        });
    }

    public function getMessages(): array
    {
        $messages = $_SESSION['flash_messages'] ?? [];
        return $messages;
    }
}