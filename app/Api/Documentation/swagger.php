<?php

/**
 * @OA\Info(
 *     title="API Plateforme Évaluations",
 *     version="1.0.0",
 *     description="API pour la gestion des évaluations"
 * )
 * 
 * @OA\SecurityScheme(
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="JWT",
 *     securityScheme="bearerAuth"
 * )
 */

/**
 * @OA\Get(
 *     path="/api/evaluations",
 *     summary="Liste toutes les évaluations",
 *     tags={"Evaluations"},
 *     @OA\Parameter(
 *         name="page",
 *         in="query",
 *         description="Numéro de page",
 *         required=false,
 *         @OA\Schema(type="integer")
 *     ),
 *     @OA\Parameter(
 *         name="limit",
 *         in="query",
 *         description="Nombre d'éléments par page",
 *         required=false,
 *         @OA\Schema(type="integer")
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Liste des évaluations",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="evaluations", type="array",
 *                 @OA\Items(ref="#/components/schemas/Evaluation")
 *             ),
 *             @OA\Property(property="pagination", type="object",
 *                 @OA\Property(property="current_page", type="integer"),
 *                 @OA\Property(property="total_pages", type="integer"),
 *                 @OA\Property(property="total_items", type="integer")
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=401,
 *         description="Non authentifié"
 *     )
 * )
 */

/**
 * @OA\Post(
 *     path="/api/evaluations",
 *     summary="Crée une nouvelle évaluation",
 *     tags={"Evaluations"},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             required={"type", "date", "matiere_id"},
 *             @OA\Property(property="type", type="string", enum={"Examen", "Contrôle continu", "TP", "Projet", "Oral"}),
 *             @OA\Property(property="date", type="string", format="date"),
 *             @OA\Property(property="matiere_id", type="integer"),
 *             @OA\Property(property="description", type="string"),
 *             @OA\Property(property="notes", type="object",
 *                 @OA\AdditionalProperties(
 *                     type="number",
 *                     format="float",
 *                     minimum=0,
 *                     maximum=20
 *                 )
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=201,
 *         description="Évaluation créée"
 *     ),
 *     @OA\Response(
 *         response=400,
 *         description="Données invalides"
 *     ),
 *     @OA\Response(
 *         response=401,
 *         description="Non authentifié"
 *     )
 * )
 */

/**
 * @OA\Schema(
 *     schema="Evaluation",
 *     required={"id", "type", "date", "matiere_id"},
 *     @OA\Property(property="id", type="integer"),
 *     @OA\Property(property="type", type="string"),
 *     @OA\Property(property="date", type="string", format="date"),
 *     @OA\Property(property="matiere_id", type="integer"),
 *     @OA\Property(property="description", type="string"),
 *     @OA\Property(property="notes", type="object"),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * ) 