<?php

namespace App\Models\Repository;

use App\Interfaces\Repository\UserRepositoryInterface;
use App\Models\Entity\User;
use Core\Database\Database;
use Core\Exception\DatabaseException;
use Core\Exception\RepositoryException;

class UserRepository implements UserRepositoryInterface
{
    private array $cache = [];
    private $db;
    protected static string $table = 'users';

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function findAll(array $criteria = [], array $orderBy = ['created_at' => 'DESC']): array
    {
        try {
            $sql = "SELECT * FROM " . static::$table;
            $params = [];

            // Ajouter les critères WHERE si présents
            if (!empty($criteria)) {
                $sql .= " WHERE ";
                $conditions = [];
                foreach ($criteria as $field => $value) {
                    $conditions[] = "$field = :$field";
                    $params[$field] = $value;
                }
                $sql .= implode(" AND ", $conditions);
            }

            // Ajouter ORDER BY si présent
            if (!empty($orderBy)) {
                $sql .= " ORDER BY ";
                $orders = [];
                foreach ($orderBy as $field => $direction) {
                    $orders[] = "`$field` $direction";
                }
                $sql .= implode(", ", $orders);
            }

            $results = $this->db->fetchAll($sql, $params);
            return array_map(function($result) {
                return new User($result);
            }, $results);
        } catch (DatabaseException $e) {
            throw RepositoryException::queryError('User', 'findAll', $e->getMessage());
        }
    }

    public function findAllPaginated(
        int $page = 1,
        int $perPage = 10,
        string $sort = 'created_at',
        string $order = 'DESC',
        ?string $role = null
    ): array {
        try {
            $offset = ($page - 1) * $perPage;
            
            $sql = "SELECT * FROM " . static::$table;
            $params = [];

            if ($role !== null) {
                $sql .= " WHERE role_id = :role";
                $params['role'] = $role;
            }

            $sql .= " ORDER BY `$sort` $order";
            $sql .= " LIMIT :limit OFFSET :offset";
            
            $params['limit'] = $perPage;
            $params['offset'] = $offset;

            $results = $this->db->fetchAll($sql, $params);
            return array_map(function($result) {
                return new User($result);
            }, $results);
        } catch (DatabaseException $e) {
            throw RepositoryException::queryError('User', 'findAllPaginated', $e->getMessage());
        }
    }

    public function getStats(): array
    {
        try {
            $stats = [
                'total' => 0,
                'professeurs' => 0,
                'etudiants' => 0
            ];

            $sql = "SELECT role_id, COUNT(*) as count FROM " . static::$table . " GROUP BY role_id";
            $results = $this->db->fetchAll($sql);

            $stats['total'] = array_sum(array_column($results, 'count'));

            foreach ($results as $result) {
                if ($result['role_id'] === 2) {
                    $stats['professeurs'] = $result['count'];
                } elseif ($result['role_id'] === 3) {
                    $stats['etudiants'] = $result['count'];
                }
            }

            return $stats;
        } catch (DatabaseException $e) {
            throw RepositoryException::queryError('User', 'getStats', $e->getMessage());
        }
    }

    public function findById(int $id): ?User
    {
        // Check cache first
        if (isset($this->cache[$id])) {
            return $this->cache[$id];
        }

        try {
            $result = $this->db->query(
                "SELECT * FROM users WHERE id = ?",
                [$id]
            )->fetch();

            if (!$result) {
                return null;
            }

            $user = new User($result);
            // Cache the result
            $this->cache[$id] = $user;
            return $user;
        } catch (\Exception $e) {
            error_log('Erreur lors de la récupération de l\'utilisateur: ' . $e->getMessage());
            return null;
        }
    }

    public function findByEmail(string $email): ?User
    {
        try {
            $result = $this->db->fetchOne(
                'SELECT * FROM users WHERE email = :email',
                ['email' => $email]
            );
            return $result ? new User($result) : null;
        } catch (DatabaseException $e) {
            throw RepositoryException::queryError('User', 'findByEmail', $e->getMessage());
        }
    }

