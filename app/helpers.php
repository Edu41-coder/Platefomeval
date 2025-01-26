<?php

if (!function_exists('url')) {
    /**
     * Génère une URL pour une route nommée
     *
     * @param string $path Chemin de la route
     * @param array $params Paramètres de la route
     * @return string
     */
    function url(string $path = '', array $params = []): string
    {
        $basePath = defined('BASE_PATH') ? BASE_PATH : '/Plateformeval';
        
        // Clean the input path
        $path = trim($path, '/');

        // Handle home route
        if ($path === 'home') {
            $path = '';
        }

        // Convert route names to paths and handle parameters
        if (strpos($path, 'professor.') === 0) {
            $parts = explode('.', substr($path, strlen('professor.')));
            $path = 'professor';  // Pas de slash à la fin
            
            if ($parts[0] === 'matieres') {
                $path .= '/matieres';  // Ajouter le slash ici
                
                if (count($parts) === 1) {
                    if (isset($params['matiere_id'])) {
                        unset($params['matiere_id']);
                    }
                }
                elseif (isset($parts[1])) {
                    if ($parts[1] === 'show' && isset($params['id'])) {
                        $path .= '/' . $params['id'];
                        unset($params['id']);
                    }
                    elseif ($parts[1] === 'evaluations') {
                        if (isset($params['matiere_id'])) {
                            $path .= '/' . $params['matiere_id'] . '/evaluations';
                            unset($params['matiere_id']);
                            
                            if (isset($parts[2])) {
                                if ($parts[2] === 'create') {
                                    $path .= '/create';
                                }
                                elseif ($parts[2] !== 'index' && $parts[2] !== 'store') {
                                    $path .= '/' . $parts[2];
                                }
                            }
                        }
                    }
                }
            }
        } elseif (strpos($path, 'admin.') === 0) {
            $parts = explode('.', substr($path, strlen('admin.')));
            $path = 'admin/';
            
            if ($parts[0] === 'users' && isset($parts[1]) && $parts[1] === 'delete' && isset($params['id'])) {
                $path .= 'users/' . $params['id'] . '/delete';
                unset($params['id']);
            } else {
                $path .= implode('/', $parts);
            }
        } elseif (strpos($path, 'student.') === 0) {
            $parts = explode('.', substr($path, strlen('student.')));
            $path = 'student';
            
            if ($parts[0] === 'matieres') {
                $path .= '/matieres';
                
                if (count($parts) === 1) {
                    if (isset($params['matiere_id'])) {
                        unset($params['matiere_id']);
                    }
                }
                elseif (isset($parts[1])) {
                    if ($parts[1] === 'show' && isset($params['id'])) {
                        $path .= '/' . $params['id'];
                        unset($params['id']);
                    }
                    elseif ($parts[1] === 'evaluations') {
                        if (isset($params['matiere_id'])) {
                            $path .= '/' . $params['matiere_id'] . '/evaluations';
                            unset($params['matiere_id']);
                            
                            if (isset($parts[2]) && $parts[2] !== 'index') {
                                $path .= '/' . $parts[2];
                            }
                        }
                    }
                }
            }
            elseif ($parts[0] === 'evaluations') {
                $path .= '/evaluations';
                if (isset($parts[1]) && $parts[1] !== 'index') {
                    $path .= '/' . $parts[1];
                }
            }
        } elseif (strpos($path, '.') !== false) {
            $parts = explode('.', $path);
            $path = implode('/', $parts);
        }

        // Clean up the path
        $path = trim($path, '/');
        
        // Construct final URL
        $finalPath = rtrim($basePath, '/');
        
        // Add public prefix for non-API routes
        if (!empty($path)) {
            if (strpos($path, 'api/') !== 0) {
                $finalPath .= '/public/' . $path;
            } else {
                $finalPath .= '/' . $path;
            }
        } else {
            $finalPath .= '/public';
        }

        // Add remaining query parameters
        if (!empty($params)) {
            $finalPath .= '?' . http_build_query($params);
        }

        return $finalPath;
    }
}

if (!function_exists('redirect_path')) {
    /**
     * Génère une URL de redirection
     *
     * @param string $path Chemin de redirection
     * @return string
     */
    function redirect_path(string $path = ''): string
    {
        $basePath = defined('BASE_PATH') ? BASE_PATH : '/Plateformeval';
        
        // Nettoyer le chemin d'entrée et gérer les cas spéciaux
        $path = trim($path, '/');
        if ($path === 'home' || $path === '/') {
            $path = '';
        }
        
        // Construire l'URL finale
        $finalPath = rtrim($basePath, '/');
        
        // Ajouter le préfixe public uniquement si le chemin n'est pas vide et n'est pas une route API
        if (!empty($path)) {
            if (strpos($path, 'api/') !== 0) {
                // Vérifiez si le chemin ne contient pas déjà 'public'
                if (strpos($path, 'public/') === false) {
                    $finalPath .= '/public/' . $path;
                } else {
                    $finalPath .= '/' . $path; // Si 'public/' est déjà présent, ajoutez simplement le chemin
                }
            } else {
                $finalPath .= '/' . $path; // Pour les routes API
            }
        } else {
            // Pour le chemin racine, ajouter simplement public
            $finalPath .= '/public';
        }
        
        error_log('Final constructed path: ' . $finalPath);
        return $finalPath;
    }
}

if (!function_exists('asset')) {
    /**
     * Génère une URL pour un fichier d'assets
     *
     * @param string $path Chemin du fichier dans le dossier public/assets
     * @return string
     */
    function asset(string $path): string
    {
        $basePath = defined('BASE_PATH') ? BASE_PATH : '/Plateformeval';
        
        // Clean the path and remove any existing 'assets' prefix
        $path = trim($path, '/');
        $path = preg_replace('#^assets/#', '', $path);
        
        // Ensure we have the correct assets path
        return $basePath . '/public/assets/' . $path;
    }
}

if (!function_exists('view')) {
    /**
     * Charge une vue
     *
     * @param string $name Nom de la vue
     * @param array $data Données à passer à la vue
     * @return string
     */
    function view(string $name, array $data = []): string
    {
        $viewPath = __DIR__ . '/views/' . str_replace('.', '/', $name) . '.php';
        if (!file_exists($viewPath)) {
            throw new \Exception("View not found: $name");
        }

        extract($data);
        ob_start();
        include $viewPath;
        return ob_get_clean();
    }
}

if (!function_exists('redirect')) {
    /**
     * Redirige vers une URL
     *
     * @param string $url URL de redirection
     * @return void
     */
    function redirect(string $url): void
    {
        header('Location: ' . $url);
        exit;
    }
}

if (!function_exists('session')) {
    /**
     * Accède à la session
     *
     * @param string|null $key Clé de session
     * @param mixed $default Valeur par défaut
     * @return mixed
     */
    function session(?string $key = null, $default = null)
    {
        if ($key === null) {
            return $_SESSION;
        }
        return $_SESSION[$key] ?? $default;
    }
}

if (!function_exists('csrf_token')) {
    /**
     * Récupère le token CSRF actuel
     *
     * @return string|null
     */
    function csrf_token(): ?string
    {
        return \App\Security\CsrfToken::getToken();
    }
}

if (!function_exists('ensureAuthenticated')) {
    /**
     * Vérifie si l'utilisateur est authentifié, sinon redirige vers la page de connexion
     *
     * @param mixed $user L'utilisateur à vérifier
     * @param string $loginUrl URL de la page de connexion
     * @return void
     */
    function ensureAuthenticated($user, string $loginUrl = '/login'): void
    {
        if (!$user) {
            redirect($loginUrl);
        }
    }
} 