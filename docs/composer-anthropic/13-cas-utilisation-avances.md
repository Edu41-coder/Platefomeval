# Cas d'utilisation avancés

## 1. Système de gestion de contenu (CMS)

### Structure du projet
```bash
cms/
├── app/
│   ├── Controllers/
│   │   ├── ContentController.php
│   │   └── MediaController.php
│   ├── Models/
│   │   ├── Content.php
│   │   └── Media.php
│   └── Services/
│       ├── ContentService.php
│       └── MediaService.php
├── config/
└── public/
```

### Gestionnaire de contenu
```php
namespace App\Services;

class ContentManager {
    private ContentRepository $repository;
    private MediaService $mediaService;
    private CacheService $cache;
    
    public function createContent(array $data): Content {
        // Validation
        $this->validateContent($data);
        
        // Traitement des médias
        if (isset($data['media'])) {
            $data['media'] = $this->mediaService->processMedia($data['media']);
        }
        
        // Création du contenu
        $content = $this->repository->create($data);
        
        // Invalidation du cache
        $this->cache->invalidateTag('content');
        
        return $content;
    }
    
    public function renderContent(Content $content): string {
        return $this->cache->remember("content.{$content->id}", function() use ($content) {
            $template = $this->loadTemplate($content->template);
            return $template->render([
                'content' => $content,
                'media' => $content->media
            ]);
        });
    }
}
```

## 2. API Gateway

### Configuration
```php
namespace App\Gateway;

class ApiGateway {
    private RouteRegistry $routes;
    private ServiceRegistry $services;
    private AuthService $auth;
    
    public function handleRequest(Request $request): Response {
        // Authentication
        $token = $this->auth->validateToken($request->header('Authorization'));
        
        // Route matching
        $route = $this->routes->match($request->path());
        
        // Service discovery
        $service = $this->services->getService($route->service);
        
        // Request forwarding
        return $this->forwardRequest($service, $request, $token);
    }
    
    private function forwardRequest(Service $service, Request $request, Token $token): Response {
        $client = new HttpClient();
        
        return $client->send($service->url, [
            'method' => $request->method(),
            'headers' => array_merge($request->headers(), [
                'X-Auth-Token' => $token->toString(),
                'X-Request-ID' => uniqid()
            ]),
            'body' => $request->body()
        ]);
    }
}
```

## 3. Système de paiement

### Intégration Stripe
```php
namespace App\Payment;

class PaymentProcessor {
    private StripeClient $stripe;
    private PaymentRepository $repository;
    private EventDispatcher $events;
    
    public function processPayment(Order $order): PaymentResult {
        try {
            // Création du paiement Stripe
            $payment = $this->stripe->payments->create([
                'amount' => $order->getTotal() * 100,
                'currency' => $order->getCurrency(),
                'customer' => $order->getCustomer()->getStripeId(),
                'payment_method' => $order->getPaymentMethod(),
                'confirm' => true,
                'return_url' => $this->generateReturnUrl($order)
            ]);
            
            // Enregistrement
            $this->repository->savePayment($order, $payment);
            
            // Événements
            $this->events->dispatch(new PaymentProcessedEvent($order, $payment));
            
            return PaymentResult::success($payment);
        } catch (StripeException $e) {
            $this->handlePaymentError($order, $e);
            return PaymentResult::error($e);
        }
    }
}
```

## 4. Système de notification

### Service de notification
```php
namespace App\Notifications;

class NotificationService {
    private array $channels = [];
    private TemplateEngine $templates;
    private UserPreferences $preferences;
    
    public function send(User $user, string $type, array $data): void {
        // Récupération des préférences
        $userChannels = $this->preferences->getChannels($user, $type);
        
        // Préparation du contenu
        $content = $this->templates->render($type, $data);
        
        // Envoi sur chaque canal
        foreach ($userChannels as $channel) {
            $this->channels[$channel]->send($user, $content);
        }
    }
    
    public function addChannel(string $name, NotificationChannel $channel): void {
        $this->channels[$name] = $channel;
    }
}

// Implémentation des canaux
class EmailChannel implements NotificationChannel {
    public function send(User $user, NotificationContent $content): void {
        $email = (new Email())
            ->to($user->email)
            ->subject($content->subject)
            ->html($content->body);
            
        $this->mailer->send($email);
    }
}

class PushChannel implements NotificationChannel {
    public function send(User $user, NotificationContent $content): void {
        $this->firebase->send([
            'token' => $user->deviceToken,
            'notification' => [
                'title' => $content->subject,
                'body' => $content->summary
            ],
            'data' => $content->data
        ]);
    }
}
```

## 5. Système de recherche avancée

### Moteur de recherche
```php
namespace App\Search;

class SearchEngine {
    private ElasticsearchClient $elasticsearch;
    private SearchRepository $repository;
    private array $indexes = [];
    
    public function search(string $query, array $filters = []): SearchResults {
        // Préparation de la requête
        $searchQuery = $this->buildSearchQuery($query, $filters);
        
        // Exécution de la recherche
        $results = $this->elasticsearch->search([
            'index' => $this->getRelevantIndexes($filters),
            'body' => $searchQuery
        ]);
        
        // Traitement des résultats
        return $this->processResults($results);
    }
    
    private function buildSearchQuery(string $query, array $filters): array {
        return [
            'query' => [
                'bool' => [
                    'must' => [
                        'multi_match' => [
                            'query' => $query,
                            'fields' => ['title^3', 'content', 'tags^2']
                        ]
                    ],
                    'filter' => $this->buildFilters($filters)
                ]
            ],
            'highlight' => [
                'fields' => [
                    'content' => new \stdClass()
                ]
            ],
            'aggs' => $this->buildAggregations($filters)
        ];
    }
}
```

