# Services et Repositories

## 1. Introduction aux Services et Repositories

Les Services et Repositories sont des couches additionnelles qui améliorent l'architecture MVC en :
- Séparant la logique métier des controllers
- Abstrayant l'accès aux données
- Facilitant la maintenance
- Améliorant la testabilité
- Respectant le principe de responsabilité unique

## 2. Pattern Repository

### Interface de Base
```php
namespace App\Repositories;

interface RepositoryInterface {
    public function find($id);
    public function findAll();
    public function create(array $data);
    public function update($id, array $data);
    public function delete($id);
}
```

### Implémentation Générique
```php
namespace App\Repositories;

abstract class BaseRepository implements RepositoryInterface {
    protected $model;
    protected $db;
    
    public function __construct(PDO $db) {
        $this->db = $db;
    }
    
    public function find($id) {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_CLASS, $this->model);
    }
    
    public function findAll() {
        $stmt = $this->db->query("SELECT * FROM {$this->table}");
        return $stmt->fetchAll(PDO::FETCH_CLASS, $this->model);
    }
    
    public function create(array $data) {
        $columns = implode(', ', array_keys($data));
        $values = implode(', ', array_fill(0, count($data), '?'));
        
        $stmt = $this->db->prepare(
            "INSERT INTO {$this->table} ({$columns}) VALUES ({$values})"
        );
        
        $stmt->execute(array_values($data));
        return $this->find($this->db->lastInsertId());
    }
}
```

### Repository Spécifique
```php
namespace App\Repositories;

class UserRepository extends BaseRepository {
    protected $table = 'users';
    protected $model = User::class;
    
    public function findByEmail($email) {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        return $stmt->fetch(PDO::FETCH_CLASS, $this->model);
    }
    
    public function findWithPosts($id) {
        $stmt = $this->db->prepare("
            SELECT users.*, posts.* 
            FROM users 
            LEFT JOIN posts ON posts.user_id = users.id 
            WHERE users.id = ?
        ");
        $stmt->execute([$id]);
        return $this->hydrateWithPosts($stmt->fetchAll());
    }
}
```

## 3. Pattern Service

### Service de Base
```php
namespace App\Services;

abstract class BaseService {
    protected $repository;
    protected $validator;
    
    public function __construct(
        RepositoryInterface $repository,
        ValidatorInterface $validator
    ) {
        $this->repository = $repository;
        $this->validator = $validator;
    }
    
    protected function validate($data, $rules) {
        if (!$this->validator->validate($data, $rules)) {
            throw new ValidationException($this->validator->getErrors());
        }
    }
}
```

### Service Spécifique
```php
namespace App\Services;

class UserService extends BaseService {
    private $passwordHasher;
    private $emailService;
    
    public function register(array $data) {
        // Validation
        $this->validate($data, [
            'email' => ['required', 'email', 'unique:users'],
            'password' => ['required', 'min:8']
        ]);
        
        // Traitement des données
        $data['password'] = $this->passwordHasher->hash($data['password']);
        
        // Création de l'utilisateur
        $user = $this->repository->create($data);
        
        // Envoi email de bienvenue
        $this->emailService->sendWelcomeEmail($user);
        
        return $user;
    }
    
    public function authenticate($email, $password) {
        $user = $this->repository->findByEmail($email);
        
        if (!$user || !$this->passwordHasher->verify($password, $user->password)) {
            throw new AuthenticationException('Invalid credentials');
        }
        
        return $user;
    }
}
```

## 4. Injection de Dépendances

### Container de Services
```php
namespace App\Container;

class ServiceContainer {
    private $services = [];
    private $factories = [];
    
    public function register($name, $factory) {
        $this->factories[$name] = $factory;
    }
    
    public function get($name) {
        if (!isset($this->services[$name])) {
            $this->services[$name] = $this->factories[$name]($this);
        }
        
        return $this->services[$name];
    }
}

// Configuration
$container = new ServiceContainer();

$container->register(UserRepository::class, function($container) {
    return new UserRepository($container->get(PDO::class));
});

$container->register(UserService::class, function($container) {
    return new UserService(
        $container->get(UserRepository::class),
        $container->get(Validator::class)
    );
});
```

