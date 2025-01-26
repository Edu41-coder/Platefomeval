# Les Middlewares Expliqués Simplement

## Introduction

Imaginez un immeuble avec un portier :
- Quand quelqu'un veut entrer dans l'immeuble, le portier :
  1. Vérifie son badge
  2. Note son heure d'arrivée
  3. Peut lui refuser l'accès
  4. Peut le diriger vers un autre endroit

C'est exactement ce que fait un Middleware dans une application web !

## 1. Qu'est-ce qu'un Middleware ?

Un Middleware est un "morceau de code" qui :
- S'exécute AVANT ou APRÈS une requête
- Peut modifier la requête
- Peut bloquer la requête
- Peut rediriger la requête

```php
// Structure de base d'un Middleware
class MonMiddleware {
    public function handle($request, $next) {
        // 1. Code exécuté AVANT la requête
        
        // 2. Passer au middleware suivant (ou au contrôleur)
        $response = $next($request);
        
        // 3. Code exécuté APRÈS la requête
        
        return $response;
    }
}
```

## 2. Exemples Simples de Middlewares

### 2.1 Middleware d'Authentification
```php
class AuthMiddleware {
    public function handle($request, $next) {
        // Vérifier si l'utilisateur est connecté
        if (!isset($_SESSION['user_id'])) {
            // Si non connecté, rediriger vers la page de connexion
            return redirect('/login')->with('error', 'Veuillez vous connecter');
        }
        
        // Si connecté, continuer normalement
        return $next($request);
    }
}
```

### 2.2 Middleware de Journal (Logging)
```php
class LoggingMiddleware {
    public function handle($request, $next) {
        // Avant la requête
        $startTime = microtime(true);
        $url = $request->getUrl();
        
        // Exécuter la requête
        $response = $next($request);
        
        // Après la requête
        $duration = microtime(true) - $startTime;
        
        // Enregistrer les informations
        $this->logRequest([
            'url' => $url,
            'duration' => $duration,
            'date' => date('Y-m-d H:i:s')
        ]);
        
        return $response;
    }
}
```

### 2.3 Middleware de Vérification CSRF
```php
class CsrfMiddleware {
    public function handle($request, $next) {
        // Vérifier si c'est une requête POST
        if ($request->isPost()) {
            $token = $request->post('csrf_token');
            
            // Vérifier si le token est valide
            if (!$this->isValidToken($token)) {
                throw new SecurityException('Token CSRF invalide');
            }
        }
        
        return $next($request);
    }
    
    private function isValidToken($token) {
        return $token === $_SESSION['csrf_token'];
    }
}
```

## 3. Comment Fonctionnent les Middlewares ?

### 3.1 Le Principe de l'Oignon
Les middlewares fonctionnent comme les couches d'un oignon :
```php
// 1. La requête traverse les middlewares de l'extérieur vers l'intérieur
Request → Middleware1 → Middleware2 → Middleware3 → Controller

// 2. La réponse traverse les middlewares de l'intérieur vers l'extérieur
Controller → Middleware3 → Middleware2 → Middleware1 → Response
```

### 3.2 Exemple Concret
```php
// Classe pour gérer la chaîne de middlewares
class MiddlewareStack {
    private $middlewares = [];
    private $controller;
    
    public function addMiddleware($middleware) {
        $this->middlewares[] = $middleware;
    }
    
    public function setController($controller) {
        $this->controller = $controller;
    }
    
    public function handle($request) {
        // Créer la chaîne de middlewares
        $chain = array_reduce(
            array_reverse($this->middlewares),
            function($next, $middleware) {
                return function($request) use ($next, $middleware) {
                    return $middleware->handle($request, $next);
                };
            },
            function($request) {
                return $this->controller->handle($request);
            }
        );
        
        // Exécuter la chaîne
        return $chain($request);
    }
}
```

## 4. Types Courants de Middlewares

