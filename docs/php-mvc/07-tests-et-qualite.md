# Tests et Qualité

## 1. Types de Tests

### Vue d'Ensemble
- Tests Unitaires
- Tests d'Intégration
- Tests Fonctionnels
- Tests de Performance
- Tests de Sécurité
- Tests d'Acceptance

## 2. Tests Unitaires

### Configuration PHPUnit
```php
// phpunit.xml
<?xml version="1.0" encoding="UTF-8"?>
<phpunit bootstrap="vendor/autoload.php"
         colors="true"
         verbose="true">
    <testsuites>
        <testsuite name="Unit">
            <directory>tests/Unit</directory>
        </testsuite>
        <testsuite name="Integration">
            <directory>tests/Integration</directory>
        </testsuite>
    </testsuites>
    <coverage>
        <include>
            <directory suffix=".php">app</directory>
        </include>
    </coverage>
</phpunit>
```

### Test de Model
```php
namespace Tests\Unit\Models;

class UserTest extends TestCase {
    private $user;
    
    protected function setUp(): void {
        parent::setUp();
        $this->user = new User([
            'name' => 'John Doe',
            'email' => 'john@example.com'
        ]);
    }
    
    public function testUserHasName() {
        $this->assertEquals('John Doe', $this->user->name);
    }
    
    public function testUserHasEmail() {
        $this->assertEquals('john@example.com', $this->user->email);
    }
    
    public function testPasswordIsHashed() {
        $this->user->setPassword('secret123');
        $this->assertNotEquals('secret123', $this->user->password);
        $this->assertTrue(password_verify('secret123', $this->user->password));
    }
}
```

## 3. Tests d'Intégration

### Test de Repository
```php
namespace Tests\Integration\Repositories;

class UserRepositoryTest extends TestCase {
    private $repository;
    private $db;
    
    protected function setUp(): void {
        parent::setUp();
        $this->db = $this->getTestDatabase();
        $this->repository = new UserRepository($this->db);
    }
    
    public function testCreateUser() {
        // Arrange
        $data = [
            'name' => 'John Doe',
            'email' => 'john@example.com'
        ];
        
        // Act
        $user = $this->repository->create($data);
        
        // Assert
        $this->assertNotNull($user->id);
        $this->assertEquals($data['name'], $user->name);
        $this->assertEquals($data['email'], $user->email);
    }
    
    public function testFindUserById() {
        // Arrange
        $created = $this->repository->create([
            'name' => 'John Doe',
            'email' => 'john@example.com'
        ]);
        
        // Act
        $found = $this->repository->find($created->id);
        
        // Assert
        $this->assertEquals($created->id, $found->id);
        $this->assertEquals($created->name, $found->name);
    }
}
```

## 4. Tests Fonctionnels

### Test de Controller
```php
namespace Tests\Functional\Controllers;

class UserControllerTest extends TestCase {
    public function testIndex() {
        // Arrange
        $this->createTestUsers();
        
        // Act
        $response = $this->get('/users');
        
        // Assert
        $response->assertStatus(200)
            ->assertViewHas('users')
            ->assertSee('John Doe');
    }
    
    public function testStore() {
        // Arrange
        $data = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'secret123'
        ];
        
        // Act
        $response = $this->post('/users', $data);
        
        // Assert
        $response->assertRedirect('/users')
            ->assertSessionHas('success');
            
        $this->assertDatabaseHas('users', [
            'name' => 'John Doe',
            'email' => 'john@example.com'
        ]);
    }
}
```

## 5. Tests de Service

### Test avec Mocks
```php
namespace Tests\Unit\Services;

class UserServiceTest extends TestCase {
    private $service;
    private $repository;
    private $mailer;
    
    protected function setUp(): void {
        parent::setUp();
        
        $this->repository = $this->createMock(UserRepository::class);
        $this->mailer = $this->createMock(MailerService::class);
        
        $this->service = new UserService(
            $this->repository,
            $this->mailer
        );
    }
    
    public function testRegisterUser() {
        // Arrange
        $data = [
            'name' => 'John Doe',
            'email' => 'john@example.com'
        ];
        
        $this->repository->expects($this->once())
            ->method('create')
            ->with($data)
            ->willReturn(new User($data));
            
        $this->mailer->expects($this->once())
            ->method('sendWelcomeEmail')
            ->with($this->isInstanceOf(User::class));
            
        // Act
        $user = $this->service->register($data);
        
        // Assert
        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals($data['name'], $user->name);
    }
}
```

