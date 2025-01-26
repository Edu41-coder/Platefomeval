<?php

namespace Core\Router;


interface RouterInterface
{
    /**
     * Ajoute une route GET
     * 
     * @param string $path Le chemin de la route
     * @param array|callable $callable Le callback à exécuter
     * @param string|null $name Le nom de la route (optionnel)
     * @return Route
     */
    public function get(string $path, $callable, ?string $name = null): Route;

    /**
     * Ajoute une route POST
     * 
     * @param string $path Le chemin de la route
     * @param array|callable $callable Le callback à exécuter
     * @param string|null $name Le nom de la route (optionnel)
     * @return Route
     */
    public function post(string $path, $callable, ?string $name = null): Route;

    /**
     * Ajoute une route PUT
     * 
     * @param string $path Le chemin de la route
     * @param array|callable $callable Le callback à exécuter
     * @param string|null $name Le nom de la route (optionnel)
     * @return Route
     */
    public function put(string $path, $callable, ?string $name = null): Route;

    /**
     * Ajoute une route DELETE
     * 
     * @param string $path Le chemin de la route
     * @param array|callable $callable Le callback à exécuter
     * @param string|null $name Le nom de la route (optionnel)
     * @return Route
     */
    public function delete(string $path, $callable, ?string $name = null): Route;

    /**
     * Ajoute une route OPTIONS
     * 
     * @param string $path Le chemin de la route
     * @param array|callable $callable Le callback à exécuter
     * @param string|null $name Le nom de la route (optionnel)
     * @return Route
     */
    public function options(string $path, $callable, ?string $name = null): Route;

    /**
     * Ajoute une route pour toutes les méthodes HTTP
     * 
     * @param string $path Le chemin de la route
     * @param array|callable $callable Le callback à exécuter
     * @param string|null $name Le nom de la route (optionnel)
     * @return Route
     */
    public function any(string $path, $callable, ?string $name = null): Route;

    /**
     * Groupe des routes avec un préfixe commun
     * 
     * @param string $prefix Le préfixe pour toutes les routes du groupe
     * @param callable $callback La fonction qui définit les routes du groupe
     * @param array $middlewares Les middlewares à appliquer au groupe
     */
    public function group(string $prefix, callable $callback, array $middlewares = []): void;

    /**
     * Ajoute un middleware global au routeur
     * 
     * @param string|object $middleware Le middleware à ajouter
     * @param array $params Les paramètres optionnels du middleware
     * @return self
     */
    public function middleware($middleware, array $params = []): self;

    /**
     * Exécute le routeur
     * 
     * @throws RouterException Si aucune route ne correspond ou si une erreur survient
     * @return mixed Le résultat de l'exécution de la route
     */
    public function run();

    /**
     * Génère une URL à partir du nom de la route
     * 
     * @param string $name Le nom de la route
     * @param array $params Les paramètres de la route
     * @throws RouterException Si la route nommée n'existe pas
     * @return string L'URL générée
     */
    public function url(string $name, array $params = []): string;

    /**
     * Obtient toutes les routes enregistrées
     * 
     * @return array Les routes enregistrées, indexées par méthode HTTP
     */
    public function getRoutes(): array;

    /**
     * Obtient toutes les routes nommées
     * 
     * @return array Les routes nommées, indexées par leur nom
     */
    public function getNamedRoutes(): array;

    /**
     * Vérifie si une route nommée existe
     * 
     * @param string $name Le nom de la route à vérifier
     * @return bool True si la route existe, false sinon
     */
    public function hasNamedRoute(string $name): bool;

    /**
     * Obtient l'URL actuelle
     * 
     * @return string L'URL actuelle sans le préfixe
     */
    public function getCurrentUrl(): string;

    /**
     * Obtient le préfixe actuel du routeur
     * 
     * @return string Le préfixe actuel
     */
    public function getPrefix(): string;
}