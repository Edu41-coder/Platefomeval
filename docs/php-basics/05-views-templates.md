# Vues et Templates en PHP

## Introduction
Les vues et les templates sont comme la partie visible d'un restaurant :
- Les **templates** sont comme la décoration et l'agencement du restaurant
- Les **vues** sont comme les différents plats présentés aux clients
- Les **layouts** sont comme la disposition générale des tables et du mobilier

## 1. Structure de Base des Vues

### Organisation des Fichiers
```plaintext
app/
└── Views/
    ├── layouts/           # Les mises en page générales
    │   ├── default.php
    │   └── admin.php
    ├── partials/         # Les éléments réutilisables
    │   ├── header.php
    │   ├── footer.php
    │   └── navigation.php
    └── pages/            # Les pages spécifiques
        ├── home.php
        ├── contact.php
        └── articles/
            ├── index.php
            ├── show.php
            └── create.php
```

## 2. Layout Principal

```php
<!-- app/Views/layouts/default.php -->
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?? 'Mon Site' ?></title>
    
    <!-- Inclusion des CSS -->
    <link rel="stylesheet" href="/css/style.css">
    <?php if (isset($css)): ?>
        <?php foreach ($css as $file): ?>
            <link rel="stylesheet" href="/css/<?= $file ?>.css">
        <?php endforeach; ?>
    <?php endif; ?>
</head>
<body>
    <!-- En-tête du site -->
    <?php include '../app/Views/partials/header.php'; ?>
    
    <!-- Navigation principale -->
    <?php include '../app/Views/partials/navigation.php'; ?>
    
    <!-- Messages flash (succès, erreurs, etc.) -->
    <?php if (isset($_SESSION['flash'])): ?>
        <div class="flash-messages">
            <?php foreach ($_SESSION['flash'] as $type => $message): ?>
                <div class="alert alert-<?= $type ?>">
                    <?= $message ?>
                </div>
            <?php endforeach; ?>
            <?php unset($_SESSION['flash']); ?>
        </div>
    <?php endif; ?>
    
    <!-- Contenu principal -->
    <main class="container">
        <?= $content ?? '' ?>
    </main>
    
    <!-- Pied de page -->
    <?php include '../app/Views/partials/footer.php'; ?>
    
    <!-- Inclusion des scripts JavaScript -->
    <script src="/js/app.js"></script>
    <?php if (isset($js)): ?>
        <?php foreach ($js as $file): ?>
            <script src="/js/<?= $file ?>.js"></script>
        <?php endforeach; ?>
    <?php endif; ?>
</body>
</html>
```

## 3. Éléments Partiels (Partials)

### En-tête
```php
<!-- app/Views/partials/header.php -->
<header class="site-header">
    <div class="logo">
        <a href="/">
            <img src="/images/logo.png" alt="Logo du site">
        </a>
    </div>
    
    <!-- Barre de recherche -->
    <form class="search-form" action="/recherche" method="GET">
        <input type="text" name="q" placeholder="Rechercher...">
        <button type="submit">
            <i class="fas fa-search"></i>
        </button>
    </form>
    
    <!-- Menu utilisateur -->
    <div class="user-menu">
        <?php if (isset($_SESSION['user'])): ?>
            <div class="dropdown">
                <button class="dropdown-toggle">
                    <?= htmlspecialchars($_SESSION['user']['nom']) ?>
                </button>
                <div class="dropdown-menu">
                    <a href="/profil">Mon Profil</a>
                    <a href="/logout">Déconnexion</a>
                </div>
            </div>
        <?php else: ?>
            <a href="/login">Connexion</a>
            <a href="/register">Inscription</a>
        <?php endif; ?>
    </div>
</header>
```

### Navigation
```php
<!-- app/Views/partials/navigation.php -->
<nav class="main-nav">
    <?php
    // Définir les éléments du menu
    $menuItems = [
        '/' => 'Accueil',
        '/articles' => 'Articles',
        '/services' => 'Services',
        '/contact' => 'Contact'
    ];
    
    // Obtenir l'URL actuelle
    $currentUrl = $_SERVER['REQUEST_URI'];
    ?>
    
    <ul class="nav-list">
        <?php foreach ($menuItems as $url => $label): ?>
            <li class="nav-item <?= $url === $currentUrl ? 'active' : '' ?>">
                <a href="<?= $url ?>"><?= $label ?></a>
            </li>
        <?php endforeach; ?>
    </ul>
</nav>
```

## 4. Pages Spécifiques

