<?php

namespace Core\Model;

use App\Interfaces\Model\ModelInterface;
use Core\Database\Database;
use Core\Exception\DatabaseException;
use PDO;

abstract class BaseModel implements ModelInterface
{
    protected static string $table;
    protected static array $fillable = [];
    protected array $attributes = [];
    protected static ?Database $db = null;

    public function __construct(array $attributes = [])
    {
        $this->attributes = $attributes;
        if (self::$db === null) {
            self::$db = Database::getInstance();
        }
    }

    public function __set(string $name, $value): void
    {
        if (in_array($name, static::$fillable)) {
            $this->attributes[$name] = $value;
        }
    }

    public function __get(string $name)
    {
        return $this->attributes[$name] ?? null;
    }

    public static function findAll(): array
    {
        if (self::$db === null) {
            self::$db = Database::getInstance();
        }
        return self::$db->fetchAll("SELECT * FROM " . static::$table);
    }

    public static function find(int $id)
    {
        return static::findById($id);
    }

    public static function findById(int $id): ?self
    {
        if (self::$db === null) {
            self::$db = Database::getInstance();
        }
        $data = self::$db->fetchOne(
            "SELECT * FROM " . static::$table . " WHERE id = :id",
            ['id' => $id]
        );

        return $data ? new static($data) : null;
    }

    public static function findBy(string $column, $value): array
    {
        if (self::$db === null) {
            self::$db = Database::getInstance();
        }

        $sql = "SELECT * FROM " . static::$table . " WHERE {$column} = :value";
        return self::$db->fetchAll($sql, ['value' => $value]);
    }

    public static function findAllBy(string $column, $value): array
    {
        return static::findBy($column, $value);
    }
    public static function create(array $data): int
    {
        if (self::$db === null) {
            self::$db = Database::getInstance();
        }

        try {
            return self::$db->insert(static::$table, $data);
        } catch (DatabaseException $e) {
            error_log($e->getMessage());
            return 0;
        }
    }

    public function save(): bool
    {
        try {
            $data = array_intersect_key($this->attributes, array_flip(static::$fillable));

            if (isset($this->attributes['id'])) {
                return self::$db->update(static::$table, $this->attributes['id'], $data);
            } else {
                $id = self::$db->insert(static::$table, $data);
                if ($id) {
                    $this->attributes['id'] = $id;
                    return true;
                }
            }
            return false;
        } catch (DatabaseException $e) {
            error_log($e->getMessage());
            return false;
        }
    }

    public static function update(int $id, array $data): bool
    {
        if (self::$db === null) {
            self::$db = Database::getInstance();
        }

        try {
            return self::$db->update(static::$table, $id, $data);
        } catch (DatabaseException $e) {
            error_log($e->getMessage());
            return false;
        }
    }

    public static function exists(int $id): bool
    {
        if (self::$db === null) {
            self::$db = Database::getInstance();
        }

        $result = self::$db->fetchOne(
            "SELECT 1 FROM " . static::$table . " WHERE id = :id",
            ['id' => $id]
        );

        return !empty($result);
    }

    public static function delete(int $id): bool
    {
        if (self::$db === null) {
            self::$db = Database::getInstance();
        }

        try {
            return self::$db->delete(static::$table, $id);
        } catch (DatabaseException $e) {
            error_log($e->getMessage());
            return false;
        }
    }

    public function hydrate(array $data): ModelInterface
    {
        foreach ($data as $key => $value) {
            if (in_array($key, static::$fillable)) {
                $this->attributes[$key] = $value;
            }
        }
        return $this;
    }
    /**
     * Trouve des enregistrements selon plusieurs critères
     */
    public static function findByCriteria(array $criteria): array
    {
        if (self::$db === null) {
            self::$db = Database::getInstance();
        }

        $conditions = [];
        $params = [];
        foreach ($criteria as $key => $value) {
            $conditions[] = "$key = :$key";
            $params[$key] = $value;
        }

        $sql = "SELECT * FROM " . static::$table;
        if (!empty($conditions)) {
            $sql .= " WHERE " . implode(' AND ', $conditions);
        }

        return self::$db->fetchAll($sql, $params);
    }

    /**
     * Supprime l'instance courante
     */
    public function deleteInstance(): bool
    {
        if (!isset($this->attributes['id'])) {
            return false;
        }

        return static::delete($this->attributes['id']);
    }
    public function toArray(): array
    {
        return $this->attributes;
    }

    public function getId(): ?int
    {
        return $this->attributes['id'] ?? null;
    }

    public function getAttributes(): array
    {
        return $this->attributes;
    }

    public static function getFillable(): array
    {
        return static::$fillable;
    }

    public static function getTable(): string
    {
        return static::$table;
    }

    public static function getValidationRules(): array
    {
        return [];
    }
}
