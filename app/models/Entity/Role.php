<?php

namespace App\Models\Entity;

use Core\Database\Database;
use DateTime;

/**
 * Modèle pour la table roles
 */
class Role
{
    /**
     * Nom de la table
     */
    protected static string $table = 'roles';

    /**
     * Attributs de l'entité
     */
    protected ?int $id = null;
    protected string $name;
    protected DateTime $created_at;
    protected DateTime $updated_at;

    /**
     * Constantes pour les noms de rôles
     */
    public const ADMIN = 'admin';
    public const PROFESSEUR = 'professeur';
    public const ETUDIANT = 'etudiant';

    /**
     * Instance de la base de données
     */
    protected static ?Database $db = null;

    /**
     * Initialise la connexion à la base de données si nécessaire
     */
    protected static function initDb(): void
    {
        if (self::$db === null) {
            self::$db = Database::getInstance();
        }
    }

    /**
     * Constructeur
     */
    public function __construct(array $data = [])
    {
        $this->hydrate($data);
    }

    /**
     * Hydrate l'objet avec les données
     */
    protected function hydrate(array $data): void
    {
        foreach ($data as $key => $value) {
            $method = 'set' . str_replace('_', '', ucwords($key, '_'));
            if (method_exists($this, $method)) {
                if (in_array($key, ['created_at', 'updated_at']) && !($value instanceof DateTime)) {
                    $value = new DateTime($value);
                }
                $this->$method($value);
            }
        }
    }

    // Getters
    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getCreatedAt(): DateTime
    {
        return $this->created_at;
    }

    public function getUpdatedAt(): DateTime
    {
        return $this->updated_at;
    }

    // Setters
    public function setId(int $id): self
    {
        $this->id = $id;
        return $this;
    }

    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function setCreatedAt(DateTime $created_at): self
    {
        $this->created_at = $created_at;
        return $this;
    }

    public function setUpdatedAt(DateTime $updated_at): self
    {
        $this->updated_at = $updated_at;
        return $this;
    }

    /**
     * Trouve un rôle par son ID
     */
    public static function find(int $id): ?self
    {
        self::initDb();
        try {
            $data = self::$db->fetchOne(
                "SELECT * FROM " . self::$table . " WHERE id = ?",
                [$id]
            );
            return $data ? new self($data) : null;
        } catch (\Exception $e) {
            error_log("Error finding role by ID $id: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Trouve un rôle par son nom
     */
    public static function findByName(string $name): ?self
    {
        self::initDb();
        $data = self::$db->fetchOne(
            "SELECT * FROM " . self::$table . " WHERE name = ?",
            [$name]
        );
        return $data ? new self($data) : null;
    }

    /**
     * Récupère tous les rôles
     */
    public static function all(): array
    {
        self::initDb();
        $roles = [];
        $data = self::$db->fetchAll("SELECT * FROM " . self::$table);
        foreach ($data as $roleData) {
            $roles[] = new self($roleData);
        }
        return $roles;
    }

    /**
     * Sauvegarde le rôle
     */
    public function save(): bool
    {
        $data = [
            'name' => $this->name
        ];

        if ($this->id === null) {
            $id = self::$db->insert(self::$table, $data);
            if ($id) {
                $this->id = $id;
                return true;
            }
            return false;
        }

        return self::$db->update(self::$table, $this->id, $data);
    }

    /**
     * Supprime le rôle
     */
    public function delete(): bool
    {
        if ($this->id === null) {
            return false;
        }
        return self::$db->delete(self::$table, $this->id);
    }

    /**
     * Vérifie si c'est un rôle admin
     */
    public function isAdmin(): bool
    {
        return $this->name === self::ADMIN;
    }

    /**
     * Vérifie si c'est un rôle professeur
     */
    public function isProfesseur(): bool
    {
        return $this->name === self::PROFESSEUR;
    }

    /**
     * Vérifie si c'est un rôle étudiant
     */
    public function isEtudiant(): bool
    {
        return $this->name === self::ETUDIANT;
    }

    /**
     * Convertit l'objet en tableau
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at->format('Y-m-d H:i:s')
        ];
    }

    /**
     * Obtient la liste de tous les rôles disponibles
     */
    public static function getAvailableRoles(): array
    {
        return [
            self::ADMIN,
            self::PROFESSEUR,
            self::ETUDIANT
        ];
    }

    /**
     * Vérifie si un nom de rôle est valide
     */
    public static function isValidRole(string $role): bool
    {
        return in_array($role, self::getAvailableRoles());
    }
}