### Page d'Accueil
```php
<!-- app/Views/pages/home.php -->
<?php
// Définir le titre de la page
$title = "Accueil - Mon Site";

// Commencer la mise en mémoire tampon
ob_start();
?>

<div class="welcome-section">
    <h1>Bienvenue sur Mon Site</h1>
    <p>Découvrez nos derniers articles et services.</p>
</div>

<section class="featured-articles">
    <h2>Articles à la Une</h2>
    
    <div class="articles-grid">
        <?php foreach ($articles as $article): ?>
            <article class="article-card">
                <img src="<?= $article->image ?>" alt="<?= htmlspecialchars($article->titre) ?>">
                <h3><?= htmlspecialchars($article->titre) ?></h3>
                <p><?= htmlspecialchars($article->resume) ?></p>
                <a href="/article/<?= $article->id ?>" class="btn">Lire plus</a>
            </article>
        <?php endforeach; ?>
    </div>
</section>

<?php
// Récupérer le contenu mis en mémoire tampon
$content = ob_get_clean();

// Inclure le layout
require '../app/Views/layouts/default.php';
?>
```

## 5. Helpers pour les Vues

```php
// app/Helpers/ViewHelper.php
class ViewHelper {
    // Échapper le HTML pour la sécurité
    public static function e($string) {
        return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
    }
    
    // Formater une date
    public static function formatDate($date, $format = 'd/m/Y') {
        return date($format, strtotime($date));
    }
    
    // Tronquer un texte
    public static function truncate($text, $length = 100) {
        if (strlen($text) <= $length) {
            return $text;
        }
        
        return substr($text, 0, $length) . '...';
    }
    
    // Générer un lien actif
    public static function activeLink($url, $text) {
        $class = $_SERVER['REQUEST_URI'] === $url ? 'active' : '';
        return sprintf(
            '<a href="%s" class="%s">%s</a>',
            $url,
            $class,
            self::e($text)
        );
    }
}
```

## 6. Formulaires

```php
<!-- app/Views/pages/contact.php -->
<form class="contact-form" method="POST" action="/contact">
    <!-- Afficher les erreurs s'il y en a -->
    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <ul>
                <?php foreach ($errors as $error): ?>
                    <li><?= ViewHelper::e($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>
    
    <!-- Champ Nom -->
    <div class="form-group">
        <label for="nom">Nom</label>
        <input type="text" 
               id="nom" 
               name="nom" 
               value="<?= ViewHelper::e($old['nom'] ?? '') ?>"
               class="<?= !empty($errors['nom']) ? 'is-invalid' : '' ?>">
        
        <?php if (!empty($errors['nom'])): ?>
            <div class="invalid-feedback">
                <?= ViewHelper::e($errors['nom']) ?>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Champ Email -->
    <div class="form-group">
        <label for="email">Email</label>
        <input type="email" 
               id="email" 
               name="email"
               value="<?= ViewHelper::e($old['email'] ?? '') ?>"
               class="<?= !empty($errors['email']) ? 'is-invalid' : '' ?>">
        
        <?php if (!empty($errors['email'])): ?>
            <div class="invalid-feedback">
                <?= ViewHelper::e($errors['email']) ?>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Champ Message -->
    <div class="form-group">
        <label for="message">Message</label>
        <textarea id="message" 
                  name="message"
                  class="<?= !empty($errors['message']) ? 'is-invalid' : '' ?>"
                  rows="5"><?= ViewHelper::e($old['message'] ?? '') ?></textarea>
        
        <?php if (!empty($errors['message'])): ?>
            <div class="invalid-feedback">
                <?= ViewHelper::e($errors['message']) ?>
            </div>
        <?php endif; ?>
    </div>
    
    <button type="submit" class="btn btn-primary">Envoyer</button>
</form>
```

## 7. Bonnes Pratiques

### 1. Organisation du Code
```php
// ✅ BON : Séparer la logique de présentation
<div class="user-card">
    <h2><?= ViewHelper::e($user->nom) ?></h2>
    <p><?= ViewHelper::formatDate($user->date_inscription) ?></p>
</div>

// ❌ MAUVAIS : Mélanger la logique métier et la présentation
<div class="user-card">
    <?php
    $user = $db->query("SELECT * FROM users WHERE id = 1");
    echo "<h2>" . $user->nom . "</h2>";
    ?>
</div>
```

### 2. Réutilisation du Code
```php
// Créer des composants réutilisables
<!-- app/Views/components/alert.php -->
<div class="alert alert-<?= $type ?>">
    <?= $message ?>
    <?php if ($dismissible): ?>
        <button class="close">&times;</button>
    <?php endif; ?>
</div>

// Utilisation
<?php include_once '../app/Views/components/alert.php' with [
    'type' => 'success',
    'message' => 'Opération réussie !',
    'dismissible' => true
]; ?>
```

## Conclusion

Points clés à retenir :

1. **Organisation**
   - Structure claire des fichiers
   - Séparation des responsabilités
   - Composants réutilisables

2. **Sécurité**
   - Échapper les données affichées
   - Valider les entrées utilisateur
   - Protéger contre les XSS

3. **Maintenance**
   - Code DRY (Don't Repeat Yourself)
   - Documentation claire
   - Conventions de nommage cohérentes 