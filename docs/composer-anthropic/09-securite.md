# Sécurité et bonnes pratiques

## 1. Authentification sécurisée

### Service d'authentification
```php
namespace App\Services;

use App\Models\User;
use App\Exceptions\AuthException;

class SecurityService {
    private const HASH_ALGO = PASSWORD_ARGON2ID;
    private const HASH_OPTIONS = [
        'memory_cost' => 65536,
        'time_cost' => 4,
        'threads' => 3
    ];

    public function hashPassword(string $password): string {
        return password_hash($password, self::HASH_ALGO, self::HASH_OPTIONS);
    }

    public function verifyPassword(string $password, string $hash): bool {
        if (password_needs_rehash($hash, self::HASH_ALGO, self::HASH_OPTIONS)) {
            // Log pour mise à jour future du hash
            $this->logger->warning('Password hash needs rehash');
        }
        return password_verify($password, $hash);
    }

    public function generateSecureToken(int $length = 32): string {
        return bin2hex(random_bytes($length));
    }
}
```

### Double authentification (2FA)
```php
namespace App\Services;

use PragmaRX\Google2FA\Google2FA;

class TwoFactorAuthService {
    private Google2FA $google2fa;
    
    public function __construct() {
        $this->google2fa = new Google2FA();
    }
    
    public function generateSecret(): string {
        return $this->google2fa->generateSecretKey();
    }
    
    public function verifyCode(string $secret, string $code): bool {
        return $this->google2fa->verifyKey($secret, $code);
    }
    
    public function getQRCodeUrl(string $email, string $secret): string {
        return $this->google2fa->getQRCodeUrl(
            config('app.name'),
            $email,
            $secret
        );
    }
}
```

## 2. Protection contre les attaques

### Middleware CSRF
```php
namespace App\Middleware;

class CsrfMiddleware {
    public function handle(Request $request, Closure $next) {
        if ($this->isReading($request)) {
            return $next($request);
        }

        if (!$this->tokensMatch($request)) {
            throw new TokenMismatchException('CSRF token mismatch');
        }

        return $next($request);
    }

    private function tokensMatch(Request $request): bool {
        $token = $request->input('_token') ?: $request->header('X-CSRF-TOKEN');
        $sessionToken = $request->session()->token();

        return hash_equals($sessionToken, $token);
    }
}
```

### Protection XSS
```php
namespace App\Services;

class SecurityHeadersService {
    public function configure(Response $response): void {
        $response->headers->set('X-XSS-Protection', '1; mode=block');
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Content-Security-Policy', $this->getCSPHeader());
    }

    private function getCSPHeader(): string {
        return implode('; ', [
            "default-src 'self'",
            "script-src 'self' 'unsafe-inline' 'unsafe-eval'",
            "style-src 'self' 'unsafe-inline'",
            "img-src 'self' data: https:",
            "font-src 'self' data:",
            "connect-src 'self'",
            "frame-ancestors 'none'"
        ]);
    }
}
```

## 3. Gestion sécurisée des données sensibles

### Service de chiffrement
```php
namespace App\Services;

class EncryptionService {
    private string $key;
    private const CIPHER = 'aes-256-gcm';
    
    public function __construct(string $key) {
        $this->key = base64_decode($key);
    }
    
    public function encrypt(string $data): array {
        $iv = random_bytes(openssl_cipher_iv_length(self::CIPHER));
        $tag = '';
        
        $encrypted = openssl_encrypt(
            $data,
            self::CIPHER,
            $this->key,
            OPENSSL_RAW_DATA,
            $iv,
            $tag
        );
        
        return [
            'data' => base64_encode($encrypted),
            'iv' => base64_encode($iv),
            'tag' => base64_encode($tag)
        ];
    }
    
    public function decrypt(array $encryptedData): string {
        $decrypted = openssl_decrypt(
            base64_decode($encryptedData['data']),
            self::CIPHER,
            $this->key,
            OPENSSL_RAW_DATA,
            base64_decode($encryptedData['iv']),
            base64_decode($encryptedData['tag'])
        );
        
        if ($decrypted === false) {
            throw new DecryptionException('Failed to decrypt data');
        }
        
        return $decrypted;
    }
}
```

### Gestion des secrets
```php
namespace App\Services;

use Vault\Client as VaultClient;

class SecretsManager {
    private VaultClient $vault;
    
    public function __construct(VaultClient $vault) {
        $this->vault = $vault;
    }
    
    public function getSecret(string $path): string {
        try {
            $response = $this->vault->read($path);
            return $response->getData()['value'] ?? '';
        } catch (VaultException $e) {
            throw new SecretsException("Failed to retrieve secret: {$e->getMessage()}");
        }
    }
    
    public function storeSecret(string $path, string $value): void {
        try {
            $this->vault->write($path, ['value' => $value]);
        } catch (VaultException $e) {
            throw new SecretsException("Failed to store secret: {$e->getMessage()}");
        }
    }
}
```

