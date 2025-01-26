# Performance et Optimisation

## 1. Cache

### Système de Cache
```php
namespace App\Cache;

class CacheManager {
    private $driver;
    private $prefix;
    
    public function get(string $key, $default = null) {
        $value = $this->driver->get($this->prefix . $key);
        return $value !== null ? unserialize($value) : $default;
    }
    
    public function put(string $key, $value, int $ttl = 3600): void {
        $this->driver->set(
            $this->prefix . $key,
            serialize($value),
            $ttl
        );
    }
    
    public function remember(string $key, int $ttl, callable $callback) {
        $value = $this->get($key);
        
        if ($value !== null) {
            return $value;
        }
        
        $value = $callback();
        $this->put($key, $value, $ttl);
        
        return $value;
    }
    
    public function tags(array $tags): TaggedCache {
        return new TaggedCache($this, $tags);
    }
}
```

### Cache Redis
```php
namespace App\Cache\Drivers;

class RedisCache implements CacheDriver {
    private $redis;
    
    public function __construct(Redis $redis) {
        $this->redis = $redis;
    }
    
    public function get(string $key) {
        return $this->redis->get($key);
    }
    
    public function set(string $key, $value, int $ttl = null): bool {
        if ($ttl) {
            return $this->redis->setex($key, $ttl, $value);
        }
        
        return $this->redis->set($key, $value);
    }
    
    public function delete(string $key): bool {
        return $this->redis->del($key) > 0;
    }
    
    public function clear(): bool {
        return $this->redis->flushDB();
    }
}
```

## 2. Optimisation des Requêtes

### Query Optimizer
```php
namespace App\Database;

class QueryOptimizer {
    private $db;
    private $logger;
    
    public function analyze(string $query): array {
        $explainQuery = "EXPLAIN ANALYZE " . $query;
        return $this->db->query($explainQuery)->fetch();
    }
    
    public function suggestIndexes(string $query): array {
        $analysis = $this->analyze($query);
        $suggestions = [];
        
        if ($this->needsIndex($analysis)) {
            $suggestions[] = $this->generateIndexSuggestion($analysis);
        }
        
        return $suggestions;
    }
    
    public function optimizeQuery(string $query): string {
        // Analyse basique des sous-requêtes
        if ($this->hasInefficiencySubqueries($query)) {
            $query = $this->optimizeSubqueries($query);
        }
        
        // Optimisation des JOINs
        if ($this->hasInefficiencyJoins($query)) {
            $query = $this->optimizeJoins($query);
        }
        
        return $query;
    }
}
```

## 3. Mise en Cache des Vues

### View Cache
```php
namespace App\View;

class ViewCache {
    private $cache;
    private $viewFactory;
    
    public function render(string $view, array $data = [], int $ttl = 3600) {
        $cacheKey = $this->getCacheKey($view, $data);
        
        return $this->cache->remember($cacheKey, $ttl, function() use ($view, $data) {
            return $this->viewFactory->make($view, $data)->render();
        });
    }
    
    public function fragment(string $name, int $ttl, callable $callback) {
        $cacheKey = "view.fragment.{$name}";
        
        return $this->cache->remember($cacheKey, $ttl, function() use ($callback) {
            ob_start();
            $callback();
            return ob_get_clean();
        });
    }
    
    private function getCacheKey(string $view, array $data): string {
        return 'view.' . sha1($view . serialize($data));
    }
}
```

## 4. Optimisation des Assets

### Asset Manager
```php
namespace App\Assets;

class AssetOptimizer {
    private $publicPath;
    private $manifestPath;
    
    public function optimize(array $assets): array {
        $optimized = [];
        
        foreach ($assets as $type => $files) {
            if ($type === 'css') {
                $optimized[$type] = $this->optimizeCss($files);
            } elseif ($type === 'js') {
                $optimized[$type] = $this->optimizeJs($files);
            }
        }
        
        return $optimized;
    }
    
    private function optimizeCss(array $files): string {
        $minifier = new CssMinifier();
        $content = '';
        
        foreach ($files as $file) {
            $content .= file_get_contents($this->publicPath . $file);
        }
        
        return $minifier->minify($content);
    }
    
    private function optimizeJs(array $files): string {
        $minifier = new JsMinifier();
        $content = '';
        
        foreach ($files as $file) {
            $content .= file_get_contents($this->publicPath . $file);
        }
        
        return $minifier->minify($content);
    }
}
```

## 5. Optimisation des Images

