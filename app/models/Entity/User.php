<?php

namespace App\Models\Entity;

use Core\Model\BaseModel;
use Core\Database\Database;
use App\Models\Entity\Evaluation;

class User extends BaseModel
{
    protected static string $table = 'users';
    
    protected static array $fillable = [
        'nom',
        'prenom',
        'email',
        'password',
        'adresse',
        'role_id',
        'is_admin'
    ];

    protected ?float $moyenne = null;
    protected ?string $derniere_eval = null;

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        if (!isset($this->attributes['password']) && isset($attributes['password'])) {
            $this->attributes['password'] = $attributes['password'];
        }
    }

    /**
     * Getters
     */
    public function getId(): ?int
    {
        return $this->attributes['id'] ?? null;
    }

    public function getNom(): string
    {
        return $this->attributes['nom'] ?? '';
    }

    public function getPrenom(): string
    {
        return $this->attributes['prenom'] ?? '';
    }

    public function getEmail(): string
    {
        return $this->attributes['email'] ?? '';
    }

    public function getAdresse(): ?string
    {
        return $this->attributes['adresse'] ?? null;
    }

    public function getRoleId(): int
    {
        return $this->attributes['role_id'] ?? 0;
    }

    public function isAdmin(): bool
    {
        return $this->attributes['is_admin'] ?? false;
    }

    public function getPassword(): ?string
    {
        return $this->attributes['password'] ?? null;
    }

    public function getCreatedAt(): string
    {
        return $this->attributes['created_at'] ?? '';
    }

    public function getMoyenne(): ?float
    {
        return $this->moyenne;
    }

    public function getDerniereEval(): ?string
    {
        return $this->derniere_eval;
    }

    /**
     * Retourne le nom complet de l'utilisateur
     * 
     * @return string
     */
    public function getFullName(): string
    {
        return $this->nom . ' ' . $this->prenom;
    }

    /**
     * Setters
     */
    public function setNom(string $nom): void
    {
        $this->attributes['nom'] = $nom;
    }

    public function setPrenom(string $prenom): void
    {
        $this->attributes['prenom'] = $prenom;
    }

    public function setEmail(string $email): void
    {
        if (!self::validateEmail($email)) {
            throw new \InvalidArgumentException('Adresse email invalide. Utilisez une adresse @univ-fr.net');
        }
        $this->attributes['email'] = $email;
    }

    public function setPassword(string $password): void
    {
        $this->attributes['password'] = password_hash($password, PASSWORD_DEFAULT);
        
        if (self::$db === null) {
            self::$db = Database::getInstance();
        }
        
        self::$db->update(self::$table, $this->getId(), [
            'password' => $this->attributes['password']
        ]);
    }

    public function setAdresse(?string $adresse): void
    {
        $this->attributes['adresse'] = $adresse;
    }

    public function setRoleId(int $roleId): void
    {
        $this->attributes['role_id'] = $roleId;
    }

    public function setIsAdmin(bool $isAdmin): void
    {
        $this->attributes['is_admin'] = $isAdmin;
    }

    public function setMoyenne(?float $moyenne): void
    {
        $this->moyenne = $moyenne;
    }

    public function setDerniereEval(?string $derniere_eval): void
    {
        $this->derniere_eval = $derniere_eval;
    }

    public function setCreatedAt(\DateTime $date): void 
    {
        $this->attributes['created_at'] = $date->format('Y-m-d H:i:s');
    }

    public function setUpdatedAt(\DateTime $date): void 
    {
        $this->attributes['updated_at'] = $date->format('Y-m-d H:i:s');
    }

    public function setStatus(string $status): void 
    {
        $this->attributes['status'] = $status;
    }

    public function setRole(string $roleName): void 
    {
        $role = Role::findByName($roleName);
        if ($role) {
            $this->setRoleId($role->getId());
        }
    }

    /**
     * Méthodes spécifiques au modèle User
     */
    public static function findByEmail(string $email): ?self
    {
        if (self::$db === null) {
            self::$db = Database::getInstance();
        }
        
        $data = self::$db->fetchOne(
            "SELECT * FROM " . self::$table . " WHERE email = :email",
            ['email' => $email]
        );
        
        return $data ? new static($data) : null;
    }

    public function verifyPassword(string $password): bool
    {
        try {
            $storedPassword = $this->attributes['password'] ?? null;
            if (!$storedPassword) {
                error_log('No password stored for user: ' . $this->getEmail());
                return false;
            }

            $result = password_verify($password, $storedPassword);
            error_log('Password verification result for user ' . $this->getEmail() . ': ' . ($result ? 'true' : 'false'));
            return $result;
        } catch (\Throwable $e) {
            error_log('Error verifying password: ' . $e->getMessage());
            return false;
        }
    }
        /**
     * Supprime un utilisateur par son ID
     */
    public static function deleteById(int $id): bool
    {
        if (self::$db === null) {
            self::$db = Database::getInstance();
        }

        try {
            return self::$db->delete(self::$table, $id);
        } catch (\Exception $e) {
            error_log("Erreur lors de la suppression de l'utilisateur #$id: " . $e->getMessage());
            return false;
        }
    }

    public function toArray(): array
    {
        $data = parent::toArray();
        unset($data['password']);
        return $data;
    }

    public function hasRole(string $role): bool
    {
        $userRole = $this->getRole();
        return $userRole && $userRole->getName() === $role;
    }

    public function getRole(): ?Role
    {
        return Role::find($this->getRoleId());
    }

    public function isStudent(): bool
    {
        $role = $this->getRole();
        return $role && $role->isEtudiant();
    }

    public function isProfessor(): bool
    {
        $role = $this->getRole();
        return $role && $role->isProfesseur();
    }

    public static function getAllStudents(): array
    {
        if (self::$db === null) {
            self::$db = Database::getInstance();
        }

        $studentRole = Role::findByName(Role::ETUDIANT);
        if (!$studentRole) {
            return [];
        }

        $data = self::$db->fetchAll(
            "SELECT * FROM " . self::$table . " WHERE role_id = :role_id",
            ['role_id' => $studentRole->getId()]
        );

        return array_map(function($userData) {
            return new static($userData);
        }, $data);
    }

    public static function getAllProfessors(): array
    {
        if (self::$db === null) {
            self::$db = Database::getInstance();
        }

        $professorRole = Role::findByName(Role::PROFESSEUR);
        if (!$professorRole) {
            return [];
        }

        $data = self::$db->fetchAll(
            "SELECT * FROM " . self::$table . " WHERE role_id = :role_id",
            ['role_id' => $professorRole->getId()]
        );

        return array_map(function($userData) {
            return new static($userData);
        }, $data);
    }

    public static function countStudents(): int
    {
        if (self::$db === null) {
            self::$db = Database::getInstance();
        }

        $studentRole = Role::findByName(Role::ETUDIANT);
        if (!$studentRole) {
            return 0;
        }

        $result = self::$db->fetchOne(
            "SELECT COUNT(*) as count FROM " . self::$table . " WHERE role_id = :role_id",
            ['role_id' => $studentRole->getId()]
        );

        return (int)($result['count'] ?? 0);
    }

    public static function validateEmail(string $email): bool
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) 
            && preg_match('/@univ-fr\.net$/', $email);
    }

    public function getMoyenneMatiere(int $matiereId): ?float
    {
        if (self::$db === null) {
            self::$db = Database::getInstance();
        }

        try {
            $result = self::$db->fetchOne(
                "SELECT AVG(note) as moyenne FROM evaluations 
                WHERE etudiant_id = :etudiant_id AND matiere_id = :matiere_id",
                [
                    'etudiant_id' => $this->getId(),
                    'matiere_id' => $matiereId
                ]
            );

            return $result && $result['moyenne'] !== null ? (float)$result['moyenne'] : null;
        } catch (\Exception $e) {
            error_log("Erreur lors du calcul de la moyenne: " . $e->getMessage());
            return null;
        }
    }

    public function getLastEvaluationMatiere(int $matiereId): ?Evaluation
    {
        if (self::$db === null) {
            self::$db = Database::getInstance();
        }

        try {
            $result = self::$db->fetchOne(
                "SELECT * FROM evaluations 
                WHERE etudiant_id = :etudiant_id AND matiere_id = :matiere_id 
                ORDER BY date_evaluation DESC 
                LIMIT 1",
                [
                    'etudiant_id' => $this->getId(),
                    'matiere_id' => $matiereId
                ]
            );

            return $result ? new Evaluation($result) : null;
        } catch (\Exception $e) {
            error_log("Erreur lors de la récupération de la dernière évaluation: " . $e->getMessage());
            return null;
        }
    }
}