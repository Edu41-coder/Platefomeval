# Formulaires et Validation en PHP

## Introduction
Les formulaires et la validation sont comme un guichet d'accueil :
- Les **formulaires** sont comme les documents à remplir
- La **validation** est comme la vérification des documents
- Les **messages d'erreur** sont comme les retours du guichetier

## 1. Création d'un Formulaire de Base

```php
<!-- app/Views/auth/register.php -->
<!-- Formulaire d'inscription avec validation côté client et serveur -->
<form class="register-form" method="POST" action="/register">
    <!-- Protection CSRF -->
    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
    
    <!-- Champ Nom -->
    <div class="form-group">
        <label for="nom">Nom *</label>
        <input type="text" 
               id="nom" 
               name="nom" 
               required
               minlength="2"
               value="<?= old('nom') ?>"
               class="<?= hasError('nom') ? 'is-invalid' : '' ?>">
        
        <?php if (hasError('nom')): ?>
            <div class="invalid-feedback">
                <?= getError('nom') ?>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Champ Email -->
    <div class="form-group">
        <label for="email">Email *</label>
        <input type="email" 
               id="email" 
               name="email"
               required
               pattern="[a-z0-9._%+-]+@[a-z0-9.-]+\.[a-z]{2,}$"
               value="<?= old('email') ?>"
               class="<?= hasError('email') ? 'is-invalid' : '' ?>">
        
        <?php if (hasError('email')): ?>
            <div class="invalid-feedback">
                <?= getError('email') ?>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Champ Mot de passe -->
    <div class="form-group">
        <label for="password">Mot de passe *</label>
        <input type="password" 
               id="password" 
               name="password"
               required
               minlength="8"
               class="<?= hasError('password') ? 'is-invalid' : '' ?>">
        
        <?php if (hasError('password')): ?>
            <div class="invalid-feedback">
                <?= getError('password') ?>
            </div>
        <?php endif; ?>
        
        <!-- Indicateur de force du mot de passe -->
        <div class="password-strength"></div>
    </div>
    
    <!-- Confirmation du mot de passe -->
    <div class="form-group">
        <label for="password_confirmation">Confirmer le mot de passe *</label>
        <input type="password" 
               id="password_confirmation" 
               name="password_confirmation"
               required>
    </div>
    
    <button type="submit" class="btn btn-primary">S'inscrire</button>
</form>

<!-- JavaScript pour la validation côté client -->
<script>
document.querySelector('.register-form').addEventListener('submit', function(e) {
    // Vérifier si les mots de passe correspondent
    const password = document.getElementById('password');
    const confirmation = document.getElementById('password_confirmation');
    
    if (password.value !== confirmation.value) {
        e.preventDefault();
        alert('Les mots de passe ne correspondent pas');
    }
});

// Vérifier la force du mot de passe en temps réel
document.getElementById('password').addEventListener('input', function(e) {
    const password = e.target.value;
    const strength = checkPasswordStrength(password);
    
    const indicator = document.querySelector('.password-strength');
    indicator.className = 'password-strength ' + strength.class;
    indicator.textContent = strength.message;
});

function checkPasswordStrength(password) {
    const hasUpperCase = /[A-Z]/.test(password);
    const hasLowerCase = /[a-z]/.test(password);
    const hasNumbers = /\d/.test(password);
    const hasSpecialChars = /[!@#$%^&*]/.test(password);
    
    const strength = 
        (hasUpperCase ? 1 : 0) +
        (hasLowerCase ? 1 : 0) +
        (hasNumbers ? 1 : 0) +
        (hasSpecialChars ? 1 : 0);
    
    if (strength === 4) {
        return { class: 'strong', message: 'Mot de passe fort' };
    } else if (strength >= 2) {
        return { class: 'medium', message: 'Mot de passe moyen' };
    } else {
        return { class: 'weak', message: 'Mot de passe faible' };
    }
}
</script>
```

## 2. Validation Côté Serveur

