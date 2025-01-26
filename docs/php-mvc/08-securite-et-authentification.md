# Sécurité et Authentification

## 1. Authentification

### Système d'Authentification
```php
namespace App\Auth;

class AuthManager {
    private $userProvider;
    private $session;
    private $hasher;
    
    public function attempt(array $credentials): bool {
        $user = $this->userProvider->findByEmail($credentials['email']);
        
        if (!$user || !$this->hasher->verify($credentials['password'], $user->password)) {
            return false;
        }
        
        $this->login($user);
        return true;
    }
    
    public function login(User $user): void {
        $this->session->put('auth_user_id', $user->id);
        $this->session->regenerate();
    }
    
    public function logout(): void {
        $this->session->remove('auth_user_id');
        $this->session->invalidate();
    }
    
    public function user(): ?User {
        $id = $this->session->get('auth_user_id');
        return $id ? $this->userProvider->find($id) : null;
    }
}
```

## 2. Gestion des Sessions

### Session Manager
```php
namespace App\Session;

class SessionManager {
    public function start(): void {
        if (session_status() === PHP_SESSION_NONE) {
            session_start([
                'cookie_httponly' => true,
                'cookie_secure' => true,
                'cookie_samesite' => 'Lax',
                'use_strict_mode' => true
            ]);
        }
    }
    
    public function regenerate(): void {
        session_regenerate_id(true);
    }
    
    public function destroy(): void {
        session_destroy();
        setcookie(session_name(), '', time() - 3600, '/');
    }
    
    public function put(string $key, $value): void {
        $_SESSION[$key] = $value;
    }
    
    public function get(string $key, $default = null) {
        return $_SESSION[$key] ?? $default;
    }
}
```

## 3. Protection CSRF

### CSRF Middleware
```php
namespace App\Middleware;

class CsrfMiddleware {
    private $session;
    
    public function handle(Request $request, Closure $next) {
        if ($this->isReading($request)) {
            return $next($request);
        }
        
        if (!$this->tokensMatch($request)) {
            throw new TokenMismatchException();
        }
        
        return $next($request);
    }
    
    private function tokensMatch(Request $request): bool {
        $token = $request->input('_token') ?: $request->header('X-CSRF-TOKEN');
        $sessionToken = $this->session->get('_token');
        
        return is_string($token) && 
               is_string($sessionToken) && 
               hash_equals($sessionToken, $token);
    }
}

// Utilisation dans les vues
class CsrfHelper {
    public static function token(): string {
        return '<input type="hidden" name="_token" value="' . 
               Session::get('_token') . '">';
    }
}
```

## 4. Hachage et Cryptage

### Service de Hachage
```php
namespace App\Security;

class PasswordHasher {
    private $algo = PASSWORD_ARGON2ID;
    private $options = [
        'memory_cost' => 65536,
        'time_cost' => 4,
        'threads' => 3
    ];
    
    public function hash(string $password): string {
        return password_hash($password, $this->algo, $this->options);
    }
    
    public function verify(string $password, string $hash): bool {
        return password_verify($password, $hash);
    }
    
    public function needsRehash(string $hash): bool {
        return password_needs_rehash($hash, $this->algo, $this->options);
    }
}
```

### Service de Cryptage
```php
namespace App\Security;

class Encrypter {
    private $key;
    private $cipher = 'AES-256-GCM';
    
    public function encrypt($value): string {
        $iv = random_bytes(openssl_cipher_iv_length($this->cipher));
        $tag = '';
        
        $encrypted = openssl_encrypt(
            serialize($value),
            $this->cipher,
            $this->key,
            OPENSSL_RAW_DATA,
            $iv,
            $tag
        );
        
        return base64_encode($iv . $tag . $encrypted);
    }
    
    public function decrypt(string $payload) {
        $decoded = base64_decode($payload);
        $ivLength = openssl_cipher_iv_length($this->cipher);
        
        $iv = substr($decoded, 0, $ivLength);
        $tag = substr($decoded, $ivLength, 16);
        $encrypted = substr($decoded, $ivLength + 16);
        
        $decrypted = openssl_decrypt(
            $encrypted,
            $this->cipher,
            $this->key,
            OPENSSL_RAW_DATA,
            $iv,
            $tag
        );
        
        return unserialize($decrypted);
    }
}
```

## 5. Autorisation

