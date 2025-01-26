<?php

namespace App\Models\Repository;

use App\Models\Entity\Evaluation;
use App\Models\Entity\EvaluationNote;
use App\Interfaces\Repository\EvaluationRepositoryInterface;
use Core\Database\Database;
use Core\Exception\RepositoryException;
use PDO;
use PDOException;

class EvaluationRepository implements EvaluationRepositoryInterface
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public function findAll(): array
    {
        try {
            $sql = "SELECT e.*, m.nom as matiere_nom 
                    FROM evaluations e
                    LEFT JOIN matieres m ON e.matiere_id = m.id
                    ORDER BY e.date_evaluation DESC";
            $result = $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
            
            // Convertir en objets Evaluation
            return array_map(function($data) {
                return new Evaluation($data);
            }, $result);
        } catch (\Exception $e) {
            throw new RepositoryException('Erreur lors de la récupération des évaluations: ' . $e->getMessage());
        }
    }

    public function findById(int $id): ?Evaluation
    {
        try {
            $stmt = $this->db->prepare("SELECT e.*, m.nom as matiere_nom 
                    FROM evaluations e
                    LEFT JOIN matieres m ON e.matiere_id = m.id
                    WHERE e.id = :id");
            $stmt->execute(['id' => $id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$result) {
                return null;
            }

            // Récupérer les notes associées
            $notes = $this->findNotesByEvaluationId($id);
            $result['notes'] = $notes;
            
            return new Evaluation($result);
        } catch (\Exception $e) {
            throw new RepositoryException('Erreur lors de la récupération de l\'évaluation: ' . $e->getMessage());
        }
    }

    public function findByStudentId(int $studentId): array
    {
        try {
            $sql = "SELECT DISTINCT e.*, m.nom as matiere_nom 
                    FROM evaluations e
                    LEFT JOIN matieres m ON e.matiere_id = m.id
                    LEFT JOIN evaluation_notes en ON e.id = en.evaluation_id
                    WHERE en.etudiant_id = :student_id
                    ORDER BY e.date_evaluation DESC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute(['student_id' => $studentId]);
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Convertir en objets Evaluation
            return array_map(function($data) {
                return new Evaluation($data);
            }, $result);
        } catch (\Exception $e) {
            throw new RepositoryException('Erreur lors de la récupération des évaluations: ' . $e->getMessage());
        }
    }

    public function getByMatiere(int $matiereId): array
    {
        try {
            $stmt = $this->db->prepare("SELECT e.*, m.nom as matiere_nom 
                    FROM evaluations e
                    LEFT JOIN matieres m ON e.matiere_id = m.id
                    WHERE e.matiere_id = :matiere_id
                    ORDER BY e.date_evaluation DESC");
            $stmt->execute(['matiere_id' => $matiereId]);
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Convertir en objets Evaluation
            return array_map(function($data) {
                return new Evaluation($data);
            }, $result);
        } catch (\Exception $e) {
            throw new RepositoryException('Erreur lors de la récupération des évaluations: ' . $e->getMessage());
        }
    }

    public function create(array $data): int
    {
        $this->db->beginTransaction();
        try {
            // 1. Créer l'évaluation
            $stmt = $this->db->prepare("
                INSERT INTO evaluations 
                (matiere_id, prof_id, type, description, date_evaluation)
                VALUES (:matiere_id, :prof_id, :type, :description, :date_evaluation)
            ");
            
            $stmt->execute([
                'matiere_id' => $data['matiere_id'],
                'prof_id' => $data['prof_id'],
                'type' => $data['type'],
                'description' => $data['description'] ?? '',
                'date_evaluation' => $data['date_evaluation']
            ]);
            
            $evaluationId = $this->db->lastInsertId();

            // 2. Créer les notes si présentes
            if (isset($data['notes']) && is_array($data['notes'])) {
                $noteStmt = $this->db->prepare("
                    INSERT INTO evaluation_notes 
                    (evaluation_id, etudiant_id, note, commentaire)
                    VALUES (:evaluation_id, :etudiant_id, :note, :commentaire)
                ");

                foreach ($data['notes'] as $etudiantId => $noteData) {
                    if (!empty($noteData['note']) || !empty($noteData['commentaire'])) {
                        $noteStmt->execute([
                            'evaluation_id' => $evaluationId,
                            'etudiant_id' => $etudiantId,
                            'note' => $noteData['note'] ?? null,
                            'commentaire' => $noteData['commentaire'] ?? null
                        ]);
                    }
                }
            }

            $this->db->commit();
            return $evaluationId;
        } catch (\Exception $e) {
            $this->db->rollBack();
            error_log("EvaluationRepository::create - Error: " . $e->getMessage());
            throw new RepositoryException('Erreur lors de la création de l\'évaluation: ' . $e->getMessage());
        }
    }

    public function createNote(int $evaluationId, array $noteData): void
    {
        try {
            if (!isset($noteData['etudiant_id'])) {
                throw new RepositoryException('L\'ID de l\'étudiant est requis');
            }

            if (isset($noteData['note'])) {
                $note = (float) $noteData['note'];
                if ($note < 0 || $note > 20) {
                    throw new RepositoryException('La note doit être comprise entre 0 et 20');
                }
            }

            $sql = "INSERT INTO evaluation_notes (evaluation_id, etudiant_id, note, commentaire)
                    VALUES (:evaluation_id, :etudiant_id, :note, :commentaire)";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                'evaluation_id' => $evaluationId,
                'etudiant_id' => $noteData['etudiant_id'],
                'note' => $noteData['note'] ?? null,
                'commentaire' => $noteData['commentaire'] ?? null
            ]);
        } catch (\PDOException $e) {
            throw new RepositoryException('Erreur lors de la création de la note: ' . $e->getMessage());
        }
    }

    public function update(int $id, array $data): bool
    {
        $this->db->beginTransaction();
        try {
            $sql = "UPDATE evaluations 
                    SET type = :type,
                        description = :description,
                        date_evaluation = :date_evaluation,
                        updated_at = CURRENT_TIMESTAMP
                    WHERE id = :id";

            $stmt = $this->db->prepare($sql);
            $success = $stmt->execute([
                'id' => $id,
                'type' => $data['type'],
                'description' => $data['description'] ?? null,
                'date_evaluation' => $data['date_evaluation']
            ]);

            $this->db->commit();
            return $success;
        } catch (\Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    private function deleteNotes(int $evaluationId): void
    {
        $stmt = $this->db->prepare("DELETE FROM evaluation_notes WHERE evaluation_id = :evaluation_id");
        $stmt->execute(['evaluation_id' => $evaluationId]);
    }

    /**
     * Récupère les notes d'une évaluation avec les informations des étudiants
     */
    public function findNotesByEvaluationId(int $evaluationId): array
    {
        try {
            $sql = "SELECT en.*, u.nom, u.prenom 
                    FROM evaluation_notes en
                    INNER JOIN users u ON en.etudiant_id = u.id
                    WHERE en.evaluation_id = :evaluation_id
                    ORDER BY u.nom ASC, u.prenom ASC";
                    
            $stmt = $this->db->prepare($sql);
            $stmt->execute(['evaluation_id' => $evaluationId]);
            
            $notes = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $notes[$row['etudiant_id']] = new EvaluationNote($row);
            }
            
            return $notes;
        } catch (\PDOException $e) {
            throw new RepositoryException('Erreur lors de la récupération des notes: ' . $e->getMessage());
        }
    }

    /**
     * Met à jour les notes d'une évaluation
     */
    public function updateNotes(int $evaluationId, array $notes): bool
    {
        $this->db->beginTransaction();
        try {
            foreach ($notes as $etudiantId => $noteData) {
                // Vérifier si une note existe déjà
                $stmt = $this->db->prepare(
                    "SELECT id FROM evaluation_notes 
                     WHERE evaluation_id = :evaluation_id 
                     AND etudiant_id = :etudiant_id"
                );
                $stmt->execute([
                    'evaluation_id' => $evaluationId,
                    'etudiant_id' => $etudiantId
                ]);
                $existingNote = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($existingNote) {
                    // Mise à jour
                    $sql = "UPDATE evaluation_notes 
                            SET note = :note,
                                commentaire = :commentaire,
                                updated_at = CURRENT_TIMESTAMP
                            WHERE evaluation_id = :evaluation_id 
                            AND etudiant_id = :etudiant_id";
                } else {
                    // Insertion
                    $sql = "INSERT INTO evaluation_notes 
                            (evaluation_id, etudiant_id, note, commentaire)
                            VALUES (:evaluation_id, :etudiant_id, :note, :commentaire)";
                }

                $stmt = $this->db->prepare($sql);
                $stmt->execute([
                    'evaluation_id' => $evaluationId,
                    'etudiant_id' => $etudiantId,
                    'note' => $noteData['note'] ?? null,
                    'commentaire' => $noteData['commentaire'] ?? null
                ]);
            }

            $this->db->commit();
            return true;
        } catch (\PDOException $e) {
            $this->db->rollBack();
            throw new RepositoryException('Erreur lors de la mise à jour des notes: ' . $e->getMessage());
        }
    }

    /**
     * Supprime une note
     */
    public function deleteNote(int $evaluationId, int $etudiantId): bool
    {
        try {
            $sql = "DELETE FROM evaluation_notes 
                    WHERE evaluation_id = :evaluation_id 
                    AND etudiant_id = :etudiant_id";
                    
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([
                'evaluation_id' => $evaluationId,
                'etudiant_id' => $etudiantId
            ]);
        } catch (\PDOException $e) {
            throw new RepositoryException('Erreur lors de la suppression de la note: ' . $e->getMessage());
        }
    }

    public function delete(int $id): bool
    {
        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare("DELETE FROM evaluations WHERE id = :evaluation_id");
            $result = $stmt->execute(['evaluation_id' => $id]);
            
            if (!$result) {
                throw new \PDOException('Échec de la suppression');
            }
            
            $this->db->commit();
            return true;
        } catch (\Exception $e) {
            $this->db->rollBack();
            throw new RepositoryException('Erreur lors de l\'exécution de la requête: ' . $e->getMessage());
        }
    }

    public function calculateStudentAverage(int $studentId, ?int $matiereId = null): ?float
    {
        try {
            $sql = "SELECT AVG(en.note) as moyenne
                    FROM evaluation_notes en
                    INNER JOIN evaluations e ON en.evaluation_id = e.id
                    WHERE en.etudiant_id = :student_id";
            
            $params = ['student_id' => $studentId];
            
            if ($matiereId !== null) {
                $sql .= " AND e.matiere_id = :matiere_id";
                $params['matiere_id'] = $matiereId;
            }
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result['moyenne'] ? (float)$result['moyenne'] : null;
            
        } catch (\PDOException $e) {
            error_log('Erreur dans calculateStudentAverage: ' . $e->getMessage());
            throw new RepositoryException('Erreur lors du calcul de la moyenne: ' . $e->getMessage());
        }
    }

    public function getEvaluationsForStudent(int $studentId, ?int $matiereId = null): array
    {
        try {
            $sql = "SELECT e.*, m.nom as matiere_nom, en.note, en.commentaire 
                    FROM evaluations e
                    LEFT JOIN matieres m ON e.matiere_id = m.id
                    LEFT JOIN evaluation_notes en ON e.id = en.evaluation_id
                    WHERE en.etudiant_id = :student_id";
            
            $params = ['student_id' => $studentId];
            
            if ($matiereId !== null) {
                $sql .= " AND e.matiere_id = :matiere_id";
                $params['matiere_id'] = $matiereId;
            }
            
            $sql .= " ORDER BY e.date_evaluation DESC";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Convertir en objets Evaluation
            return array_map(function($data) {
                return new Evaluation($data);
            }, $result);
        } catch (\Exception $e) {
            throw new RepositoryException('Erreur lors de la récupération des évaluations: ' . $e->getMessage());
        }
    }

    /**
     * Trouve les évaluations selon des critères
     * 
     * @param array $criteria Les critères de recherche
     * @return array Liste des évaluations trouvées
     * @throws RepositoryException
     */
    public function findBy(array $criteria): array
    {
        try {
            $sql = "SELECT e.*, m.nom as matiere_nom 
                    FROM evaluations e
                    LEFT JOIN matieres m ON e.matiere_id = m.id
                    WHERE 1=1";
            $params = [];

            foreach ($criteria as $key => $value) {
                $sql .= " AND e.$key = :$key";
                $params[$key] = $value;
            }

            $sql .= " ORDER BY e.date_evaluation DESC";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return array_map(function($data) {
                return new Evaluation($data);
            }, $result);
        } catch (\Exception $e) {
            throw new RepositoryException('Erreur lors de la recherche des évaluations: ' . $e->getMessage());
        }
    }

    /**
     * Trouve une évaluation selon des critères
     * 
     * @param array $criteria Les critères de recherche
     * @return Evaluation|null L'évaluation trouvée ou null
     * @throws RepositoryException
     */
    public function findOneBy(array $criteria): ?Evaluation
    {
        try {
            $sql = "SELECT * FROM evaluations WHERE 1=1";
            $params = [];

            foreach ($criteria as $key => $value) {
                $sql .= " AND $key = :$key";
                $params[$key] = $value;
            }

            $sql .= " LIMIT 1";
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            return $result ? new Evaluation($result) : null;
        } catch (PDOException $e) {
            throw new RepositoryException('Erreur lors de la recherche: ' . $e->getMessage());
        }
    }

    /**
     * Vérifie si une évaluation existe
     * 
     * @param int $id ID de l'évaluation
     * @return bool True si l'évaluation existe
     * @throws RepositoryException
     */
    public function exists(int $id): bool
    {
        try {
            $stmt = $this->db->prepare("SELECT 1 FROM evaluations WHERE id = :id");
            $stmt->execute(['id' => $id]);
            return (bool) $stmt->fetch(PDO::FETCH_COLUMN);
        } catch (\Exception $e) {
            throw new RepositoryException('Erreur lors de la vérification de l\'existence: ' . $e->getMessage());
        }
    }

    /**
     * Compte le nombre d'évaluations selon des critères
     * 
     * @param array $criteria Les critères de comptage
     * @return int Le nombre d'évaluations
     * @throws RepositoryException
     */
    public function count(array $criteria = []): int
    {
        try {
            $sql = "SELECT COUNT(*) FROM evaluations e WHERE 1=1";
            $params = [];

            foreach ($criteria as $key => $value) {
                $sql .= " AND e.$key = :$key";
                $params[$key] = $value;
            }
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return (int) $stmt->fetch(PDO::FETCH_COLUMN);
        } catch (\Exception $e) {
            throw new RepositoryException('Erreur lors du comptage des évaluations: ' . $e->getMessage());
        }
    }

    public function findEvaluationsByMatiereWithPagination(
        int $matiereId, 
        int $page = 1, 
        int $itemsPerPage = 10, 
        string $sort = 'date_evaluation', 
        string $order = 'DESC'
    ): array {
        try {
            // Vérifier les champs de tri autorisés
            $allowedFields = ['date_evaluation', 'type', 'created_at', 'updated_at', 'moyenne_classe'];
            if (!in_array($sort, $allowedFields)) {
                error_log("Champ de tri non autorisé: $sort, utilisation de date_evaluation");
                $sort = 'date_evaluation';
            }
            
            // Valider l'ordre de tri
            $order = strtoupper($order);
            if (!in_array($order, ['ASC', 'DESC'])) {
                error_log("Ordre de tri non valide: $order, utilisation de DESC");
                $order = 'DESC';
            }

            $offset = ($page - 1) * $itemsPerPage;
            
            // Adapter la clause ORDER BY selon le champ de tri
            $orderClause = $sort === 'moyenne_classe' 
                ? "ORDER BY COALESCE(AVG(en.note), 0) " . $order 
                : "ORDER BY e.{$sort} " . $order;
            
            $sql = "SELECT e.*, 
                    COUNT(en.id) as nombre_notes, 
                    AVG(en.note) as moyenne_classe 
                    FROM evaluations e 
                    LEFT JOIN evaluation_notes en ON e.id = en.evaluation_id 
                    WHERE e.matiere_id = :matiere_id 
                    GROUP BY e.id, e.date_evaluation, e.type, e.description 
                    {$orderClause}
                    LIMIT :limit OFFSET :offset";

            error_log("SQL généré: " . $sql);
            error_log("Paramètres - matiereId: $matiereId, page: $page, itemsPerPage: $itemsPerPage, sort: $sort, order: $order");

            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':matiere_id', $matiereId, PDO::PARAM_INT);
            $stmt->bindValue(':limit', $itemsPerPage, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();

            $evaluations = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $evaluation = new Evaluation($row);
                $evaluation->nombre_notes = $row['nombre_notes'] ? (int)$row['nombre_notes'] : 0;
                $evaluation->moyenne_classe = $row['moyenne_classe'] ? (float)$row['moyenne_classe'] : null;
                $evaluations[] = $evaluation;
            }

            // Récupérer le nombre total d'évaluations
            $countQuery = "SELECT COUNT(*) FROM evaluations WHERE matiere_id = :matiere_id";
            $countStmt = $this->db->prepare($countQuery);
            $countStmt->bindValue(':matiere_id', $matiereId, PDO::PARAM_INT);
            $countStmt->execute();
            $totalResults = $countStmt->fetchColumn();

            $totalPages = ceil($totalResults / $itemsPerPage);

            return [
                'evaluations' => $evaluations,
                'totalPages' => $totalPages,
                'totalResults' => $totalResults
            ];
        } catch (\PDOException $e) {
            error_log("Erreur dans EvaluationRepository::findEvaluationsByMatiereWithPagination - " . $e->getMessage());
            throw new RepositoryException("Erreur lors de la récupération des évaluations: " . $e->getMessage());
        }
    }

    public function getNotesForEvaluation(int $evaluationId): array
    {
        try {
            $sql = "SELECT en.*, u.nom, u.prenom
                    FROM evaluation_notes en
                    INNER JOIN users u ON en.etudiant_id = u.id
                    WHERE en.evaluation_id = :evaluation_id
                    ORDER BY u.nom ASC, u.prenom ASC";

            $stmt = $this->db->prepare($sql);
            $stmt->execute(['evaluation_id' => $evaluationId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            throw new RepositoryException('Erreur lors de la récupération des notes: ' . $e->getMessage());
        }
    }

    /**
     * Récupère toutes les notes d'un étudiant pour une matière
     * 
     * @param int $studentId ID de l'étudiant
     * @param int|null $matiereId ID de la matière (optionnel)
     * @return array Les notes trouvées
     */
    public function getStudentNotes(int $studentId, ?int $matiereId = null): array
    {
        try {
            $sql = "SELECT n.note 
                    FROM evaluation_notes n 
                    JOIN evaluations e ON n.evaluation_id = e.id 
                    WHERE n.etudiant_id = :student_id";
            
            $params = ['student_id' => $studentId];
            
            if ($matiereId !== null) {
                $sql .= " AND e.matiere_id = :matiere_id";
                $params['matiere_id'] = $matiereId;
            }
                
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            error_log("Erreur dans getStudentNotes: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Récupère la dernière évaluation d'un étudiant pour une matière
     */
    public function getLastEvaluation(int $studentId, int $matiereId): ?array
    {
        try {
            $sql = "SELECT e.type, e.date_evaluation as date, n.note 
                    FROM evaluations e 
                    JOIN evaluation_notes n ON e.id = n.evaluation_id 
                    WHERE n.etudiant_id = :student_id 
                    AND e.matiere_id = :matiere_id 
                    ORDER BY e.date_evaluation DESC 
                    LIMIT 1";
                    
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                'student_id' => $studentId,
                'matiere_id' => $matiereId
            ]);
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        } catch (\PDOException $e) {
            error_log("Erreur dans getLastEvaluation: " . $e->getMessage());
            return null;
        }
    }

    public function getEvaluationsWithNotes(int $studentId, int $matiereId, string $sort = 'date', string $order = 'desc'): array
    {
        try {
            // Valider les paramètres de tri
            $allowedSorts = [
                'date' => 'e.date_evaluation',
                'type' => 'e.type',
                'note' => 'en.note'
            ];
            
            $sortColumn = $allowedSorts[$sort] ?? 'e.date_evaluation';
            $order = strtoupper($order) === 'ASC' ? 'ASC' : 'DESC';
            $orderClause = "{$sortColumn} {$order}";
            
            // Ajouter un tri secondaire si le tri principal n'est pas la date
            if ($sort !== 'date') {
                $orderClause .= ", e.date_evaluation DESC";
            }

            $sql = "SELECT e.*, en.note, en.commentaire 
                    FROM evaluations e
                    INNER JOIN evaluation_notes en ON e.id = en.evaluation_id
                    WHERE e.matiere_id = :matiere_id 
                    AND en.etudiant_id = :etudiant_id
                    ORDER BY " . $orderClause;

            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                'matiere_id' => $matiereId,
                'etudiant_id' => $studentId
            ]);

            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return array_map(function($data) {
                $evaluation = new Evaluation($data);
                if (isset($data['note'])) {
                    $note = new EvaluationNote([
                        'note' => $data['note'],
                        'commentaire' => $data['commentaire']
                    ]);
                    $evaluation->setNote($note);
                }
                return $evaluation;
            }, $results);

        } catch (PDOException $e) {
            throw new RepositoryException('Erreur lors de la récupération des évaluations: ' . $e->getMessage());
        }
    }

    public function findAllForStudent(int $studentId, ?int $matiereId = null, string $sort = 'date', string $order = 'desc'): array
    {
        try {
            // Valider les paramètres de tri
            $allowedSorts = [
                'matiere' => 'm.nom',
                'date' => 'e.date_evaluation',
                'type' => 'e.type',
                'note' => 'n.note'
            ];
            
            // Ajouter un tri secondaire par date pour maintenir un ordre cohérent
            $sortColumn = $allowedSorts[$sort] ?? 'e.date_evaluation';
            $order = strtoupper($order) === 'ASC' ? 'ASC' : 'DESC';
            $orderClause = "{$sortColumn} {$order}";
            
            // Ajouter un tri secondaire si le tri principal n'est pas la date
            if ($sort !== 'date') {
                $orderClause .= ", e.date_evaluation DESC";
            }

            $sql = "SELECT e.*, m.nom as matiere_nom, n.note, n.commentaire 
                    FROM evaluations e
                    JOIN matieres m ON e.matiere_id = m.id
                    INNER JOIN evaluation_notes n ON e.id = n.evaluation_id 
                        AND n.etudiant_id = :student_id
                        AND n.note IS NOT NULL
                    WHERE e.matiere_id IN (
                        SELECT matiere_id 
                        FROM etudiant_matieres 
                        WHERE etudiant_id = :student_id2
                    )";
            
            $params = [
                'student_id' => $studentId,
                'student_id2' => $studentId
            ];
            
            if ($matiereId !== null) {
                $sql .= " AND e.matiere_id = :matiere_id";
                $params['matiere_id'] = $matiereId;
            }

            $sql .= " ORDER BY " . $orderClause;
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return array_map(function($data) {
                $evaluation = new Evaluation($data);
                if (isset($data['note'])) {
                    $note = new EvaluationNote([
                        'note' => $data['note'],
                        'commentaire' => $data['commentaire']
                    ]);
                    $evaluation->setNote($note);
                }
                return $evaluation;
            }, $results);
            
        } catch (PDOException $e) {
            throw RepositoryException::queryError('Evaluation', 'findAllForStudent', $e->getMessage());
        }
    }

    /**
     * Récupère toutes les évaluations avec les détails
     */
    public function findAllWithDetails(): array
    {
        try {
            $query = "
                SELECT e.*, 
                       m.nom as matiere_name, 
                       u.nom as etudiant_name
                FROM evaluations e
                LEFT JOIN matieres m ON e.matiere_id = m.id
                LEFT JOIN users u ON e.etudiant_id = u.id
                ORDER BY e.date_evaluation DESC
            ";
            
            return $this->db->query($query)->fetchAll();
        } catch (\Exception $e) {
            error_log('Error in EvaluationRepository::findAllWithDetails: ' . $e->getMessage());
            throw new RepositoryException('Erreur lors de la récupération des évaluations');
        }
    }

    /**
     * Récupère une évaluation avec les détails
     */
    public function findOneWithDetails(int $id): ?array
    {
        try {
            $query = "
                SELECT e.*, 
                       m.nom as matiere_name, 
                       u.nom as etudiant_name
                FROM evaluations e
                LEFT JOIN matieres m ON e.matiere_id = m.id
                LEFT JOIN users u ON e.etudiant_id = u.id
                WHERE e.id = ?
            ";
            
            $result = $this->db->prepare($query, [$id])->fetch();
            return $result ?: null;
        } catch (\Exception $e) {
            error_log('Error in EvaluationRepository::findOneWithDetails: ' . $e->getMessage());
            throw new RepositoryException('Erreur lors de la récupération de l\'évaluation');
        }
    }
}
