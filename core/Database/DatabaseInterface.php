<?php

namespace Core\Database;

use PDO;
use PDOStatement;

interface DatabaseInterface
{
    /**
     * Obtient l'instance unique de la base de données
     */
    public static function getInstance(): self;

    /**
     * Obtient la connexion PDO
     */
    public function getConnection(): PDO;

    /**
     * Exécute une requête préparée
     * @param string $sql La requête SQL
     * @param array $params Les paramètres de la requête
     */
    public function prepare(string $sql, array $params = []): PDOStatement;
     public function query(string $sql, array $params = []): PDOStatement;

    /**
     * Récupère un seul enregistrement
     * @param string $sql La requête SQL
     * @param array $params Les paramètres de la requête
     */
    public function fetchOne(string $sql, array $params = []): ?array;

    /**
     * Récupère tous les enregistrements
     * @param string $sql La requête SQL
     * @param array $params Les paramètres de la requête
     */
    public function fetchAll(string $sql, array $params = []): array;

    /**
     * Insère des données et retourne l'ID
     * @param string $table Le nom de la table
     * @param array $data Les données à insérer
     */
    public function insert(string $table, array $data): int;

    /**
     * Met à jour des données
     * @param string $table Le nom de la table
     * @param int $id L'ID de l'enregistrement
     * @param array $data Les données à mettre à jour
     */
    public function update(string $table, int $id, array $data): bool;

    /**
     * Supprime un enregistrement
     * @param string $table Le nom de la table
     * @param int $id L'ID de l'enregistrement
     */
    public function delete(string $table, int $id): bool;

    /**
     * Commence une transaction
     */
    public function beginTransaction(): bool;

    /**
     * Valide une transaction
     */
    public function commit(): bool;

    /**
     * Annule une transaction
     */
    public function rollback(): bool;
}