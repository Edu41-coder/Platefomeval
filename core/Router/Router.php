<?php

namespace Core\Router;

use Core\Http\Request;
use Core\Http\Response;
use Core\Middleware\MiddlewareInterface;

class Router implements RouterInterface
{
    private array $routes = [];
    private string $url;
    private array $namedRoutes = [];
    private string $prefix;
    private array $middlewares = [];
    private array $globalMiddlewares = []; // Ajout d'un tableau pour les middlewares globaux

    public function __construct(string $url, string $prefix = '')
    {
        $this->url = trim($url, '/');
        $this->prefix = trim($prefix, '/');
    }

    /**
     * Ajoute un middleware global qui sera appliqué à toutes les routes
     */
    public function addGlobalMiddleware($middleware, array $params = []): self
    {
        if (is_string($middleware)) {
            $this->globalMiddlewares[] = [$middleware, $params];
        } elseif ($middleware instanceof MiddlewareInterface) {
            $this->globalMiddlewares[] = $middleware;
        }
        return $this;
    }

    public function get(string $path, $callable, ?string $name = null): Route
    {
        return $this->add($this->prefix . $path, $callable, 'GET', $name);
    }

    public function post(string $path, $callable, ?string $name = null): Route
    {
        return $this->add($this->prefix . $path, $callable, 'POST', $name);
    }

    public function put(string $path, $callable, ?string $name = null): Route
    {
        return $this->add($this->prefix . $path, $callable, 'PUT', $name);
    }

    public function delete(string $path, $callable, ?string $name = null): Route
    {
        return $this->add($this->prefix . $path, $callable, 'DELETE', $name);
    }

    public function options(string $path, $callable, ?string $name = null): Route
    {
        return $this->add($this->prefix . $path, $callable, 'OPTIONS', $name);
    }

    public function any(string $path, $callable, ?string $name = null): Route
    {
        return $this->add(
            $this->prefix . $path,
            $callable,
            ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS', 'PATCH'],
            $name
        );
    }

    public function group(string $prefix, callable $callback, array $middlewares = []): void
    {
        $previousPrefix = $this->prefix;
        $previousMiddlewares = $this->middlewares;

        $this->prefix .= '/' . trim($prefix, '/');
        $this->middlewares = array_merge($this->middlewares, $middlewares);

        $callback($this);

        $this->prefix = $previousPrefix;
        $this->middlewares = $previousMiddlewares;
    }

    public function middleware($middleware, array $params = []): self
    {
        if (is_string($middleware)) {
            $this->middlewares[] = [$middleware, $params];
        } elseif ($middleware instanceof MiddlewareInterface) {
            $this->middlewares[] = $middleware;
        }
        return $this;
    }

    private function add(string $path, $callable, $method, ?string $name): Route
    {
        $route = new Route($path, $callable);

        // Appliquer les middlewares globaux
        foreach ($this->globalMiddlewares as $middleware) {
            if (is_array($middleware)) {
                $route->middleware($middleware[0], $middleware[1] ?? []);
            } else {
                $route->middleware($middleware);
            }
        }

        // Appliquer les middlewares spécifiques à la route
        foreach ($this->middlewares as $middleware) {
            if (is_array($middleware)) {
                $route->middleware($middleware[0], $middleware[1] ?? []);
            } else {
                $route->middleware($middleware);
            }
        }

        if (is_array($method)) {
            foreach ($method as $m) {
                $this->routes[$m][] = $route;
            }
        } else {
            $this->routes[$method][] = $route;
        }

        if ($name) {
            $this->namedRoutes[$name] = $route;
        }

        return $route;
    }

    public function run()
    {
        try {
            $request = new Request();
            $response = new Response();
            $method = $request->getMethod();

            if ($method === 'OPTIONS') {
                header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
                header('Access-Control-Allow-Headers: Content-Type, Authorization, X-CSRF-TOKEN');
                header('Access-Control-Max-Age: 86400');
                http_response_code(204);
                return null;
            }

            if (!isset($this->routes[$method])) {
                if ($request->wantsJson()) {
                    header('Content-Type: application/json');
                    http_response_code(405);
                    echo json_encode([
                        'success' => false,
                        'message' => 'Method Not Allowed',
                        'code' => 405
                    ]);
                    exit;
                }
                throw RouterException::methodNotAllowed($method);
            }

            $url = $this->url;
            if (!empty($this->prefix) && strpos($url, $this->prefix) === 0) {
                $url = substr($url, strlen($this->prefix));
            }

            foreach ($this->routes[$method] as $route) {
                if ($route->match($url)) {
                    try {
                        $result = $route->call($request, $response);

                        if ($result instanceof Response) {
                            $result->send();
                            return null;
                        }

                        return $result;
                    } catch (\Exception $e) {
                        if ($request->wantsJson()) {
                            header('Content-Type: application/json');
                            http_response_code($e->getCode() ?: 500);
                            echo json_encode([
                                'success' => false,
                                'message' => $e->getMessage(),
                                'code' => $e->getCode() ?: 500
                            ]);
                            exit;
                        }
                        if ($e instanceof RouterException) {
                            throw $e;
                        }
                        throw RouterException::invalidCallback($route->getPath() . ': ' . $e->getMessage());
                    }
                }
            }

            if ($request->wantsJson()) {
                header('Content-Type: application/json');
                http_response_code(404);
                echo json_encode([
                    'success' => false,
                    'message' => 'Route Not Found',
                    'code' => 404
                ]);
                exit;
            }
            throw RouterException::routeNotFound($url);
        } catch (RouterException $e) {
            error_log('Router error: ' . $e->getMessage());
            if ($request->wantsJson()) {
                header('Content-Type: application/json');
                http_response_code($e->getCode() ?: 500);
                echo json_encode([
                    'success' => false,
                    'message' => $e->getMessage(),
                    'code' => $e->getCode() ?: 500
                ]);
                exit;
            }
            throw $e;
        }
    }

    public function url(string $name, array $params = []): string
    {
        if (!isset($this->namedRoutes[$name])) {
            throw RouterException::namedRouteNotFound($name);
        }
        return $this->namedRoutes[$name]->getUrl($params);
    }

    public function getRoutes(): array
    {
        return $this->routes;
    }

    public function getNamedRoutes(): array
    {
        return $this->namedRoutes;
    }

    public function hasNamedRoute(string $name): bool
    {
        return isset($this->namedRoutes[$name]);
    }

    public function getCurrentUrl(): string
    {
        return $this->url;
    }

    public function getPrefix(): string
    {
        return $this->prefix;
    }
}
