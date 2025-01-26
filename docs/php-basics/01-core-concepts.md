# Les Concepts de Base en PHP Orienté Objet

## Introduction
Ce document explique les concepts fondamentaux de la programmation orientée objet (POO) en PHP de manière simple et claire, avec des exemples commentés.

## 1. Qu'est-ce qu'une Classe ?
Une classe est comme un "plan" ou un "modèle" qui définit les caractéristiques (propriétés) et les comportements (méthodes) que les objets de ce type auront.

```php
// Une classe simple représentant un étudiant
class Etudiant {
    // Les propriétés (caractéristiques) de l'étudiant
    private $nom;
    private $age;
    private $notes = [];

    // Le constructeur est appelé quand on crée un nouvel étudiant
    public function __construct($nom, $age) {
        $this->nom = $nom;
        $this->age = $age;
    }

    // Une méthode pour ajouter une note
    public function ajouterNote($note) {
        $this->notes[] = $note;
    }

    // Une méthode pour calculer la moyenne
    public function calculerMoyenne() {
        if (empty($this->notes)) {
            return 0;
        }
        return array_sum($this->notes) / count($this->notes);
    }
}

// Création et utilisation d'un objet Etudiant
$etudiant = new Etudiant("Jean Dupont", 20);
$etudiant->ajouterNote(15);
$etudiant->ajouterNote(17);
echo $etudiant->calculerMoyenne(); // Affiche: 16
```

## 2. Qu'est-ce qu'un Namespace ?
Un namespace (espace de noms) est comme un "dossier virtuel" qui permet d'organiser et de grouper des classes liées. Il évite les conflits de noms entre différentes classes.

```php
// Déclaration d'un namespace
namespace App\Models;

// Maintenant cette classe est dans le namespace App\Models
class Utilisateur {
    private $nom;
    
    public function __construct($nom) {
        $this->nom = $nom;
    }
}

// Pour utiliser cette classe ailleurs, on doit :
// Soit utiliser le namespace complet
$user = new \App\Models\Utilisateur("Pierre");

// Soit importer la classe avec use
use App\Models\Utilisateur;
$user = new Utilisateur("Pierre");
```

## 3. Qu'est-ce qu'une Interface ?
Une interface est un "contrat" qui définit quelles méthodes une classe doit implémenter. C'est comme une liste de promesses que la classe doit tenir.

```php
// Définition d'une interface
interface NotificationInterface {
    // L'interface définit QUELLES méthodes doivent exister
    // mais pas COMMENT elles fonctionnent
    public function envoyer($message);
    public function verifierStatut();
}

// Une classe qui implémente l'interface
class EmailNotification implements NotificationInterface {
    // La classe DOIT implémenter toutes les méthodes de l'interface
    public function envoyer($message) {
        // Code pour envoyer un email
        echo "Envoi d'email: $message";
    }

    public function verifierStatut() {
        // Code pour vérifier si l'email a été envoyé
        return true;
    }
}

// Une autre implémentation pour SMS
class SMSNotification implements NotificationInterface {
    public function envoyer($message) {
        // Code pour envoyer un SMS
        echo "Envoi de SMS: $message";
    }

    public function verifierStatut() {
        // Code pour vérifier si le SMS a été envoyé
        return true;
    }
}
```

## 4. Qu'est-ce qu'un Trait ?
Un trait est un mécanisme qui permet de réutiliser du code dans plusieurs classes. C'est comme un "copier-coller" de code mais en plus intelligent.

```php
// Définition d'un trait
trait LoggableTrait {
    // Ce code peut être réutilisé dans plusieurs classes
    private $logs = [];

    public function addLog($message) {
        $this->logs[] = date('Y-m-d H:i:s') . ": $message";
    }

    public function getLogs() {
        return $this->logs;
    }
}

// Utilisation du trait dans une classe
class Commande {
    // On "importe" le code du trait dans la classe
    use LoggableTrait;

    public function passerCommande() {
        // On peut utiliser les méthodes du trait
        $this->addLog("Nouvelle commande créée");
        // ... reste du code
    }
}

class Utilisateur {
    // Le même trait peut être utilisé dans plusieurs classes
    use LoggableTrait;

    public function connexion() {
        $this->addLog("Utilisateur connecté");
        // ... reste du code
    }
}
```

## 5. Qu'est-ce qu'un Repository ?
Un repository est une classe qui gère l'accès aux données. C'est comme un "bibliothécaire" qui sait comment chercher, sauvegarder et organiser les données.

```php
// Exemple simple d'un repository
class UtilisateurRepository {
    private $db; // Connexion à la base de données

    // Trouver un utilisateur par son ID
    public function trouverParId($id) {
        // Le repository sait comment chercher dans la base de données
        $sql = "SELECT * FROM utilisateurs WHERE id = ?";
        return $this->db->execute($sql, [$id]);
    }

    // Sauvegarder un utilisateur
    public function sauvegarder($utilisateur) {
        // Le repository sait comment sauvegarder les données
        $sql = "INSERT INTO utilisateurs (nom, email) VALUES (?, ?)";
        $this->db->execute($sql, [
            $utilisateur->getNom(),
            $utilisateur->getEmail()
        ]);
    }
}
```

## 6. Qu'est-ce qu'un Service ?
Un service est une classe qui contient la logique métier de l'application. C'est comme un "expert" qui sait comment effectuer des opérations complexes.

```php
// Exemple d'un service qui gère les inscriptions
class InscriptionService {
    private $utilisateurRepository;
    private $emailService;

    public function __construct(
        UtilisateurRepository $utilisateurRepository,
        EmailService $emailService
    ) {
        $this->utilisateurRepository = $utilisateurRepository;
        $this->emailService = $emailService;
    }

    // Le service contient la logique métier
    public function inscrireUtilisateur($nom, $email, $motDePasse) {
        // 1. Vérifier si l'email n'est pas déjà utilisé
        if ($this->utilisateurRepository->emailExiste($email)) {
            throw new Exception("Email déjà utilisé");
        }

        // 2. Créer l'utilisateur
        $utilisateur = new Utilisateur($nom, $email);
        $utilisateur->setMotDePasse(password_hash($motDePasse, PASSWORD_DEFAULT));

        // 3. Sauvegarder dans la base de données
        $this->utilisateurRepository->sauvegarder($utilisateur);

        // 4. Envoyer un email de bienvenue
        $this->emailService->envoyerEmailBienvenue($utilisateur);

        return $utilisateur;
    }
}
```

## Différences Principales

### Repository vs Service
- **Repository**: Se concentre uniquement sur l'accès aux données (lecture/écriture)
- **Service**: Contient la logique métier et peut utiliser plusieurs repositories

### Interface vs Classe
- **Interface**: Définit seulement ce qui doit être fait (contrat)
- **Classe**: Implémente comment les choses sont faites

### Trait vs Classe
- **Trait**: Code réutilisable qui peut être "injecté" dans plusieurs classes
- **Classe**: Définition complète d'un type d'objet

## Conclusion
Ces concepts sont les fondations de la programmation orientée objet en PHP moderne. Comprendre ces concepts vous aidera à :
- Mieux organiser votre code
- Créer du code plus facile à maintenir
- Réutiliser du code efficacement
- Suivre les bonnes pratiques de développement 