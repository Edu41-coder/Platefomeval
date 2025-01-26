# Déploiement et pratiques DevOps

## 1. Configuration des environnements

### Structure des environnements
```bash
environments/
├── local/
│   ├── .env
│   └── docker-compose.yml
├── staging/
│   ├── .env
│   └── docker-compose.yml
└── production/
    ├── .env
    └── docker-compose.yml
```

### Configuration Docker
```yaml
# docker-compose.yml
version: '3.8'

services:
  app:
    build:
      context: .
      dockerfile: Dockerfile
      args:
        - ENV=${APP_ENV}
    environment:
      - APP_ENV=${APP_ENV}
      - DB_HOST=${DB_HOST}
    volumes:
      - ./:/var/www/html
    networks:
      - app-network

  nginx:
    image: nginx:alpine
    ports:
      - "80:80"
    volumes:
      - ./:/var/www/html
      - ./docker/nginx:/etc/nginx/conf.d
    networks:
      - app-network

networks:
  app-network:
    driver: bridge
```

## 2. Pipeline CI/CD

### Configuration GitHub Actions
```yaml
# .github/workflows/ci-cd.yml
name: CI/CD Pipeline

on:
  push:
    branches: [ main, develop ]
  pull_request:
    branches: [ main, develop ]

jobs:
  test:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v2
      
      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.1'
          
      - name: Install dependencies
        run: composer install --prefer-dist --no-progress
        
      - name: Run tests
        run: vendor/bin/phpunit
        
  deploy:
    needs: test
    if: github.ref == 'refs/heads/main'
    runs-on: ubuntu-latest
    steps:
      - name: Deploy to production
        uses: appleboy/ssh-action@master
        with:
          host: ${{ secrets.SERVER_HOST }}
          username: ${{ secrets.SERVER_USER }}
          key: ${{ secrets.SSH_PRIVATE_KEY }}
          script: |
            cd /var/www/production
            git pull origin main
            composer install --no-dev
            php artisan migrate --force
            php artisan optimize
```

## 3. Scripts de déploiement

### Script de déploiement automatisé
```php
namespace App\Console\Commands;

class DeployCommand extends Command {
    protected $signature = 'deploy {environment}';
    
    public function handle(): void {
        $env = $this->argument('environment');
        
        $this->info("Deploying to {$env}...");
        
        $steps = [
            'Backup' => fn() => $this->backup(),
            'Pull changes' => fn() => $this->pullChanges(),
            'Install dependencies' => fn() => $this->installDependencies(),
            'Migrate database' => fn() => $this->migrate(),
            'Clear cache' => fn() => $this->clearCache(),
            'Restart services' => fn() => $this->restartServices(),
        ];
        
        foreach ($steps as $step => $callback) {
            $this->info("Step: {$step}");
            $callback();
        }
    }
    
    private function backup(): void {
        // Logique de backup
    }
    
    private function pullChanges(): void {
        Process::run('git pull origin main');
    }
    
    private function installDependencies(): void {
        Process::run('composer install --no-dev --optimize-autoloader');
    }
}
```

## 4. Monitoring et logging

### Configuration de la surveillance
```php
// config/monitoring.php
return [
    'services' => [
        'newrelic' => [
            'license_key' => env('NEWRELIC_LICENSE_KEY'),
            'app_name' => env('NEWRELIC_APP_NAME')
        ],
        'sentry' => [
            'dsn' => env('SENTRY_DSN')
        ]
    ],
    'metrics' => [
        'response_time',
        'memory_usage',
        'database_queries',
        'cache_hits'
    ]
];

// MonitoringService.php
namespace App\Services;

class MonitoringService {
    public function trackMetric(string $name, float $value): void {
        if (extension_loaded('newrelic')) {
            newrelic_custom_metric("Custom/{$name}", $value);
        }
    }
    
    public function captureException(\Throwable $e): void {
        if (class_exists('Sentry')) {
            \Sentry\captureException($e);
        }
    }
}
```

## 5. Gestion des assets

### Configuration Webpack
```javascript
// webpack.config.js
const path = require('path');
const MiniCssExtractPlugin = require('mini-css-extract-plugin');

module.exports = {
    entry: './resources/js/app.js',
    output: {
        path: path.resolve(__dirname, 'public/dist'),
        filename: '[name].[contenthash].js'
    },
    plugins: [
        new MiniCssExtractPlugin({
            filename: '[name].[contenthash].css'
        })
    ],
    optimization: {
        splitChunks: {
            chunks: 'all'
        }
    }
};
```

### Script de build
```php
namespace App\Console\Commands;

class BuildAssetsCommand extends Command {
    public function handle(): void {
        $this->info('Building assets...');
        
        // Compilation des assets
        Process::run('npm run production');
        
        // Mise à jour du manifest
        $this->updateManifest();
        
        // Nettoyage des anciens assets
        $this->cleanOldAssets();
    }
    
    private function updateManifest(): void {
        $manifest = json_decode(
            file_get_contents(public_path('dist/manifest.json')),
            true
        );
        
        cache()->forever('assets.manifest', $manifest);
    }
}
```

