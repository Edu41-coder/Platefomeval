<?php

namespace App\Interfaces\Controller;

use Core\Http\Request;
use Core\Http\Response;
use Core\Exception\ValidatorException;

/**
 * Interface pour les contrôleurs de l'application
 */
interface ControllerInterface
{
    /**
     * Initialise le contrôleur
     *
     * @return void
     */
    public function initialize(): void;

    /**
     * Page d'accueil du contrôleur
     *
     * @param Request $request Requête HTTP
     * @param Response $response Réponse HTTP
     * @return Response
     */
    public function index(Request $request, Response $response): Response;

    /**
     * Obtient la requête HTTP
     *
     * @return Request
     */
    public function getRequest(): Request;

    /**
     * Définit la requête HTTP
     *
     * @param Request $request Requête HTTP
     * @return self
     */
    public function setRequest(Request $request): self;

    /**
     * Obtient la réponse HTTP
     *
     * @return Response
     */
    public function getResponse(): Response;

    /**
     * Définit la réponse HTTP
     *
     * @param Response $response Réponse HTTP
     * @return self
     */
    public function setResponse(Response $response): self;

    /**
     * Vérifie si la requête est de type AJAX
     *
     * @return bool
     */
    public function isAjax(): bool;

    /**
     * Vérifie si la requête attend une réponse JSON
     *
     * @return bool
     */
    public function wantsJson(): bool;

    /**
     * Renvoie une réponse JSON
     *
     * @param mixed $data Données à envoyer
     * @param int $status Code de statut HTTP
     * @return Response
     */
    public function json($data, int $status = 200): Response;

    /**
     * Renvoie une réponse avec redirection
     *
     * @param string $url URL de redirection
     * @param int $status Code de statut HTTP
     * @return Response
     */
    public function redirect(string $url, int $status = 302): Response;

    /**
     * Renvoie une vue
     *
     * @param string $view Nom de la vue
     * @param array $data Données à passer à la vue
     * @return Response
     */
    public function render(string $view, array $data = []): Response;

    /**
     * Vérifie si l'utilisateur est authentifié
     *
     * @return bool
     */
    public function isAuthenticated(): bool;

    /**
     * Obtient l'utilisateur authentifié
     *
     * @return array|null Données de l'utilisateur ou null si non authentifié
     */
    public function getUser(): ?array;

    /**
     * Vérifie si l'utilisateur a un rôle spécifique
     *
     * @param string $role Rôle à vérifier
     * @return bool
     */
    public function hasRole(string $role): bool;

    /**
     * Vérifie si l'utilisateur a une permission spécifique
     *
     * @param string $permission Permission à vérifier
     * @return bool
     */
    public function hasPermission(string $permission): bool;

    /**
     * Valide les données de la requête
     *
     * @param array $rules Règles de validation
     * @return array Données validées
     * @throws ValidatorException Si la validation échoue
     */
    public function validate(array $rules): array;

    /**
     * Gère une erreur
     *
     * @param \Throwable $e Exception à gérer
     * @return Response
     */
    public function handleError(\Throwable $e): Response;
}