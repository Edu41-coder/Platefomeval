<?php

namespace App\Controllers\User;

use Core\Controller\BaseController;
use Core\Database\Database;
use Core\Exception\ValidatorException;
use Core\Validator\Validator;
use Core\Http\Request;
use Core\Http\Response;
use App\Services\PhotoService;
use Core\View\Template;
use App\Security\CsrfToken;

class UserController extends BaseController
{
    protected Database $db;
    protected Validator $validator;
    protected PhotoService $photoService;
    protected bool $initialized = false;
    protected array $data = [];
    private const ITEMS_PER_PAGE = 30;
    private const ALLOWED_SORT_COLUMNS = [
        'nom' => 'u.nom',
        'prenom' => 'u.prenom',
        'email' => 'u.email',
        'role' => 'role',
        'created_at' => 'u.created_at'
    ];

    public function __construct()
    {
        parent::__construct();
        $this->photoService = new PhotoService(new \App\Models\Repository\PhotoRepository(Database::getInstance()));
    }

    public function initialize(): void
    {
        if ($this->initialized) {
            return;
        }

        parent::initialize();
        $this->db = Database::getInstance();
        $this->validator = new Validator();

        if (!$this->isAuthenticated()) {
            $this->addFlash('error', 'Vous devez être connecté pour accéder à cette page');
            $this->redirectAndExit('/login');
        }

        if (!$this->isAdmin()) {
            $this->addFlash('error', 'Accès non autorisé');
            $this->redirectAndExit('/dashboard');
        }

        $this->data['section_title'] = 'Gestion des utilisateurs';
        $this->initialized = true;
    }

    protected function redirectAndExit(string $url): void
    {
        $response = $this->redirect($url);
        $response->send();
        exit;
    }

    public function index(Request $request, Response $response): Response
    {
        try {
            // Get pagination and sorting parameters
            $page = max(1, intval($request->get['page'] ?? 1));
            $sort = $request->get['sort'] ?? 'created_at';
            $order = strtoupper($request->get['order'] ?? 'DESC');
            $roleFilter = $request->get['role'] ?? null;

            // Validate sort column and order
            $sortColumn = self::ALLOWED_SORT_COLUMNS[$sort] ?? 'u.created_at';
            $order = in_array($order, ['ASC', 'DESC']) ? $order : 'DESC';

            // Calculate offset
            $offset = ($page - 1) * self::ITEMS_PER_PAGE;
            
            // Base query with role join
            $baseQuery = "FROM users u
                         LEFT JOIN roles r ON u.role_id = r.id
                         WHERE u.is_admin = 0";
            
            $params = [];
            
            // Add role filter if specified
            if ($roleFilter) {
                $roleMap = [
                    'professor' => 2,
                    'professeur' => 2,
                    'student' => 3,
                    'etudiant' => 3
                ];

                if (isset($roleMap[$roleFilter])) {
                    $baseQuery .= " AND r.id = :role_id";
                    $params['role_id'] = $roleMap[$roleFilter];
                } elseif (is_numeric($roleFilter)) {
                    $baseQuery .= " AND r.id = :role_id";
                    $params['role_id'] = (int)$roleFilter;
                }
            }

            // Count total results
            $countQuery = "SELECT COUNT(*) as total " . $baseQuery;
            $totalResults = $this->db->fetchOne($countQuery, $params)['total'];
            $totalPages = ceil($totalResults / self::ITEMS_PER_PAGE);

            // Main query with pagination
            $query = "SELECT u.id, u.nom, u.prenom, u.email, u.is_admin, u.created_at,
                     CASE 
                        WHEN r.id = 2 THEN 'professeur'
                        WHEN r.id = 3 THEN 'etudiant'
                        ELSE r.name
                     END as role,
                     r.id as role_id " . 
                     $baseQuery . 
                     " ORDER BY {$sortColumn} {$order}
                     LIMIT :offset, :limit";

            // Add pagination parameters
            $params['offset'] = $offset;
            $params['limit'] = self::ITEMS_PER_PAGE;
            
            // Get users
            $users = $this->db->fetchAll($query, $params);

            // Calculate stats (using total counts, not just current page)
            $statsQuery = "SELECT 
                            COUNT(*) as total,
                            SUM(CASE WHEN r.id = 2 THEN 1 ELSE 0 END) as professeurs,
                            SUM(CASE WHEN r.id = 3 THEN 1 ELSE 0 END) as etudiants
                         FROM users u
                         LEFT JOIN roles r ON u.role_id = r.id
                         WHERE u.is_admin = 0";
            $stats = $this->db->fetchOne($statsQuery);

            return $this->render('user/dashboard/admin/index', [
                'pageTitle' => 'Gestion des utilisateurs',
                'users' => $users,
                'stats' => $stats,
                'currentRole' => $roleFilter,
                'currentPage' => $page,
                'totalPages' => $totalPages,
                'sort' => $sort,
                'order' => $order,
                'itemsPerPage' => self::ITEMS_PER_PAGE,
                'totalResults' => $totalResults,
                'csrfToken' => CsrfToken::getToken()
            ]);
        } catch (\Exception $e) {
            $this->addFlash('error', 'Erreur lors de la récupération des utilisateurs');
            return $this->render('user/dashboard/admin/index', [
                'pageTitle' => 'Gestion des utilisateurs',
                'users' => [],
                'stats' => ['total' => 0, 'professeurs' => 0, 'etudiants' => 0],
                'currentRole' => null,
                'currentPage' => 1,
                'totalPages' => 0,
                'sort' => 'created_at',
                'order' => 'DESC',
                'itemsPerPage' => self::ITEMS_PER_PAGE,
                'totalResults' => 0
            ]);
        }
    }

