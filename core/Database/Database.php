<?php

namespace Core\Database;

use PDO;
use PDOException;
use PDOStatement;
use Core\Exception\DatabaseException;

class Database implements DatabaseInterface
{
    private static ?Database $instance = null;
    private PDO $connection;
    private array $config;
    private array $appConfig;

    /**
     * Constructeur privé (pattern Singleton)
     */
    private function __construct()
    {
        try {
            // Charger les configurations
            $this->loadConfigurations();

            // Construction du DSN avec la config
            $dbConfig = $this->config['mysql'];
            $dsn = sprintf(
                "%s:host=%s;port=%s;dbname=%s;charset=%s",
                $dbConfig['driver'],
                $dbConfig['host'],
                $dbConfig['port'],
                $dbConfig['database'],
                $dbConfig['charset']
            );

            // Création de la connexion PDO
            $this->connection = new PDO(
                $dsn,
                $dbConfig['username'],
                $dbConfig['password'],
                $dbConfig['options']
            );
        } catch (PDOException $e) {
            $this->handleError(
                "Erreur de connexion à la base de données.",
                DatabaseException::CONNECTION_ERROR,
                $e
            );
        }
    }

    /**
     * Charge la configuration depuis les variables d'environnement
     */
    private function loadConfigurations(): void
    {
        $this->config = [
            'mysql' => [
                'driver' => 'mysql',
                'host' => $_ENV['DB_HOST'],
                'port' => $_ENV['DB_PORT'],
                'database' => $_ENV['DB_NAME'],
                'username' => $_ENV['DB_USER'],
                'password' => $_ENV['DB_PASSWORD'],
                'charset' => $_ENV['DB_CHARSET'] ?? 'utf8mb4',
                'options' => [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                    'retry' => [
                        'times' => 3,
                        'delay' => 100
                    ]
                ]
            ]
        ];

        $this->appConfig = [
            'debug' => $_ENV['APP_DEBUG'] === 'true'
        ];
    }

    /**
     * Gère les erreurs de base de données
     */
    private function handleError(string $message, int $code, \Throwable $previous): void
    {
        // Log l'erreur
        error_log($previous->getMessage());

        // En mode debug, inclure plus de détails
        if ($this->appConfig['debug']) {
            throw new DatabaseException(
                $message . ' ' . $previous->getMessage(),
                $code,
                $previous
            );
        }
        throw new DatabaseException($message, $code);
    }
    /**
     * Empêche le clonage de l'instance (pattern Singleton)
     */
    private function __clone() {}

    /**
     * Obtient l'instance unique de la base de données
     */
    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Obtient la connexion PDO
     */
    public function getConnection(): PDO
    {
        return $this->connection;
    }

    /**
     * Prépare et exécute une requête SQL
     */
    public function prepare(string $sql, array $params = []): PDOStatement
    {
        return $this->query($sql, $params);
    }

    /**
     * Exécute une requête préparée avec système de retry
     */
    public function query(string $sql, array $params = []): PDOStatement
    {
        try {
            $stmt = $this->connection->prepare($sql);

            // Utiliser les options de retry si configurées
            $retryTimes = $this->config['mysql']['options']['retry']['times'] ?? 1;
            $retryDelay = $this->config['mysql']['options']['retry']['delay'] ?? 100;

            $attempt = 0;
            do {
                try {
                    $stmt->execute($params);
                    return $stmt;
                } catch (PDOException $e) {
                    $attempt++;
                    if ($attempt >= $retryTimes) {
                        throw $e;
                    }
                    usleep($retryDelay * 1000);
                }
            } while ($attempt < $retryTimes);

            throw new PDOException('Maximum retry attempts reached');
        } catch (PDOException $e) {
            throw new DatabaseException(
                "Erreur lors de l'exécution de la requête: " . $e->getMessage(),
                DatabaseException::QUERY_ERROR,
                $e
            );
        }
    }

