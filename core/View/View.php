<?php

namespace Core\View;

class View
{
    /**
     * Dossier contenant les vues
     */
    private string $viewPath;

    /**
     * Dossier contenant les layouts
     */
    private string $layoutPath;

    /**
     * Layout par défaut
     */
    private string $layout = 'default';

    /**
     * Variables partagées entre toutes les vues
     */
    private array $sharedData = [];

    public function __construct()
    {
        $this->viewPath = dirname(__DIR__, 2) . '/app/views/';
        $this->layoutPath = $this->viewPath . 'layouts/';
    }

    /**
     * Rend une vue
     */
    public function render(string $view, array $data = []): string
    {
        try {
            // Combine les données partagées avec les données spécifiques
            $data = array_merge($this->sharedData, $data);

            // Crée un template pour la vue
            $viewTemplate = new Template(
                $this->viewPath . str_replace('.', '/', $view) . '.php'
            );
            $viewTemplate->withData($data);

            // Récupère le contenu de la vue
            $content = $viewTemplate->render();

            // Crée un template pour le layout
            $layoutTemplate = new Template(
                $this->layoutPath . $this->layout . '.php'
            );

            // Passe le contenu et les données au layout
            return $layoutTemplate
                ->with('content', $content)
                ->withData($data)
                ->render();
        } catch (\Throwable $e) {
            throw new \RuntimeException(
                "Erreur lors du rendu de la vue: " . $e->getMessage(),
                0,
                $e
            );
        }
    }

    /**
     * Gestion des données partagées
     */
    public function getSharedData(): array
    {
        return $this->sharedData;
    }

    public function getShared(string $key, $default = null)
    {
        return $this->sharedData[$key] ?? $default;
    }

    public function hasShared(string $key): bool
    {
        return isset($this->sharedData[$key]);
    }

    public function share(string $key, $value): self
    {
        $this->sharedData[$key] = $value;
        return $this;
    }

    public function shares(array $data): self
    {
        $this->sharedData = array_merge($this->sharedData, $data);
        return $this;
    }

    public function removeShared(string $key): self
    {
        unset($this->sharedData[$key]);
        return $this;
    }

    public function clearSharedData(): self
    {
        $this->sharedData = [];
        return $this;
    }
    /**
     * Gestion du layout
     */
    public function setLayout(string $layout): self
    {
        $this->layout = $layout;
        return $this;
    }

    /**
     * Inclut une vue partielle
     */
    public function partial(string $name, array $data = []): void
    {
        $data = array_merge($this->sharedData, $data);
        $template = new Template(
            $this->viewPath . 'partials/' . $name . '.php'
        );
        echo $template->withData($data)->render();
    }

    /**
     * Helpers HTML
     */
    public function e(?string $string): string
    {
        if ($string === null) {
            return '';
        }
        return htmlspecialchars($string, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    public function print(?string $string): void
    {
        echo $this->e($string);
    }

    public function selected($value, $current): string
    {
        return $value === $current ? ' selected' : '';
    }

    public function checked($value, $current): string
    {
        return $value === $current ? ' checked' : '';
    }

    /**
     * Helpers d'URL et de date
     */
    public function formatDate(string $date, string $format = 'd/m/Y H:i'): string
    {
        return (new \DateTime($date))->format($format);
    }

    public function url(string $path = '', array $params = []): string
    {
        return url($path, $params);
    }

    public function isActive(string $path): bool
    {
        $currentPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $urlPath = parse_url($this->url($path), PHP_URL_PATH);
        return strpos($currentPath, $urlPath) === 0;
    }
}
