# APIs et Intégration

## 1. Introduction aux APIs

Une API (Application Programming Interface) permet :
- L'interaction entre différents systèmes
- L'exposition des fonctionnalités
- La communication standardisée
- La réutilisation du code
- L'intégration avec des services externes

## 2. REST API

### Structure de Base
```php
namespace App\Controllers\Api;

class ApiController extends BaseController {
    protected function respond($data, $status = 200) {
        return response()->json($data, $status);
    }
    
    protected function error($message, $status = 400) {
        return $this->respond(['error' => $message], $status);
    }
}

class UserApiController extends ApiController {
    private $userService;
    
    public function index() {
        $users = $this->userService->getAllUsers();
        return $this->respond(['data' => $users]);
    }
    
    public function show($id) {
        $user = $this->userService->getUser($id);
        
        if (!$user) {
            return $this->error('User not found', 404);
        }
        
        return $this->respond(['data' => $user]);
    }
}
```

### Routes API
```php
// routes/api.php
$router->group(['prefix' => 'api/v1'], function($router) {
    $router->get('users', [UserApiController::class, 'index']);
    $router->get('users/{id}', [UserApiController::class, 'show']);
    $router->post('users', [UserApiController::class, 'store']);
    $router->put('users/{id}', [UserApiController::class, 'update']);
    $router->delete('users/{id}', [UserApiController::class, 'destroy']);
});
```

## 3. Authentification API

### JWT Authentication
```php
namespace App\Auth;

class JwtAuth {
    private $secret;
    
    public function createToken($user) {
        $payload = [
            'sub' => $user->id,
            'name' => $user->name,
            'iat' => time(),
            'exp' => time() + (60 * 60) // 1 heure
        ];
        
        return JWT::encode($payload, $this->secret);
    }
    
    public function validateToken($token) {
        try {
            $payload = JWT::decode($token, $this->secret, ['HS256']);
            return $payload;
        } catch (Exception $e) {
            return false;
        }
    }
}

// Middleware
class JwtMiddleware {
    private $auth;
    
    public function handle($request, $next) {
        $token = $request->header('Authorization');
        
        if (!$token || !$this->auth->validateToken($token)) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
        
        return $next($request);
    }
}
```

## 4. Versionnement API

### Gestion des Versions
```php
namespace App\Versioning;

class ApiVersioning {
    private $version;
    private $handlers = [];
    
    public function addHandler($version, $handler) {
        $this->handlers[$version] = $handler;
    }
    
    public function handle($request, $version) {
        if (!isset($this->handlers[$version])) {
            throw new VersionNotFoundException();
        }
        
        return $this->handlers[$version]->handle($request);
    }
}

// Implémentation
class UserControllerV1 {
    public function show($id) {
        return [
            'id' => $id,
            'name' => $user->name
        ];
    }
}

class UserControllerV2 {
    public function show($id) {
        return [
            'id' => $id,
            'name' => $user->name,
            'profile' => $user->profile,
            'links' => [
                'self' => "/api/v2/users/{$id}",
                'posts' => "/api/v2/users/{$id}/posts"
            ]
        ];
    }
}
```

## 5. Documentation API

### Swagger/OpenAPI
```php
/**
 * @OA\Info(
 *     title="Mon API",
 *     version="1.0.0"
 * )
 */

/**
 * @OA\Get(
 *     path="/api/users",
 *     summary="Liste des utilisateurs",
 *     @OA\Response(
 *         response=200,
 *         description="Liste des utilisateurs",
 *         @OA\JsonContent(
 *             type="array",
 *             @OA\Items(ref="#/components/schemas/User")
 *         )
 *     )
 * )
 */
class UserApiController {
    public function index() {
        // ...
    }
}

/**
 * @OA\Schema(
 *     schema="User",
 *     type="object",
 *     @OA\Property(property="id", type="integer"),
 *     @OA\Property(property="name", type="string"),
 *     @OA\Property(property="email", type="string")
 * )
 */
```

## 6. Rate Limiting

