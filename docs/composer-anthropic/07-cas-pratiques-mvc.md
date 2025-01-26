# Cas pratiques : MVC en PHP avec Composer Anthropic

## 1. Structure MVC de base

### Organisation des dossiers
```
project/
├── app/
│   ├── Controllers/
│   ├── Models/
│   ├── Views/
│   └── Services/
├── config/
├── public/
└── composer.json
```

### Configuration Composer
```json
{
    "autoload": {
        "psr-4": {
            "App\\": "app/",
            "Config\\": "config/"
        }
    },
    "require": {
        "php": ">=7.4",
        "twig/twig": "^3.0",
        "doctrine/orm": "^2.11"
    }
}
```

## 2. Cas pratique : Système d'authentification

### Modèle (User.php)
```php
namespace App\Models;

class User {
    private int $id;
    private string $email;
    private string $password;
    
    public function verifyPassword(string $password): bool {
        return password_verify($password, $this->password);
    }
    
    public function setPassword(string $password): void {
        $this->password = password_hash($password, PASSWORD_DEFAULT);
    }
}
```

### Contrôleur (AuthController.php)
```php
namespace App\Controllers;

use App\Models\User;
use App\Services\AuthService;

class AuthController {
    private AuthService $authService;
    
    public function login(): void {
        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';
        
        try {
            $user = $this->authService->authenticate($email, $password);
            $_SESSION['user_id'] = $user->getId();
            redirect('/dashboard');
        } catch (AuthException $e) {
            $this->view->render('auth/login', ['error' => $e->getMessage()]);
        }
    }
}
```

### Vue (login.php)
```php
<form method="POST" action="/login">
    <div class="form-group">
        <label>Email</label>
        <input type="email" name="email" required>
    </div>
    <div class="form-group">
        <label>Mot de passe</label>
        <input type="password" name="password" required>
    </div>
    <button type="submit">Connexion</button>
</form>
```

## 3. Cas pratique : Gestion de produits

### Modèle (Product.php)
```php
namespace App\Models;

use App\Interfaces\ProductInterface;

class Product implements ProductInterface {
    private int $id;
    private string $name;
    private float $price;
    private string $description;
    
    public function calculateTax(): float {
        return $this->price * 0.2; // TVA 20%
    }
    
    public function toArray(): array {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'price' => $this->price,
            'description' => $this->description
        ];
    }
}
```

### Service (ProductService.php)
```php
namespace App\Services;

use App\Models\Product;
use App\Repositories\ProductRepository;

class ProductService {
    private ProductRepository $repository;
    
    public function __construct(ProductRepository $repository) {
        $this->repository = $repository;
    }
    
    public function createProduct(array $data): Product {
        $this->validateProductData($data);
        
        $product = new Product();
        $product->setName($data['name']);
        $product->setPrice($data['price']);
        $product->setDescription($data['description']);
        
        return $this->repository->save($product);
    }
    
    private function validateProductData(array $data): void {
        if (empty($data['name'])) {
            throw new ValidationException('Le nom est requis');
        }
        if (!is_numeric($data['price']) || $data['price'] <= 0) {
            throw new ValidationException('Prix invalide');
        }
    }
}
```

### Contrôleur (ProductController.php)
```php
namespace App\Controllers;

use App\Services\ProductService;

class ProductController {
    private ProductService $productService;
    private ViewRenderer $view;
    
    public function index(): void {
        $products = $this->productService->getAllProducts();
        $this->view->render('products/index', ['products' => $products]);
    }
    
    public function create(): void {
        try {
            $product = $this->productService->createProduct($_POST);
            redirect('/products/' . $product->getId());
        } catch (ValidationException $e) {
            $this->view->render('products/create', [
                'error' => $e->getMessage(),
                'data' => $_POST
            ]);
        }
    }
}
```

## 4. Cas pratique : API RESTful

### Contrôleur API (ApiController.php)
```php
namespace App\Controllers\Api;

use App\Services\ProductService;

class ApiController {
    private ProductService $productService;
    
    public function getProducts(): void {
        try {
            $products = $this->productService->getAllProducts();
            $this->jsonResponse($products, 200);
        } catch (\Exception $e) {
            $this->jsonResponse(['error' => $e->getMessage()], 500);
        }
    }
    
    private function jsonResponse(array $data, int $status = 200): void {
        header('Content-Type: application/json');
        http_response_code($status);
        echo json_encode($data);
    }
}
```