    /**
     * Récupère un seul enregistrement
     */
    public function fetchOne(string $sql, array $params = []): ?array
    {
        try {
            $stmt = $this->query($sql, $params);
            $result = $stmt->fetch();
            return $result !== false ? $result : null;
        } catch (PDOException $e) {
            $this->handleError(
                "Erreur lors de la récupération de l'enregistrement.",
                DatabaseException::QUERY_ERROR,
                $e
            );
            return null;
        }
    }

    /**
     * Récupère tous les enregistrements
     */
    public function fetchAll(string $sql, array $params = []): array
    {
        try {
            $result = $this->query($sql, $params)->fetchAll();
            return $result ?: [];
        } catch (PDOException $e) {
            $this->handleError(
                "Erreur lors de la récupération des enregistrements.",
                DatabaseException::QUERY_ERROR,
                $e
            );
            return [];
        }
    }

    /**
     * Insère des données et retourne l'ID
     */
    public function insert(string $table, array $data): int
    {
        try {
            $fields = array_keys($data);
            $values = array_map(fn($field) => ":$field", $fields);

            $sql = sprintf(
                "INSERT INTO %s (%s) VALUES (%s)",
                $table,
                implode(', ', $fields),
                implode(', ', $values)
            );

            $this->query($sql, $data);
            return (int)$this->connection->lastInsertId();
        } catch (PDOException $e) {
            $this->handleError(
                "Erreur lors de l'insertion des données.",
                DatabaseException::QUERY_ERROR,
                $e
            );
            return 0;
        }
    }

    /**
     * Met à jour des données
     */
    public function update(string $table, int $id, array $data): bool
    {
        try {
            $fields = array_map(
                fn($field) => "$field = :$field",
                array_keys($data)
            );

            $sql = sprintf(
                "UPDATE %s SET %s WHERE id = :id",
                $table,
                implode(', ', $fields)
            );

            $data['id'] = $id;
            return $this->query($sql, $data)->rowCount() > 0;
        } catch (PDOException $e) {
            $this->handleError(
                "Erreur lors de la mise à jour des données.",
                DatabaseException::QUERY_ERROR,
                $e
            );
            return false;
        }
    }

    /**
     * Supprime un enregistrement
     */
    public function delete(string $table, int $id): bool
    {
        try {
            $sql = "DELETE FROM $table WHERE id = :id";
            return $this->query($sql, ['id' => $id])->rowCount() > 0;
        } catch (PDOException $e) {
            $this->handleError(
                "Erreur lors de la suppression de l'enregistrement.",
                DatabaseException::QUERY_ERROR,
                $e
            );
            return false;
        }
    }

    /**
     * Gestion des transactions
     */
    public function beginTransaction(): bool
    {
        try {
            return $this->connection->beginTransaction();
        } catch (PDOException $e) {
            $this->handleError(
                "Erreur lors du début de la transaction.",
                DatabaseException::TRANSACTION_ERROR,
                $e
            );
            return false;
        }
    }

    public function commit(): bool
    {
        try {
            return $this->connection->commit();
        } catch (PDOException $e) {
            $this->handleError(
                "Erreur lors de la validation de la transaction.",
                DatabaseException::TRANSACTION_ERROR,
                $e
            );
            return false;
        }
    }

    public function rollback(): bool
    {
        try {
            return $this->connection->rollBack();
        } catch (PDOException $e) {
            $this->handleError(
                "Erreur lors de l'annulation de la transaction.",
                DatabaseException::TRANSACTION_ERROR,
                $e
            );
            return false;
        }
    }

    /**
     * Vérifie si un enregistrement existe dans la table
     */
    public function exists(string $table, $id): bool
    {
        try {
            $stmt = $this->prepare(
                "SELECT 1 FROM {$table} WHERE id = ? LIMIT 1",
                [$id]
            );
            return (bool) $stmt->fetch();
        } catch (PDOException $e) {
            $this->handleError(
                "Erreur lors de la vérification de l'existence.",
                DatabaseException::QUERY_ERROR,
                $e
            );
            return false;
        }
    }

    /**
     * Retourne l'ID de la dernière ligne insérée
     */
    public function lastInsertId(): string
    {
        return $this->connection->lastInsertId();
    }
}
