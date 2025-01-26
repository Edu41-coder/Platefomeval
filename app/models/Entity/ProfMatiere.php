<?php
namespace App\Models\Entity;

use Core\Model\BaseModel;

class ProfMatiere extends BaseModel
{
    protected static string $table = 'prof_matieres';

    protected static array $fillable = [
        'prof_id',
        'matiere_id',
        'created_at'
    ];

    // Ajoutez des méthodes pour gérer les relations si nécessaire
}