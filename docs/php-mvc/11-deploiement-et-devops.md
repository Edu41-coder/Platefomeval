# Déploiement et DevOps

## 1. Environnements de Déploiement

### Configuration des Environnements
```php
namespace App\Config;

class Environment {
    private $configs = [];
    
    public function load(string $env): void {
        $path = ".env.{$env}";
        if (file_exists($path)) {
            $this->configs = parse_ini_file($path);
        }
    }
    
    public function get(string $key, $default = null) {
        return $this->configs[$key] ?? $default;
    }
}

// Exemple de .env.production
```
DB_HOST=production-db
DB_NAME=app_prod
DB_USER=prod_user
DB_PASS=secure_password

CACHE_DRIVER=redis
SESSION_DRIVER=redis
QUEUE_DRIVER=redis
```

## 2. Intégration Continue (CI)

### Configuration GitHub Actions
```yaml
# .github/workflows/ci.yml
name: CI

on:
  push:
    branches: [ main ]
  pull_request:
    branches: [ main ]

jobs:
  test:
    runs-on: ubuntu-latest
    
    steps:
    - uses: actions/checkout@v2
    
    - name: Setup PHP
      uses: shivammathur/setup-php@v2
      with:
        php-version: '8.1'
        
    - name: Install Dependencies
      run: composer install
      
    - name: Run Tests
      run: vendor/bin/phpunit
      
    - name: Run PHP CS Fixer
      run: vendor/bin/php-cs-fixer fix --dry-run
      
    - name: Run PHPStan
      run: vendor/bin/phpstan analyse
```

## 3. Déploiement Automatisé

### Script de Déploiement
```php
namespace App\Deploy;

class Deployer {
    private $connection;
    private $config;
    
    public function deploy(): void {
        try {
            $this->beforeDeploy();
            $this->uploadCode();
            $this->runMigrations();
            $this->clearCache();
            $this->restartServices();
            $this->afterDeploy();
        } catch (Exception $e) {
            $this->handleDeploymentError($e);
        }
    }
    
    private function beforeDeploy(): void {
        // Vérifications pré-déploiement
        $this->checkRequirements();
        $this->backupDatabase();
    }
    
    private function uploadCode(): void {
        // Upload via SFTP ou Git pull
        $this->connection->execute('git pull origin main');
        $this->connection->execute('composer install --no-dev');
    }
    
    private function restartServices(): void {
        $this->connection->execute('sudo systemctl restart php-fpm');
        $this->connection->execute('sudo systemctl restart nginx');
    }
}
```

## 4. Containers et Docker

### Configuration Docker
```dockerfile
# Dockerfile
FROM php:8.1-fpm

# Installation des dépendances
RUN apt-get update && apt-get install -y \
    git \
    zip \
    unzip \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev

# Extensions PHP
RUN docker-php-ext-install pdo_mysql

# Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY . .

RUN composer install --no-dev

EXPOSE 9000

CMD ["php-fpm"]
```

### Docker Compose
```yaml
# docker-compose.yml
version: '3'

services:
  app:
    build:
      context: .
      dockerfile: Dockerfile
    volumes:
      - .:/var/www/html
    networks:
      - app-network
      
  nginx:
    image: nginx:alpine
    ports:
      - "80:80"
    volumes:
      - .:/var/www/html
      - ./nginx.conf:/etc/nginx/conf.d/default.conf
    networks:
      - app-network
      
  mysql:
    image: mysql:8.0
    environment:
      MYSQL_ROOT_PASSWORD: root
      MYSQL_DATABASE: app
    volumes:
      - dbdata:/var/lib/mysql
    networks:
      - app-network
      
  redis:
    image: redis:alpine
    networks:
      - app-network

networks:
  app-network:
    driver: bridge

volumes:
  dbdata:
```

## 5. Monitoring et Logs

### Service de Monitoring
```php
namespace App\Monitoring;

class MonitoringService {
    private $metrics;
    private $alerts;
    
    public function collectMetrics(): void {
        // Métriques système
        $this->metrics->gauge('system.memory', memory_get_usage(true));
        $this->metrics->gauge('system.cpu', sys_getloadavg()[0]);
        
        // Métriques application
        $this->metrics->gauge('app.requests', $this->getRequestCount());
        $this->metrics->gauge('app.errors', $this->getErrorCount());
        $this->metrics->gauge('app.response_time', $this->getAverageResponseTime());
    }
    
    public function checkAlerts(): void {
        foreach ($this->metrics->getAll() as $metric => $value) {
            if ($this->shouldAlert($metric, $value)) {
                $this->alerts->send(new Alert($metric, $value));
            }
        }
    }
}
```

## 6. Sauvegardes

### Service de Backup
```php
namespace App\Backup;

class BackupService {
    private $storage;
    private $config;
    
    public function createBackup(): string {
        $filename = date('Y-m-d_His') . '_backup.zip';
        
        try {
            // Backup de la base de données
            $this->backupDatabase();
            
            // Backup des fichiers
            $this->backupFiles();
            
            // Upload vers le stockage distant
            $this->uploadToStorage($filename);
            
            return $filename;
        } catch (Exception $e) {
            $this->handleBackupError($e);
        }
    }
    
    private function backupDatabase(): void {
        $command = sprintf(
            'mysqldump -u%s -p%s %s > %s',
            $this->config->get('db.user'),
            $this->config->get('db.password'),
            $this->config->get('db.name'),
            storage_path('backups/database.sql')
        );
        
        exec($command);
    }
}
```

## 7. Scalabilité

### Load Balancer
```php
namespace App\LoadBalancer;

class LoadBalancer {
    private $nodes = [];
    private $strategy;
    
    public function addNode(string $host, int $port): void {
        $this->nodes[] = new Node($host, $port);
    }
    
    public function getNode(): Node {
        return $this->strategy->selectNode($this->nodes);
    }
}

class RoundRobinStrategy implements BalancingStrategy {
    private $current = 0;
    
    public function selectNode(array $nodes): Node {
        if (empty($nodes)) {
            throw new NoNodesAvailableException();
        }
        
        $node = $nodes[$this->current];
        $this->current = ($this->current + 1) % count($nodes);
        
        return $node;
    }
}
```

## 8. Configuration des Serveurs

### Nginx Configuration
```nginx
server {
    listen 80;
    server_name example.com;
    root /var/www/html/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php;

    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

## 9. Gestion des Processus

### Supervisor Configuration
```ini
[program:queue-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/html/artisan queue:work
autostart=true
autorestart=true
user=www-data
numprocs=8
redirect_stderr=true
stdout_logfile=/var/log/supervisor/queue-worker.log
```

## 10. Sécurité en Production

### Security Checklist
```php
namespace App\Security;

class ProductionSecurity {
    public function verify(): array {
        $checks = [
            'https' => $this->verifyHttps(),
            'headers' => $this->verifySecurityHeaders(),
            'permissions' => $this->verifyFilePermissions(),
            'dependencies' => $this->verifyDependencies(),
            'configs' => $this->verifyConfigs()
        ];
        
        return array_filter($checks, function($check) {
            return !$check['passed'];
        });
    }
    
    private function verifyHttps(): array {
        return [
            'name' => 'HTTPS',
            'passed' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
            'message' => 'HTTPS should be enabled'
        ];
    }
}
```

## Conclusion

Points clés pour le Déploiement :
1. Automatisation complète
2. Monitoring constant
3. Sauvegardes régulières
4. Sécurité renforcée
5. Scalabilité prévue

Recommandations :
- Utiliser CI/CD
- Containeriser l'application
- Monitorer en production
- Automatiser les sauvegardes
- Maintenir la documentation 