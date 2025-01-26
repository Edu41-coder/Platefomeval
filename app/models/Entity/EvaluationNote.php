<?php
namespace App\Models\Entity;

use Core\Model\BaseModel;
use App\Models\Entity\Evaluation;
use App\Models\Entity\User;

class EvaluationNote extends BaseModel
{
    protected static string $table = 'evaluation_notes';

    protected static array $fillable = [
        'evaluation_id',
        'etudiant_id',
        'note',
        'commentaire',
        'created_at',
        'updated_at'
    ];

    public function getEvaluationId(): int
    {
        return $this->attributes['evaluation_id'];
    }

    public function setEvaluationId(int $evaluationId): void
    {
        $this->attributes['evaluation_id'] = $evaluationId;
    }

    public function getEtudiantId(): int
    {
        return $this->attributes['etudiant_id'];
    }

    public function setEtudiantId(int $etudiantId): void
    {
        $this->attributes['etudiant_id'] = $etudiantId;
    }

    public function getNote(): ?float
    {
        return $this->attributes['note'] !== null ? (float)$this->attributes['note'] : null;
    }

    public function setNote(?float $note): void
    {
        if ($note !== null && ($note < 0 || $note > 20)) {
            throw new \InvalidArgumentException('La note doit être comprise entre 0 et 20');
        }
        $this->attributes['note'] = $note;
    }

    public function getCommentaire(): ?string
    {
        return $this->attributes['commentaire'] ?? null;
    }

    public function setCommentaire(?string $commentaire): void
    {
        $this->attributes['commentaire'] = $commentaire;
    }

    // Méthode pour récupérer l'évaluation associée
    public function getEvaluation(): ?Evaluation
    {
        return (new Evaluation())->find($this->getEvaluationId());
    }

    // Méthode pour récupérer l'étudiant associé
    public function getEtudiant(): ?User
    {
        return (new User())->find($this->getEtudiantId());
    }
} 