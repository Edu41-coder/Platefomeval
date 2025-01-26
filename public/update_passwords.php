<?php

// Définir le chemin de base
define('BASE_PATH', dirname(__DIR__));

// Charger l'autoloader de Composer
require_once BASE_PATH . '/vendor/autoload.php';

// Charger les variables d'environnement
$dotenv = Dotenv\Dotenv::createImmutable(BASE_PATH);
$dotenv->load();

try {
    // Récupération de la configuration de la base de données
    $config = require BASE_PATH . '/app/config/database.php';
    $dbConfig = $config['connections']['mysql'];

    // Connexion à la base de données avec les paramètres du fichier de configuration
    $db = new PDO(
        "mysql:host={$dbConfig['host']};dbname={$dbConfig['database']};charset={$dbConfig['charset']}",
        $dbConfig['username'],
        $dbConfig['password'],
        $dbConfig['options']
    );
    
    // Hasher le nouveau mot de passe
    $newPassword = 'prof2023';
    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
    
    // Mettre à jour tous les mots de passe
    $stmt = $db->prepare("UPDATE users SET password = :password, updated_at = NOW()");
    $stmt->execute(['password' => $hashedPassword]);
    
    $count = $stmt->rowCount();
    echo "<pre>";
    echo "Les mots de passe de {$count} utilisateurs ont été mis à jour avec succès.\n";
    echo "Nouveau mot de passe pour tous les utilisateurs : {$newPassword}\n";
    echo "⚠️ N'oubliez pas de supprimer ce fichier après utilisation !\n";
    echo "</pre>";
    
} catch (PDOException $e) {
    echo "<pre>";
    echo "Erreur lors de la mise à jour des mots de passe : " . $e->getMessage() . "\n";
    echo "</pre>";
} catch (Exception $e) {
    echo "<pre>";
    echo "Erreur : " . $e->getMessage() . "\n";
    echo "</pre>";
} 