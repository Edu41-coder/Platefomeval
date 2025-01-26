<?php

namespace App\Interfaces\Controller;

interface RoleCheckInterface
{
    public function isStudent(): bool;
    public function isProfessor(): bool;
    public function isAdmin(): bool;
}