## 6. Tests de Performance

### Test de Charge
```php
namespace Tests\Performance;

class PerformanceTest extends TestCase {
    public function testDatabasePerformance() {
        $startTime = microtime(true);
        
        // Exécuter l'opération
        $users = User::with('posts')->paginate(20);
        
        $endTime = microtime(true);
        $executionTime = ($endTime - $startTime) * 1000; // en ms
        
        $this->assertLessThan(
            100, // 100ms maximum
            $executionTime,
            "L'opération a pris trop de temps: {$executionTime}ms"
        );
    }
    
    public function testCachePerformance() {
        $key = 'test_key';
        $value = 'test_value';
        
        // Test d'écriture
        $writeStart = microtime(true);
        Cache::put($key, $value, 60);
        $writeTime = (microtime(true) - $writeStart) * 1000;
        
        // Test de lecture
        $readStart = microtime(true);
        $cached = Cache::get($key);
        $readTime = (microtime(true) - $readStart) * 1000;
        
        $this->assertLessThan(5, $writeTime);
        $this->assertLessThan(2, $readTime);
    }
}
```

## 7. Tests de Sécurité

### Test de Vulnérabilités
```php
namespace Tests\Security;

class SecurityTest extends TestCase {
    public function testXssProtection() {
        // Arrange
        $maliciousInput = '<script>alert("XSS")</script>';
        
        // Act
        $response = $this->post('/users', [
            'name' => $maliciousInput
        ]);
        
        // Assert
        $response->assertSee(htmlspecialchars($maliciousInput));
        $response->assertDontSee($maliciousInput);
    }
    
    public function testSqlInjectionProtection() {
        // Arrange
        $maliciousId = "1; DROP TABLE users;";
        
        // Act
        $response = $this->get("/users/{$maliciousId}");
        
        // Assert
        $response->assertStatus(404);
        $this->assertDatabaseHas('users', []); // Table exists
    }
}
```

## 8. Couverture de Code

### Configuration PHPUnit
```php
// phpunit.xml
<coverage processUncoveredFiles="true">
    <include>
        <directory suffix=".php">app</directory>
    </include>
    <report>
        <html outputDirectory="tests/coverage"/>
        <clover outputFile="tests/coverage.xml"/>
    </report>
</coverage>
```

### Analyse de Couverture
```php
namespace Tests\Coverage;

class CoverageTest extends TestCase {
    /**
     * @covers \App\Services\UserService::register
     */
    public function testUserRegistration() {
        // Test complet du processus d'inscription
        $service = new UserService();
        
        $user = $service->register([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'secret123'
        ]);
        
        $this->assertInstanceOf(User::class, $user);
        $this->assertNotNull($user->id);
    }
}
```

## 9. Tests d'Acceptance

### Configuration Behat
```yaml
# behat.yml
default:
    suites:
        default:
            contexts:
                - FeatureContext
                - WebContext
    extensions:
        Behat\MinkExtension:
            base_url: http://localhost:8000
            sessions:
                default:
                    symfony2: ~
```

### Test d'Acceptance
```gherkin
# features/registration.feature
Feature: User Registration
    In order to access the system
    As a visitor
    I need to be able to register an account

    Scenario: Successful registration
        Given I am on "/register"
        When I fill in "name" with "John Doe"
        And I fill in "email" with "john@example.com"
        And I fill in "password" with "secret123"
        And I press "Register"
        Then I should see "Registration successful"
```

## 10. Qualité de Code

### Configuration PHPStan
```yaml
# phpstan.neon
parameters:
    level: 8
    paths:
        - app
    excludes_analyse:
        - tests
    checkMissingIterableValueType: false
```

### Configuration PHP_CodeSniffer
```xml
<!-- phpcs.xml -->
<?xml version="1.0"?>
<ruleset name="Custom Standard">
    <rule ref="PSR2"/>
    <file>app</file>
    <exclude-pattern>vendor</exclude-pattern>
</ruleset>
```

## Conclusion

Points clés pour les Tests :
1. Couverture complète
2. Tests automatisés
3. Intégration continue
4. Qualité du code
5. Documentation des tests

Recommandations :
- Écrire les tests avant le code (TDD)
- Maintenir une couverture élevée
- Automatiser les tests
- Utiliser des outils d'analyse
- Documenter les scénarios de test 