    public function create(Request $request, Response $response): Response
    {
        // Get available roles for the form
        $roles = $this->db->fetchAll("SELECT id, name FROM roles ORDER BY name");
        
        return $this->render('user/dashboard/admin/create', [
            'title' => 'Nouvel utilisateur',
            'roles' => $roles,
            'csrf_token' => $this->generateCsrfToken()
        ]);
    }

    public function store(Request $request, Response $response): Response
    {
        try {
            if (!$this->validateCsrf()) {
                throw new \Exception('Token CSRF invalide');
            }

            $data = $this->validator->validate($request->post(), [
                'nom' => ['required', 'string', 'min:2', 'max:100'],
                'prenom' => ['required', 'string', 'min:2', 'max:100'],
                'email' => ['required', 'email', 'unique:users'],
                'password' => ['required', 'string', 'min:8'],
                'role_id' => ['required', 'integer'],
                'adresse' => ['nullable', 'string'],
                'is_admin' => ['boolean']
            ]);

            $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
            $data['created_at'] = date('Y-m-d H:i:s');
            $data['updated_at'] = date('Y-m-d H:i:s');
            $data['is_admin'] = $data['is_admin'] ?? false;

            $this->db->insert('users', $data);

            $this->addFlash('success', 'Utilisateur créé avec succès');
            return $this->redirect('/admin/users');
        } catch (ValidatorException $e) {
            $roles = $this->db->fetchAll("SELECT id, name FROM roles ORDER BY name");
            return $this->render('user/dashboard/admin/create', [
                'errors' => $e->getErrors(),
                'old' => $request->post(),
                'roles' => $roles,
                'title' => 'Nouvel utilisateur',
                'csrf_token' => $this->generateCsrfToken()
            ]);
        } catch (\Exception $e) {
            $this->addFlash('error', 'Erreur lors de la création : ' . $e->getMessage());
            return $this->redirect('/admin/users');
        }
    }

