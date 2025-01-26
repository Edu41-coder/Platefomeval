<?php
namespace App\Models\Repository;

use App\Models\Entity\Role;
use Core\Database\Database;

class RoleRepository
{
    private Database $db;
    protected string $table = 'roles';

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function find(int $id): ?Role
    {
        try {
            $data = $this->db->fetchOne(
                "SELECT * FROM {$this->table} WHERE id = ?",
                [$id]
            );
            return $data ? new Role($data) : null;
        } catch (\Exception $e) {
            error_log("Error finding role by ID $id: " . $e->getMessage());
            return null;
        }
    }

    public function findByName(string $name): ?Role
    {
        $data = $this->db->fetchOne(
            "SELECT * FROM {$this->table} WHERE name = ?",
            [$name]
        );
        return $data ? new Role($data) : null;
    }

    public function findAll(): array
    {
        $roles = [];
        $data = $this->db->fetchAll("SELECT * FROM {$this->table}");
        foreach ($data as $roleData) {
            $roles[] = new Role($roleData);
        }
        return $roles;
    }

    public function save(Role $role): bool
    {
        $data = $role->toArray();
        unset($data['id'], $data['created_at'], $data['updated_at']);

        if ($role->getId() === null) {
            $id = $this->db->insert($this->table, $data);
            if ($id) {
                $role->setId($id);
                return true;
            }
            return false;
        }

        return $this->db->update($this->table, $role->getId(), $data);
    }

    public function delete(Role $role): bool
    {
        if ($role->getId() === null) {
            return false;
        }
        return $this->db->delete($this->table, $role->getId());
    }
} 