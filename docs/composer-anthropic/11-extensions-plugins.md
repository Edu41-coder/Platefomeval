# Extensions et plugins personnalisés

## 1. Création d'un plugin de base

### Structure du plugin
```bash
mon-plugin/
├── src/
│   └── MonPlugin.php
├── tests/
│   └── MonPluginTest.php
├── composer.json
└── README.md
```

### Configuration du plugin
```json
{
    "name": "vendor/mon-plugin",
    "type": "composer-plugin",
    "require": {
        "composer-plugin-api": "^2.0"
    },
    "require-dev": {
        "composer/composer": "^2.0"
    },
    "autoload": {
        "psr-4": {
            "Vendor\\MonPlugin\\": "src/"
        }
    },
    "extra": {
        "class": "Vendor\\MonPlugin\\MonPlugin"
    }
}
```

### Classe de base du plugin
```php
namespace Vendor\MonPlugin;

use Composer\Composer;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginInterface;
use Composer\EventDispatcher\EventSubscriberInterface;

class MonPlugin implements PluginInterface, EventSubscriberInterface {
    private Composer $composer;
    private IOInterface $io;
    
    public function activate(Composer $composer, IOInterface $io): void {
        $this->composer = $composer;
        $this->io = $io;
    }
    
    public function deactivate(Composer $composer, IOInterface $io): void {
        // Logique de désactivation
    }
    
    public function uninstall(Composer $composer, IOInterface $io): void {
        // Logique de désinstallation
    }
    
    public static function getSubscribedEvents(): array {
        return [
            'post-install-cmd' => 'onPostInstall',
            'post-update-cmd' => 'onPostUpdate'
        ];
    }
}
```

## 2. Hooks et événements

### Gestionnaire d'événements
```php
namespace Vendor\MonPlugin\Events;

class EventManager {
    private array $handlers = [];
    
    public function addHandler(string $event, callable $handler): void {
        if (!isset($this->handlers[$event])) {
            $this->handlers[$event] = [];
        }
        
        $this->handlers[$event][] = $handler;
    }
    
    public function dispatch(string $event, array $data = []): void {
        if (isset($this->handlers[$event])) {
            foreach ($this->handlers[$event] as $handler) {
                $handler($data);
            }
        }
    }
}
```

### Exemple d'utilisation
```php
namespace Vendor\MonPlugin;

class MonPlugin implements PluginInterface {
    private EventManager $events;
    
    public function __construct() {
        $this->events = new EventManager();
        
        // Enregistrement des handlers
        $this->events->addHandler('pre-install', function($data) {
            $this->io->write('Préparation de l\'installation...');
        });
        
        $this->events->addHandler('post-install', function($data) {
            $this->io->write('Installation terminée !');
        });
    }
    
    public function onPostInstall(): void {
        $this->events->dispatch('post-install', [
            'time' => time(),
            'status' => 'success'
        ]);
    }
}
```

## 3. Commandes personnalisées

### Définition de commande
```php
namespace Vendor\MonPlugin\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class MaCommande extends Command {
    protected static $defaultName = 'mon-plugin:action';
    
    protected function configure(): void {
        $this->setDescription('Description de ma commande')
             ->addArgument('nom', InputArgument::REQUIRED, 'Le nom');
    }
    
    protected function execute(InputInterface $input, OutputInterface $output): int {
        $nom = $input->getArgument('nom');
        $output->writeln("Exécution pour: {$nom}");
        
        // Logique de la commande
        
        return Command::SUCCESS;
    }
}
```

### Enregistrement de la commande
```php
namespace Vendor\MonPlugin;

use Composer\Plugin\Capability\CommandProvider;
use Vendor\MonPlugin\Commands\MaCommande;

class CommandProvider implements CommandProvider {
    public function getCommands(): array {
        return [
            new MaCommande()
        ];
    }
}
```

## 4. Intégration avec l'API Composer