### Image Optimizer
```php
namespace App\Assets;

class ImageOptimizer {
    private $driver;
    private $quality;
    
    public function optimize(string $path): void {
        $image = $this->driver->load($path);
        
        // Redimensionnement automatique si nécessaire
        if ($this->shouldResize($image)) {
            $image->resize(1920, null, function($constraint) {
                $constraint->aspectRatio();
                $constraint->upsize();
            });
        }
        
        // Compression
        $image->save($path, $this->quality);
    }
    
    public function generateThumbnail(string $path, int $width, int $height): string {
        $image = $this->driver->load($path);
        $thumbPath = $this->getThumbnailPath($path);
        
        $image->fit($width, $height)
            ->save($thumbPath, $this->quality);
            
        return $thumbPath;
    }
}
```

## 6. Optimisation de Session

### Session Optimizer
```php
namespace App\Session;

class SessionOptimizer {
    private $handler;
    private $options;
    
    public function optimize(): void {
        // Configuration du garbage collector
        ini_set('session.gc_probability', 1);
        ini_set('session.gc_divisor', 100);
        ini_set('session.gc_maxlifetime', 3600);
        
        // Compression des données
        $this->handler->setCompression(true);
        
        // Configuration des cookies
        session_set_cookie_params([
            'lifetime' => 3600,
            'path' => '/',
            'domain' => null,
            'secure' => true,
            'httponly' => true,
            'samesite' => 'Lax'
        ]);
    }
    
    public function gc(): void {
        $this->handler->gc(3600);
    }
}
```

## 7. Optimisation de Route

### Route Cache
```php
namespace App\Routing;

class RouteCacheManager {
    private $cache;
    private $router;
    
    public function cache(): void {
        $routes = $this->router->getRoutes();
        $compiled = $this->compileRoutes($routes);
        
        $this->cache->put('routes', $compiled, 3600);
    }
    
    public function clear(): void {
        $this->cache->forget('routes');
    }
    
    private function compileRoutes(array $routes): array {
        $compiled = [];
        
        foreach ($routes as $route) {
            $compiled[] = [
                'uri' => $route->uri(),
                'regex' => $route->getRegex(),
                'controller' => $route->getController(),
                'method' => $route->getMethod()
            ];
        }
        
        return $compiled;
    }
}
```

## 8. Profilage

### Profiler
```php
namespace App\Profiler;

class Profiler {
    private $profiles = [];
    private $currentProfile;
    
    public function start(string $name): void {
        $this->currentProfile = [
            'name' => $name,
            'start' => microtime(true),
            'memory_start' => memory_get_usage()
        ];
    }
    
    public function end(): void {
        $this->currentProfile['end'] = microtime(true);
        $this->currentProfile['memory_end'] = memory_get_usage();
        $this->currentProfile['duration'] = 
            $this->currentProfile['end'] - $this->currentProfile['start'];
        $this->currentProfile['memory'] = 
            $this->currentProfile['memory_end'] - $this->currentProfile['memory_start'];
            
        $this->profiles[] = $this->currentProfile;
    }
    
    public function getProfiles(): array {
        return $this->profiles;
    }
}
```

## 9. Monitoring

### Performance Monitor
```php
namespace App\Monitoring;

class PerformanceMonitor {
    private $metrics;
    private $threshold;
    
    public function measure(string $key, callable $callback) {
        $start = microtime(true);
        $result = $callback();
        $duration = microtime(true) - $start;
        
        $this->metrics->gauge("performance.{$key}", $duration);
        
        if ($duration > $this->threshold) {
            $this->alert("Performance threshold exceeded for {$key}");
        }
        
        return $result;
    }
    
    public function report(): array {
        return [
            'metrics' => $this->metrics->get(),
            'alerts' => $this->getAlerts(),
            'recommendations' => $this->getRecommendations()
        ];
    }
}
```

## 10. Optimisation de Code

### Code Optimizer
```php
namespace App\Optimization;

class CodeOptimizer {
    public function optimizeLoop(array $data, callable $callback): array {
        $count = count($data);
        $result = [];
        
        // Pré-allouer le tableau
        $result = array_fill(0, $count, null);
        
        // Utiliser foreach au lieu de for
        foreach ($data as $i => $item) {
            $result[$i] = $callback($item);
        }
        
        return $result;
    }
    
    public function optimizeMemory(callable $callback): mixed {
        // Désactiver le garbage collector
        gc_disable();
        
        try {
            return $callback();
        } finally {
            // Réactiver et forcer le garbage collector
            gc_enable();
            gc_collect_cycles();
        }
    }
}
```

## Conclusion

Points clés pour la Performance :
1. Mise en cache efficace
2. Optimisation des requêtes
3. Gestion des assets
4. Monitoring continu
5. Profilage régulier

Recommandations :
- Utiliser le cache approprié
- Optimiser les requêtes SQL
- Compresser les assets
- Monitorer les performances
- Profiler régulièrement 