## 6. Système de cache avancé

### Cache distribué
```php
namespace App\Cache;

class DistributedCacheManager {
    private array $nodes = [];
    private ConsistentHashing $hashRing;
    private Serializer $serializer;
    
    public function get(string $key): mixed {
        $node = $this->getNode($key);
        
        try {
            $data = $node->get($key);
            return $data ? $this->serializer->unserialize($data) : null;
        } catch (NodeException $e) {
            $this->handleNodeFailure($node);
            return $this->getFromBackup($key);
        }
    }
    
    public function set(string $key, mixed $value, int $ttl = null): bool {
        $serialized = $this->serializer->serialize($value);
        $node = $this->getNode($key);
        
        try {
            $success = $node->set($key, $serialized, $ttl);
            
            if ($success && $this->shouldReplicate($key)) {
                $this->replicateToBackups($key, $serialized, $ttl);
            }
            
            return $success;
        } catch (NodeException $e) {
            $this->handleNodeFailure($node);
            return false;
        }
    }
}
```

## 7. Système de file d'attente

### Gestionnaire de jobs
```php
namespace App\Queue;

class JobManager {
    private QueueConnection $queue;
    private JobRepository $repository;
    private RetryPolicy $retryPolicy;
    
    public function dispatch(Job $job): void {
        // Préparation du job
        $job->prepare();
        
        // Enregistrement
        $this->repository->save($job);
        
        // Envoi dans la file
        $this->queue->push($job->getQueue(), [
            'id' => $job->getId(),
            'type' => get_class($job),
            'data' => $job->getData(),
            'options' => $job->getOptions()
        ]);
    }
    
    public function process(string $queue): void {
        while ($message = $this->queue->pop($queue)) {
            try {
                $job = $this->repository->find($message['id']);
                $job->execute();
                
                $this->repository->markAsCompleted($job);
            } catch (Exception $e) {
                $this->handleFailedJob($job, $e);
            }
        }
    }
}
```

## 8. Système de reporting

### Générateur de rapports
```php
namespace App\Reporting;

class ReportGenerator {
    private DataCollector $collector;
    private array $processors = [];
    private array $formatters = [];
    
    public function generateReport(string $type, array $parameters): Report {
        // Collecte des données
        $data = $this->collector->collect($type, $parameters);
        
        // Traitement des données
        foreach ($this->processors as $processor) {
            $data = $processor->process($data);
        }
        
        // Formatage
        $formatter = $this->formatters[$parameters['format']] ?? $this->formatters['default'];
        
        return new Report(
            $type,
            $formatter->format($data),
            $parameters
        );
    }
    
    public function scheduleReport(string $type, array $parameters, Schedule $schedule): void {
        $this->scheduler->schedule(function() use ($type, $parameters) {
            $report = $this->generateReport($type, $parameters);
            $this->distributeReport($report);
        }, $schedule);
    }
}
```

## 9. Système de workflow

### Moteur de workflow
```php
namespace App\Workflow;

class WorkflowEngine {
    private WorkflowRegistry $registry;
    private StateManager $stateManager;
    private TransitionValidator $validator;
    
    public function start(string $workflowName, mixed $subject): Workflow {
        $workflow = $this->registry->get($workflowName);
        
        $initialState = $workflow->getInitialState();
        $this->stateManager->setState($subject, $initialState);
        
        return $workflow;
    }
    
    public function canTransition(Workflow $workflow, mixed $subject, string $transition): bool {
        $currentState = $this->stateManager->getState($subject);
        
        return $workflow->hasTransition($currentState, $transition) &&
               $this->validator->validate($workflow, $subject, $transition);
    }
    
    public function transition(Workflow $workflow, mixed $subject, string $transition): void {
        if (!$this->canTransition($workflow, $subject, $transition)) {
            throw new InvalidTransitionException();
        }
        
        $this->executeTransition($workflow, $subject, $transition);
    }
}
```

## 10. Système d'authentification avancé

### Service d'authentification
```php
namespace App\Auth;

class AuthenticationService {
    private UserProvider $users;
    private TokenManager $tokens;
    private array $providers = [];
    
    public function authenticate(Request $request): AuthResult {
        foreach ($this->providers as $provider) {
            if ($provider->supports($request)) {
                try {
                    $user = $provider->authenticate($request);
                    
                    if ($user) {
                        return $this->createSession($user);
                    }
                } catch (AuthException $e) {
                    continue;
                }
            }
        }
        
        throw new AuthenticationFailedException();
    }
    
    private function createSession(User $user): AuthResult {
        $token = $this->tokens->create($user);
        
        $session = new Session(
            $user,
            $token,
            $this->getPermissions($user)
        );
        
        return new AuthResult($session);
    }
}
```

## Conclusion

Points clés des cas d'utilisation avancés :
1. Architecture modulaire
2. Gestion des erreurs robuste
3. Performance optimisée
4. Sécurité renforcée
5. Maintenance simplifiée

Recommandations :
- Documenter les cas d'utilisation
- Tester les scénarios complexes
- Monitorer les performances
- Prévoir la scalabilité
- Former les équipes 