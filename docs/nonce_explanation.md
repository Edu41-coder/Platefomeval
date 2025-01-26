# Le Nonce en Sécurité Web

## Définition
Le nonce (Number used ONCE) est un nombre ou une chaîne de caractères unique généré pour une utilisation unique dans un contexte de sécurité web.

## Utilisations Principales

### 1. Sécurité CSP (Content Security Policy)
- **Autorisation de scripts** : Permet d'autoriser des scripts inline spécifiques
- **Validation** : Chaque script doit avoir le même nonce que celui défini dans l'en-tête CSP
- **Protection** : Empêche l'injection de scripts malveillants non autorisés

### 2. Protection contre les attaques de rejeu
- **Usage unique** : Un nonce ne peut être utilisé qu'une seule fois
- **Sécurité** : Empêche qu'une requête valide soit rejouée par un attaquant
- **Validation** : Garantit la fraîcheur de chaque requête

## Implémentation dans notre Application

### Situation actuelle
- Erreur initiale : `Undefined variable $nonce` dans la vue login.php
- Solution : Rendre le nonce optionnel dans le code

### Modifications apportées
```php
<script <?= isset($nonce) ? "nonce=\"{$nonce}\"" : '' ?> src="..."></script>
```

### Résumé
1. Le nonce est un m��canisme de sécurité pour les scripts inline
2. Non strictement nécessaire sans CSP stricte
3. Rendu optionnel pour éviter les warnings

## Remarques
- L'application fonctionne normalement sans CSP stricte
- Le warning a été résolu
- La sécurité reste maintenue même sans nonce dans ce contexte

---
*Note : Ce document peut être mis à jour selon l'évolution des besoins en sécurité de l'application.* 