## 5. Patterns de Transaction

### Unit of Work
```php
namespace App\Database;

class UnitOfWork {
    private $newEntities = [];
    private $dirtyEntities = [];
    private $deletedEntities = [];
    
    public function registerNew($entity) {
        $this->newEntities[] = $entity;
    }
    
    public function registerDirty($entity) {
        $this->dirtyEntities[] = $entity;
    }
    
    public function registerDeleted($entity) {
        $this->deletedEntities[] = $entity;
    }
    
    public function commit() {
        using ($transaction = new Transaction()) {
            foreach ($this->newEntities as $entity) {
                $this->persistNew($entity);
            }
            
            foreach ($this->dirtyEntities as $entity) {
                $this->persistUpdate($entity);
            }
            
            foreach ($this->deletedEntities as $entity) {
                $this->persistDelete($entity);
            }
            
            $transaction->commit();
        }
    }
}
```

## 6. Caching

### Cache Repository Decorator
```php
namespace App\Repositories;

class CachedRepository implements RepositoryInterface {
    private $repository;
    private $cache;
    
    public function __construct(
        RepositoryInterface $repository,
        CacheInterface $cache
    ) {
        $this->repository = $repository;
        $this->cache = $cache;
    }
    
    public function find($id) {
        $key = "entity.{$id}";
        
        return $this->cache->remember($key, 3600, function() use ($id) {
            return $this->repository->find($id);
        });
    }
    
    public function create(array $data) {
        $entity = $this->repository->create($data);
        $this->cache->put("entity.{$entity->id}", $entity, 3600);
        return $entity;
    }
}
```

## 7. Événements et Observers

### Event Dispatcher
```php
namespace App\Events;

class EventDispatcher {
    private $listeners = [];
    
    public function addListener($event, $listener) {
        $this->listeners[$event][] = $listener;
    }
    
    public function dispatch($event, $data = null) {
        if (isset($this->listeners[$event])) {
            foreach ($this->listeners[$event] as $listener) {
                $listener($data);
            }
        }
    }
}

// Utilisation dans le service
class UserService extends BaseService {
    private $events;
    
    public function register(array $data) {
        $user = parent::register($data);
        $this->events->dispatch('user.registered', $user);
        return $user;
    }
}
```

## 8. Tests

### Test de Repository
```php
namespace Tests\Repositories;

class UserRepositoryTest extends TestCase {
    private $repository;
    private $db;
    
    protected function setUp(): void {
        $this->db = new PDO('sqlite::memory:');
        $this->repository = new UserRepository($this->db);
    }
    
    public function testFindReturnsUser() {
        // Arrange
        $this->createUser(['id' => 1, 'name' => 'John']);
        
        // Act
        $user = $this->repository->find(1);
        
        // Assert
        $this->assertEquals('John', $user->name);
    }
}
```

### Test de Service
```php
namespace Tests\Services;

class UserServiceTest extends TestCase {
    public function testRegisterCreatesUser() {
        // Arrange
        $repository = $this->createMock(UserRepository::class);
        $validator = $this->createMock(Validator::class);
        
        $repository->expects($this->once())
            ->method('create')
            ->willReturn(new User(['id' => 1]));
            
        $service = new UserService($repository, $validator);
        
        // Act
        $user = $service->register([
            'email' => 'test@example.com',
            'password' => 'password123'
        ]);
        
        // Assert
        $this->assertInstanceOf(User::class, $user);
    }
}
```

## Conclusion

Points clés pour les Services et Repositories :
1. Séparation des responsabilités
2. Abstraction de la persistance
3. Logique métier centralisée
4. Facilité de test
5. Maintenance simplifiée

Recommandations :
- Utiliser des interfaces
- Implémenter le caching approprié
- Gérer les transactions
- Écrire des tests unitaires
- Documenter les méthodes 