    public function create(array $data): int
    {
        try {
            $id = $this->db->insert('users', [
                'nom' => $data['nom'],
                'prenom' => $data['prenom'],
                'email' => $data['email'],
                'password' => $data['password'],
                'adresse' => $data['adresse'] ?? null,
                'role_id' => $data['role_id'],
                'is_admin' => $data['is_admin'] ?? false
            ]);

            if (!$id) {
                throw RepositoryException::createError('User', 'Échec de la création');
            }

            return $id;
        } catch (DatabaseException $e) {
            throw RepositoryException::createError('User', $e->getMessage(), $data);
        }
    }

    public function update(int $id, array $data): bool
    {
        try {
            if (isset($data['password'])) {
                $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
            }

            $allowedFields = [
                'nom',
                'prenom',
                'email',
                'password',
                'adresse',
                'role_id',
                'is_admin'
            ];
            $filteredData = array_intersect_key($data, array_flip($allowedFields));

            // Check if the address already exists for another user
            if (isset($filteredData['adresse'])) {
                $addressExists = $this->addressExists($filteredData['adresse'], $id);
                if ($addressExists) {
                    throw new \Exception("Cette adresse est déjà utilisée par un autre utilisateur");
                }
            }

            $updateResult = $this->db->update('users', $id, $filteredData);

            if (!$updateResult) {
                throw new \Exception('La mise à jour a échoué');
            }

            return true;
        } catch (DatabaseException $e) {
            error_log('Erreur lors de la mise à jour de l\'utilisateur: ' . $e->getMessage());
            throw RepositoryException::updateError('User', $id, $e->getMessage());
        } catch (\Exception $e) {
            error_log('Erreur lors de la mise à jour de l\'utilisateur: ' . $e->getMessage());
            throw $e;
        }
    }

    public function delete(int $id): bool
    {
        $sql = "DELETE FROM users WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute(['id' => $id]);
    }

    public function verifyCredentials(string $email, string $password): ?User
    {
        try {
            $user = $this->findByEmail($email);
            if ($user && password_verify($password, $user->getPassword())) {
                return $user;
            }
            return null;
        } catch (DatabaseException $e) {
            throw RepositoryException::queryError('User', 'verifyCredentials', $e->getMessage());
        }
    }

    public function emailExists(string $email, ?int $excludeId = null): bool
    {
        try {
            $sql = 'SELECT COUNT(*) as count FROM users WHERE email = :email';
            $params = ['email' => $email];

            if ($excludeId !== null) {
                $sql .= ' AND id != :id';
                $params['id'] = $excludeId;
            }

            $result = $this->db->fetchOne($sql, $params);
            return ($result['count'] ?? 0) > 0;
        } catch (DatabaseException $e) {
            throw RepositoryException::queryError('User', 'emailExists', $e->getMessage());
        }
    }

    /**
     * Trouve des utilisateurs selon des critères
     * 
     * @param array $criteria
     * @return User[]
     * @throws RepositoryException
     */
    public function findBy(array $criteria): array
    {
        try {
            $conditions = [];
            $params = [];
            foreach ($criteria as $key => $value) {
                $conditions[] = "$key = :$key";
                $params[$key] = $value;
            }

            $sql = 'SELECT * FROM users';
            if (!empty($conditions)) {
                $sql .= ' WHERE ' . implode(' AND ', $conditions);
            }

            $users = $this->db->fetchAll($sql, $params);
            return array_map(fn(array $userData) => new User($userData), $users);
        } catch (DatabaseException $e) {
            throw RepositoryException::queryError('User', 'findBy', $e->getMessage());
        }
    }