### Middleware (AuthMiddleware.php)
```php
namespace App\Middleware;

class AuthMiddleware {
    public function handle(Request $request, Closure $next) {
        if (!isset($_SESSION['user_id'])) {
            if ($request->expectsJson()) {
                return response()->json(['error' => 'Non autorisé'], 401);
            }
            return redirect('/login');
        }
        
        return $next($request);
    }
}
```

## 5. Cas pratique : Gestion des vues avec Twig

### Configuration Twig
```php
namespace App\Config;

use Twig\Environment;
use Twig\Loader\FilesystemLoader;

class TwigConfig {
    public static function initialize(): Environment {
        $loader = new FilesystemLoader(__DIR__ . '/../Views');
        
        return new Environment($loader, [
            'cache' => __DIR__ . '/../cache/twig',
            'auto_reload' => true,
            'debug' => true
        ]);
    }
}
```

### Vue Twig (products.twig)
```twig
{% extends 'layout.twig' %}

{% block content %}
    <h1>Liste des produits</h1>
    
    {% for product in products %}
        <div class="product-card">
            <h2>{{ product.name }}</h2>
            <p>Prix: {{ product.price|number_format(2, ',', ' ') }} €</p>
            <p>{{ product.description }}</p>
        </div>
    {% endfor %}
{% endblock %}
```

## 6. Cas pratique : Formulaires et validation

### Service de validation
```php
namespace App\Services;

class ValidationService {
    private array $errors = [];
    
    public function validate(array $data, array $rules): bool {
        foreach ($rules as $field => $rule) {
            if (!$this->validateField($data[$field] ?? null, $rule)) {
                $this->errors[$field] = $this->getErrorMessage($field, $rule);
            }
        }
        
        return empty($this->errors);
    }
    
    private function validateField($value, string $rule): bool {
        switch ($rule) {
            case 'required':
                return !empty($value);
            case 'email':
                return filter_var($value, FILTER_VALIDATE_EMAIL) !== false;
            case 'numeric':
                return is_numeric($value);
            default:
                return true;
        }
    }
}
```

### Formulaire avec validation
```php
namespace App\Controllers;

class ProductController {
    private ValidationService $validator;
    
    public function store(): void {
        $rules = [
            'name' => 'required',
            'price' => 'required|numeric',
            'description' => 'required'
        ];
        
        if (!$this->validator->validate($_POST, $rules)) {
            $this->view->render('products/create', [
                'errors' => $this->validator->getErrors(),
                'old' => $_POST
            ]);
            return;
        }
        
        // Création du produit...
    }
}
```

## 7. Bonnes pratiques MVC

### 1. Séparation des responsabilités
- Modèles : logique métier
- Vues : présentation
- Contrôleurs : coordination
- Services : traitements complexes

### 2. Injection de dépendances
```php
namespace App\Controllers;

class ProductController {
    public function __construct(
        private ProductService $productService,
        private ValidationService $validator,
        private ViewRenderer $view
    ) {}
}
```

### 3. Utilisation des interfaces
```php
namespace App\Interfaces;

interface RepositoryInterface {
    public function find(int $id);
    public function findAll(): array;
    public function save($entity);
    public function delete($entity);
}
```

### 4. Gestion des erreurs
```php
namespace App\Exceptions;

class Handler {
    public function handle(\Exception $e): void {
        if ($e instanceof ValidationException) {
            $this->handleValidationException($e);
        } elseif ($e instanceof DatabaseException) {
            $this->handleDatabaseException($e);
        } else {
            $this->handleGenericException($e);
        }
    }
}
```

## Conclusion

L'architecture MVC avec Composer Anthropic permet :
- Une organisation claire du code
- Une maintenance facilitée
- Une évolution simple
- Une réutilisation optimale

Points clés à retenir :
1. Respecter la séparation des responsabilités
2. Utiliser l'injection de dépendances
3. Implémenter des interfaces
4. Gérer proprement les erreurs
5. Documenter le code 