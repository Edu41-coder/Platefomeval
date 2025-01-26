# Flux de l'Application PHP

## Introduction
Imaginez votre application comme un bureau de poste :
- Le `index.php` est comme le guichet principal qui reçoit toutes les demandes
- Le `Router` est comme le trieur qui dirige les lettres vers les bons services
- Les `Controllers` sont comme les différents services qui traitent les demandes

## 1. Point d'Entrée (index.php)

```php
// public/index.php
// C'est le fichier qui reçoit TOUTES les requêtes

// 1. Charger l'autoloader de Composer
require_once '../vendor/autoload.php';

// 2. Démarrer la session
session_start();

// 3. Charger les variables d'environnement
$dotenv = new Dotenv\Dotenv(__DIR__ . '/..');
$dotenv->load();

// 4. Charger la configuration de base
$config = require_once '../config/app.php';

// 5. Créer l'application
$app = new Application($config);

// 6. Exécuter l'application
$app->run();
```

## 2. La Classe Application

```php
// core/Application.php
class Application {
    private $router;
    private $request;
    private $response;
    
    public function __construct(array $config) {
        // 1. Créer les composants essentiels
        $this->request = new Request();
        $this->response = new Response();
        $this->router = new Router();
        
        // 2. Charger les routes
        $this->loadRoutes();
        
        // 3. Configurer les services de base
        $this->setupServices($config);
    }
    
    // Charger toutes les routes depuis le fichier de routes
    private function loadRoutes() {
        require_once '../routes/web.php';
    }
    
    // Configurer les services essentiels
    private function setupServices($config) {
        // Base de données
        Database::connect($config['database']);
        
        // Gestionnaire d'erreurs
        $this->setupErrorHandler();
        
        // Autres services...
    }
    
    // Exécuter l'application
    public function run() {
        try {
            // 1. Analyser la requête
            $path = $this->request->getPath();
            $method = $this->request->getMethod();
            
            // 2. Trouver la route correspondante
            $route = $this->router->match($path, $method);
            
            // 3. Si aucune route ne correspond
            if (!$route) {
                throw new NotFoundException("Page non trouvée");
            }
            
            // 4. Exécuter les middlewares
            $this->runMiddleware($route->getMiddleware());
            
            // 5. Appeler le contrôleur
            $response = $this->callController($route);
            
            // 6. Envoyer la réponse
            $this->response->send($response);
            
        } catch (Exception $e) {
            // Gérer les erreurs
            $this->handleError($e);
        }
    }
    
    // Appeler le bon contrôleur avec la bonne action
    private function callController($route) {
        // 1. Récupérer les informations de la route
        $controller = $route->getController();
        $action = $route->getAction();
        $params = $route->getParams();
        
        // 2. Créer une instance du contrôleur
        $controllerInstance = new $controller();
        
        // 3. Appeler l'action avec les paramètres
        return call_user_func_array(
            [$controllerInstance, $action],
            $params
        );
    }
}
```

## 3. La Classe Request

```php
// core/Request.php
class Request {
    private $get;
    private $post;
    private $server;
    private $files;
    private $cookies;
    
    public function __construct() {
        // Copier les superglobales pour plus de sécurité
        $this->get = $_GET;
        $this->post = $_POST;
        $this->server = $_SERVER;
        $this->files = $_FILES;
        $this->cookies = $_COOKIES;
    }
    
    // Obtenir le chemin de la requête
    public function getPath() {
        $path = $this->server['REQUEST_URI'] ?? '/';
        $position = strpos($path, '?');
        
        if ($position !== false) {
            $path = substr($path, 0, $position);
        }
        
        return $path;
    }
    
    // Obtenir la méthode HTTP
    public function getMethod() {
        return strtoupper($this->server['REQUEST_METHOD']);
    }
    
    // Obtenir une valeur POST
    public function post($key, $default = null) {
        return $this->post[$key] ?? $default;
    }
    
    // Obtenir une valeur GET
    public function get($key, $default = null) {
        return $this->get[$key] ?? $default;
    }
    
    // Vérifier si la requête est en AJAX
    public function isAjax() {
        return isset($this->server['HTTP_X_REQUESTED_WITH']) &&
            strtolower($this->server['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }
}
```

## 4. La Classe Response

