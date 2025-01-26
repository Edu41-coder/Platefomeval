<?php
namespace App\Models\Entity;

use Core\Model\BaseModel;

class EtudiantMatiere extends BaseModel
{
    protected static string $table = 'etudiant_matieres';

    protected static array $fillable = [
        'etudiant_id',
        'matiere_id',
        'created_at'
    ];

    // Ajoutez des méthodes pour gérer les relations si nécessaire
}