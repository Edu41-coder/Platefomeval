<?php
namespace App\Models\Repository;

use App\Interfaces\Repository\MatiereRepositoryInterface;
use App\Models\Entity\Matiere;
use App\Models\Entity\User;
use Core\Database\Database;
use Core\Exception\RepositoryException;
use PDO;
use PDOException;

class MatiereRepository implements MatiereRepositoryInterface
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public function findAll(): array
    {
        try {
            $stmt = $this->db->prepare("SELECT * FROM matieres");
            $stmt->execute();
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return array_map(fn($item) => new Matiere($item), $data);
        } catch (PDOException $e) {
            throw new RepositoryException('Erreur lors de la récupération: ' . $e->getMessage());
        }
    }

    public function findById(int $id): ?Matiere
    {
        try {
            $stmt = $this->db->prepare("SELECT * FROM matieres WHERE id = :id");
            $stmt->execute(['id' => $id]);
            $data = $stmt->fetch(PDO::FETCH_ASSOC);
            return $data ? new Matiere($data) : null;
        } catch (PDOException $e) {
            throw new RepositoryException('Erreur lors de la récupération: ' . $e->getMessage());
        }
    }

    public function create(array $data): int
    {
        try {
            $stmt = $this->db->prepare("INSERT INTO matieres (nom, description) VALUES (:nom, :description)");
            $stmt->execute([
                'nom' => $data['nom'],
                'description' => $data['description'] ?? null
            ]);
            return (int) $this->db->lastInsertId();
        } catch (PDOException $e) {
            throw new RepositoryException('Erreur lors de la création: ' . $e->getMessage());
        }
    }

    public function update(int $id, array $data): bool
    {
        try {
            $stmt = $this->db->prepare("UPDATE matieres SET nom = :nom, description = :description WHERE id = :id");
            return $stmt->execute([
                'id' => $id,
                'nom' => $data['nom'],
                'description' => $data['description'] ?? null
            ]);
        } catch (PDOException $e) {
            throw new RepositoryException('Erreur lors de la mise à jour: ' . $e->getMessage());
        }
    }

    public function delete(int $id): bool
    {
        try {
            $stmt = $this->db->prepare("DELETE FROM matieres WHERE id = :id");
            return $stmt->execute(['id' => $id]);
        } catch (PDOException $e) {
            throw new RepositoryException('Erreur lors de la suppression: ' . $e->getMessage());
        }
    }

    public function exists(int $id): bool
    {
        try {
            $stmt = $this->db->prepare("SELECT 1 FROM matieres WHERE id = :id");
            $stmt->execute(['id' => $id]);
            return (bool) $stmt->fetchColumn();
        } catch (PDOException $e) {
            throw new RepositoryException('Erreur lors de la vérification: ' . $e->getMessage());
        }
    }

    public function findByName(string $nom): ?Matiere
    {
        try {
            $stmt = $this->db->prepare("SELECT * FROM matieres WHERE nom = :nom");
            $stmt->execute(['nom' => $nom]);
            $data = $stmt->fetch(PDO::FETCH_ASSOC);
            return $data ? new Matiere($data) : null;
        } catch (PDOException $e) {
            throw new RepositoryException('Erreur lors de la recherche: ' . $e->getMessage());
        }
    }

    /**
     * Récupère toutes les matières d'un professeur
     */
    public function findByProfessorId(int $profId): array
    {
        try {
            $sql = "SELECT DISTINCT m.* 
                    FROM matieres m
                    INNER JOIN prof_matieres pm ON m.id = pm.matiere_id
                    WHERE pm.prof_id = :prof_id";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute(['prof_id' => $profId]);
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return array_map(fn($data) => new Matiere($data), $result);
        } catch (PDOException $e) {
            throw new RepositoryException('Erreur lors de la récupération des matières du professeur: ' . $e->getMessage());
        }
    }

    /**
     * Récupère tous les étudiants inscrits à une matière
     */
    public function getStudentsByMatiereId(int $matiereId): array
    {
        try {
            $sql = "SELECT u.* 
                    FROM users u
                    INNER JOIN etudiant_matieres em ON u.id = em.etudiant_id
                    WHERE em.matiere_id = :matiere_id
                    ORDER BY u.nom, u.prenom";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute(['matiere_id' => $matiereId]);
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return array_map(fn($data) => new User($data), $result);
        } catch (PDOException $e) {
            throw new RepositoryException('Erreur lors de la récupération des étudiants: ' . $e->getMessage());
        }
    }

    /**
     * Vérifie si un professeur enseigne une matière spécifique
     */
    public function isProfessorTeachingMatiere(int $profId, int $matiereId): bool
    {
        try {
            $sql = "SELECT COUNT(*) 
                    FROM prof_matieres 
                    WHERE prof_id = :prof_id 
                    AND matiere_id = :matiere_id";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                'prof_id' => $profId,
                'matiere_id' => $matiereId
            ]);
            
            return (int) $stmt->fetchColumn() > 0;
        } catch (PDOException $e) {
            throw new RepositoryException('Erreur lors de la vérification: ' . $e->getMessage());
        }
    }

    /**
     * Trouve toutes les matières d'un étudiant
     */
    public function findAllForStudent(int $studentId): array
    {
        try {
            $sql = "SELECT DISTINCT m.* 
                    FROM matieres m
                    INNER JOIN etudiant_matieres em ON m.id = em.matiere_id
                    WHERE em.etudiant_id = :student_id";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute(['student_id' => $studentId]);
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return array_map(fn($data) => new Matiere($data), $result);
        } catch (PDOException $e) {
            throw new RepositoryException('Erreur lors de la récupération des matières de l\'étudiant: ' . $e->getMessage());
        }
    }

    /**
     * Vérifie si un étudiant est inscrit à une matière
     */
    public function isStudentEnrolled(int $matiereId, int $studentId): bool
    {
        try {
            $sql = "SELECT COUNT(*) FROM etudiant_matieres 
                    WHERE matiere_id = :matiere_id 
                    AND etudiant_id = :student_id";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                'matiere_id' => $matiereId,
                'student_id' => $studentId
            ]);
            
            return (int) $stmt->fetchColumn() > 0;
        } catch (PDOException $e) {
            throw new RepositoryException('Erreur lors de la vérification de l\'inscription: ' . $e->getMessage());
        }
    }

    /**
     * Calcule la moyenne d'un étudiant pour une matière
     */
    public function getStudentAverage(int $matiereId, int $studentId): ?float
    {
        try {
            $sql = "SELECT AVG(en.note) as moyenne 
                    FROM evaluations e
                    INNER JOIN evaluation_notes en ON e.id = en.evaluation_id
                    WHERE e.matiere_id = :matiere_id 
                    AND en.etudiant_id = :student_id 
                    AND en.note IS NOT NULL";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                'matiere_id' => $matiereId,
                'student_id' => $studentId
            ]);
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result['moyenne'] !== null ? (float) $result['moyenne'] : null;
        } catch (PDOException $e) {
            throw new RepositoryException('Erreur lors du calcul de la moyenne: ' . $e->getMessage());
        }
    }

    /**
     * Récupère les informations du professeur d'une matière
     */
    public function getProfesseur(int $matiereId): ?array
    {
        try {
            $sql = "SELECT u.* 
                    FROM users u
                    INNER JOIN prof_matieres pm ON u.id = pm.prof_id
                    WHERE pm.matiere_id = :matiere_id
                    AND u.role_id = 2";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute(['matiere_id' => $matiereId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return $result ?: null;
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération du professeur: " . $e->getMessage());
            return null;
        }
    }

    public function getEtudiantsByMatiereWithPagination(
        int $matiereId,
        int $page = 1,
        int $itemsPerPage = 10,
        string $sort = 'nom',
        string $order = 'ASC'
    ): array {
        try {
            $offset = ($page - 1) * $itemsPerPage;
            
            $sql = "SELECT DISTINCT u.* 
                    FROM users u 
                    JOIN etudiant_matieres em ON u.id = em.etudiant_id 
                    WHERE em.matiere_id = :matiere_id 
                    AND u.role_id = 3 
                    ORDER BY u.{$sort} {$order} 
                    LIMIT :limit OFFSET :offset";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':matiere_id', $matiereId, PDO::PARAM_INT);
            $stmt->bindValue(':limit', $itemsPerPage, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            
            $etudiants = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $etudiants[] = new User($row);
            }
            
            $countSql = "SELECT COUNT(DISTINCT u.id) 
                         FROM users u 
                         JOIN etudiant_matieres em ON u.id = em.etudiant_id 
                         WHERE em.matiere_id = :matiere_id 
                         AND u.role_id = 3";
                         
            $countStmt = $this->db->prepare($countSql);
            $countStmt->bindValue(':matiere_id', $matiereId, PDO::PARAM_INT);
            $countStmt->execute();
            $totalResults = $countStmt->fetchColumn();

            return [
                'etudiants' => $etudiants,
                'totalPages' => ceil($totalResults / $itemsPerPage),
                'totalResults' => $totalResults
            ];
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération des étudiants: " . $e->getMessage());
            throw new RepositoryException("Erreur lors de la récupération des étudiants: " . $e->getMessage());
        }
    }

    public function createAndReturn(array $data): Matiere
    {
        $id = $this->create($data);
        $matiere = $this->findById($id);
        if (!$matiere) {
            throw new RepositoryException('Erreur lors de la création de la matière');
        }
        return $matiere;
    }

    public function updateAndReturn(int $id, array $data): Matiere
    {
        $success = $this->update($id, $data);
        if (!$success) {
            throw new RepositoryException('Erreur lors de la mise à jour de la matière');
        }
        $matiere = $this->findById($id);
        if (!$matiere) {
            throw new RepositoryException('La matière mise à jour est introuvable');
        }
        return $matiere;
    }

    public function findBy(array $criteria): array
    {
        try {
            $sql = "SELECT * FROM matieres WHERE 1=1";
            $params = [];

            foreach ($criteria as $key => $value) {
                $sql .= " AND $key = :$key";
                $params[$key] = $value;
            }

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return array_map(fn($data) => new Matiere($data), $result);
        } catch (PDOException $e) {
            throw new RepositoryException('Erreur lors de la recherche: ' . $e->getMessage());
        }
    }

    public function findOneBy(array $criteria): ?Matiere
    {
        try {
            $sql = "SELECT * FROM matieres WHERE 1=1";
            $params = [];

            foreach ($criteria as $key => $value) {
                $sql .= " AND $key = :$key";
                $params[$key] = $value;
            }

            $sql .= " LIMIT 1";
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            return $result ? new Matiere($result) : null;
        } catch (PDOException $e) {
            throw new RepositoryException('Erreur lors de la recherche: ' . $e->getMessage());
        }
    }

    public function count(array $criteria = []): int
    {
        try {
            $sql = "SELECT COUNT(*) FROM matieres WHERE 1=1";
            $params = [];

            foreach ($criteria as $key => $value) {
                $sql .= " AND $key = :$key";
                $params[$key] = $value;
            }

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return (int) $stmt->fetchColumn();
        } catch (PDOException $e) {
            throw new RepositoryException('Erreur lors du comptage: ' . $e->getMessage());
        }
    }
}