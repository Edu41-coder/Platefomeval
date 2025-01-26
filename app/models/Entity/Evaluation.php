<?php
namespace App\Models\Entity;

use Core\Model\BaseModel;
use Core\Database\Database;
use App\Models\Entity\EvaluationNote;
use App\Models\Entity\User;

class Evaluation extends BaseModel
{
    protected static string $table = 'evaluations';

    protected static array $fillable = [
        'matiere_id',
        'prof_id',
        'type',
        'description',
        'date_evaluation',
        'created_at',
        'updated_at'
    ];

    private ?EvaluationNote $note = null;

    public function getType(): string
    {
        return $this->attributes['type'] ?? 'Contrôle continu';
    }

    public function setType(string $type): void
    {
        $validTypes = ['Contrôle continu', 'Examen', 'TP', 'Projet', 'Oral'];
        if (!in_array($type, $validTypes)) {
            throw new \InvalidArgumentException('Type d\'évaluation invalide');
        }
        $this->attributes['type'] = $type;
    }

    public function getDescription(): ?string
    {
        return $this->attributes['description'] ?? null;
    }

    public function setDescription(?string $description): void
    {
        $this->attributes['description'] = $description;
    }

    public function getProfessorId(): ?int
    {
        return $this->attributes['prof_id'] ?? null;
    }

    public function setProfessorId(int $profId): void
    {
        $this->attributes['prof_id'] = $profId;
    }

    public function getMatiereId(): ?int
    {
        return $this->attributes['matiere_id'] ?? null;
    }

    public function setMatiereId(int $matiereId): void
    {
        $this->attributes['matiere_id'] = $matiereId;
    }

    public function getDate(): ?string
    {
        return $this->attributes['date_evaluation'] ?? null;
    }

    public function setDate(string $date): void
    {
        $this->attributes['date_evaluation'] = $date;
    }

    public function getDateEvaluation(): string
    {
        return $this->attributes['date_evaluation'] ?? '';
    }

    // Méthode pour récupérer les notes associées
    public function getNotes(): array
    {
        $evaluationNote = new EvaluationNote();
        return $evaluationNote->findBy('evaluation_id', $this->getId());
    }

    // Méthode pour calculer la moyenne de la classe
    public function getMoyenneClasse(): ?float
    {
        $notes = $this->getNotes();
        if (empty($notes)) {
            return null;
        }

        $sum = 0;
        $count = 0;
        foreach ($notes as $note) {
            if ($note['note'] !== null) {
                $sum += $note['note'];
                $count++;
            }
        }

        return $count > 0 ? $sum / $count : null;
    }

    // Méthode pour récupérer les étudiants évalués
    public function getNotesEtudiants(): array
    {
        return array_filter($this->getNotes(), function($note) {
            return $note['note'] !== null;
        });
    }

    // Méthode pour récupérer tous les étudiants de la matière
    public function getEtudiants(): array
    {
        try {
            $db = \Core\Database\Database::getInstance();
            $sql = "SELECT u.* FROM users u 
                    INNER JOIN etudiant_matieres em ON u.id = em.etudiant_id 
                    WHERE em.matiere_id = :matiere_id";
            
            $result = $db->fetchAll($sql, ['matiere_id' => $this->getMatiereId()]);
            
            // Convertir les résultats en objets User
            return array_map(function($data) {
                return new User($data);
            }, $result);
        } catch (\Exception $e) {
            error_log("Error in Evaluation::getEtudiants: " . $e->getMessage());
            return [];
        }
    }

    public function setNote(EvaluationNote $note): void
    {
        $this->note = $note;
    }

    public function getNote(): ?EvaluationNote
    {
        return $this->note;
    }

    public function getMatiereName(): string
    {
        return $this->attributes['matiere_nom'] ?? 'Non défini';
    }
}
