<?php

namespace App\Models\Entity;

use Core\Model\BaseModel;

class Photo extends BaseModel
{
    protected static string $table = 'photos';

    protected static array $fillable = [
        'user_id',
        'filename'
    ];

    public function getUserId(): int
    {
        return $this->attributes['user_id'];
    }

    public function setUserId(int $userId): void
    {
        $this->attributes['user_id'] = $userId;
    }

    public function getFilename(): string
    {
        return $this->attributes['filename'];
    }

    public function setFilename(string $filename): void
    {
        $this->attributes['filename'] = $filename;
    }

    public function getFullPath(): string
    {
        return 'uploads/profile_photos/' . $this->getFilename();
    }
} 