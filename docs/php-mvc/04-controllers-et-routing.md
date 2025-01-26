# Controllers et Routing

## 1. Rôle des Controllers

Les Controllers sont responsables de :
- Traiter les requêtes HTTP
- Coordonner les interactions Model-View
- Gérer la logique de l'application
- Retourner les réponses appropriées
- Valider les entrées utilisateur

## 2. Structure des Controllers

### Controller de Base
```php
namespace App\Controllers;

abstract class BaseController {
    protected $request;
    protected $response;
    
    public function __construct() {
        $this->request = new Request();
        $this->response = new Response();
    }
    
    protected function render($view, $data = []) {
        return view($view, $data);
    }
    
    protected function json($data, $status = 200) {
        return $this->response->json($data, $status);
    }
    
    protected function redirect($url) {
        return $this->response->redirect($url);
    }
}
```

### Controller Spécifique
```php
namespace App\Controllers;

class UserController extends BaseController {
    private $userService;
    
    public function __construct(UserService $userService) {
        parent::__construct();
        $this->userService = $userService;
    }
    
    public function index() {
        $users = $this->userService->getAllUsers();
        return $this->render('users/index', ['users' => $users]);
    }
    
    public function show($id) {
        $user = $this->userService->getUser($id);
        
        if (!$user) {
            return $this->response->notFound();
        }
        
        return $this->render('users/show', ['user' => $user]);
    }
}
```

## 3. Système de Routing

### Configuration des Routes
```php
namespace App\Routing;

class Router {
    private $routes = [];
    
    public function get($path, $handler) {
        $this->addRoute('GET', $path, $handler);
    }
    
    public function post($path, $handler) {
        $this->addRoute('POST', $path, $handler);
    }
    
    private function addRoute($method, $path, $handler) {
        $this->routes[] = [
            'method' => $method,
            'path' => $path,
            'handler' => $handler
        ];
    }
    
    public function dispatch($request) {
        foreach ($this->routes as $route) {
            if ($this->matchRoute($route, $request)) {
                return $this->executeHandler($route['handler'], $request);
            }
        }
        
        throw new RouteNotFoundException();
    }
}

// Configuration
$router = new Router();

$router->get('/users', [UserController::class, 'index']);
$router->get('/users/{id}', [UserController::class, 'show']);
$router->post('/users', [UserController::class, 'store']);
```

## 4. Middleware

### Système de Middleware
```php
namespace App\Middleware;

interface Middleware {
    public function handle($request, $next);
}

class AuthMiddleware implements Middleware {
    public function handle($request, $next) {
        if (!$request->session()->has('user_id')) {
            return redirect('/login');
        }
        
        return $next($request);
    }
}

class ControllerMiddleware {
    private $middlewares = [];
    
    public function addMiddleware($middleware) {
        $this->middlewares[] = $middleware;
    }
    
    public function execute($request, $controller) {
        $next = function($request) use ($controller) {
            return $controller($request);
        };
        
        $pipeline = array_reduce(
            array_reverse($this->middlewares),
            function($next, $middleware) {
                return function($request) use ($next, $middleware) {
                    return $middleware->handle($request, $next);
                };
            },
            $next
        );
        
        return $pipeline($request);
    }
}
```

## 5. Gestion des Requêtes

### Classe Request
```php
namespace App\Http;

class Request {
    private $get;
    private $post;
    private $server;
    private $files;
    
    public function __construct() {
        $this->get = $_GET;
        $this->post = $_POST;
        $this->server = $_SERVER;
        $this->files = $_FILES;
    }
    
    public function input($key, $default = null) {
        return $this->post[$key] ?? $this->get[$key] ?? $default;
    }
    
    public function file($key) {
        return $this->files[$key] ?? null;
    }
    
    public function method() {
        return $this->server['REQUEST_METHOD'];
    }
    
    public function isAjax() {
        return isset($this->server['HTTP_X_REQUESTED_WITH']) &&
               $this->server['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest';
    }
}
```

## 6. Gestion des Réponses

### Classe Response
```php
namespace App\Http;

class Response {
    private $content;
    private $status = 200;
    private $headers = [];
    
    public function setContent($content) {
        $this->content = $content;
        return $this;
    }
    
    public function setStatus($status) {
        $this->status = $status;
        return $this;
    }
    
    public function addHeader($name, $value) {
        $this->headers[$name] = $value;
        return $this;
    }
    
    public function json($data, $status = 200) {
        $this->addHeader('Content-Type', 'application/json');
        $this->setStatus($status);
        $this->setContent(json_encode($data));
        return $this;
    }
    
    public function send() {
        http_response_code($this->status);
        
        foreach ($this->headers as $name => $value) {
            header("{$name}: {$value}");
        }
        
        echo $this->content;
    }
}
```

## 7. Validation des Entrées

### Système de Validation
```php
namespace App\Validation;

class RequestValidator {
    private $rules = [];
    private $errors = [];
    
    public function validate(Request $request, array $rules) {
        foreach ($rules as $field => $fieldRules) {
            $value = $request->input($field);
            
            foreach ($fieldRules as $rule) {
                if (!$this->validateRule($value, $rule)) {
                    $this->errors[$field][] = $this->getErrorMessage($field, $rule);
                }
            }
        }
        
        return empty($this->errors);
    }
    
    public function getErrors() {
        return $this->errors;
    }
}

// Utilisation dans le controller
public function store(Request $request) {
    $validator = new RequestValidator();
    
    if (!$validator->validate($request, [
        'name' => ['required', 'min:3'],
        'email' => ['required', 'email', 'unique:users']
    ])) {
        return $this->json([
            'errors' => $validator->getErrors()
        ], 422);
    }
    
    // Création de l'utilisateur
}
```

## 8. Gestion des Erreurs

### Handler d'Exceptions
```php
namespace App\Exceptions;

class ExceptionHandler {
    public function handle($exception) {
        if ($exception instanceof ValidationException) {
            return $this->handleValidationException($exception);
        }
        
        if ($exception instanceof AuthenticationException) {
            return $this->handleAuthenticationException($exception);
        }
        
        return $this->handleGenericException($exception);
    }
    
    private function handleValidationException($exception) {
        return (new Response())
            ->json([
                'message' => 'Validation failed',
                'errors' => $exception->getErrors()
            ], 422);
    }
}

// Utilisation
try {
    $router->dispatch($request);
} catch (Exception $e) {
    $handler = new ExceptionHandler();
    $response = $handler->handle($e);
    $response->send();
}
```

## Conclusion

Points clés pour les Controllers :
1. Gestion claire des requêtes
2. Routing flexible et sécurisé
3. Middleware pour la réutilisation
4. Validation robuste
5. Gestion d'erreurs structurée

Recommandations :
- Garder les controllers légers
- Utiliser les middleware appropriés
- Valider toutes les entrées
- Gérer les erreurs proprement
- Documenter les routes 