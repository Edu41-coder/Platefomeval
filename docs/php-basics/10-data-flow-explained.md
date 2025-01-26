# Le Flux des Données Expliqué

## Introduction

Imaginez une bibliothèque moderne :
- Les **Modèles** sont les livres eux-mêmes
- Les **Repositories** sont les bibliothécaires qui gèrent les livres
- Les **Interfaces** sont les règles que les bibliothécaires doivent suivre
- Les **Services** sont les experts qui utilisent les informations des livres
- Les **Controllers** sont les guichets où vous faites vos demandes
- Les **Middlewares** sont les agents de sécurité qui vérifient votre carte
- Les **Vues** sont la façon dont les livres vous sont présentés

## 1. Flux des Données dans une Application Web Classique

### 1.1 Structure de Base
```php
// 1. Le Modèle (La structure des données)
class User {
    private $id;
    private $name;
    private $email;
    
    // Getters et Setters
}

// 2. L'Interface (Le contrat)
interface UserRepositoryInterface {
    public function find($id);
    public function create(array $data);
    public function update($id, array $data);
    public function delete($id);
}

// 3. Le Repository (L'accès aux données)
class UserRepository implements UserRepositoryInterface {
    private $db;
    
    public function find($id) {
        $result = $this->db->query("SELECT * FROM users WHERE id = ?", [$id]);
        return $this->createUser($result);
    }
}

// 4. Le Service (La logique métier)
class UserService {
    private $repository;
    
    public function register($data) {
        // Validation
        $this->validate($data);
        
        // Hashage du mot de passe
        $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
        
        // Création de l'utilisateur
        return $this->repository->create($data);
    }
}

// 5. Le Controller (La gestion des requêtes)
class UserController {
    private $userService;
    
    public function register(Request $request) {
        try {
            $user = $this->userService->register($request->all());
            return view('users.success', ['user' => $user]);
        } catch (Exception $e) {
            return view('users.register', ['error' => $e->getMessage()]);
        }
    }
}
```

### 1.2 Exemple de Flux Complet (Web)
```php
// 1. Route définie
$router->post('/register', 'UserController@register');

// 2. Middleware vérifie
class RegistrationMiddleware {
    public function handle($request, $next) {
        // Vérifier si l'inscription est ouverte
        if (!$this->isRegistrationOpen()) {
            return redirect('/')->with('error', 'Inscription fermée');
        }
        return $next($request);
    }
}

// 3. Controller reçoit la requête
class UserController {
    public function register(Request $request) {
        // Validation des données
        $this->validate($request, [
            'email' => 'required|email|unique:users',
            'password' => 'required|min:8'
        ]);
        
        // Appel du service
        $user = $this->userService->register($request->all());
        
        // Redirection avec message
        return redirect('/login')
            ->with('success', 'Inscription réussie !');
    }
}

// 4. Service traite la logique
class UserService {
    public function register($data) {
        // Logique métier
        $this->checkEmailDomain($data['email']);
        $this->sendWelcomeEmail($data['email']);
        
        // Création via repository
        return $this->repository->create($data);
    }
}

// 5. Vue affiche le résultat
// resources/views/users/success.php
?>
<div class="alert alert-success">
    Bienvenue <?= $user->name ?> !
    <a href="/login">Connectez-vous</a>
</div>
```

## 2. Flux des Données dans une API

### 2.1 Structure de Base pour API
```php
// 1. DTO (Data Transfer Object)
class UserDTO {
    public $id;
    public $name;
    public $email;
    
    public static function fromUser(User $user) {
        $dto = new self();
        $dto->id = $user->getId();
        $dto->name = $user->getName();
        $dto->email = $user->getEmail();
        return $dto;
    }
}

// 2. Controller API
class UserApiController {
    public function register(Request $request) {
        try {
            $user = $this->userService->register($request->all());
            return new JsonResponse([
                'success' => true,
                'data' => UserDTO::fromUser($user)
            ], 201);
        } catch (Exception $e) {
            return new JsonResponse([
                'success' => false,
                'error' => $e->getMessage()
            ], 400);
        }
    }
}

// 3. Middleware API
class ApiAuthMiddleware {
    public function handle($request, $next) {
        $token = $request->header('Authorization');
        
        if (!$this->validateToken($token)) {
            return new JsonResponse([
                'error' => 'Invalid token'
            ], 401);
        }
        
        return $next($request);
    }
}
```

