<?php

namespace App\Interfaces\Repository;

use App\Models\Entity\Photo;

interface PhotoRepositoryInterface
{
    public function findByUserId(int $userId): ?Photo;
    public function create(array $data): int;
    public function update(int $id, array $data): bool;
    public function delete(int $id): bool;
} 