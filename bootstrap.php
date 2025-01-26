<?php

use Dotenv\Dotenv;

// Chargement de l'autoloader de Composer
require_once __DIR__ . '/vendor/autoload.php';

// Chargement des helpers
require_once __DIR__ . '/app/helpers.php';

// Start the session if it hasn't been started yet
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Chargement des variables d'environnement
$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

// Validation des variables d'environnement requises
$dotenv->required([
    'APP_ENV',
    'APP_DEBUG',
    'APP_URL',
    'DB_HOST',
    'DB_PORT',
    'DB_NAME',
    'DB_USER'
])->notEmpty();

// Validation des types spécifiques
$dotenv->required('APP_DEBUG')->isBoolean();
$dotenv->required('DB_PORT')->isInteger();

// Configuration des erreurs selon l'environnement
if (filter_var($_ENV['APP_DEBUG'], FILTER_VALIDATE_BOOLEAN)) {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// Définir le fuseau horaire depuis la config
date_default_timezone_set($_ENV['APP_TIMEZONE'] ?? 'Europe/Paris');

// Définir l'encodage par défaut
mb_internal_encoding('UTF-8');