### 4.1 Middlewares de Sécurité
```php
// Protection contre les XSS
class XssMiddleware {
    public function handle($request, $next) {
        // Nettoyer toutes les entrées
        $clean = array_map(function($value) {
            return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
        }, $request->all());
        
        // Remplacer les données nettoyées
        $request->replace($clean);
        
        return $next($request);
    }
}

// Protection contre les injections SQL
class SqlInjectionMiddleware {
    public function handle($request, $next) {
        foreach ($request->all() as $key => $value) {
            if ($this->containsSqlInjection($value)) {
                throw new SecurityException('Tentative d\'injection SQL détectée');
            }
        }
        
        return $next($request);
    }
}
```

### 4.2 Middlewares de Performance
```php
// Compression des réponses
class CompressionMiddleware {
    public function handle($request, $next) {
        // Obtenir la réponse normale
        $response = $next($request);
        
        // Compresser si le navigateur le supporte
        if (strpos($request->header('Accept-Encoding'), 'gzip') !== false) {
            $response->setContent(gzencode($response->getContent()));
            $response->headers->set('Content-Encoding', 'gzip');
        }
        
        return $response;
    }
}

// Cache
class CacheMiddleware {
    public function handle($request, $next) {
        $key = $this->getCacheKey($request);
        
        // Vérifier si la réponse est en cache
        if ($cachedResponse = $this->cache->get($key)) {
            return $cachedResponse;
        }
        
        // Si non, obtenir la réponse normale
        $response = $next($request);
        
        // Mettre en cache pour les prochaines fois
        $this->cache->set($key, $response, 3600); // 1 heure
        
        return $response;
    }
}
```

## 5. Utilisation dans une Application

### 5.1 Configuration des Middlewares
```php
// Dans votre fichier de configuration
return [
    'middlewares' => [
        'global' => [
            // Middlewares appliqués à toutes les requêtes
            LoggingMiddleware::class,
            SecurityMiddleware::class
        ],
        'web' => [
            // Middlewares pour les routes web
            SessionMiddleware::class,
            CsrfMiddleware::class,
            AuthMiddleware::class
        ],
        'api' => [
            // Middlewares pour les routes API
            ApiAuthMiddleware::class,
            ThrottleMiddleware::class
        ]
    ]
];
```

### 5.2 Application sur les Routes
```php
// Dans vos routes
$router->group(['middleware' => ['auth', 'admin']], function($router) {
    $router->get('/admin/dashboard', 'AdminController@dashboard');
    $router->get('/admin/users', 'AdminController@users');
});

// Ou sur une route individuelle
$router->get('/profile', 'UserController@profile')
    ->middleware('auth');
```

## 6. Bonnes Pratiques

### 6.1 Garder les Middlewares Simples
```php
// ✅ BON : Un middleware avec une seule responsabilité
class AuthMiddleware {
    public function handle($request, $next) {
        if (!$this->isAuthenticated()) {
            return redirect('/login');
        }
        return $next($request);
    }
}

// ❌ MAUVAIS : Un middleware qui fait trop de choses
class ComplexMiddleware {
    public function handle($request, $next) {
        // Vérifier l'authentification
        if (!$this->isAuthenticated()) {
            return redirect('/login');
        }
        
        // Vérifier les permissions
        if (!$this->hasPermission()) {
            return redirect('/unauthorized');
        }
        
        // Logger la requête
        $this->logRequest();
        
        // Vérifier le cache
        if ($cached = $this->getCache()) {
            return $cached;
        }
        
        return $next($request);
    }
}
```

### 6.2 Ordre des Middlewares
```php
// ✅ BON : Ordre logique des middlewares
$middlewares = [
    // 1. Sécurité de base
    SecurityMiddleware::class,
    
    // 2. Session et authentification
    SessionMiddleware::class,
    AuthMiddleware::class,
    
    // 3. Traitement des données
    ValidationMiddleware::class,
    
    // 4. Logging (après tout le reste)
    LoggingMiddleware::class
];
```

## Conclusion

Les Middlewares sont essentiels car ils permettent de :
1. **Séparer les Responsabilités**
   - Chaque middleware a un rôle unique
   - Code plus facile à maintenir

2. **Réutiliser le Code**
   - Les middlewares peuvent être utilisés sur différentes routes
   - Évite la duplication de code

3. **Sécuriser l'Application**
   - Vérifications systématiques
   - Protection contre les attaques courantes

4. **Améliorer les Performances**
   - Mise en cache
   - Compression des réponses 