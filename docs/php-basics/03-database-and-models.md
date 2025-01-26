# Interactions avec la Base de Données et Modèles

## Introduction
Les modèles sont la partie de votre application qui gère les données. Pensez-y comme à une bibliothèque :
- La **base de données** est comme les étagères où sont rangés les livres
- Les **modèles** sont comme les bibliothécaires qui savent où trouver et ranger les livres
- Les **requêtes** sont comme les demandes que vous faites au bibliothécaire

## 1. Configuration de la Base de Données

```php
// config/database.php
// Configuration de la connexion à la base de données
return [
    'host' => 'localhost',     // L'adresse du serveur
    'dbname' => 'mon_site',    // Le nom de la base de données
    'user' => 'root',          // L'utilisateur
    'password' => '',          // Le mot de passe
    'charset' => 'utf8mb4'     // L'encodage des caractères
];

// app/Database/Connection.php
// Classe pour gérer la connexion à la base de données
class Database {
    private static $instance = null;
    private $pdo;

    private function __construct() {
        // Charger la configuration
        $config = require '../config/database.php';
        
        // Créer la connexion PDO
        try {
            $this->pdo = new PDO(
                "mysql:host={$config['host']};dbname={$config['dbname']};charset={$config['charset']}",
                $config['user'],
                $config['password'],
                [
                    // Configurer PDO pour qu'il lance des exceptions en cas d'erreur
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    // Retourner les résultats sous forme d'objets
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ
                ]
            );
        } catch (PDOException $e) {
            // En cas d'erreur, afficher un message
            die("Erreur de connexion : " . $e->getMessage());
        }
    }

    // Obtenir l'instance unique de la connexion
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    // Obtenir la connexion PDO
    public function getConnection() {
        return $this->pdo;
    }
}
```

## 2. Création d'un Modèle de Base

```php
// app/Models/BaseModel.php
// Classe de base pour tous les modèles
abstract class BaseModel {
    protected $db;
    protected $table;  // Nom de la table dans la base de données
    
    public function __construct() {
        // Obtenir la connexion à la base de données
        $this->db = Database::getInstance()->getConnection();
    }
    
    // Trouver un enregistrement par son ID
    public function findById($id) {
        $query = $this->db->prepare("SELECT * FROM {$this->table} WHERE id = ?");
        $query->execute([$id]);
        return $query->fetch();
    }
    
    // Récupérer tous les enregistrements
    public function findAll() {
        $query = $this->db->query("SELECT * FROM {$this->table}");
        return $query->fetchAll();
    }
    
    // Créer un nouvel enregistrement
    public function create($data) {
        // 1. Préparer les colonnes et les valeurs
        $columns = implode(', ', array_keys($data));
        $values = implode(', ', array_fill(0, count($data), '?'));
        
        // 2. Préparer la requête
        $query = $this->db->prepare(
            "INSERT INTO {$this->table} ($columns) VALUES ($values)"
        );
        
        // 3. Exécuter la requête
        $query->execute(array_values($data));
        
        // 4. Retourner l'ID du nouvel enregistrement
        return $this->db->lastInsertId();
    }
    
    // Mettre à jour un enregistrement
    public function update($id, $data) {
        // 1. Préparer les paires colonne = ?
        $sets = implode(' = ?, ', array_keys($data)) . ' = ?';
        
        // 2. Préparer la requête
        $query = $this->db->prepare(
            "UPDATE {$this->table} SET $sets WHERE id = ?"
        );
        
        // 3. Ajouter l'ID à la fin des valeurs
        $values = array_values($data);
        $values[] = $id;
        
        // 4. Exécuter la requête
        return $query->execute($values);
    }
    
    // Supprimer un enregistrement
    public function delete($id) {
        $query = $this->db->prepare("DELETE FROM {$this->table} WHERE id = ?");
        return $query->execute([$id]);
    }
}
```

## 3. Création d'un Modèle Spécifique

