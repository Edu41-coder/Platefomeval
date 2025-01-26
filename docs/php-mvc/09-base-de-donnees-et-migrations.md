# Base de Données et Migrations

## 1. Conception de la Base de Données

### Structure de Base
```sql
-- Exemple de schéma de base
CREATE TABLE users (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    remember_token VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE posts (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    title VARCHAR(255) NOT NULL,
    content TEXT,
    published_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
```

## 2. Système de Migration

### Gestionnaire de Migration
```php
namespace App\Database;

class Migration {
    protected $db;
    protected $migrations = [];
    
    public function __construct(PDO $db) {
        $this->db = $db;
    }
    
    public function migrate(): void {
        $this->createMigrationsTable();
        $pendingMigrations = $this->getPendingMigrations();
        
        foreach ($pendingMigrations as $migration) {
            $instance = new $migration();
            
            $this->runMigration($instance);
            $this->logMigration($migration);
        }
    }
    
    protected function createMigrationsTable(): void {
        $sql = "CREATE TABLE IF NOT EXISTS migrations (
            id INT AUTO_INCREMENT PRIMARY KEY,
            migration VARCHAR(255),
            batch INT,
            executed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )";
        
        $this->db->exec($sql);
    }
    
    protected function getPendingMigrations(): array {
        $executed = $this->getExecutedMigrations();
        return array_diff($this->migrations, $executed);
    }
}
```

### Exemple de Migration
```php
namespace App\Database\Migrations;

class CreateUsersTable implements MigrationInterface {
    public function up(): string {
        return "CREATE TABLE users (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            email VARCHAR(255) NOT NULL UNIQUE,
            password VARCHAR(255) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )";
    }
    
    public function down(): string {
        return "DROP TABLE IF EXISTS users";
    }
}
```

## 3. Relations et Clés Étrangères

### Définition des Relations
```php
namespace App\Database\Schema;

class Table {
    private $name;
    private $columns = [];
    private $foreignKeys = [];
    
    public function foreignKey(string $column): ForeignKeyDefinition {
        $foreignKey = new ForeignKeyDefinition($this, $column);
        $this->foreignKeys[] = $foreignKey;
        return $foreignKey;
    }
    
    public function create(): string {
        $sql = "CREATE TABLE {$this->name} (\n";
        $sql .= implode(",\n", $this->columns);
        
        foreach ($this->foreignKeys as $foreignKey) {
            $sql .= ",\n" . $foreignKey->toString();
        }
        
        return $sql . "\n)";
    }
}

class ForeignKeyDefinition {
    private $column;
    private $references;
    private $on;
    private $onDelete;
    private $onUpdate;
    
    public function references(string $table): self {
        $this->references = $table;
        return $this;
    }
    
    public function on(string $column): self {
        $this->on = $column;
        return $this;
    }
    
    public function onDelete(string $action): self {
        $this->onDelete = $action;
        return $this;
    }
    
    public function toString(): string {
        return "FOREIGN KEY ({$this->column}) REFERENCES {$this->references}({$this->on})"
            . ($this->onDelete ? " ON DELETE {$this->onDelete}" : "");
    }
}
```

## 4. Indexes et Performance

### Gestion des Index
```php
namespace App\Database\Schema;

class IndexManager {
    private $db;
    
    public function createIndex(string $table, string $column, bool $unique = false): void {
        $type = $unique ? 'UNIQUE' : '';
        $indexName = $this->generateIndexName($table, $column);
        
        $sql = "CREATE {$type} INDEX {$indexName} ON {$table} ({$column})";
        $this->db->exec($sql);
    }
    
    public function createCompositeIndex(string $table, array $columns): void {
        $indexName = $this->generateCompositeIndexName($table, $columns);
        $columnList = implode(', ', $columns);
        
        $sql = "CREATE INDEX {$indexName} ON {$table} ({$columnList})";
        $this->db->exec($sql);
    }
    
    public function dropIndex(string $table, string $indexName): void {
        $sql = "DROP INDEX {$indexName} ON {$table}";
        $this->db->exec($sql);
    }
}
```

## 5. Seeding et Données de Test

### Système de Seeding
```php
namespace App\Database\Seeds;

abstract class Seeder {
    protected $db;
    protected $faker;
    
    public function __construct(PDO $db) {
        $this->db = $db;
        $this->faker = Factory::create();
    }
    
    abstract public function run(): void;
}

class UserSeeder extends Seeder {
    public function run(): void {
        $stmt = $this->db->prepare("
            INSERT INTO users (name, email, password) 
            VALUES (:name, :email, :password)
        ");
        
        for ($i = 0; $i < 10; $i++) {
            $stmt->execute([
                'name' => $this->faker->name,
                'email' => $this->faker->unique()->safeEmail,
                'password' => password_hash('password', PASSWORD_DEFAULT)
            ]);
        }
    }
}
```