### Système de Rôles et Permissions
```php
namespace App\Auth;

class Gate {
    private $abilities = [];
    private $userResolver;
    
    public function define(string $ability, callable $callback): void {
        $this->abilities[$ability] = $callback;
    }
    
    public function allows(string $ability, $arguments = []): bool {
        $user = call_user_func($this->userResolver);
        
        if (isset($this->abilities[$ability])) {
            return call_user_func($this->abilities[$ability], $user, ...$arguments);
        }
        
        return false;
    }
    
    public function denies(string $ability, $arguments = []): bool {
        return !$this->allows($ability, $arguments);
    }
}

// Utilisation
class PostPolicy {
    public function update(User $user, Post $post): bool {
        return $user->id === $post->user_id || $user->isAdmin();
    }
}

// Dans le controller
if ($gate->denies('update', $post)) {
    throw new UnauthorizedException();
}
```

## 6. Protection XSS

### Middleware XSS
```php
namespace App\Security;

class XssProtection {
    public function clean($data) {
        if (is_string($data)) {
            return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
        }
        
        if (is_array($data)) {
            return array_map([$this, 'clean'], $data);
        }
        
        return $data;
    }
}

class XssMiddleware {
    private $protection;
    
    public function handle(Request $request, Closure $next) {
        $request->merge($this->protection->clean($request->all()));
        return $next($request);
    }
}
```

## 7. Protection contre les Injections SQL

### Query Builder Sécurisé
```php
namespace App\Database;

class QueryBuilder {
    private $pdo;
    
    public function select(string $table, array $conditions = []): array {
        $query = "SELECT * FROM {$table}";
        $params = [];
        
        if (!empty($conditions)) {
            $query .= " WHERE ";
            $whereClauses = [];
            
            foreach ($conditions as $column => $value) {
                $whereClauses[] = "{$column} = ?";
                $params[] = $value;
            }
            
            $query .= implode(' AND ', $whereClauses);
        }
        
        $stmt = $this->pdo->prepare($query);
        $stmt->execute($params);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
```

## 8. Journalisation de Sécurité

### Logger de Sécurité
```php
namespace App\Security;

class SecurityLogger {
    private $logger;
    
    public function logLogin(User $user, bool $success): void {
        $this->logger->info('Login attempt', [
            'user_id' => $user->id,
            'email' => $user->email,
            'ip' => $_SERVER['REMOTE_ADDR'],
            'success' => $success,
            'user_agent' => $_SERVER['HTTP_USER_AGENT']
        ]);
    }
    
    public function logFailedAttempt(string $email): void {
        $this->logger->warning('Failed login attempt', [
            'email' => $email,
            'ip' => $_SERVER['REMOTE_ADDR']
        ]);
    }
    
    public function logSuspiciousActivity(string $type, array $details): void {
        $this->logger->alert('Suspicious activity detected', [
            'type' => $type,
            'details' => $details,
            'ip' => $_SERVER['REMOTE_ADDR']
        ]);
    }
}
```

## 9. Rate Limiting

### Limiteur d'Accès
```php
namespace App\Security;

class RateLimiter {
    private $redis;
    private $maxAttempts = 5;
    private $decayMinutes = 1;
    
    public function tooManyAttempts(string $key): bool {
        return $this->attempts($key) >= $this->maxAttempts;
    }
    
    public function hit(string $key): int {
        $key = $this->getKey($key);
        $this->redis->incr($key);
        $this->redis->expire($key, $this->decayMinutes * 60);
        
        return $this->attempts($key);
    }
    
    public function attempts(string $key): int {
        return (int) $this->redis->get($this->getKey($key)) ?? 0;
    }
    
    public function resetAttempts(string $key): void {
        $this->redis->del($this->getKey($key));
    }
}
```

## 10. Headers de Sécurité

### Middleware Headers
```php
namespace App\Security;

class SecurityHeadersMiddleware {
    public function handle(Request $request, Closure $next) {
        $response = $next($request);
        
        return $response->withHeaders([
            'X-Frame-Options' => 'SAMEORIGIN',
            'X-XSS-Protection' => '1; mode=block',
            'X-Content-Type-Options' => 'nosniff',
            'Referrer-Policy' => 'strict-origin-when-cross-origin',
            'Content-Security-Policy' => "default-src 'self'",
            'Strict-Transport-Security' => 'max-age=31536000; includeSubDomains'
        ]);
    }
}
```

## Conclusion

Points clés pour la Sécurité :
1. Authentification robuste
2. Protection contre les attaques courantes
3. Cryptage des données sensibles
4. Journalisation des événements
5. Contrôle d'accès strict

Recommandations :
- Maintenir les dépendances à jour
- Utiliser HTTPS partout
- Valider toutes les entrées
- Limiter les tentatives d'accès
- Journaliser les événements importants 