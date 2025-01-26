<?php
$professors = range(1, 25); // IDs des professeurs
$matieres = range(1, 10);   // IDs des matières

$associations = [];

foreach ($professors as $prof_id) {
    // Choisir aléatoirement entre 2 et 5 matières pour chaque professeur
    $num_matieres = rand(2, 5);
    $selected_matieres = array_rand(array_flip($matieres), $num_matieres);

    foreach ((array)$selected_matieres as $matiere_id) {
        $associations[] = "($prof_id, $matiere_id, NOW())";
    }
}

// Générer la requête SQL
$sql = "INSERT INTO `prof_matieres` (`prof_id`, `matiere_id`, `created_at`) VALUES " . implode(", ", $associations) . ";";

echo $sql;
?>