## 6. Transactions et Intégrité

### Gestionnaire de Transaction
```php
namespace App\Database;

class TransactionManager {
    private $db;
    private $transactionLevel = 0;
    
    public function begin(): void {
        if ($this->transactionLevel === 0) {
            $this->db->beginTransaction();
        }
        $this->transactionLevel++;
    }
    
    public function commit(): void {
        if ($this->transactionLevel === 1) {
            $this->db->commit();
        }
        $this->transactionLevel = max(0, $this->transactionLevel - 1);
    }
    
    public function rollback(): void {
        if ($this->transactionLevel === 1) {
            $this->db->rollBack();
        }
        $this->transactionLevel = max(0, $this->transactionLevel - 1);
    }
    
    public function transaction(callable $callback) {
        $this->begin();
        
        try {
            $result = $callback();
            $this->commit();
            return $result;
        } catch (Exception $e) {
            $this->rollback();
            throw $e;
        }
    }
}
```

## 7. Backup et Restauration

### Service de Backup
```php
namespace App\Database;

class BackupManager {
    private $db;
    private $backupPath;
    
    public function createBackup(): string {
        $filename = date('Y-m-d_His') . '_backup.sql';
        $filepath = $this->backupPath . '/' . $filename;
        
        $tables = $this->getTables();
        $dump = '';
        
        foreach ($tables as $table) {
            $dump .= $this->getTableStructure($table);
            $dump .= $this->getTableData($table);
        }
        
        file_put_contents($filepath, $dump);
        return $filepath;
    }
    
    public function restore(string $filepath): void {
        $sql = file_get_contents($filepath);
        $statements = explode(';', $sql);
        
        $this->db->beginTransaction();
        
        try {
            foreach ($statements as $statement) {
                if (!empty(trim($statement))) {
                    $this->db->exec($statement);
                }
            }
            $this->db->commit();
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
}
```

## 8. Monitoring et Maintenance

### Database Monitor
```php
namespace App\Database;

class DatabaseMonitor {
    private $db;
    private $logger;
    
    public function checkHealth(): array {
        return [
            'connection' => $this->checkConnection(),
            'size' => $this->getDatabaseSize(),
            'tables' => $this->getTableStatistics(),
            'slow_queries' => $this->getSlowQueries()
        ];
    }
    
    public function optimize(): void {
        $tables = $this->getTables();
        
        foreach ($tables as $table) {
            $this->db->exec("OPTIMIZE TABLE {$table}");
        }
    }
    
    public function analyze(): array {
        $results = [];
        $tables = $this->getTables();
        
        foreach ($tables as $table) {
            $results[$table] = $this->db->query("ANALYZE TABLE {$table}")->fetch();
        }
        
        return $results;
    }
}
```

## 9. Migrations Avancées

### Migration avec Dépendances
```php
namespace App\Database\Migrations;

class CreatePostsTable implements MigrationInterface {
    public function dependencies(): array {
        return [
            CreateUsersTable::class
        ];
    }
    
    public function up(): string {
        return "CREATE TABLE posts (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id BIGINT UNSIGNED NOT NULL,
            title VARCHAR(255) NOT NULL,
            content TEXT,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )";
    }
    
    public function down(): string {
        return "DROP TABLE IF EXISTS posts";
    }
}
```

## 10. Versioning de Schema

### Schema Version Manager
```php
namespace App\Database;

class SchemaVersionManager {
    private $db;
    private $table = 'schema_versions';
    
    public function getCurrentVersion(): string {
        $stmt = $this->db->query("
            SELECT version FROM {$this->table} 
            ORDER BY id DESC LIMIT 1
        ");
        return $stmt->fetchColumn() ?: '0.0.0';
    }
    
    public function updateVersion(string $version): void {
        $stmt = $this->db->prepare("
            INSERT INTO {$this->table} (version) 
            VALUES (:version)
        ");
        $stmt->execute(['version' => $version]);
    }
    
    public function getHistory(): array {
        return $this->db->query("
            SELECT version, created_at 
            FROM {$this->table} 
            ORDER BY id DESC
        ")->fetchAll();
    }
}
```

## Conclusion

Points clés pour la Base de Données :
1. Conception robuste
2. Migrations versionnées
3. Intégrité des données
4. Performance optimisée
5. Maintenance régulière

Recommandations :
- Planifier le schéma soigneusement
- Utiliser les migrations
- Maintenir les index appropriés
- Sauvegarder régulièrement
- Surveiller les performances
  </rewritten_file> 