```php
// core/Response.php
class Response {
    private $headers = [];
    private $statusCode = 200;
    
    // Définir le code de statut HTTP
    public function setStatusCode($code) {
        $this->statusCode = $code;
        return $this;
    }
    
    // Ajouter un header
    public function addHeader($name, $value) {
        $this->headers[$name] = $value;
        return $this;
    }
    
    // Envoyer une réponse JSON
    public function json($data) {
        $this->addHeader('Content-Type', 'application/json');
        return json_encode($data);
    }
    
    // Envoyer la réponse au navigateur
    public function send($content) {
        // 1. Envoyer le code de statut
        http_response_code($this->statusCode);
        
        // 2. Envoyer les headers
        foreach ($this->headers as $name => $value) {
            header("$name: $value");
        }
        
        // 3. Envoyer le contenu
        echo $content;
    }
}
```

## 5. Exemple de Flux Complet

### 1. Configuration des Routes
```php
// routes/web.php
// Définir les routes de l'application
$router->get('/', 'HomeController@index');
$router->get('/articles', 'ArticleController@index');
$router->get('/article/{id}', 'ArticleController@show');
$router->post('/article', 'ArticleController@store');
```

### 2. Contrôleur
```php
// app/Controllers/ArticleController.php
class ArticleController extends Controller {
    private $articleService;
    
    public function __construct() {
        $this->articleService = new ArticleService();
    }
    
    // Action pour afficher un article
    public function show($id) {
        try {
            // 1. Récupérer l'article
            $article = $this->articleService->findById($id);
            
            // 2. Vérifier si l'article existe
            if (!$article) {
                throw new NotFoundException("Article non trouvé");
            }
            
            // 3. Rendre la vue
            return $this->view('articles.show', [
                'article' => $article
            ]);
            
        } catch (Exception $e) {
            // Gérer l'erreur
            return $this->error($e->getMessage());
        }
    }
}
```

## 6. Flux d'une Requête Typique

1. **Le Navigateur fait une Requête**
```plaintext
L'utilisateur visite : http://monsite.com/article/123
```

2. **Le Serveur Web (Apache/Nginx)**
```plaintext
- Reçoit la requête
- La redirige vers public/index.php grâce au fichier .htaccess
```

3. **index.php**
```php
// 1. Initialise l'application
$app = new Application($config);

// 2. Exécute l'application
$app->run();
```

4. **Application**
```php
// 1. Analyse l'URL : /article/123
$path = $request->getPath();  // "/article/123"

// 2. Trouve la route correspondante
$route = $router->match($path, 'GET');
// Trouve : ArticleController@show avec paramètre id=123

// 3. Crée le contrôleur et appelle l'action
$controller = new ArticleController();
$response = $controller->show(123);
```

5. **Contrôleur**
```php
// 1. Récupère les données via le service
$article = $this->articleService->findById(123);

// 2. Prépare la vue
return $this->view('articles.show', ['article' => $article]);
```

6. **Application (fin)**
```php
// Envoie la réponse au navigateur
$response->send($content);
```

## 7. Bonnes Pratiques

### 1. Organisation du Code
```php
// ✅ BON : Utiliser des classes autoloadées
use App\Controllers\ArticleController;
use App\Services\ArticleService;

// ❌ MAUVAIS : Inclure des fichiers manuellement
require_once 'controllers/ArticleController.php';
require_once 'services/ArticleService.php';
```

### 2. Gestion des Erreurs
```php
// ✅ BON : Utiliser try/catch pour gérer les erreurs
try {
    $result = $this->process();
} catch (Exception $e) {
    // Logger l'erreur
    log_error($e);
    // Afficher un message utilisateur
    return $this->error("Une erreur est survenue");
}

// ❌ MAUVAIS : Laisser les erreurs non gérées
$result = $this->process();  // Peut crasher !
```

## Conclusion

Points clés à retenir :

1. **Organisation**
   - Un point d'entrée unique (index.php)
   - Routage centralisé
   - Structure MVC claire

2. **Flux**
   - Requête → index.php → Router → Controller → Response
   - Chaque composant a une responsabilité unique
   - Les erreurs sont gérées à chaque niveau

3. **Bonnes Pratiques**
   - Autoloading des classes
   - Gestion des erreurs
   - Code modulaire et maintenable 