```php
// app/Validation/Validator.php
class Validator {
    private $data = [];
    private $errors = [];
    private $rules = [];
    
    public function __construct(array $data, array $rules) {
        $this->data = $data;
        $this->rules = $rules;
    }
    
    // Valider les données selon les règles
    public function validate(): bool {
        foreach ($this->rules as $field => $rules) {
            // Diviser les règles (ex: "required|min:3|email")
            $fieldRules = explode('|', $rules);
            
            foreach ($fieldRules as $rule) {
                // Vérifier si la règle a des paramètres
                if (strpos($rule, ':') !== false) {
                    list($rule, $parameter) = explode(':', $rule);
                } else {
                    $parameter = null;
                }
                
                // Appeler la méthode de validation correspondante
                $method = 'validate' . ucfirst($rule);
                if (method_exists($this, $method)) {
                    $this->$method($field, $parameter);
                }
            }
        }
        
        return empty($this->errors);
    }
    
    // Règle : Champ requis
    private function validateRequired($field) {
        $value = $this->data[$field] ?? null;
        if (empty($value)) {
            $this->addError($field, 'Ce champ est requis');
        }
    }
    
    // Règle : Longueur minimum
    private function validateMin($field, $min) {
        $value = $this->data[$field] ?? '';
        if (strlen($value) < $min) {
            $this->addError(
                $field, 
                "Ce champ doit contenir au moins {$min} caractères"
            );
        }
    }
    
    // Règle : Email valide
    private function validateEmail($field) {
        $value = $this->data[$field] ?? '';
        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
            $this->addError($field, "L'email n'est pas valide");
        }
    }
    
    // Règle : Confirmation de champ
    private function validateConfirmed($field) {
        $value = $this->data[$field] ?? '';
        $confirmation = $this->data["{$field}_confirmation"] ?? '';
        
        if ($value !== $confirmation) {
            $this->addError($field, 'La confirmation ne correspond pas');
        }
    }
    
    // Règle : Unique en base de données
    private function validateUnique($field, $table) {
        $value = $this->data[$field] ?? '';
        $db = Database::getInstance();
        
        $query = $db->prepare(
            "SELECT COUNT(*) FROM {$table} WHERE {$field} = ?"
        );
        $query->execute([$value]);
        
        if ($query->fetchColumn() > 0) {
            $this->addError($field, 'Cette valeur existe déjà');
        }
    }
    
    // Ajouter une erreur
    private function addError($field, $message) {
        $this->errors[$field][] = $message;
    }
    
    // Récupérer toutes les erreurs
    public function getErrors(): array {
        return $this->errors;
    }
}
```

## 3. Utilisation dans un Contrôleur

```php
// app/Controllers/Auth/RegisterController.php
class RegisterController extends Controller {
    public function register() {
        // Si c'est une requête POST
        if ($this->isPost()) {
            // Définir les règles de validation
            $rules = [
                'nom' => 'required|min:2',
                'email' => 'required|email|unique:users',
                'password' => 'required|min:8|confirmed'
            ];
            
            // Créer le validateur
            $validator = new Validator($_POST, $rules);
            
            // Si la validation passe
            if ($validator->validate()) {
                try {
                    // Créer l'utilisateur
                    $user = User::create([
                        'nom' => $_POST['nom'],
                        'email' => $_POST['email'],
                        'password' => password_hash($_POST['password'], PASSWORD_DEFAULT)
                    ]);
                    
                    // Connecter l'utilisateur
                    Auth::login($user);
                    
                    // Rediriger vers le tableau de bord
                    return $this->redirect('/dashboard');
                    
                } catch (Exception $e) {
                    // En cas d'erreur, afficher un message
                    $this->addError('general', "Erreur lors de l'inscription");
                }
            } else {
                // Si la validation échoue, stocker les erreurs
                $this->setErrors($validator->getErrors());
            }
        }
        
        // Afficher le formulaire
        return $this->render('auth/register', [
            'old' => $_POST // Pour repeupler le formulaire
        ]);
    }
}
```

## 4. Helpers pour les Formulaires

