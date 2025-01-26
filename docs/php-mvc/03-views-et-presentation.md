# Views et Couche de Présentation

## 1. Rôle des Views

Les Views sont responsables de :
- La présentation des données
- L'interface utilisateur
- Le rendu HTML/CSS/JS
- La gestion des templates
- La réutilisation des composants

## 2. Structure des Views

### Organisation des Fichiers
```
views/
├── layouts/
│   ├── default.php
│   └── admin.php
├── partials/
│   ├── header.php
│   ├── footer.php
│   └── navigation.php
├── users/
│   ├── index.php
│   ├── show.php
│   └── edit.php
└── components/
    ├── form.php
    └── alert.php
```

### Layout de Base
```php
<!-- layouts/default.php -->
<!DOCTYPE html>
<html>
<head>
    <title><?= $title ?? 'Mon Application' ?></title>
    <?php include 'partials/head.php' ?>
</head>
<body>
    <?php include 'partials/header.php' ?>
    
    <main class="container">
        <?php include $content ?>
    </main>
    
    <?php include 'partials/footer.php' ?>
</body>
</html>
```

## 3. Système de Template

### Moteur de Template Simple
```php
class Template {
    private $layout;
    private $vars = [];
    
    public function setLayout($layout) {
        $this->layout = $layout;
    }
    
    public function assign($key, $value) {
        $this->vars[$key] = $value;
    }
    
    public function render($view) {
        extract($this->vars);
        
        ob_start();
        include "views/{$view}.php";
        $content = ob_get_clean();
        
        include "views/layouts/{$this->layout}.php";
    }
}

// Utilisation
$template = new Template();
$template->setLayout('default');
$template->assign('title', 'Liste des Utilisateurs');
$template->assign('users', $users);
$template->render('users/index');
```

## 4. Helpers de Vue

### Création de Helpers
```php
class ViewHelper {
    public static function escape($string) {
        return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
    }
    
    public static function formatDate($date, $format = 'd/m/Y') {
        return date($format, strtotime($date));
    }
    
    public static function asset($path) {
        return "/assets/{$path}";
    }
}

// Utilisation dans la vue
<img src="<?= ViewHelper::asset('img/logo.png') ?>" alt="Logo">
<p>Date: <?= ViewHelper::formatDate($user->created_at) ?></p>
```

## 5. Composants Réutilisables

### Système de Composants
```php
class Component {
    public static function render($name, $data = []) {
        extract($data);
        include "views/components/{$name}.php";
    }
}

// Définition du composant
<!-- components/alert.php -->
<div class="alert alert-<?= $type ?>">
    <?= $message ?>
    <?php if (isset($dismissible)): ?>
        <button class="close">&times;</button>
    <?php endif; ?>
</div>

// Utilisation
<?php Component::render('alert', [
    'type' => 'success',
    'message' => 'Opération réussie!',
    'dismissible' => true
]); ?>
```

## 6. Gestion des Formulaires

### Helper de Formulaire
```php
class Form {
    public static function open($action = '', $method = 'POST') {
        return "<form action='{$action}' method='{$method}'>";
    }
    
    public static function input($name, $value = '', $type = 'text') {
        return "<input type='{$type}' name='{$name}' value='{$value}'>";
    }
    
    public static function submit($value = 'Envoyer') {
        return "<button type='submit'>{$value}</button>";
    }
    
    public static function close() {
        return "</form>";
    }
}

// Utilisation
<?= Form::open('/users/create') ?>
    <?= Form::input('name', '', 'text') ?>
    <?= Form::input('email', '', 'email') ?>
    <?= Form::submit('Créer') ?>
<?= Form::close() ?>
```

## 7. Gestion des Assets

### Asset Manager
```php
class AssetManager {
    private $css = [];
    private $js = [];
    
    public function addCss($path) {
        $this->css[] = $path;
    }
    
    public function addJs($path) {
        $this->js[] = $path;
    }
    
    public function renderCss() {
        foreach ($this->css as $css) {
            echo "<link rel='stylesheet' href='/css/{$css}'>\n";
        }
    }
    
    public function renderJs() {
        foreach ($this->js as $js) {
            echo "<script src='/js/{$js}'></script>\n";
        }
    }
}

// Utilisation dans le layout
<?php
$assets = new AssetManager();
$assets->addCss('main.css');
$assets->addJs('app.js');
?>
<!DOCTYPE html>
<html>
<head>
    <?php $assets->renderCss() ?>
</head>
<body>
    <!-- Contenu -->
    <?php $assets->renderJs() ?>
</body>
</html>
```

## 8. Internationalisation

### Système de Traduction
```php
class Translator {
    private $lang;
    private $translations = [];
    
    public function setLang($lang) {
        $this->lang = $lang;
        $this->loadTranslations();
    }
    
    public function trans($key) {
        return $this->translations[$key] ?? $key;
    }
    
    private function loadTranslations() {
        $file = "resources/lang/{$this->lang}.php";
        if (file_exists($file)) {
            $this->translations = include $file;
        }
    }
}

// Fichier de traduction (resources/lang/fr.php)
return [
    'welcome' => 'Bienvenue',
    'login' => 'Connexion',
    'register' => 'Inscription'
];

// Utilisation dans la vue
<?php $translator->setLang('fr'); ?>
<h1><?= $translator->trans('welcome') ?></h1>
```

## 9. Sécurité dans les Views

### Protection XSS
```php
class ViewSecurity {
    public static function escape($data) {
        if (is_array($data)) {
            return array_map([self::class, 'escape'], $data);
        }
        return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    }
    
    public static function csrf() {
        $token = bin2hex(random_bytes(32));
        $_SESSION['csrf_token'] = $token;
        return "<input type='hidden' name='csrf_token' value='{$token}'>";
    }
}

// Utilisation
<form method="POST">
    <?= ViewSecurity::csrf() ?>
    <input name="name" value="<?= ViewSecurity::escape($user->name) ?>">
</form>
```

## Conclusion

Points clés pour les Views :
1. Séparation claire de la présentation
2. Réutilisation des composants
3. Sécurité des données affichées
4. Performance du rendu
5. Maintenance facilitée

Recommandations :
- Utiliser des layouts cohérents
- Créer des composants réutilisables
- Sécuriser les sorties
- Optimiser le chargement des assets
- Maintenir une structure claire
  </rewritten_file> 