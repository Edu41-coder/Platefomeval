<?php

namespace Core\View;

class Template
{
    private array $data = [];
    private string $templatePath;
    private ?string $layoutName = null;
    private array $layoutData = [];
    private string $viewsPath;
    private static array $fileCache = [];
    private static array $dataCache = [];
    private static array $assetsCache = [];
    protected bool $flashScriptRendered = false;

    public function __construct(string $templatePath)
    {
        $this->viewsPath = dirname(dirname(__DIR__)) . '/app/views';
        $this->validateAndSetTemplatePath($templatePath);
        $this->initializeDefaultData();
    }

    private function validateAndSetTemplatePath(string $path): void
    {
        if (!isset(self::$fileCache[$path])) {
            self::$fileCache[$path] = $this->validateFilePath($path);
        }
        $this->templatePath = self::$fileCache[$path];
    }

    private function validateFilePath(string $path): string
    {
        if (!file_exists($path)) {
            throw new \RuntimeException("File not found: {$path}");
        }
        return $path;
    }

    private function initializeDefaultData(): void
    {
        $this->data = [
            'site_title' => 'Plateforme d\'évaluation',
            'assets' => [
        'css' => [],
        'js' => []
            ]
        ];
    }

    private function includeFile(string $path, array $data): void
    {
        if (!isset(self::$fileCache[$path])) {
            self::$fileCache[$path] = $this->validateFilePath($path);
        }
        extract($data);
        include self::$fileCache[$path];
    }

    private function getCachedData(string $key)
    {
        return self::$dataCache[$key] ?? null;
    }

    private function setCachedData(string $key, $value): void
    {
        self::$dataCache[$key] = $value;
    }

    public function with(string $key, $value): self
    {
        if ($this->getCachedData($key) !== $value) {
            $this->data[$key] = $value;
            $this->setCachedData($key, $value);
        }
        return $this;
    }

    public function withData(array $data): self
    {
        $newData = [];
        foreach ($data as $key => $value) {
            if ($this->getCachedData($key) !== $value) {
                $newData[$key] = $value;
                $this->setCachedData($key, $value);
            }
        }
        if (!empty($newData)) {
            $this->data = array_merge($this->data, $newData);
        }
        return $this;
    }

    public function shares(array $data): self
    {
        $this->data = array_merge($this->data, $data);
        return $this;
    }

    public function layout(string $name, array $data = []): self
    {
        $this->layoutName = $name;
        $this->layoutData = array_merge($this->layoutData, $data);
        return $this;
    }

    private function cleanBuffers(): void
    {
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
    }

    public function partial(string $name, array $data = []): void
    {
        $partialPath = $this->viewsPath . '/partials/' . $name . '.php';
        
        if (!file_exists($partialPath)) {
            throw new \RuntimeException("Partial file not found: {$partialPath}");
        }
        
        $this->includeFile($partialPath, array_merge($this->data, $data, ['template' => $this]));
    }

    public function get(string $key, $default = null)
    {
        return $this->data[$key] ?? $default;
    }

    public function url(string $path = '', array $params = []): string
    {
        return url($path, $params);
    }

    public function e(?string $string): string
    {
        if ($string === null) {
            return '';
        }
        return htmlspecialchars($string, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    public function asset(string $path): string
    {
        // Use the global asset helper
        return asset($path);
    }

    public function isActive(string $path): bool
    {
        $currentPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $urlPath = parse_url($this->url($path), PHP_URL_PATH);
        return strpos($currentPath, $urlPath) === 0;
    }

    public function formatDate(string $date, string $format = 'd/m/Y H:i'): string
    {
        return (new \DateTime($date))->format($format);
    }

    public function csrf_token(): string
    {
        return csrf_token();
    }

    public function csrf(): string
    {
        return '<input type="hidden" name="_token" value="' . $this->e($this->csrf_token()) . '">';
    }

    public function session($key = null, $default = null)
    {
        return session($key, $default);
    }

    private function loadAssets(): void
    {
        $cacheKey = md5(serialize($this->data['assets'] ?? []));
        if (!isset(self::$assetsCache[$cacheKey])) {
            // Traitement des assets ici
            self::$assetsCache[$cacheKey] = [
                'css' => $this->processCssAssets($this->data['assets']['css'] ?? []),
                'js' => $this->processJsAssets($this->data['assets']['js'] ?? [])
            ];
        }
        $this->data['assets'] = self::$assetsCache[$cacheKey];
    }

    private function processCssAssets(array $css): array
    {
        return array_unique($css);
    }

    private function processJsAssets(array $js): array
    {
        return array_unique($js);
    }

    public function render(): string
    {
        try {
            $this->loadAssets();
            return $this->renderTemplate();
        } catch (\Throwable $e) {
            $this->cleanBuffers();
            throw $e;
        }
    }

    private function renderTemplate(): string
    {
        $content = $this->renderView();
        return $this->layoutName === null ? $content : $this->renderWithLayout($content);
    }

    private function renderView(): string
    {
        $cacheKey = $this->getCacheKey();
        if (isset(self::$dataCache[$cacheKey])) {
            return self::$dataCache[$cacheKey];
        }

        ob_start();
        $this->includeFile($this->templatePath, ['template' => $this] + $this->data);
        $content = ob_get_clean();
        self::$dataCache[$cacheKey] = $content;
        
        return $content;
    }

    private function getCacheKey(): string
    {
        return md5(serialize([
            $this->templatePath,
            $this->data,
            $this->layoutName,
            $this->layoutData
        ]));
    }

    private function renderWithLayout(string $content): string
    {
        $layoutPath = $this->viewsPath . '/layouts/' . $this->layoutName . '.php';
        ob_start();
        $this->includeFile($layoutPath, [
            'content' => $content,
            'template' => $this
        ] + $this->data + $this->layoutData);
        return ob_get_clean();
    }

    public function isFlashScriptRendered(): bool
    {
        return $this->flashScriptRendered;
    }

    public function setFlashScriptRendered(): void
    {
        $this->flashScriptRendered = true;
    }
}
