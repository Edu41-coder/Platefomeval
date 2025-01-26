<?php

namespace Core\Middleware;

use Core\Http\Request;
use Core\Http\Response;

/**
 * Interface pour les middlewares de l'application
 */
interface MiddlewareInterface
{
    /**
     * Gère la requête et applique les règles du middleware
     * 
     * @param Request $request Requête HTTP
     * @param callable $next Fonction suivante dans la chaîne des middlewares
     * @return Response Réponse HTTP
     * @throws MiddlewareException Si l'accès est refusé ou si une erreur survient
     */
    public function handle(Request $request, callable $next): Response;
}