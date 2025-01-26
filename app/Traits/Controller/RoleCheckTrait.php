<?php

namespace App\Traits\Controller;

use App\Models\Entity\Role;

trait RoleCheckTrait
{
    /**
     * Vérifie si l'utilisateur est un étudiant
     */
    public function isStudent(): bool
    {
        return $this->checkRole(function($role) {
            return $role->isEtudiant();
        });
    }

    /**
     * Vérifie si l'utilisateur est un professeur
     */
    public function isProfessor(): bool
    {
        return $this->checkRole(function($role) {
            return $role->isProfesseur();
        });
    }

    /**
     * Vérifie si l'utilisateur est un administrateur
     */
    public function isAdmin(): bool
    {
        if (!$this->isAuthenticated()) {
            return false;
        }
        $user = $this->getUser();
        return $user['is_admin'] ?? false;
    }

    /**
     * Méthode utilitaire pour vérifier un rôle
     * 
     * @param callable $roleCheck Fonction de vérification du rôle
     */
    private function checkRole(callable $roleCheck): bool
    {
        if (!$this->isAuthenticated()) {
            return false;
        }

        $user = $this->getUser();
        if (!$user || !isset($user['role_id'])) {
            return false;
        }

        $role = Role::find($user['role_id']);
        if (!$role) {
            return false;
        }

        return $roleCheck($role);
    }
}