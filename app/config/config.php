<?php

/**
 * Configuration générale de l'application
 */
return [
    /*
    |--------------------------------------------------------------------------
    | Configuration de l'environnement
    |--------------------------------------------------------------------------
    */
    'env' => [
        'name' => $_ENV['APP_ENV'] ?? 'development',
        'debug' => filter_var($_ENV['APP_DEBUG'] ?? true, FILTER_VALIDATE_BOOLEAN),
        'url' => $_ENV['APP_URL'] ?? 'http://localhost',
        'timezone' => 'Europe/Paris',
        'locale' => 'fr',
    ],

    /*
    |--------------------------------------------------------------------------
    | Configuration de l'API
    |--------------------------------------------------------------------------
    */
    'api' => [
        'version' => '1.0',
        'prefix' => '/api',
    ],
    // ... autres configurations ...

    /*
|--------------------------------------------------------------------------
| Configuration CORS
|--------------------------------------------------------------------------
*/
    'cors' => [
        'enabled' => filter_var($_ENV['CORS_ENABLED'] ?? true, FILTER_VALIDATE_BOOLEAN),
        'allowed_origins' => array_filter(
            explode(',', $_ENV['CORS_ALLOWED_ORIGINS'] ?? '*')
        ),
        'allowed_methods' => array_filter(
            explode(',', $_ENV['CORS_ALLOWED_METHODS'] ?? 'GET,POST,PUT,DELETE,OPTIONS,PATCH,HEAD')
        ),
        'allowed_headers' => array_filter(
            explode(',', $_ENV['CORS_ALLOWED_HEADERS'] ?? implode(',', [
                'Content-Type',
                'Authorization',
                'X-Requested-With',
                'Accept',
                'Origin',
                'X-CSRF-TOKEN',
                'Cache-Control',
                'If-Match',
                'If-None-Match'
            ]))
        ),
        'exposed_headers' => array_filter(
            explode(',', $_ENV['CORS_EXPOSED_HEADERS'] ?? implode(',', [
                'X-RateLimit-Limit',
                'X-RateLimit-Remaining',
                'X-RateLimit-Reset'
            ]))
        ),
        'max_age' => max(0, min(
            (int)($_ENV['CORS_MAX_AGE'] ?? 3600),
            86400  // Maximum 24 heures
        )),
        'supports_credentials' => filter_var(
            $_ENV['CORS_SUPPORTS_CREDENTIALS'] ?? true,
            FILTER_VALIDATE_BOOLEAN
        ),
        'allow_private_network' => filter_var(
            $_ENV['CORS_ALLOW_PRIVATE_NETWORK'] ?? false,
            FILTER_VALIDATE_BOOLEAN
        ),
    ],

    // ... autres configurations ...

    /*
    |--------------------------------------------------------------------------
    | Configuration du rate limiting
    |--------------------------------------------------------------------------
    */
    'rate_limit' => [
        'enabled' => filter_var($_ENV['RATE_LIMIT_ENABLED'] ?? true, FILTER_VALIDATE_BOOLEAN),
        'max_requests' => (int)($_ENV['RATE_LIMIT_MAX'] ?? 60),
        'reset_time' => (int)($_ENV['RATE_LIMIT_RESET'] ?? 60)
    ],

    /*
    |--------------------------------------------------------------------------
    | Configuration des uploads
    |--------------------------------------------------------------------------
    */
    'uploads' => [
        'max_size' => $_ENV['UPLOAD_MAX_SIZE'] ?? '5M',
        'allowed_types' => array_filter(
            explode(',', $_ENV['UPLOAD_ALLOWED_TYPES'] ?? 'jpg,jpeg,png,pdf')
        ),
        'path' => $_ENV['UPLOAD_PATH'] ?? $_SERVER['DOCUMENT_ROOT'] . '/public/uploads',
        'public_path' => '/uploads'
    ],
    /*
    |--------------------------------------------------------------------------
    | Configuration de la sécurité
    |--------------------------------------------------------------------------
    */
    'security' => [
        'csrf' => [
            'enabled' => true,
            'token_length' => 32,
            'token_name' => 'csrf_token',
            'expire' => 7200, // 2 heures en secondes
        ],
        'session' => [
            'name' => 'PHPSESSID',
            'lifetime' => 7200,
            'path' => '/',
            'domain' => null,
            'secure' => true,
            'httponly' => true,
            'samesite' => 'Lax'
        ]
    ]

];
