# Intégrations et extensions avancées

## 1. Intégration avec les bases de données

### Configuration Doctrine ORM
```php
// config/database.php
return [
    'driver' => 'pdo_mysql',
    'host' => 'localhost',
    'dbname' => 'app_db',
    'user' => 'db_user',
    'password' => 'db_password',
    'charset' => 'utf8mb4'
];

// bootstrap.php
use Doctrine\ORM\Tools\Setup;
use Doctrine\ORM\EntityManager;

$config = Setup::createAnnotationMetadataConfiguration(
    [__DIR__ . '/src/Entity'],
    true,
    null,
    null,
    false
);

$entityManager = EntityManager::create(
    require __DIR__ . '/config/database.php',
    $config
);
```

### Entité avec relations
```php
namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * @ORM\Entity(repositoryClass="App\Repository\OrderRepository")
 * @ORM\Table(name="orders")
 */
class Order {
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private int $id;

    /**
     * @ORM\ManyToOne(targetEntity="User", inversedBy="orders")
     */
    private User $user;

    /**
     * @ORM\OneToMany(targetEntity="OrderItem", mappedBy="order", cascade={"persist"})
     */
    private $items;

    public function __construct() {
        $this->items = new ArrayCollection();
    }
}
```

## 2. Système de cache avancé

### Configuration Redis
```php
// config/cache.php
return [
    'redis' => [
        'host' => '127.0.0.1',
        'port' => 6379,
        'timeout' => 0.0,
    ]
];

// CacheService.php
namespace App\Services;

use Redis;

class CacheService {
    private Redis $redis;
    
    public function __construct(array $config) {
        $this->redis = new Redis();
        $this->redis->connect(
            $config['redis']['host'],
            $config['redis']['port'],
            $config['redis']['timeout']
        );
    }
    
    public function remember(string $key, int $ttl, callable $callback) {
        if ($cached = $this->redis->get($key)) {
            return unserialize($cached);
        }
        
        $fresh = $callback();
        $this->redis->setex($key, $ttl, serialize($fresh));
        
        return $fresh;
    }
}
```

### Utilisation du cache
```php
namespace App\Services;

class ProductService {
    private CacheService $cache;
    private ProductRepository $repository;
    
    public function getProducts(): array {
        return $this->cache->remember('products', 3600, function() {
            return $this->repository->findAll();
        });
    }
    
    public function invalidateCache(): void {
        $this->cache->delete('products');
    }
}
```

## 3. Intégration API externe

### Client HTTP avec Guzzle
```php
namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

class PaymentService {
    private Client $client;
    private string $apiKey;
    
    public function __construct(string $apiKey) {
        $this->client = new Client([
            'base_uri' => 'https://api.stripe.com/v1/',
            'headers' => [
                'Authorization' => 'Bearer ' . $apiKey,
                'Content-Type' => 'application/json'
            ]
        ]);
    }
    
    public function createPayment(array $data): array {
        try {
            $response = $this->client->post('payments', [
                'json' => $data
            ]);
            
            return json_decode($response->getBody()->getContents(), true);
        } catch (GuzzleException $e) {
            throw new PaymentException($e->getMessage());
        }
    }
}
```

### Service d'intégration
```php
namespace App\Services;

class StripeIntegrationService {
    private PaymentService $paymentService;
    private OrderRepository $orderRepository;
    
    public function processOrder(Order $order): void {
        try {
            $payment = $this->paymentService->createPayment([
                'amount' => $order->getTotal() * 100, // en centimes
                'currency' => 'eur',
                'customer' => $order->getUser()->getStripeId(),
                'description' => "Commande #{$order->getId()}"
            ]);
            
            $order->setPaymentId($payment['id']);
            $order->setStatus('paid');
            
            $this->orderRepository->save($order);
        } catch (PaymentException $e) {
            $order->setStatus('payment_failed');
            $this->orderRepository->save($order);
            throw $e;
        }
    }
}
```

## 4. File d'attente et jobs

### Configuration RabbitMQ
```php
// config/queue.php
return [
    'rabbitmq' => [
        'host' => 'localhost',
        'port' => 5672,
        'user' => 'guest',
        'password' => 'guest',
        'vhost' => '/'
    ]
];

// QueueService.php
namespace App\Services;

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

class QueueService {
    private AMQPStreamConnection $connection;
    
    public function __construct(array $config) {
        $this->connection = new AMQPStreamConnection(
            $config['rabbitmq']['host'],
            $config['rabbitmq']['port'],
            $config['rabbitmq']['user'],
            $config['rabbitmq']['password'],
            $config['rabbitmq']['vhost']
        );
    }
    
    public function publish(string $queue, array $data): void {
        $channel = $this->connection->channel();
        $channel->queue_declare($queue, false, true, false, false);
        
        $message = new AMQPMessage(json_encode($data));
        $channel->basic_publish($message, '', $queue);
        
        $channel->close();
    }
}
```

