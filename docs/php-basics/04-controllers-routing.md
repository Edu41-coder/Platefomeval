# Contrôleurs et Routage en PHP

## Introduction
Dans une application MVC, les contrôleurs et le routage sont comme des agents de circulation :
- Le **routeur** est comme un panneau de signalisation qui indique quelle route prendre
- Le **contrôleur** est comme un agent qui dirige la circulation à un carrefour

## 1. Le Système de Routage

### Configuration des Routes
```php
// config/routes.php
// Définition des routes de l'application
return [
    // Format : 'URL' => ['controller' => 'NomController', 'action' => 'nomAction']
    '/' => [
        'controller' => 'HomeController',
        'action' => 'index'
    ],
    '/articles' => [
        'controller' => 'ArticleController',
        'action' => 'index'
    ],
    '/article/{id}' => [
        'controller' => 'ArticleController',
        'action' => 'voir'
    ],
    '/contact' => [
        'controller' => 'ContactController',
        'action' => 'index'
    ]
];
```

### Classe Router
```php
// core/Router.php
class Router {
    private $routes = [];
    
    public function __construct() {
        // Charger les routes depuis la configuration
        $this->routes = require '../config/routes.php';
    }
    
    // Analyser l'URL actuelle et trouver la bonne route
    public function analyze($url) {
        // Nettoyer l'URL
        $url = trim($url, '/');
        
        // Parcourir toutes les routes définies
        foreach ($this->routes as $route => $params) {
            // Convertir la route en expression régulière
            $pattern = $this->convertRouteToRegex($route);
            
            // Si l'URL correspond à une route
            if (preg_match($pattern, $url, $matches)) {
                // Extraire les paramètres de l'URL
                $params['params'] = $this->extractParams($matches);
                return $params;
            }
        }
        
        // Si aucune route ne correspond, retourner la route 404
        return [
            'controller' => 'ErrorController',
            'action' => 'notFound'
        ];
    }
    
    // Convertir une route en expression régulière
    private function convertRouteToRegex($route) {
        // Remplacer les paramètres {param} par des captures regex
        $pattern = preg_replace('/\{([a-z]+)\}/', '(?P<\1>[^/]+)', $route);
        return "#^{$pattern}$#i";
    }
    
    // Extraire les paramètres de l'URL
    private function extractParams($matches) {
        $params = [];
        foreach ($matches as $key => $value) {
            if (is_string($key)) {
                $params[$key] = $value;
            }
        }
        return $params;
    }
}
```

## 2. Le Contrôleur de Base

```php
// core/Controller.php
// Classe de base pour tous les contrôleurs
abstract class Controller {
    protected $request;  // Données de la requête
    protected $session;  // Session utilisateur
    
    public function __construct() {
        // Initialiser la session
        $this->session = new Session();
        
        // Préparer les données de la requête
        $this->request = $this->prepareRequest();
    }
    
    // Préparer les données de la requête
    protected function prepareRequest() {
        return [
            'get' => $_GET,
            'post' => $_POST,
            'files' => $_FILES,
            'method' => $_SERVER['REQUEST_METHOD']
        ];
    }
    
    // Afficher une vue
    protected function render($view, $data = []) {
        // Extraire les données pour la vue
        extract($data);
        
        // Charger la vue
        $viewPath = "../app/Views/{$view}.php";
        if (file_exists($viewPath)) {
            require $viewPath;
        } else {
            throw new Exception("Vue non trouvée : {$view}");
        }
    }
    
    // Rediriger vers une autre page
    protected function redirect($url) {
        header("Location: {$url}");
        exit;
    }
    
    // Vérifier si la requête est en POST
    protected function isPost() {
        return $this->request['method'] === 'POST';
    }
    
    // Récupérer une donnée POST
    protected function getPost($key, $default = null) {
        return $this->request['post'][$key] ?? $default;
    }
    
    // Récupérer une donnée GET
    protected function getQuery($key, $default = null) {
        return $this->request['get'][$key] ?? $default;
    }
}
```

## 3. Exemple de Contrôleur Spécifique

