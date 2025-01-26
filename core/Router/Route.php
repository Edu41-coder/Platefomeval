<?php

namespace Core\Router;

use Core\Middleware\MiddlewareInterface;
use Core\Http\Request;
use Core\Http\Response;

class Route
{
    private string $path;
    private $callable;
    private array $matches = [];
    private array $params = [];
    private array $middlewares = [];
    private static array $controllerCache = [];

    /**
     * @param string $path
     * @param array|callable $callable
     */
    public function __construct(string $path, $callable)
    {
        $this->path = trim($path, '/');
        $this->callable = $callable;
    }

    /**
     * Vérifie si l'URL correspond à la route
     */
    public function match(string $url): bool
    {
        // Supprimer les paramètres de requête de l'URL avant la comparaison
        $url = explode('?', $url)[0];
        
        $path = preg_replace('#:([\w]+)#', '([^/]+)', $this->path);
        $pathToMatch = "#^$path$#i";

        if (!preg_match($pathToMatch, $url, $matches)) {
            return false;
        }

        array_shift($matches);
        $this->matches = $matches;
        return true;
    }

    /**
     * Gén��re l'URL avec les paramètres
     */
    public function getUrl(array $params): string
    {
        $path = $this->path;
        foreach ($params as $k => $v) {
            $path = str_replace(":$k", (string)$v, $path);
        }
        return '/' . $path;
    }

    /**
     * Ajoute un paramètre à la route avec une expression régulière
     */
    public function with(string $param, string $regex): self
    {
        $this->params[$param] = str_replace('(', '(?:', $regex);
        return $this;
    }

    /**
     * Ajoute un middleware à la route
     * @param string|MiddlewareInterface $middleware
     * @param array $params Paramètres optionnels pour le middleware
     * @throws RouterException
     */
    public function middleware($middleware, array $params = []): self
    {
        if ($middleware instanceof MiddlewareInterface) {
            $this->middlewares[] = $middleware;
            return $this;
        }

        $namespaces = [
            "App\\Middleware\\",
            "App\\Api\\Middleware\\",
            "Core\\Middleware\\"
        ];

        $middlewareFound = false;

        foreach ($namespaces as $namespace) {
            $middlewareClass = $namespace . $middleware . "Middleware";
            if (class_exists($middlewareClass)) {
                try {
                    $middlewareInstance = empty($params)
                        ? new $middlewareClass()
                        : new $middlewareClass(...$params);

                    if (!$middlewareInstance instanceof MiddlewareInterface) {
                        throw RouterException::invalidCallback(
                            "Le middleware '$middleware' n'implémente pas l'interface MiddlewareInterface"
                        );
                    }

                    $this->middlewares[] = $middlewareInstance;
                    $middlewareFound = true;
                    break;
                } catch (\Throwable $e) {
                    throw RouterException::invalidCallback(
                        "Erreur lors de l'instanciation du middleware '$middleware': " . $e->getMessage()
                    );
                }
            }
        }

        if (!$middlewareFound) {
            throw RouterException::invalidCallback(
                "Middleware '$middleware' non trouvé dans les namespaces disponibles"
            );
        }

        return $this;
    }

    /**
     * Ajoute plusieurs middlewares à la fois
     */
    public function middlewares(array $middlewares): self
    {
        foreach ($middlewares as $middleware) {
            if (is_array($middleware)) {
                $this->middleware($middleware[0], $middleware[1] ?? []);
            } else {
                $this->middleware($middleware);
            }
        }
        return $this;
    }

    /**
     * Appelle la fonction associée à la route
     */
    public function call(Request $request, Response $response)
    {
        try {
            $next = function ($request) use ($response) {
                if (is_string($this->callable)) {
                    return $this->handleControllerCall($request, $response);
                }

                if (!is_callable($this->callable)) {
                    throw RouterException::invalidCallback('Callback non valide');
                }

                return call_user_func_array(
                    $this->callable,
                    array_merge([$request, $response], $this->matches)
                );
            };

            // Appliquer les middlewares en chaîne
            $chain = array_reduce(
                array_reverse($this->middlewares),
                function ($next, $middleware) {
                    return function ($request) use ($next, $middleware) {
                        return $middleware->handle($request, $next);
                    };
                },
                $next
            );

            return $chain($request);
        } catch (\Core\Middleware\MiddlewareException $e) {
            // Gérer les exceptions du middleware au niveau supérieur
            if ($request->wantsJson()) {
                return new \Core\Http\JsonResponse([
                    'success' => false,
                    'message' => $e->getMessage()
                ], $e->getCode());
            }
            
            if ($e->getCode() === 401) {
                $_SESSION['flash_error'] = $e->getMessage();
                header('Location: ' . url('login'));
                exit;
            }
            
            throw $e;
        } catch (\Exception $e) {
            if ($request->wantsJson()) {
                return new \Core\Http\JsonResponse([
                    'success' => false,
                    'message' => $e->getMessage()
                ], $e->getCode() ?: 500);
            }
            throw RouterException::invalidCallback($e->getMessage());
        }
    }

    /**
     * Gère l'appel à un contrôleur
     */
    private function handleControllerCall(Request $request, Response $response)
    {
        $params = explode('@', $this->callable);
        if (count($params) !== 2) {
            throw RouterException::invalidCallback($this->callable);
        }

        [$controllerClass, $methodName] = $params;

        // Utiliser le cache des contrôleurs
        if (!isset(self::$controllerCache[$controllerClass])) {
            // Initialiser le cache pour ce contrôleur
            $this->initializeControllerCache($controllerClass);
        }

        $cachedController = self::$controllerCache[$controllerClass];
        
        if (!in_array($methodName, $cachedController['methods'])) {
            throw RouterException::methodNotFound($controllerClass, $methodName);
        }

        try {
            $controller = new $controllerClass();
            return call_user_func_array(
                [$controller, $methodName],
                array_merge([$request, $response], $this->matches)
            );
        } catch (\Throwable $e) {
            error_log("Exception lors de l'exécution du contrôleur: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Initialise le cache pour un contrôleur
     */
    private function initializeControllerCache(string $controllerClass): void
    {
        $controllerFile = str_replace('\\', DIRECTORY_SEPARATOR, $controllerClass) . '.php';
        $possiblePaths = [
            $_SERVER['DOCUMENT_ROOT'] . '/Plateformeval/' . $controllerFile,
            __DIR__ . '/../../' . str_replace('App\\Controllers\\', '', $controllerFile)
        ];

        $fileFound = false;
        foreach ($possiblePaths as $path) {
            $normalizedPath = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path);
            if (file_exists($normalizedPath)) {
                require_once $normalizedPath;
                $fileFound = true;
                break;
            }
        }

        if (!$fileFound || !class_exists($controllerClass)) {
            throw RouterException::controllerNotFound($controllerClass);
        }

        // Mettre en cache les informations du contrôleur
        self::$controllerCache[$controllerClass] = [
            'path' => $normalizedPath,
            'methods' => get_class_methods($controllerClass)
        ];
    }

    // Getters
    public function getPath(): string
    {
        return $this->path;
    }

    public function getCallable()
    {
        return $this->callable;
    }

    public function getParams(): array
    {
        return $this->params;
    }

    public function getMatches(): array
    {
        return $this->matches;
    }

    public function getMiddlewares(): array
    {
        return $this->middlewares;
    }
}