### Job et Worker
```php
namespace App\Jobs;

abstract class Job {
    abstract public function handle(): void;
    
    public static function dispatch(array $data): void {
        $queueService = app(QueueService::class);
        $queueService->publish(static::class, $data);
    }
}

class SendEmailJob extends Job {
    private array $data;
    
    public function __construct(array $data) {
        $this->data = $data;
    }
    
    public function handle(): void {
        // Logique d'envoi d'email
        mail(
            $this->data['to'],
            $this->data['subject'],
            $this->data['body']
        );
    }
}
```

## 5. Websockets temps réel

### Configuration Pusher
```php
// config/websocket.php
return [
    'pusher' => [
        'app_id' => 'your_app_id',
        'key' => 'your_key',
        'secret' => 'your_secret',
        'cluster' => 'eu'
    ]
];

// WebsocketService.php
namespace App\Services;

use Pusher\Pusher;

class WebsocketService {
    private Pusher $pusher;
    
    public function __construct(array $config) {
        $this->pusher = new Pusher(
            $config['pusher']['key'],
            $config['pusher']['secret'],
            $config['pusher']['app_id'],
            [
                'cluster' => $config['pusher']['cluster'],
                'useTLS' => true
            ]
        );
    }
    
    public function broadcast(string $channel, string $event, array $data): void {
        $this->pusher->trigger($channel, $event, $data);
    }
}
```

### Utilisation temps réel
```php
namespace App\Controllers;

class NotificationController {
    private WebsocketService $websocket;
    
    public function sendNotification(string $userId, array $data): void {
        // Enregistrement en base de données
        $notification = $this->notificationRepository->create($data);
        
        // Broadcast en temps réel
        $this->websocket->broadcast(
            "user.{$userId}",
            'notification.received',
            $notification->toArray()
        );
    }
}
```

## 6. Système de logging avancé

### Configuration Monolog
```php
// config/logging.php
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\SlackHandler;

return [
    'channels' => [
        'file' => [
            'handler' => StreamHandler::class,
            'path' => __DIR__ . '/../storage/logs/app.log',
            'level' => Logger::DEBUG
        ],
        'slack' => [
            'handler' => SlackHandler::class,
            'webhook_url' => 'your_webhook_url',
            'level' => Logger::ERROR
        ]
    ]
];

// LoggerService.php
namespace App\Services;

use Monolog\Logger;

class LoggerService {
    private array $loggers = [];
    
    public function channel(string $channel): Logger {
        if (!isset($this->loggers[$channel])) {
            $config = config('logging.channels.' . $channel);
            $handler = new $config['handler']($config['path'], $config['level']);
            
            $logger = new Logger($channel);
            $logger->pushHandler($handler);
            
            $this->loggers[$channel] = $logger;
        }
        
        return $this->loggers[$channel];
    }
}
```

## 7. Système de recherche Elasticsearch

### Configuration
```php
// config/elasticsearch.php
return [
    'hosts' => [
        'localhost:9200'
    ],
    'indices' => [
        'products' => [
            'settings' => [
                'number_of_shards' => 1,
                'number_of_replicas' => 0
            ],
            'mappings' => [
                'properties' => [
                    'name' => ['type' => 'text'],
                    'description' => ['type' => 'text'],
                    'price' => ['type' => 'float'],
                    'categories' => ['type' => 'keyword']
                ]
            ]
        ]
    ]
];

// SearchService.php
namespace App\Services;

use Elasticsearch\ClientBuilder;

class SearchService {
    private $client;
    
    public function __construct(array $config) {
        $this->client = ClientBuilder::create()
            ->setHosts($config['hosts'])
            ->build();
    }
    
    public function search(string $index, array $query): array {
        return $this->client->search([
            'index' => $index,
            'body' => $query
        ]);
    }
}
```

### Utilisation de la recherche
```php
namespace App\Services;

class ProductSearchService {
    private SearchService $search;
    
    public function searchProducts(string $query, array $filters = []): array {
        $searchQuery = [
            'query' => [
                'bool' => [
                    'must' => [
                        'multi_match' => [
                            'query' => $query,
                            'fields' => ['name^2', 'description']
                        ]
                    ]
                ]
            ]
        ];
        
        if (!empty($filters)) {
            $searchQuery['query']['bool']['filter'] = $this->buildFilters($filters);
        }
        
        return $this->search->search('products', $searchQuery);
    }
}
```

## Conclusion

Les intégrations avancées permettent :
- Une meilleure scalabilité
- Des fonctionnalités enrichies
- Une meilleure performance
- Une maintenance simplifiée

Points clés :
1. Utiliser les bons outils pour chaque besoin
2. Configurer correctement les services
3. Gérer les erreurs et exceptions
4. Maintenir la cohérence du système
5. Documenter les intégrations 