```php
// app/Models/Utilisateur.php
// Modèle pour gérer les utilisateurs
class Utilisateur extends BaseModel {
    protected $table = 'utilisateurs';
    
    // Trouver un utilisateur par son email
    public function findByEmail($email) {
        $query = $this->db->prepare(
            "SELECT * FROM {$this->table} WHERE email = ?"
        );
        $query->execute([$email]);
        return $query->fetch();
    }
    
    // Créer un nouvel utilisateur avec validation
    public function creerUtilisateur($data) {
        // 1. Valider les données
        if (empty($data['email']) || empty($data['mot_de_passe'])) {
            throw new Exception("Email et mot de passe requis");
        }
        
        // 2. Vérifier si l'email existe déjà
        if ($this->findByEmail($data['email'])) {
            throw new Exception("Cet email est déjà utilisé");
        }
        
        // 3. Hasher le mot de passe
        $data['mot_de_passe'] = password_hash(
            $data['mot_de_passe'], 
            PASSWORD_DEFAULT
        );
        
        // 4. Créer l'utilisateur
        return $this->create($data);
    }
}
```

## 4. Utilisation des Modèles

```php
// Exemple d'utilisation dans un contrôleur
class UtilisateurController {
    private $utilisateurModel;
    
    public function __construct() {
        $this->utilisateurModel = new Utilisateur();
    }
    
    // Action pour l'inscription
    public function inscription() {
        try {
            // 1. Récupérer les données du formulaire
            $data = [
                'nom' => $_POST['nom'],
                'email' => $_POST['email'],
                'mot_de_passe' => $_POST['mot_de_passe']
            ];
            
            // 2. Créer l'utilisateur via le modèle
            $id = $this->utilisateurModel->creerUtilisateur($data);
            
            // 3. Rediriger vers la page de connexion
            $this->redirect('/connexion');
            
        } catch (Exception $e) {
            // En cas d'erreur, afficher le message
            $this->render('utilisateurs/inscription', [
                'erreur' => $e->getMessage()
            ]);
        }
    }
}
```

## 5. Bonnes Pratiques

### 1. Sécurité
```php
// TOUJOURS utiliser des requêtes préparées
// ❌ MAUVAIS (risque d'injection SQL)
$query = $this->db->query("SELECT * FROM users WHERE id = $id");

// ✅ BON (utilise une requête préparée)
$query = $this->db->prepare("SELECT * FROM users WHERE id = ?");
$query->execute([$id]);
```

### 2. Validation des Données
```php
// Exemple de méthode de validation
public function validerDonnees($data) {
    $erreurs = [];
    
    // Valider l'email
    if (empty($data['email'])) {
        $erreurs['email'] = "L'email est requis";
    } elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        $erreurs['email'] = "L'email n'est pas valide";
    }
    
    // Valider le mot de passe
    if (empty($data['mot_de_passe'])) {
        $erreurs['mot_de_passe'] = "Le mot de passe est requis";
    } elseif (strlen($data['mot_de_passe']) < 8) {
        $erreurs['mot_de_passe'] = "Le mot de passe doit faire au moins 8 caractères";
    }
    
    return $erreurs;
}
```

### 3. Gestion des Erreurs
```php
// Exemple de méthode avec gestion d'erreurs
public function mettreAJourProfil($id, $data) {
    try {
        // 1. Valider les données
        $erreurs = $this->validerDonnees($data);
        if (!empty($erreurs)) {
            throw new ValidationException($erreurs);
        }
        
        // 2. Mettre à jour les données
        $succes = $this->update($id, $data);
        if (!$succes) {
            throw new Exception("Erreur lors de la mise à jour");
        }
        
        return true;
        
    } catch (ValidationException $e) {
        // Erreurs de validation
        throw $e;
    } catch (Exception $e) {
        // Autres erreurs
        // Logger l'erreur
        error_log($e->getMessage());
        throw new Exception("Une erreur est survenue");
    }
}
```

## Conclusion

Les points clés à retenir :

1. **Organisation**
   - Un modèle par type de données
   - Méthodes claires et bien nommées
   - Code réutilisable avec une classe de base

2. **Sécurité**
   - Toujours utiliser des requêtes préparées
   - Valider toutes les données
   - Gérer correctement les erreurs

3. **Maintenance**
   - Centraliser la logique d'accès aux données
   - Documenter les méthodes importantes
   - Suivre les bonnes pratiques 