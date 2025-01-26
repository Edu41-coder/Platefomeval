# Intégration avec les Services Externes et APIs

## 1. Introduction aux Intégrations Externes

L'intégration de services externes et d'APIs est essentielle pour étendre les fonctionnalités d'une application. Ce document explore les meilleures pratiques et les approches recommandées pour intégrer divers services externes avec Composer Anthropic.

## 2. Configuration des Services Externes

### Structure de Configuration
```php
// config/services.php
return [
    'api_keys' => [
        'stripe' => env('STRIPE_API_KEY'),
        'mailgun' => env('MAILGUN_API_KEY'),
        'aws' => [
            'key' => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
            'region' => env('AWS_DEFAULT_REGION')
        ]
    ],
    'endpoints' => [
        'payment_gateway' => env('PAYMENT_GATEWAY_URL'),
        'email_service' => env('EMAIL_SERVICE_URL')
    ]
];
```

### Gestionnaire de Configuration
```php
namespace App\Config;

class ServiceConfig {
    private array $config;
    
    public function getApiKey(string $service): ?string {
        return $this->config['api_keys'][$service] ?? null;
    }
    
    public function getEndpoint(string $service): ?string {
        return $this->config['endpoints'][$service] ?? null;
    }
}
```

## 3. Clients API Réutilisables

### Client HTTP de Base
```php
namespace App\Http;

class ApiClient {
    protected HttpClient $client;
    protected string $baseUrl;
    protected array $headers;
    
    public function __construct(string $baseUrl, array $headers = []) {
        $this->client = new HttpClient();
        $this->baseUrl = $baseUrl;
        $this->headers = array_merge([
            'Accept' => 'application/json',
            'Content-Type' => 'application/json'
        ], $headers);
    }
    
    protected function request(string $method, string $endpoint, array $data = []): Response {
        return $this->client->request($method, $this->baseUrl . $endpoint, [
            'headers' => $this->headers,
            'json' => $data
        ]);
    }
}
```

### Client Spécifique
```php
namespace App\Services;

class PaymentGatewayClient extends ApiClient {
    public function createPayment(array $paymentData): array {
        $response = $this->request('POST', '/payments', $paymentData);
        return $response->json();
    }
    
    public function getPaymentStatus(string $paymentId): string {
        $response = $this->request('GET', "/payments/{$paymentId}");
        return $response->json()['status'];
    }
}
```

## 4. Gestion des Webhooks

### Gestionnaire de Webhooks
```php
namespace App\Webhooks;

class WebhookHandler {
    private EventDispatcher $events;
    private array $handlers = [];
    
    public function handle(Request $request): Response {
        $payload = $request->json();
        $signature = $request->header('X-Webhook-Signature');
        
        // Vérification de la signature
        $this->verifySignature($payload, $signature);
        
        // Traitement de l'événement
        $event = $this->createEvent($payload);
        $this->dispatchEvent($event);
        
        return new Response('Webhook processed', 200);
    }
    
    protected function dispatchEvent(WebhookEvent $event): void {
        if (isset($this->handlers[$event->type])) {
            $handler = $this->handlers[$event->type];
            $handler->process($event);
        }
        
        $this->events->dispatch($event);
    }
}
```

## 5. Gestion des Erreurs et Retry

### Politique de Retry
```php
namespace App\Services;

class RetryPolicy {
    private int $maxAttempts;
    private array $retryableErrors;
    private int $baseDelay;
    
    public function execute(callable $operation): mixed {
        $attempt = 0;
        
        while (true) {
            try {
                return $operation();
            } catch (Exception $e) {
                if (!$this->shouldRetry($e, ++$attempt)) {
                    throw $e;
                }
                
                $this->wait($attempt);
            }
        }
    }
    
    private function shouldRetry(Exception $e, int $attempt): bool {
        return $attempt < $this->maxAttempts &&
               in_array(get_class($e), $this->retryableErrors);
    }
}
```

## 6. Cache et Rate Limiting

### Gestionnaire de Rate Limit
```php
namespace App\Services;

class RateLimiter {
    private CacheInterface $cache;
    private array $limits;
    
    public function checkLimit(string $key, string $window): bool {
        $current = $this->cache->get($key) ?? 0;
        
        if ($current >= $this->limits[$window]) {
            return false;
        }
        
        $this->cache->increment($key);
        return true;
    }
    
    public function reset(string $key): void {
        $this->cache->delete($key);
    }
}
```

## 7. Authentification et Sécurité

### Gestionnaire d'OAuth
```php
namespace App\Auth;

class OAuthManager {
    private TokenStorage $storage;
    private array $providers;
    
    public function getAccessToken(string $provider): ?string {
        $token = $this->storage->get($provider);
        
        if ($this->isTokenExpired($token)) {
            $token = $this->refreshToken($provider);
        }
        
        return $token;
    }
    
    private function refreshToken(string $provider): string {
        $client = $this->providers[$provider];
        $response = $client->refreshAccessToken();
        
        $this->storage->save($provider, $response['access_token']);
        return $response['access_token'];
    }
}
```

## 8. Logging et Monitoring

### Service de Monitoring
```php
namespace App\Monitoring;

class ApiMonitor {
    private Logger $logger;
    private MetricsCollector $metrics;
    
    public function recordRequest(string $service, Request $request, Response $response): void {
        // Logging
        $this->logger->info('API Request', [
            'service' => $service,
            'method' => $request->method(),
            'path' => $request->path(),
            'status' => $response->status()
        ]);
        
        // Métriques
        $this->metrics->increment("api.{$service}.requests");
        $this->metrics->timing("api.{$service}.response_time", $response->duration());
    }
}
```

## 9. Tests d'Intégration

### Exemple de Test
```php
namespace Tests\Integration;

class PaymentGatewayTest extends TestCase {
    private PaymentGatewayClient $client;
    
    protected function setUp(): void {
        parent::setUp();
        $this->client = new PaymentGatewayClient(
            $this->app->make(ServiceConfig::class)
        );
    }
    
    public function testCreatePayment(): void {
        $payment = $this->client->createPayment([
            'amount' => 1000,
            'currency' => 'EUR',
            'description' => 'Test payment'
        ]);
        
        $this->assertArrayHasKey('id', $payment);
        $this->assertEquals('pending', $payment['status']);
    }
}
```

## 10. Documentation des APIs

### Exemple de Documentation
```php
/**
 * @api {post} /api/payments Créer un nouveau paiement
 * @apiName CreatePayment
 * @apiGroup Payments
 *
 * @apiParam {Number} amount Montant du paiement
 * @apiParam {String} currency Code de la devise
 * @apiParam {String} description Description du paiement
 *
 * @apiSuccess {String} id Identifiant unique du paiement
 * @apiSuccess {String} status Statut du paiement
 *
 * @apiError {Object} error Détails de l'erreur
 */
```

## Conclusion

Points clés pour l'intégration de services externes :
1. Configuration sécurisée
2. Gestion robuste des erreurs
3. Monitoring et logging
4. Tests d'intégration
5. Documentation claire

Recommandations :
- Utiliser des clients API réutilisables
- Implémenter des mécanismes de retry
- Gérer les rate limits
- Sécuriser les authentifications
- Maintenir une documentation à jour 