# Structure MVC en PHP : Les Bases

## Introduction
Le MVC (Modèle-Vue-Contrôleur) est un modèle d'architecture qui aide à organiser le code en trois parties distinctes. Pensez-y comme une cuisine de restaurant :
- Le **Modèle** est comme le chef qui prépare les ingrédients et la nourriture (gère les données)
- La **Vue** est comme l'assiette qui présente la nourriture (affiche les données)
- Le **Contrôleur** est comme le serveur qui prend la commande et coordonne tout (gère les requêtes)

## 1. Structure de Base d'un Projet MVC

```plaintext
mon-projet/
│
├── app/                    # Dossier principal de l'application
│   ├── Controllers/       # Les contrôleurs qui gèrent les requêtes
│   ├── Models/           # Les modèles qui gèrent les données
│   ├── Views/            # Les vues qui affichent les données
│   └── Services/         # Les services pour la logique métier
│
├── public/                # Dossier public accessible par le navigateur
│   ├── index.php         # Point d'entrée de l'application
│   ├── css/             # Fichiers CSS
│   └── js/              # Fichiers JavaScript
│
└── config/               # Configuration de l'application
```

## 2. Le Point d'Entrée (index.php)

```php
// public/index.php
// C'est le fichier qui reçoit toutes les requêtes

// 1. Charger l'autoloader de Composer
require_once '../vendor/autoload.php';

// 2. Charger la configuration
$config = require_once '../config/config.php';

// 3. Créer l'application
$app = new Application($config);

// 4. Démarrer l'application qui va :
// - Analyser l'URL
// - Trouver le bon contrôleur
// - Exécuter l'action demandée
$app->run();
```

## 3. Exemple Simple de Flux MVC

### Le Modèle (Model)
```php
// app/Models/Article.php
class Article {
    private $db; // Connexion à la base de données
    
    // Récupérer tous les articles
    public function getTousLesArticles() {
        // Le modèle gère l'accès aux données
        return $this->db->query("SELECT * FROM articles");
    }
    
    // Récupérer un article par son ID
    public function getArticle($id) {
        return $this->db->query("SELECT * FROM articles WHERE id = ?", [$id]);
    }
}
```

### La Vue (View)
```php
<!-- app/Views/articles/index.php -->
<!-- La vue se concentre uniquement sur l'affichage -->
<h1>Liste des Articles</h1>

<div class="articles">
    <?php foreach ($articles as $article): ?>
        <article>
            <h2><?= htmlspecialchars($article->titre) ?></h2>
            <p><?= htmlspecialchars($article->resume) ?></p>
            <a href="/article/<?= $article->id ?>">Lire la suite</a>
        </article>
    <?php endforeach; ?>
</div>
```

### Le Contrôleur (Controller)
```php
// app/Controllers/ArticleController.php
class ArticleController {
    private $articleModel;
    
    public function __construct() {
        // Le contrôleur utilise le modèle pour accéder aux données
        $this->articleModel = new Article();
    }
    
    // Action pour afficher la liste des articles
    public function index() {
        // 1. Demander les données au modèle
        $articles = $this->articleModel->getTousLesArticles();
        
        // 2. Passer les données à la vue
        $this->render('articles/index', [
            'articles' => $articles
        ]);
    }
    
    // Action pour afficher un article spécifique
    public function voir($id) {
        // 1. Demander l'article au modèle
        $article = $this->articleModel->getArticle($id);
        
        // 2. Vérifier si l'article existe
        if (!$article) {
            // Rediriger vers une page 404
            $this->redirect('404');
        }
        
        // 3. Passer l'article à la vue
        $this->render('articles/voir', [
            'article' => $article
        ]);
    }
}
```

## 4. Comment Tout Fonctionne Ensemble

1. **La Requête Arrive** :
```plaintext
L'utilisateur visite : http://monsite.com/articles
```

2. **Le Routeur Analyse l'URL** :
```php
// Le routeur comprend que :
// - Le contrôleur est "ArticleController"
// - L'action est "index"
$route = $router->analyze('/articles');
```

3. **Le Contrôleur est Appelé** :
```php
// Le contrôleur :
// 1. Reçoit la requête
// 2. Demande les données au modèle
// 3. Passe les données à la vue
$controller = new ArticleController();
$controller->index();
```

4. **Le Modèle Gère les Données** :
```php
// Le modèle :
// 1. Communique avec la base de données
// 2. Retourne les données au contrôleur
$articles = $model->getTousLesArticles();
```

5. **La Vue Affiche les Données** :
```php
// La vue :
// 1. Reçoit les données du contrôleur
// 2. Les intègre dans le HTML
// 3. Renvoie la page complète
require 'views/articles/index.php';
```

## 5. Avantages du MVC

1. **Séparation des Responsabilités**
   - Chaque partie a un rôle précis
   - Le code est plus facile à maintenir
   - On peut modifier une partie sans toucher aux autres

2. **Réutilisation du Code**
   - Les modèles peuvent être utilisés par différents contrôleurs
   - Les vues peuvent être réutilisées avec différentes données

3. **Organisation Claire**
   - Structure de projet standardisée
   - Facile de trouver où ajouter du nouveau code
   - Plus simple pour travailler en équipe

## Conclusion

Le MVC est comme une recette bien organisée :
1. Le client (utilisateur) passe une commande (fait une requête)
2. Le serveur (contrôleur) prend la commande et la transmet au chef
3. Le chef (modèle) prépare les ingrédients (données)
4. Le serveur arrange l'assiette (vue) avec ce que le chef a préparé
5. Le client reçoit son plat bien présenté (page web)

Cette organisation permet de :
- Garder le code propre et organisé
- Faciliter la maintenance
- Permettre le travail en équipe
- Rendre le code plus facile à tester 