### Limiteur de Requêtes
```php
namespace App\Http\Middleware;

class RateLimiter {
    private $redis;
    private $maxRequests = 60;
    private $decayMinutes = 1;
    
    public function handle($request, $next) {
        $key = $this->resolveRequestSignature($request);
        
        if ($this->tooManyAttempts($key)) {
            throw new TooManyRequestsException();
        }
        
        $this->incrementAttempts($key);
        
        return $next($request);
    }
    
    protected function tooManyAttempts($key) {
        $attempts = $this->redis->get($key) ?? 0;
        return $attempts >= $this->maxRequests;
    }
    
    protected function incrementAttempts($key) {
        $this->redis->incr($key);
        $this->redis->expire($key, $this->decayMinutes * 60);
    }
}
```

## 7. CORS

### Middleware CORS
```php
namespace App\Http\Middleware;

class CorsMiddleware {
    private $allowedOrigins = ['https://example.com'];
    private $allowedMethods = ['GET', 'POST', 'PUT', 'DELETE'];
    private $allowedHeaders = ['Content-Type', 'Authorization'];
    
    public function handle($request, $next) {
        $response = $next($request);
        
        if (!$this->isAllowedOrigin($request->header('Origin'))) {
            return $response;
        }
        
        return $response->withHeaders([
            'Access-Control-Allow-Origin' => $request->header('Origin'),
            'Access-Control-Allow-Methods' => implode(', ', $this->allowedMethods),
            'Access-Control-Allow-Headers' => implode(', ', $this->allowedHeaders),
            'Access-Control-Allow-Credentials' => 'true'
        ]);
    }
}
```

## 8. Tests d'API

### Tests d'Intégration
```php
namespace Tests\Api;

class UserApiTest extends TestCase {
    public function testGetUsers() {
        // Arrange
        $this->createTestUsers();
        
        // Act
        $response = $this->get('/api/v1/users', [
            'Authorization' => 'Bearer ' . $this->createTestToken()
        ]);
        
        // Assert
        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'name', 'email']
                ]
            ]);
    }
    
    public function testCreateUser() {
        $response = $this->post('/api/v1/users', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'secret123'
        ]);
        
        $response->assertStatus(201)
            ->assertJson([
                'data' => [
                    'name' => 'John Doe',
                    'email' => 'john@example.com'
                ]
            ]);
    }
}
```

## 9. Intégration de Services Externes

### Client HTTP
```php
namespace App\Services;

class ExternalApiClient {
    private $client;
    private $baseUrl;
    private $apiKey;
    
    public function __construct(HttpClient $client) {
        $this->client = $client;
    }
    
    public function get($endpoint, $params = []) {
        try {
            $response = $this->client->request('GET', 
                $this->baseUrl . $endpoint,
                [
                    'headers' => $this->getHeaders(),
                    'query' => $params
                ]
            );
            
            return json_decode($response->getBody(), true);
        } catch (RequestException $e) {
            throw new ApiException('External API error: ' . $e->getMessage());
        }
    }
    
    private function getHeaders() {
        return [
            'Authorization' => 'Bearer ' . $this->apiKey,
            'Accept' => 'application/json'
        ];
    }
}
```

## 10. Webhooks

### Gestionnaire de Webhooks
```php
namespace App\Webhooks;

class WebhookHandler {
    private $secret;
    private $handlers;
    
    public function handle(Request $request) {
        $this->validateSignature($request);
        
        $payload = $request->json();
        $event = $payload['event'];
        
        if (isset($this->handlers[$event])) {
            return $this->handlers[$event]->handle($payload);
        }
        
        throw new UnhandledWebhookException();
    }
    
    private function validateSignature($request) {
        $signature = hash_hmac('sha256', 
            $request->getContent(), 
            $this->secret
        );
        
        if ($signature !== $request->header('X-Webhook-Signature')) {
            throw new InvalidSignatureException();
        }
    }
}
```

## Conclusion

Points clés pour les APIs :
1. Design RESTful cohérent
2. Sécurité robuste
3. Documentation claire
4. Tests complets
5. Gestion des erreurs

Recommandations :
- Versionner les APIs
- Implémenter la limitation de débit
- Sécuriser les endpoints
- Documenter les endpoints
- Tester les intégrations 