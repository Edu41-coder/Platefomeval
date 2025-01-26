<?php
namespace App\Models\Entity;

use Core\Model\BaseModel;
use Core\Database\Database;

class Matiere extends BaseModel
{
    protected static string $table = 'matieres';
    protected static ?Database $db = null;

    protected static array $fillable = [
        'nom',
        'description',
        'created_at',
        'updated_at'
    ];

    private ?array $etudiants = null;
    private ?array $evaluations = null;

    protected static function initDb(): void
    {
        if (self::$db === null) {
            self::$db = Database::getInstance();
        }
    }

    public function getNom(): string
    {
        return $this->attributes['nom'] ?? '';
    }

    public function setNom(string $nom): void
    {
        $this->attributes['nom'] = $nom;
    }

    public function getDescription(): ?string
    {
        return $this->attributes['description'] ?? null;
    }

    public function setDescription(?string $description): void
    {
        $this->attributes['description'] = $description;
    }

    public function getEtudiants(): array
    {
        if ($this->etudiants === null) {
            try {
                self::initDb();
                $query = "SELECT u.* FROM users u 
                         INNER JOIN etudiant_matieres em ON u.id = em.etudiant_id 
                         WHERE em.matiere_id = ?";
                $this->etudiants = self::$db->fetchAll($query, [$this->getId()]);
            } catch (\Exception $e) {
                error_log("Erreur lors de la récupération des étudiants : " . $e->getMessage());
                $this->etudiants = [];
            }
        }
        return $this->etudiants;
    }

    public function getEvaluations(): array
    {
        if ($this->evaluations === null) {
            self::initDb();
            $query = "SELECT * FROM evaluations WHERE matiere_id = ? ORDER BY date_evaluation DESC";
            $this->evaluations = self::$db->fetchAll($query, [$this->getId()]);
        }
        return $this->evaluations;
    }

    public function getLastEvaluation(): ?Evaluation
    {
        $evaluations = $this->getEvaluations();
        return !empty($evaluations) ? new Evaluation($evaluations[0]) : null;
    }

    public function getId(): ?int
    {
        return $this->attributes['id'] ?? null;
    }

    public function setId(int $id): void
    {
        $this->attributes['id'] = $id;
    }
}