### Gestionnaire de packages
```php
namespace Vendor\MonPlugin\Managers;

use Composer\Package\PackageInterface;

class PackageManager {
    private Composer $composer;
    
    public function __construct(Composer $composer) {
        $this->composer = $composer;
    }
    
    public function getInstalledPackages(): array {
        return $this->composer->getRepositoryManager()
            ->getLocalRepository()
            ->getPackages();
    }
    
    public function getDependencies(PackageInterface $package): array {
        return $package->getRequires();
    }
    
    public function analyzePackage(PackageInterface $package): array {
        return [
            'name' => $package->getName(),
            'version' => $package->getVersion(),
            'dependencies' => $this->getDependencies($package),
            'type' => $package->getType(),
            'autoload' => $package->getAutoload()
        ];
    }
}
```

### Manipulation des autoloaders
```php
namespace Vendor\MonPlugin\Autoload;

class AutoloadManager {
    private Composer $composer;
    
    public function addNamespace(string $prefix, string $path): void {
        $autoload = $this->composer->getPackage()->getAutoload();
        $autoload['psr-4'][$prefix] = $path;
        
        $this->composer->getPackage()->setAutoload($autoload);
    }
    
    public function generateAutoloadFiles(): void {
        $generator = $this->composer->getAutoloadGenerator();
        $generator->dump(
            $this->composer->getConfig(),
            $this->composer->getRepositoryManager()->getLocalRepository(),
            $this->composer->getPackage(),
            $this->composer->getInstallationManager(),
            'composer',
            true
        );
    }
}
```

## 5. Validation et qualité du code

### Service de validation
```php
namespace Vendor\MonPlugin\Validation;

class CodeValidator {
    private array $rules = [];
    
    public function addRule(string $name, callable $validator): void {
        $this->rules[$name] = $validator;
    }
    
    public function validate(string $code): array {
        $errors = [];
        
        foreach ($this->rules as $name => $validator) {
            if (!$validator($code)) {
                $errors[] = "Échec de la validation: {$name}";
            }
        }
        
        return $errors;
    }
}
```

### Règles personnalisées
```php
namespace Vendor\MonPlugin\Validation;

class ValidationRules {
    public static function setupDefaultRules(CodeValidator $validator): void {
        // PSR-1
        $validator->addRule('class-naming', function($code) {
            return preg_match('/^[A-Z][a-zA-Z0-9]*$/', $code);
        });
        
        // Complexité cyclomatique
        $validator->addRule('complexity', function($code) {
            return self::calculateComplexity($code) < 10;
        });
        
        // Longueur des méthodes
        $validator->addRule('method-length', function($code) {
            return substr_count($code, "\n") < 50;
        });
    }
}
```

## 6. Génération de code

### Générateur de code
```php
namespace Vendor\MonPlugin\Generators;

class CodeGenerator {
    private array $templates = [];
    
    public function addTemplate(string $name, string $template): void {
        $this->templates[$name] = $template;
    }
    
    public function generate(string $template, array $variables): string {
        if (!isset($this->templates[$template])) {
            throw new \InvalidArgumentException("Template non trouvé: {$template}");
        }
        
        $code = $this->templates[$template];
        
        foreach ($variables as $key => $value) {
            $code = str_replace("{{ $key }}", $value, $code);
        }
        
        return $code;
    }
}
```

### Templates de code
```php
namespace Vendor\MonPlugin\Generators;

class CodeTemplates {
    public static function getDefaultTemplates(): array {
        return [
            'controller' => <<<'PHP'
namespace {{ namespace }};

class {{ class }}Controller {
    public function index(): void {
        // Code généré
    }
    
    public function show(int $id): void {
        // Code généré
    }
}
PHP,
            'model' => <<<'PHP'
namespace {{ namespace }};

class {{ class }} {
    private int $id;
    private string $name;
    
    public function getId(): int {
        return $this->id;
    }
    
    public function getName(): string {
        return $this->name;
    }
}
PHP
        ];
    }
}
```

