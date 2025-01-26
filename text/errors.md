Erreurs d'authentification et d'autorisation
------------------------------------------
• 401 (Unauthorized)
  ⚠ L'utilisateur n'est pas authentifié
  ✓ Solution : Se connecter
  ℹ Exemple : Tentative d'accès à une page privée sans être connecté

• 403 (Forbidden)
  ⚠ L'utilisateur est authentifié mais n'a pas les droits nécessaires
  ✓ Solution : Obtenir les permissions requises
  ℹ Exemple : Un étudiant essaie d'accéder à une page réservée aux administrateurs


Erreurs de navigation
--------------------
• 404 (Not Found)
  ⚠ La ressource demandée n'existe pas
  ✓ Solution : Vérifier l'URL
  ℹ Exemple : L'utilisateur clique sur un lien mort ou tape une mauvaise URL

• 405 (Method Not Allowed)
  ⚠ La méthode HTTP utilisée n'est pas autorisée pour cette route
  ✓ Solution : Utiliser la bonne méthode (GET, POST, etc.)
  ℹ Exemple : Tentative d'accès en POST à une route qui n'accepte que GET


Erreurs de limitation
--------------------
• 429 (Too Many Requests)
  ⚠ Trop de requêtes dans un délai donné
  ✓ Solution : Attendre avant de réessayer
  ℹ Exemple : Un utilisateur rafraîchit une page trop rapidement


Erreurs serveur
--------------
• 500 (Internal Server Error)
  ⚠ Erreur interne du serveur
  ✓ Solution : Contacter l'administrateur
  ℹ Exemple : Une exception non gérée, une erreur de base de données