### 2.2 Exemple de Flux Complet (API)
```php
// 1. Route API
$router->post('/api/users', 'UserApiController@register');

// 2. Middleware API vérifie
class ApiMiddleware {
    public function handle($request, $next) {
        // Vérifier le Content-Type
        if ($request->header('Content-Type') !== 'application/json') {
            return new JsonResponse([
                'error' => 'Content-Type must be application/json'
            ], 400);
        }
        
        // Vérifier l'API key
        if (!$this->validateApiKey($request->header('X-API-Key'))) {
            return new JsonResponse([
                'error' => 'Invalid API key'
            ], 401);
        }
        
        return $next($request);
    }
}

// 3. Controller API reçoit la requête
class UserApiController {
    public function register(Request $request) {
        try {
            // Validation
            $validator = new UserValidator($request->all());
            if (!$validator->passes()) {
                return new JsonResponse([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }
            
            // Création de l'utilisateur
            $user = $this->userService->register($request->all());
            
            // Réponse
            return new JsonResponse([
                'success' => true,
                'data' => UserDTO::fromUser($user)
            ], 201);
            
        } catch (Exception $e) {
            return new JsonResponse([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
```

## 3. Différences Clés entre Web et API

### 3.1 Gestion des Réponses
```php
// Application Web : Retourne des vues
class WebController {
    public function show($id) {
        $user = $this->userService->find($id);
        return view('users.show', ['user' => $user]);
    }
}

// API : Retourne du JSON
class ApiController {
    public function show($id) {
        $user = $this->userService->find($id);
        return new JsonResponse([
            'data' => UserDTO::fromUser($user)
        ]);
    }
}
```

### 3.2 Gestion des Erreurs
```php
// Application Web : Redirection avec message
class WebController {
    public function update($id, Request $request) {
        try {
            $this->userService->update($id, $request->all());
            return redirect('/users')
                ->with('success', 'Utilisateur mis à jour');
        } catch (Exception $e) {
            return back()
                ->with('error', $e->getMessage())
                ->withInput();
        }
    }
}

// API : Réponse JSON avec code d'erreur
class ApiController {
    public function update($id, Request $request) {
        try {
            $user = $this->userService->update($id, $request->all());
            return new JsonResponse([
                'success' => true,
                'data' => UserDTO::fromUser($user)
            ]);
        } catch (Exception $e) {
            return new JsonResponse([
                'success' => false,
                'error' => $e->getMessage()
            ], 400);
        }
    }
}
```

## 4. Pourquoi Cette Structure ?

### 4.1 Avantages
1. **Séparation des Responsabilités**
   - Chaque classe a un rôle unique
   - Plus facile à maintenir et à tester

2. **Réutilisation du Code**
   - Les Services et Repositories sont utilisables partout
   - Pas de duplication de code

3. **Flexibilité**
   - Facile de changer l'implémentation (ex: changer de base de données)
   - Support multiple formats (Web, API, CLI)

4. **Testabilité**
   - Chaque composant peut être testé isolément
   - Mocking facile grâce aux interfaces

### 4.2 Exemple de Test
```php
class UserServiceTest extends TestCase {
    public function testRegister() {
        // Arrange
        $repository = $this->createMock(UserRepositoryInterface::class);
        $repository->expects($this->once())
            ->method('create')
            ->willReturn(new User());
            
        $service = new UserService($repository);
        
        // Act
        $user = $service->register([
            'name' => 'Test User',
            'email' => 'test@example.com'
        ]);
        
        // Assert
        $this->assertInstanceOf(User::class, $user);
    }
}
```

## Conclusion

1. **Pour une Application Web**
   - Focus sur les vues et l'expérience utilisateur
   - Gestion des sessions et des formulaires
   - Redirections et messages flash

2. **Pour une API**
   - Focus sur les données et la structure
   - Gestion des tokens et authentification
   - Réponses JSON standardisées

3. **Dans les Deux Cas**
   - Même logique métier (Services)
   - Même accès aux données (Repositories)
   - Même validation et sécurité 