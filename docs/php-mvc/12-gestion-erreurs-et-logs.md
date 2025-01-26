# Gestion des Erreurs et Logs

## 1. Gestionnaire d'Erreurs

### Handler Principal
```php
namespace App\Exceptions;

class ExceptionHandler {
    private $logger;
    private $config;
    
    public function handle(\Throwable $e): Response {
        $this->report($e);
        
        return $this->render($e);
    }
    
    protected function report(\Throwable $e): void {
        if ($this->shouldReport($e)) {
            $this->logger->error($e->getMessage(), [
                'exception' => $e,
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
    
    protected function render(\Throwable $e): Response {
        if ($this->isHttpException($e)) {
            return $this->renderHttpException($e);
        }
        
        return $this->renderGenericException($e);
    }
}
```

## 2. Types d'Exceptions Personnalisées

### Exceptions Métier
```php
namespace App\Exceptions;

class BusinessException extends \Exception {
    protected $data;
    
    public function __construct(string $message, array $data = []) {
        parent::__construct($message);
        $this->data = $data;
    }
    
    public function getData(): array {
        return $this->data;
    }
}

class ValidationException extends BusinessException {
    public function getErrors(): array {
        return $this->getData();
    }
}

class NotFoundException extends BusinessException {
    public function getResourceType(): string {
        return $this->getData()['resource'] ?? 'unknown';
    }
}
```

## 3. Système de Logging

### Logger Principal
```php
namespace App\Logging;

class Logger {
    private $handlers = [];
    private $processors = [];
    
    public function log(string $level, string $message, array $context = []): void {
        $record = [
            'message' => $message,
            'context' => $context,
            'level' => $level,
            'datetime' => new \DateTime(),
            'extra' => []
        ];
        
        foreach ($this->processors as $processor) {
            $record = $processor($record);
        }
        
        foreach ($this->handlers as $handler) {
            if ($handler->isHandling($record)) {
                $handler->handle($record);
            }
        }
    }
    
    public function addHandler(HandlerInterface $handler): self {
        $this->handlers[] = $handler;
        return $this;
    }
    
    public function addProcessor(callable $processor): self {
        $this->processors[] = $processor;
        return $this;
    }
}
```

## 4. Handlers de Log

### Handlers Spécifiques
```php
namespace App\Logging\Handlers;

class FileHandler implements HandlerInterface {
    private $path;
    private $level;
    
    public function handle(array $record): void {
        $line = $this->formatRecord($record);
        file_put_contents(
            $this->path,
            $line . PHP_EOL,
            FILE_APPEND
        );
    }
    
    private function formatRecord(array $record): string {
        return sprintf(
            '[%s] %s: %s %s',
            $record['datetime']->format('Y-m-d H:i:s'),
            $record['level'],
            $record['message'],
            json_encode($record['context'])
        );
    }
}

class DatabaseHandler implements HandlerInterface {
    private $db;
    
    public function handle(array $record): void {
        $this->db->insert('logs', [
            'level' => $record['level'],
            'message' => $record['message'],
            'context' => json_encode($record['context']),
            'created_at' => $record['datetime']
        ]);
    }
}
```

## 5. Processors de Log

### Processors Personnalisés
```php
namespace App\Logging\Processors;

class WebProcessor {
    public function __invoke(array $record): array {
        $record['extra']['url'] = $_SERVER['REQUEST_URI'] ?? null;
        $record['extra']['ip'] = $_SERVER['REMOTE_ADDR'] ?? null;
        $record['extra']['http_method'] = $_SERVER['REQUEST_METHOD'] ?? null;
        $record['extra']['server'] = gethostname();
        
        return $record;
    }
}

class UserProcessor {
    private $auth;
    
    public function __invoke(array $record): array {
        if ($user = $this->auth->getUser()) {
            $record['extra']['user_id'] = $user->id;
            $record['extra']['user_email'] = $user->email;
        }
        
        return $record;
    }
}
```

## 6. Monitoring des Erreurs

