<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>403 - Accès interdit</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f5f5f5;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }
        .error-container {
            text-align: center;
            padding: 2rem;
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        h1 { color: #d35400; }  /* Orange foncé pour distinguer du 401 */
        p { color: #666; }
        a {
            color: #3498db;
            text-decoration: none;
        }
        a:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <div class="error-container">
        <h1>403 - Accès interdit</h1>
        <p>Vous n'avez pas les permissions nécessaires pour accéder à cette page.</p>
        <p><a href="/Plateformeval/dashboard">Retourner au tableau de bord</a> ou <a href="/Plateformeval/">retourner à l'accueil</a></p>
    </div>
</body>
</html>