## 4. Audit et logging sécurisé

### Service d'audit
```php
namespace App\Services;

class AuditService {
    private LoggerInterface $logger;
    
    public function logSecurityEvent(string $event, array $context = []): void {
        $this->logger->info("Security event: {$event}", array_merge([
            'ip' => request()->ip(),
            'user_id' => auth()->id(),
            'timestamp' => now()->toIso8601String()
        ], $context));
    }
    
    public function logFailedLogin(string $username): void {
        $this->logSecurityEvent('failed_login', [
            'username' => $username,
            'attempt_count' => $this->getFailedAttempts($username)
        ]);
    }
    
    public function logSuspiciousActivity(string $type, array $details): void {
        $this->logSecurityEvent('suspicious_activity', [
            'type' => $type,
            'details' => $details
        ]);
    }
}
```

### Monitoring de sécurité
```php
namespace App\Services;

class SecurityMonitoringService {
    private const THRESHOLD_LOGIN_ATTEMPTS = 5;
    private const THRESHOLD_TIME_WINDOW = 300; // 5 minutes
    
    public function checkBruteForce(string $username): bool {
        $attempts = $this->getRecentLoginAttempts($username);
        
        if (count($attempts) >= self::THRESHOLD_LOGIN_ATTEMPTS) {
            $this->blockIP(request()->ip());
            return true;
        }
        
        return false;
    }
    
    public function detectSuspiciousActivity(): void {
        $this->checkRateLimit();
        $this->checkUnusualPatterns();
        $this->checkKnownVulnerabilities();
    }
}
```

## 5. Validation et assainissement des données

### Service de validation
```php
namespace App\Services;

class InputValidationService {
    public function sanitize(array $input, array $rules): array {
        $sanitized = [];
        
        foreach ($rules as $field => $rule) {
            if (isset($input[$field])) {
                $sanitized[$field] = $this->applySanitizationRule(
                    $input[$field],
                    $rule
                );
            }
        }
        
        return $sanitized;
    }
    
    private function applySanitizationRule($value, string $rule): mixed {
        return match($rule) {
            'string' => htmlspecialchars($value, ENT_QUOTES),
            'email' => filter_var($value, FILTER_SANITIZE_EMAIL),
            'url' => filter_var($value, FILTER_SANITIZE_URL),
            'integer' => filter_var($value, FILTER_SANITIZE_NUMBER_INT),
            default => $value
        };
    }
}
```

## 6. Sécurité des sessions

### Configuration des sessions
```php
// config/session.php
return [
    'driver' => 'redis',
    'lifetime' => 120,
    'expire_on_close' => true,
    'encrypt' => true,
    'secure' => true,
    'http_only' => true,
    'same_site' => 'lax'
];

// SessionManager.php
namespace App\Services;

class SessionManager {
    public function configure(): void {
        ini_set('session.cookie_httponly', '1');
        ini_set('session.use_only_cookies', '1');
        ini_set('session.cookie_secure', '1');
        ini_set('session.cookie_samesite', 'Lax');
        ini_set('session.gc_maxlifetime', '7200');
    }
    
    public function regenerate(): void {
        session_regenerate_id(true);
    }
}
```

## 7. Tests de sécurité

### Tests de pénétration
```php
namespace Tests\Security;

class SecurityTest extends TestCase {
    public function testXSSProtection(): void {
        $payload = "<script>alert('xss')</script>";
        
        $response = $this->post('/api/data', [
            'content' => $payload
        ]);
        
        $this->assertStringNotContainsString(
            $payload,
            $response->getContent()
        );
    }
    
    public function testSQLInjectionProtection(): void {
        $payload = "'; DROP TABLE users; --";
        
        $response = $this->get('/users/search?q=' . $payload);
        
        $this->assertDatabaseHas('users', [/* ... */]);
    }
}
```

### Scan de vulnérabilités
```php
namespace App\Console\Commands;

class SecurityScanCommand extends Command {
    public function handle(): void {
        $this->scanDependencies();
        $this->scanCodebase();
        $this->scanConfigurations();
    }
    
    private function scanDependencies(): void {
        $this->info('Scanning dependencies...');
        $process = Process::fromShellCommandline('composer audit');
        $process->run();
        
        if (!$process->isSuccessful()) {
            $this->error('Security vulnerabilities found!');
            $this->error($process->getOutput());
        }
    }
}
```

## Conclusion

Points essentiels de sécurité :
1. Authentification robuste
2. Protection contre les attaques courantes
3. Chiffrement des données sensibles
4. Audit et monitoring
5. Validation des entrées
6. Sécurité des sessions
7. Tests réguliers

Recommandations :
- Maintenir les dépendances à jour
- Effectuer des audits réguliers
- Former l'équipe aux bonnes pratiques
- Documenter les incidents de sécurité
- Planifier la réponse aux incidents 