## 6. Gestion des migrations

### Migration sécurisée
```php
namespace App\Services;

class DatabaseMigrationService {
    public function migrate(): void {
        // Backup avant migration
        $this->backup();
        
        try {
            // Migration
            $this->runMigrations();
            
            // Vérification
            $this->verifyMigration();
        } catch (\Exception $e) {
            // Rollback en cas d'erreur
            $this->rollback();
            throw $e;
        }
    }
    
    private function backup(): void {
        $filename = sprintf(
            'backup-%s.sql',
            now()->format('Y-m-d-H-i-s')
        );
        
        Process::run("mysqldump -u root -p database > {$filename}");
    }
    
    private function verifyMigration(): void {
        // Vérification de l'intégrité
        $this->checkForeignKeys();
        $this->checkIndexes();
        $this->runTests();
    }
}
```

## 7. Scalabilité et performance

### Configuration du load balancer
```php
// config/load-balancer.php
return [
    'strategy' => 'round-robin',
    'servers' => [
        [
            'host' => '10.0.0.1',
            'port' => 80,
            'weight' => 1
        ],
        [
            'host' => '10.0.0.2',
            'port' => 80,
            'weight' => 1
        ]
    ],
    'health_check' => [
        'interval' => 30,
        'timeout' => 5,
        'path' => '/health'
    ]
];

// LoadBalancerService.php
namespace App\Services;

class LoadBalancerService {
    public function getNextServer(): array {
        $servers = config('load-balancer.servers');
        $strategy = config('load-balancer.strategy');
        
        return match($strategy) {
            'round-robin' => $this->roundRobin($servers),
            'least-connections' => $this->leastConnections($servers),
            default => $servers[0]
        };
    }
    
    public function healthCheck(): void {
        foreach (config('load-balancer.servers') as $server) {
            $this->checkServer($server);
        }
    }
}
```

## 8. Sécurité en production

### Configuration de sécurité
```php
// config/security.php
return [
    'headers' => [
        'X-Frame-Options' => 'DENY',
        'X-XSS-Protection' => '1; mode=block',
        'X-Content-Type-Options' => 'nosniff',
        'Strict-Transport-Security' => 'max-age=31536000; includeSubDomains'
    ],
    'ssl' => [
        'enabled' => true,
        'redirect' => true,
        'hsts' => true
    ],
    'rate_limiting' => [
        'enabled' => true,
        'max_attempts' => 60,
        'decay_minutes' => 1
    ]
];

// SecurityMiddleware.php
namespace App\Http\Middleware;

class SecurityMiddleware {
    public function handle($request, Closure $next) {
        $response = $next($request);
        
        foreach (config('security.headers') as $header => $value) {
            $response->headers->set($header, $value);
        }
        
        return $response;
    }
}
```

## 9. Automatisation des tâches

### Tâches planifiées
```php
namespace App\Console;

class Kernel extends ConsoleKernel {
    protected function schedule(Schedule $schedule) {
        // Backup quotidien
        $schedule->command('backup:run')
                ->daily()
                ->at('01:00')
                ->onFailure(function () {
                    $this->notifyFailure('backup');
                });
        
        // Nettoyage des logs
        $schedule->command('logs:clean')
                ->weekly()
                ->sundays()
                ->at('00:00');
        
        // Monitoring
        $schedule->command('monitor:check-services')
                ->everyFiveMinutes()
                ->withoutOverlapping();
    }
}
```

## 10. Documentation et maintenance

### Générateur de documentation
```php
namespace App\Console\Commands;

class GenerateDocsCommand extends Command {
    public function handle(): void {
        $this->generateApiDocs();
        $this->generateDeploymentDocs();
        $this->generateMaintenanceDocs();
    }
    
    private function generateApiDocs(): void {
        $this->info('Generating API documentation...');
        
        $openapi = [
            'openapi' => '3.0.0',
            'info' => [
                'title' => config('app.name') . ' API',
                'version' => '1.0.0'
            ],
            'paths' => $this->generatePaths(),
            'components' => $this->generateComponents()
        ];
        
        file_put_contents(
            base_path('docs/api.json'),
            json_encode($openapi, JSON_PRETTY_PRINT)
        );
    }
}
```

## Conclusion

Points clés DevOps :
1. Automatisation complète
2. Intégration continue
3. Déploiement continu
4. Monitoring en temps réel
5. Scalabilité maîtrisée

Recommandations :
- Documenter les processus
- Automatiser les tâches répétitives
- Surveiller les performances
- Maintenir la sécurité
- Former l'équipe aux outils 