## 7. Intégration avec les outils externes

### Service d'intégration
```php
namespace Vendor\MonPlugin\Integrations;

class IntegrationService {
    private array $integrations = [];
    
    public function register(string $name, callable $handler): void {
        $this->integrations[$name] = $handler;
    }
    
    public function execute(string $name, array $params = []): mixed {
        if (!isset($this->integrations[$name])) {
            throw new \InvalidArgumentException("Integration non trouvée: {$name}");
        }
        
        return $this->integrations[$name]($params);
    }
}
```

### Exemple d'intégration
```php
namespace Vendor\MonPlugin\Integrations;

class GitIntegration {
    public static function register(IntegrationService $service): void {
        $service->register('git:commit', function($params) {
            $message = $params['message'] ?? 'Update';
            return shell_exec("git commit -m \"{$message}\"");
        });
        
        $service->register('git:push', function($params) {
            $branch = $params['branch'] ?? 'main';
            return shell_exec("git push origin {$branch}");
        });
    }
}
```

## 8. Tests et qualité

### Tests unitaires
```php
namespace Vendor\MonPlugin\Tests;

use PHPUnit\Framework\TestCase;

class MonPluginTest extends TestCase {
    private MonPlugin $plugin;
    
    protected function setUp(): void {
        $this->plugin = new MonPlugin();
    }
    
    public function testActivation(): void {
        $composer = $this->createMock(Composer::class);
        $io = $this->createMock(IOInterface::class);
        
        $this->plugin->activate($composer, $io);
        
        $this->assertTrue($this->plugin->isActive());
    }
    
    public function testEventDispatch(): void {
        $this->expectOutputString('Installation terminée !');
        $this->plugin->onPostInstall();
    }
}
```

### Configuration PHPUnit
```xml
<?xml version="1.0" encoding="UTF-8"?>
<phpunit xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
         xsi:noNamespaceSchemaLocation="vendor/phpunit/phpunit/phpunit.xsd"
         bootstrap="vendor/autoload.php"
         colors="true">
    <testsuites>
        <testsuite name="Plugin Test Suite">
            <directory>tests</directory>
        </testsuite>
    </testsuites>
    <coverage>
        <include>
            <directory>src</directory>
        </include>
    </coverage>
</phpunit>
```

## 9. Documentation

### Générateur de documentation
```php
namespace Vendor\MonPlugin\Documentation;

class DocumentationGenerator {
    private array $sections = [];
    
    public function addSection(string $title, string $content): void {
        $this->sections[] = [
            'title' => $title,
            'content' => $content
        ];
    }
    
    public function generate(): string {
        $markdown = "# Documentation du plugin\n\n";
        
        foreach ($this->sections as $section) {
            $markdown .= "## {$section['title']}\n\n";
            $markdown .= "{$section['content']}\n\n";
        }
        
        return $markdown;
    }
}
```

## 10. Distribution

### Script de build
```php
namespace Vendor\MonPlugin\Build;

class BuildManager {
    public function build(): void {
        // Nettoyage
        $this->cleanup();
        
        // Compilation
        $this->compile();
        
        // Tests
        $this->runTests();
        
        // Documentation
        $this->generateDocs();
        
        // Package
        $this->package();
    }
    
    private function compile(): void {
        // Compilation du code
        Process::run('composer dump-autoload --optimize');
    }
    
    private function package(): void {
        $version = $this->getVersion();
        Process::run("git archive --format=zip HEAD -o plugin-{$version}.zip");
    }
}
```

## Conclusion

Points clés pour les extensions :
1. Architecture modulaire
2. Intégration avec Composer
3. Tests complets
4. Documentation claire
5. Distribution simplifiée

Recommandations :
- Suivre les standards PSR
- Tester exhaustivement
- Documenter l'API
- Maintenir la compatibilité
- Publier sur Packagist 