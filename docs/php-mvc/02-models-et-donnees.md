# Models et Couche de Données

## 1. Rôle des Models

Les Models représentent la couche de données et la logique métier de l'application. Ils sont responsables de :
- La gestion des données
- Les règles métier
- La validation des données
- Les interactions avec la base de données

## 2. Structure des Models

### Model de Base
```php
namespace App\Models;

class BaseModel {
    protected $db;
    protected $table;
    protected $fillable = [];
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    public function find($id) {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }
    
    public function all() {
        $stmt = $this->db->query("SELECT * FROM {$this->table}");
        return $stmt->fetchAll();
    }
}
```

### Model Spécifique
```php
namespace App\Models;

class User extends BaseModel {
    protected $table = 'users';
    protected $fillable = ['name', 'email', 'password'];
    
    public function posts() {
        return $this->hasMany(Post::class);
    }
    
    public function validatePassword($password) {
        return password_verify($password, $this->password);
    }
}
```

## 3. Patterns de Conception pour les Models

### Active Record
```php
class User extends ActiveRecord {
    public function save() {
        if ($this->isValid()) {
            $this->db->query("INSERT INTO users (name, email) VALUES (?, ?)", 
                [$this->name, $this->email]);
        }
    }
    
    public static function findByEmail($email) {
        return static::where('email', $email)->first();
    }
}

// Utilisation
$user = new User();
$user->name = "John Doe";
$user->email = "john@example.com";
$user->save();
```

### Data Mapper
```php
class UserMapper {
    private $db;
    
    public function save(User $user) {
        $stmt = $this->db->prepare("INSERT INTO users (name, email) VALUES (?, ?)");
        $stmt->execute([$user->getName(), $user->getEmail()]);
    }
    
    public function find($id): ?User {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$id]);
        $data = $stmt->fetch();
        
        return $data ? $this->mapToObject($data) : null;
    }
}
```

## 4. Validation des Données

### Système de Validation
```php
class Validator {
    private $rules = [];
    private $errors = [];
    
    public function validate($data, $rules) {
        foreach ($rules as $field => $fieldRules) {
            foreach ($fieldRules as $rule) {
                if (!$this->validateRule($data[$field], $rule)) {
                    $this->errors[$field][] = $this->getErrorMessage($rule);
                }
            }
        }
        
        return empty($this->errors);
    }
}

// Utilisation dans le Model
class User extends BaseModel {
    public function validate() {
        $validator = new Validator();
        return $validator->validate($this->attributes, [
            'email' => ['required', 'email', 'unique:users'],
            'password' => ['required', 'min:8']
        ]);
    }
}
```

## 5. Relations entre Models

### Définition des Relations
```php
class User extends BaseModel {
    public function posts() {
        return $this->hasMany(Post::class);
    }
    
    public function profile() {
        return $this->hasOne(Profile::class);
    }
    
    public function roles() {
        return $this->belongsToMany(Role::class);
    }
}
```

### Chargement des Relations
```php
class PostRepository {
    public function getWithAuthor($id) {
        return $this->createQueryBuilder()
            ->select('p.*, u.*')
            ->from('posts', 'p')
            ->join('users', 'u', 'p.user_id = u.id')
            ->where('p.id = ?')
            ->setParameter(0, $id)
            ->execute()
            ->fetch();
    }
}
```

## 6. Événements et Observers

### Système d'Événements
```php
class Model {
    protected static $events = [];
    
    protected static function bootEvents() {
        static::creating(function($model) {
            $model->created_at = new DateTime();
        });
        
        static::updating(function($model) {
            $model->updated_at = new DateTime();
        });
    }
    
    public static function creating($callback) {
        static::$events['creating'][] = $callback;
    }
}
```

### Observer
```php
class UserObserver {
    public function created(User $user) {
        // Envoyer email de bienvenue
        Mail::send($user->email, 'Welcome!');
    }
    
    public function deleted(User $user) {
        // Nettoyer les données associées
        Storage::delete("users/{$user->id}");
    }
}
```

## 7. Transactions et Intégrité des Données

### Gestion des Transactions
```php
class OrderService {
    public function createOrder(array $data) {
        try {
            $this->db->beginTransaction();
            
            $order = Order::create($data);
            $this->processPayment($order);
            $this->updateInventory($order);
            
            $this->db->commit();
            return $order;
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
}
```

## 8. Caching et Performance

### Système de Cache
```php
class CacheableModel extends BaseModel {
    protected function getCached($key, $callback) {
        if (Cache::has($key)) {
            return Cache::get($key);
        }
        
        $value = $callback();
        Cache::put($key, $value, 3600);
        return $value;
    }
    
    public function all() {
        return $this->getCached("model.all", function() {
            return parent::all();
        });
    }
}
```

## Conclusion

Points clés pour les Models :
1. Encapsulation de la logique métier
2. Validation robuste des données
3. Relations bien définies
4. Gestion efficace des événements
5. Performance optimisée

Recommandations :
- Utiliser des patterns appropriés
- Implémenter une validation stricte
- Gérer les relations efficacement
- Maintenir la cohérence des données
- Optimiser les performances
  </rewritten_file> 