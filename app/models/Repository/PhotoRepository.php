<?php

namespace App\Models\Repository;

use App\Interfaces\Repository\PhotoRepositoryInterface;
use App\Models\Entity\Photo;
use Core\Database\DatabaseInterface;
use Core\Exception\RepositoryException;

class PhotoRepository implements PhotoRepositoryInterface
{
    private DatabaseInterface $db;
    protected static string $table = 'photos';

    public function __construct(DatabaseInterface $db)
    {
        $this->db = $db;
    }

    public function findByUserId(int $userId): ?Photo
    {
        try {
            $result = $this->db->fetchOne(
                "SELECT * FROM " . static::$table . " WHERE user_id = :user_id",
                ['user_id' => $userId]
            );
            return $result ? new Photo($result) : null;
        } catch (\Exception $e) {
            throw new RepositoryException('Error finding photo: ' . $e->getMessage());
        }
    }

    public function create(array $data): int
    {
        try {
            return $this->db->insert(static::$table, $data);
        } catch (\Exception $e) {
            throw new RepositoryException('Error creating photo: ' . $e->getMessage());
        }
    }

    public function update(int $id, array $data): bool
    {
        try {
            return (bool) $this->db->update(static::$table, $id, $data);
        } catch (\Exception $e) {
            throw new RepositoryException('Error updating photo: ' . $e->getMessage());
        }
    }

    public function delete(int $id): bool
    {
        try {
            return (bool) $this->db->delete(static::$table, $id);
        } catch (\Exception $e) {
            throw new RepositoryException('Error deleting photo: ' . $e->getMessage());
        }
    }
} 