```php
// app/Helpers/FormHelper.php
class FormHelper {
    // Récupérer une ancienne valeur
    public static function old($field, $default = '') {
        return $_SESSION['old'][$field] ?? $default;
    }
    
    // Vérifier si un champ a une erreur
    public static function hasError($field): bool {
        return isset($_SESSION['errors'][$field]);
    }
    
    // Récupérer le message d'erreur d'un champ
    public static function getError($field): string {
        if (self::hasError($field)) {
            return $_SESSION['errors'][$field][0];
        }
        return '';
    }
    
    // Générer un champ de formulaire
    public static function input($type, $name, $label, $options = []) {
        $id = $options['id'] ?? $name;
        $class = $options['class'] ?? '';
        $required = isset($options['required']) && $options['required'] ? 'required' : '';
        $value = self::old($name, $options['value'] ?? '');
        
        $html = "<div class='form-group'>";
        $html .= "<label for='{$id}'>{$label}</label>";
        $html .= "<input type='{$type}' 
                        id='{$id}' 
                        name='{$name}' 
                        value='{$value}' 
                        class='form-control {$class}' 
                        {$required}>";
        
        if (self::hasError($name)) {
            $html .= "<div class='invalid-feedback'>";
            $html .= self::getError($name);
            $html .= "</div>";
        }
        
        $html .= "</div>";
        
        return $html;
    }
}
```

## 5. Exemple d'Utilisation des Helpers

```php
<!-- Utilisation des helpers dans une vue -->
<form method="POST" action="/register">
    <?= FormHelper::input('text', 'nom', 'Nom', [
        'required' => true,
        'class' => 'custom-input'
    ]) ?>
    
    <?= FormHelper::input('email', 'email', 'Email', [
        'required' => true
    ]) ?>
    
    <?= FormHelper::input('password', 'password', 'Mot de passe', [
        'required' => true,
        'minlength' => 8
    ]) ?>
</form>
```

## 6. Validation AJAX

```php
// public/js/form-validation.js
class FormValidator {
    constructor(form) {
        this.form = form;
        this.setupValidation();
    }
    
    setupValidation() {
        // Valider en temps réel
        this.form.querySelectorAll('input').forEach(input => {
            input.addEventListener('blur', () => this.validateField(input));
        });
        
        // Valider à la soumission
        this.form.addEventListener('submit', e => this.handleSubmit(e));
    }
    
    async validateField(input) {
        const value = input.value;
        const name = input.name;
        
        try {
            const response = await fetch('/validate-field', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ field: name, value: value })
            });
            
            const data = await response.json();
            
            if (data.valid) {
                this.setFieldValid(input);
            } else {
                this.setFieldInvalid(input, data.error);
            }
            
        } catch (error) {
            console.error('Erreur de validation:', error);
        }
    }
    
    setFieldValid(input) {
        input.classList.remove('is-invalid');
        input.classList.add('is-valid');
        
        // Supprimer le message d'erreur
        const feedback = input.parentNode.querySelector('.invalid-feedback');
        if (feedback) {
            feedback.remove();
        }
    }
    
    setFieldInvalid(input, error) {
        input.classList.remove('is-valid');
        input.classList.add('is-invalid');
        
        // Ajouter le message d'erreur
        let feedback = input.parentNode.querySelector('.invalid-feedback');
        if (!feedback) {
            feedback = document.createElement('div');
            feedback.className = 'invalid-feedback';
            input.parentNode.appendChild(feedback);
        }
        feedback.textContent = error;
    }
    
    async handleSubmit(e) {
        e.preventDefault();
        
        const formData = new FormData(this.form);
        
        try {
            const response = await fetch(this.form.action, {
                method: 'POST',
                body: formData
            });
            
            const data = await response.json();
            
            if (data.success) {
                // Rediriger ou afficher un message de succès
                window.location.href = data.redirect;
            } else {
                // Afficher les erreurs
                this.showErrors(data.errors);
            }
            
        } catch (error) {
            console.error('Erreur de soumission:', error);
        }
    }
    
    showErrors(errors) {
        for (const [field, messages] of Object.entries(errors)) {
            const input = this.form.querySelector(`[name="${field}"]`);
            if (input) {
                this.setFieldInvalid(input, messages[0]);
            }
        }
    }
}

// Initialisation
document.querySelectorAll('form[data-validate]').forEach(form => {
    new FormValidator(form);
});
```

## Conclusion

Points clés à retenir :

1. **Validation**
   - Toujours valider côté serveur
   - La validation côté client est un bonus
   - Utiliser des règles de validation réutilisables

2. **Sécurité**
   - Protection CSRF
   - Échapper les données
   - Valider les types de données

3. **Expérience Utilisateur**
   - Messages d'erreur clairs
   - Validation en temps réel
   - Conservation des données en cas d'erreur 