```php
// app/Controllers/ArticleController.php
class ArticleController extends Controller {
    private $articleModel;
    
    public function __construct() {
        parent::__construct();
        $this->articleModel = new Article();
    }
    
    // Action pour la liste des articles
    // URL: /articles
    public function index() {
        // 1. Récupérer la page courante depuis l'URL
        $page = $this->getQuery('page', 1);
        
        // 2. Récupérer les articles pour cette page
        $articles = $this->articleModel->getPaginated($page);
        
        // 3. Afficher la vue avec les articles
        $this->render('articles/index', [
            'articles' => $articles,
            'page' => $page
        ]);
    }
    
    // Action pour voir un article
    // URL: /article/{id}
    public function voir($id) {
        // 1. Récupérer l'article
        $article = $this->articleModel->findById($id);
        
        // 2. Vérifier si l'article existe
        if (!$article) {
            // Rediriger vers la page 404
            $this->redirect('/404');
        }
        
        // 3. Afficher la vue avec l'article
        $this->render('articles/voir', [
            'article' => $article
        ]);
    }
    
    // Action pour créer un article
    // URL: /articles/creer
    public function creer() {
        // Si c'est une requête POST (envoi du formulaire)
        if ($this->isPost()) {
            try {
                // 1. Récupérer les données du formulaire
                $data = [
                    'titre' => $this->getPost('titre'),
                    'contenu' => $this->getPost('contenu')
                ];
                
                // 2. Créer l'article
                $id = $this->articleModel->create($data);
                
                // 3. Rediriger vers l'article créé
                $this->redirect("/article/{$id}");
                
            } catch (Exception $e) {
                // En cas d'erreur, réafficher le formulaire avec l'erreur
                $this->render('articles/creer', [
                    'erreur' => $e->getMessage(),
                    'data' => $data
                ]);
            }
        }
        
        // Si c'est une requête GET (affichage du formulaire)
        $this->render('articles/creer');
    }
}
```

## 4. Middleware pour le Contrôle d'Accès

```php
// core/Middleware/AuthMiddleware.php
// Middleware pour vérifier si l'utilisateur est connecté
class AuthMiddleware {
    public function handle($request, $next) {
        // Vérifier si l'utilisateur est connecté
        if (!isset($_SESSION['user_id'])) {
            // Rediriger vers la page de connexion
            header('Location: /login');
            exit;
        }
        
        // Continuer vers le contrôleur
        return $next($request);
    }
}

// Utilisation dans un contrôleur
class AdminController extends Controller {
    private $middleware = [];
    
    public function __construct() {
        parent::__construct();
        
        // Ajouter le middleware d'authentification
        $this->middleware[] = new AuthMiddleware();
    }
    
    // Méthode pour exécuter les middlewares
    protected function runMiddleware() {
        $next = function($request) {
            return true;
        };
        
        // Exécuter chaque middleware
        foreach (array_reverse($this->middleware) as $middleware) {
            $next = function($request) use ($middleware, $next) {
                return $middleware->handle($request, $next);
            };
        }
        
        return $next($this->request);
    }
    
    public function index() {
        // Vérifier les middlewares avant d'exécuter l'action
        if (!$this->runMiddleware()) {
            return;
        }
        
        // Code de l'action...
    }
}
```

## 5. Bonnes Pratiques

### 1. Organisation des Routes
```php
// Grouper les routes par fonctionnalité
return [
    // Routes pour l'authentification
    'auth' => [
        '/login' => ['controller' => 'AuthController', 'action' => 'login'],
        '/register' => ['controller' => 'AuthController', 'action' => 'register'],
        '/logout' => ['controller' => 'AuthController', 'action' => 'logout']
    ],
    
    // Routes pour les articles
    'articles' => [
        '/articles' => ['controller' => 'ArticleController', 'action' => 'index'],
        '/article/{id}' => ['controller' => 'ArticleController', 'action' => 'voir']
    ]
];
```

### 2. Validation dans les Contrôleurs
```php
protected function validateArticle($data) {
    $errors = [];
    
    // Valider le titre
    if (empty($data['titre'])) {
        $errors['titre'] = "Le titre est requis";
    } elseif (strlen($data['titre']) < 3) {
        $errors['titre'] = "Le titre doit faire au moins 3 caractères";
    }
    
    // Valider le contenu
    if (empty($data['contenu'])) {
        $errors['contenu'] = "Le contenu est requis";
    }
    
    return $errors;
}
```

## Conclusion

Points clés à retenir :

1. **Routage**
   - Définir des routes claires et logiques
   - Gérer les paramètres d'URL
   - Prévoir les cas d'erreur (404)

2. **Contrôleurs**
   - Un contrôleur par type de ressource
   - Méthodes claires pour chaque action
   - Validation des données entrantes

3. **Sécurité**
   - Utiliser des middlewares pour le contrôle d'accès
   - Valider toutes les entrées utilisateur
   - Gérer les erreurs proprement 