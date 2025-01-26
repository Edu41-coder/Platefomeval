<?php

use PDO;

/**
 * Configuration de la base de données
 */
return [
    /*
    |--------------------------------------------------------------------------
    | Configuration de connexion par défaut
    |--------------------------------------------------------------------------
    */
    'default' => $_ENV['DB_CONNECTION'] ?? 'mysql',

    /*
    |--------------------------------------------------------------------------
    | Configurations des connexions disponibles
    |--------------------------------------------------------------------------
    */
    'connections' => [
        'mysql' => [
            'driver' => 'mysql',
            'host' => $_ENV['DB_HOST'] ?? 'localhost',
            'port' => (int)($_ENV['DB_PORT'] ?? '3306'),
            'database' => $_ENV['DB_NAME'] ?? 'plateformeval',
            'username' => $_ENV['DB_USER'] ?? 'root',
            'password' => $_ENV['DB_PASS'] ?? '',
            'charset' => $_ENV['DB_CHARSET'] ?? 'utf8mb4',
            'collation' => $_ENV['DB_COLLATION'] ?? 'utf8mb4_unicode_ci',
            'prefix' => $_ENV['DB_PREFIX'] ?? '',
            'options' => [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
            ]
        ],
        
        // Possibilité d'ajouter d'autres connexions (PostgreSQL, SQLite, etc.)
    ],

    /*
    |--------------------------------------------------------------------------
    | Options de connexion globales
    |--------------------------------------------------------------------------
    */
    'options' => [
        'pool' => [
            'min' => (int)($_ENV['DB_POOL_MIN'] ?? 2),
            'max' => (int)($_ENV['DB_POOL_MAX'] ?? 10)
        ],
        'timeout' => (int)($_ENV['DB_TIMEOUT'] ?? 30),
        'retry' => [
            'times' => (int)($_ENV['DB_RETRY_TIMES'] ?? 3),
            'delay' => (int)($_ENV['DB_RETRY_DELAY'] ?? 100)
        ]
    ],

    /*
    |--------------------------------------------------------------------------
    | Options de sécurité
    |--------------------------------------------------------------------------
    */
    'security' => [
        'ssl' => [
            'enabled' => filter_var($_ENV['DB_SSL_ENABLED'] ?? false, FILTER_VALIDATE_BOOLEAN),
            'key' => $_ENV['DB_SSL_KEY'] ?? null,
            'cert' => $_ENV['DB_SSL_CERT'] ?? null,
            'ca' => $_ENV['DB_SSL_CA'] ?? null,
            'verify' => filter_var($_ENV['DB_SSL_VERIFY'] ?? true, FILTER_VALIDATE_BOOLEAN)
        ],
        
    ]

];