    /**
     * Trouve un utilisateur selon des critères
     * 
     * @param array $criteria
     * @return User|null
     * @throws RepositoryException
     */
    public function findOneBy(array $criteria): ?User
    {
        try {
            $conditions = [];
            $params = [];
            foreach ($criteria as $key => $value) {
                $conditions[] = "$key = :$key";
                $params[$key] = $value;
            }

            $sql = 'SELECT * FROM users';
            if (!empty($conditions)) {
                $sql .= ' WHERE ' . implode(' AND ', $conditions);
            }
            $sql .= ' LIMIT 1';

            $result = $this->db->fetchOne($sql, $params);
            return $result ? new User($result) : null;
        } catch (DatabaseException $e) {
            throw RepositoryException::queryError('User', 'findOneBy', $e->getMessage());
        }
    }

    /**
     * Compte le nombre d'utilisateurs selon des critères
     * 
     * @param array $criteria
     * @return int
     * @throws RepositoryException
     */
    public function count(array $criteria = []): int
    {
        try {
            $conditions = [];
            $params = [];
            foreach ($criteria as $key => $value) {
                $conditions[] = "$key = :$key";
                $params[$key] = $value;
            }

            $sql = 'SELECT COUNT(*) as count FROM users';
            if (!empty($conditions)) {
                $sql .= ' WHERE ' . implode(' AND ', $conditions);
            }

            $result = $this->db->fetchOne($sql, $params);
            return (int)($result['count'] ?? 0);
        } catch (DatabaseException $e) {
            throw RepositoryException::queryError('User', 'count', $e->getMessage());
        }
    }

    /**
     * Vérifie si un utilisateur existe selon son ID
     * 
     * @param int $id
     * @return bool
     */
    public function exists(int $id): bool
    {
        try {
            return $this->count(['id' => $id]) > 0;
        } catch (RepositoryException $e) {
            return false;
        }
    }
    public function saveResetToken(int $userId, string $token, \DateTime $expiresAt): bool
    {
        try {
            return $this->db->insert('password_resets', [
                'user_id' => $userId,
                'token' => $token,
                'expires_at' => $expiresAt->format('Y-m-d H:i:s')
            ]) > 0;
        } catch (DatabaseException $e) {
            throw RepositoryException::createError('PasswordReset', $e->getMessage());
        }
    }

    public function findResetToken(string $token): ?array
    {
        try {
            return $this->db->fetchOne(
                'SELECT user_id, token, expires_at FROM password_resets WHERE token = :token',
                ['token' => $token]
            );
        } catch (DatabaseException $e) {
            throw RepositoryException::queryError('PasswordReset', 'findResetToken', $e->getMessage());
        }
    }
    public function deleteResetToken(string $token): bool
    {
        try {
            $result = $this->db->fetchOne(
                'SELECT id FROM password_resets WHERE token = :token',
                ['token' => $token]
            );

            if (!$result) {
                return false;
            }

            return $this->db->delete('password_resets', $result['id']);
        } catch (DatabaseException $e) {
            throw RepositoryException::deleteError('PasswordReset', 0, $e->getMessage());
        }
    }

    public function findByRole(string $role): array {
        $sql = "SELECT * FROM users WHERE role = :role ORDER BY nom, prenom";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['role' => $role]);
        return $stmt->fetchAll();
    }

    /**
     * Vérifie si une adresse existe déjà pour un autre utilisateur
     * 
     * @param string $address
     * @param int|null $excludeId ID de l'utilisateur à exclure de la vérification
     * @return bool
     */
    public function addressExists(string $address, ?int $excludeId = null): bool
    {
        try {
            $sql = 'SELECT COUNT(*) as count FROM users WHERE adresse = :adresse';
            $params = ['adresse' => $address];

            if ($excludeId !== null) {
                $sql .= ' AND id != :id';
                $params['id'] = $excludeId;
            }

            $result = $this->db->fetchOne($sql, $params);
            return ($result['count'] ?? 0) > 0;
        } catch (DatabaseException $e) {
            throw RepositoryException::queryError('User', 'addressExists', $e->getMessage());
        }
    }
}