### Service de Monitoring
```php
namespace App\Monitoring;

class ErrorMonitor {
    private $threshold = 50;
    private $timeWindow = 3600;
    private $redis;
    
    public function track(\Throwable $e): void {
        $key = $this->getExceptionKey($e);
        $count = $this->incrementError($key);
        
        if ($count >= $this->threshold) {
            $this->notifyTeam($e, $count);
        }
    }
    
    private function incrementError(string $key): int {
        $this->redis->incr($key);
        $this->redis->expire($key, $this->timeWindow);
        return (int) $this->redis->get($key);
    }
    
    private function notifyTeam(\Throwable $e, int $count): void {
        $notification = new ErrorNotification(
            $e->getMessage(),
            $count,
            $this->timeWindow
        );
        
        $this->notifications->send($notification);
    }
}
```

## 7. Formatage des Erreurs

### Formateurs Personnalisés
```php
namespace App\Exceptions\Formatters;

class JsonFormatter implements FormatterInterface {
    public function format(\Throwable $e): array {
        return [
            'message' => $e->getMessage(),
            'code' => $e->getCode(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $this->formatTrace($e->getTrace())
        ];
    }
    
    private function formatTrace(array $trace): array {
        return array_map(function($item) {
            return [
                'file' => $item['file'] ?? null,
                'line' => $item['line'] ?? null,
                'function' => $item['function'] ?? null,
                'class' => $item['class'] ?? null
            ];
        }, $trace);
    }
}
```

## 8. Notifications d'Erreurs

### Service de Notification
```php
namespace App\Notifications;

class ErrorNotificationService {
    private $channels = [];
    
    public function addChannel(NotificationChannel $channel): void {
        $this->channels[] = $channel;
    }
    
    public function notify(\Throwable $e): void {
        $notification = new ErrorNotification($e);
        
        foreach ($this->channels as $channel) {
            $channel->send($notification);
        }
    }
}

class SlackChannel implements NotificationChannel {
    private $webhook;
    
    public function send(Notification $notification): void {
        $payload = [
            'text' => $this->formatMessage($notification),
            'attachments' => [
                [
                    'color' => 'danger',
                    'fields' => $this->formatFields($notification)
                ]
            ]
        ];
        
        $this->sendToSlack($payload);
    }
}
```

## 9. Rotation des Logs

### Gestionnaire de Rotation
```php
namespace App\Logging;

class LogRotator {
    private $maxFiles = 30;
    private $maxSize = 100 * 1024 * 1024; // 100 MB
    
    public function rotate(string $path): void {
        if (!$this->shouldRotate($path)) {
            return;
        }
        
        $this->archiveCurrentLog($path);
        $this->cleanOldLogs($path);
    }
    
    private function shouldRotate(string $path): bool {
        return filesize($path) > $this->maxSize;
    }
    
    private function archiveCurrentLog(string $path): void {
        $archived = $path . '.' . date('Y-m-d');
        rename($path, $archived);
        gzcompress($archived);
    }
}
```

## 10. Analyse des Logs

### Analyseur de Logs
```php
namespace App\Logging\Analysis;

class LogAnalyzer {
    private $parser;
    private $aggregator;
    
    public function analyze(string $path): array {
        $logs = $this->parser->parse($path);
        
        return [
            'error_count' => $this->countErrors($logs),
            'top_errors' => $this->getTopErrors($logs),
            'error_timeline' => $this->getErrorTimeline($logs),
            'affected_users' => $this->getAffectedUsers($logs)
        ];
    }
    
    private function countErrors(array $logs): array {
        return array_count_values(array_column($logs, 'level'));
    }
    
    private function getTopErrors(array $logs): array {
        $errors = array_filter($logs, function($log) {
            return $log['level'] === 'error';
        });
        
        return array_count_values(array_column($errors, 'message'));
    }
}
```

## Conclusion

Points clés pour la Gestion des Erreurs :
1. Gestion centralisée
2. Logging structuré
3. Monitoring proactif
4. Notifications efficaces
5. Analyse régulière

Recommandations :
- Implémenter une hiérarchie d'exceptions
- Utiliser plusieurs handlers de log
- Configurer la rotation des logs
- Mettre en place des alertes
- Analyser régulièrement les logs 