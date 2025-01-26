<?php
namespace App\Services;

use App\Interfaces\Service\MatiereServiceInterface;
use App\Models\Entity\Matiere;
use App\Models\Entity\User;
use App\Models\Repository\MatiereRepository;
use Core\Exception\ServiceException;
use Core\Database\DatabaseInterface;

class MatiereService implements MatiereServiceInterface
{
    private MatiereRepository $matiereRepository;
    private DatabaseInterface $db;

    public function __construct(MatiereRepository $matiereRepository, DatabaseInterface $db)
    {
        $this->matiereRepository = $matiereRepository;
        $this->db = $db;
    }

    public function get(int $id): ?Matiere
    {
        $matiere = $this->matiereRepository->findById($id);
        if (!$matiere) {
            return null;
        }
        
        // Si findById retourne un tableau, convertir en objet Matiere
        if (is_array($matiere)) {
            return new Matiere($matiere);
        }
        
        return $matiere;
    }

    public function getAll(): array
    {
        return $this->matiereRepository->findAll();
    }

    public function create(array $data): Matiere
    {
        try {
            $id = $this->matiereRepository->create($data);
            $matiere = $this->matiereRepository->findById($id);
            if (!$matiere) {
                throw new ServiceException('Erreur lors de la création de la matière');
            }
            return $matiere;
        } catch (\Exception $e) {
            throw new ServiceException('Erreur lors de la création de la matière: ' . $e->getMessage());
        }
    }

    public function update(int $id, array $data): Matiere
    {
        try {
            $success = $this->matiereRepository->update($id, $data);
            if (!$success) {
                throw new ServiceException('Erreur lors de la mise à jour de la matière');
            }
            $matiere = $this->matiereRepository->findById($id);
            if (!$matiere) {
                throw new ServiceException('La matière mise à jour est introuvable');
            }
            return $matiere;
        } catch (\Exception $e) {
            throw new ServiceException('Erreur lors de la mise à jour de la matière: ' . $e->getMessage());
        }
    }

    public function delete(int $id): bool
    {
        return $this->matiereRepository->delete($id);
    }

    public function validate(array $data, array $options = []): bool
    {
        // Logique de validation
        // Par exemple, vérifier que le nom n'est pas vide
        return isset($data['nom']) && is_string($data['nom']) && !empty($data['nom']);
    }

    public function exists(int $id): bool
    {
        return $this->matiereRepository->exists($id);
    }

    public function getErrors(): array
    {
        // Retourner les erreurs de validation
        return [];
    }

    public function findByName(string $nom): ?Matiere
    {
        return $this->matiereRepository->findByName($nom);
    }

    public function nameExists(string $nom, ?int $excludeId = null): bool
    {
        // Logique pour vérifier l'existence du nom
        $matiere = $this->findByName($nom);
        return $matiere !== null && $matiere->getId() !== $excludeId;
    }

    /**
     * Récupère toutes les matières d'un professeur
     */
    public function getMatieresByProfessor(int $professorId): array
    {
        return $this->matiereRepository->findByProfessorId($professorId);
    }

    /**
     * Récupère tous les étudiants d'une matière
     */
    public function getEtudiantsByMatiere(int $matiereId): array
    {
        return $this->matiereRepository->getStudentsByMatiereId($matiereId);
    }

    /**
     * Vérifie si une matière appartient à un professeur
     */
    public function belongsToProfessor(int $matiereId, int $professorId): bool
    {
        return $this->matiereRepository->isProfessorTeachingMatiere($professorId, $matiereId);
    }

    /**
     * Récupère toutes les matières d'un étudiant avec leurs moyennes
     */
    public function getMatieresForStudent(int $studentId): array
    {
        try {
            $matieres = $this->matiereRepository->findAllForStudent($studentId);
            
            $result = [];
            foreach ($matieres as $matiere) {
                // Convertir l'objet Matiere en tableau
                $matiereArray = [
                    'id' => $matiere->getId(),
                    'nom' => $matiere->getNom(),
                    'description' => $matiere->getDescription()
                ];
                
                // Ajouter les moyennes et le professeur
                $matiereArray['moyenne'] = $this->matiereRepository->getStudentAverage($matiere->getId(), $studentId);
                $matiereArray['professeur'] = $this->matiereRepository->getProfesseur($matiere->getId());
                
                $result[] = $matiereArray;
            }

            return $result;
        } catch (\Exception $e) {
            error_log('Erreur lors de la récupération des matières: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Récupère une matière spécifique pour un étudiant
     */
    public function getMatiereForStudent(int $matiereId, int $studentId): ?array
    {
        // Vérifier si l'étudiant est inscrit à cette matière
        if (!$this->matiereRepository->isStudentEnrolled($matiereId, $studentId)) {
            return null;
        }

        // Utiliser findById au lieu de find
        $matiere = $this->matiereRepository->findById($matiereId);
        if ($matiere) {
            // Convertir l'objet Matiere en tableau
            $matiereArray = [
                'id' => $matiere->getId(),
                'nom' => $matiere->getNom(),
                'description' => $matiere->getDescription()
            ];
            
            // Ajouter les informations supplémentaires
            $matiereArray['moyenne'] = $this->matiereRepository->getStudentAverage($matiereId, $studentId);
            $matiereArray['professeur'] = $this->matiereRepository->getProfesseur($matiereId);
            
            return $matiereArray;
        }

        return null;
    }

    /**
     * Récupère les étudiants d'une matière avec pagination et tri
     */
    public function getEtudiantsByMatiereWithPagination(
        int $matiereId,
        int $page,
        int $itemsPerPage,
        string $sort = 'nom',
        string $order = 'ASC'
    ): array {
        try {
            return $this->matiereRepository->getEtudiantsByMatiereWithPagination(
                $matiereId,
                $page,
                $itemsPerPage,
                $sort,
                $order
            );
        } catch (\Exception $e) {
            error_log('Erreur lors de la récupération des étudiants: ' . $e->getMessage());
            throw new ServiceException('Erreur lors de la récupération des étudiants');
        }
    }

    /**
     * Vérifie si un étudiant est inscrit à une matière
     */
    public function isStudentEnrolled(int $matiereId, int $studentId): bool
    {
        try {
            $matiere = $this->matiereRepository->findById($matiereId);
            if (!$matiere) {
                return false;
            }
            
            $etudiants = $this->matiereRepository->getStudentsByMatiereId($matiereId);
            foreach ($etudiants as $etudiant) {
                if ($etudiant->getId() === $studentId) {
                    return true;
                }
            }
            return false;
        } catch (\Exception $e) {
            error_log('Erreur lors de la vérification de l\'inscription: ' . $e->getMessage());
            return false;
        }
    }
}