    public function edit(Request $request, Response $response, int $id): Response
    {
        try {
            $user = $this->db->fetchOne(
                "SELECT u.id, u.nom, u.prenom, u.email, u.role_id, u.is_admin, u.adresse 
                 FROM users u
                 WHERE u.id = :id",
                ['id' => $id]
            );

            if (!$user) {
                $this->addFlash('error', 'Utilisateur non trouvé');
                return $this->redirect('/admin/users');
            }

            $roles = $this->db->fetchAll("SELECT id, name FROM roles ORDER BY name");

            return $this->render('user/dashboard/admin/edit', [
                'user' => $user,
                'roles' => $roles,
                'title' => 'Modifier l\'utilisateur',
                'csrf_token' => $this->generateCsrfToken()
            ]);
        } catch (\Exception $e) {
            $this->addFlash('error', 'Erreur lors de la récupération de l\'utilisateur');
            return $this->redirect('/admin/users');
        }
    }

    public function update(Request $request, Response $response, int $id): Response
    {
        try {
            if (!$this->validateCsrf()) {
                throw new \Exception('Token CSRF invalide');
            }

            $rules = [
                'nom' => ['required', 'string', 'min:2', 'max:100'],
                'prenom' => ['required', 'string', 'min:2', 'max:100'],
                'email' => ['required', 'email', "unique:users,email,{$id}"],
                'role_id' => ['required', 'integer'],
                'adresse' => ['nullable', 'string'],
                'is_admin' => ['boolean']
            ];

            $postData = $request->post();

            if (!empty($postData['password'])) {
                $rules['password'] = ['string', 'min:8'];
            }

            $data = $this->validator->validate($postData, $rules);

            if (!empty($data['password'])) {
                $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
            }

            $data['updated_at'] = date('Y-m-d H:i:s');
            $data['is_admin'] = $data['is_admin'] ?? false;

            $this->db->update('users', $id, $data);

            $this->addFlash('success', 'Utilisateur mis à jour avec succès');
            return $this->redirect('/admin/users');
        } catch (ValidatorException $e) {
            $roles = $this->db->fetchAll("SELECT id, name FROM roles ORDER BY name");
            return $this->render('admin/users/edit', [
                'user' => ['id' => $id] + $request->post(),
                'roles' => $roles,
                'errors' => $e->getErrors(),
                'title' => 'Modifier l\'utilisateur',
                'csrf_token' => $this->generateCsrfToken()
            ]);
        } catch (\Exception $e) {
            $this->addFlash('error', 'Erreur lors de la mise à jour : ' . $e->getMessage());
            return $this->redirect('/admin/users');
        }
    }

    public function destroy(Request $request, Response $response, int $id): Response
    {
        try {
            if (!$this->userExists($id)) {
                throw new \Exception('Utilisateur non trouvé');
            }

            $this->db->beginTransaction();

            // Supprimer la photo de l'utilisateur si elle existe
            try {
                $this->photoService->deletePhoto($id);
            } catch (\Exception $e) {
                error_log("Erreur lors de la suppression de la photo : " . $e->getMessage());
                // On continue même si la suppression de la photo échoue
            }

            // Supprimer l'utilisateur
            $this->db->query(
                "DELETE FROM users WHERE id = :id",
                ['id' => $id]
            );
            
            $this->db->commit();

            if ($request->wantsJson()) {
                return $this->json([
                    'success' => true,
                    'message' => 'Utilisateur supprimé avec succès'
                ]);
            }

            $this->addFlash('success', 'Utilisateur supprimé avec succès');
            return $this->redirect('/admin');
        } catch (\Exception $e) {
            $this->db->rollBack();
            
            if ($request->wantsJson()) {
                return $this->json([
                    'success' => false,
                    'message' => 'Erreur lors de la suppression : ' . $e->getMessage()
                ], 400);
            }

            $this->addFlash('error', 'Erreur lors de la suppression : ' . $e->getMessage());
            return $this->redirect('/admin');
        }
    }

    private function userExists(int $id): bool
    {
        $user = $this->db->fetchOne(
            "SELECT id FROM users WHERE id = :id",
            ['id' => $id]
        );
        return !empty($user);
    }
}
