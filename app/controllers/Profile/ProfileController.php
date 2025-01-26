<?php

namespace App\Controllers\Profile;

use Core\Controller\BaseController;
use Core\Http\Request;
use Core\Http\Response;
use Core\Http\JsonResponse;
use App\Models\Repository\UserRepository;
use App\Services\AuthService;
use Core\Exception\ValidatorException;
use Core\Validator\Validator;
use App\Security\CsrfToken;
use Core\Session\FlashMessage;
use App\Services\PhotoService;
use App\Models\Repository\PhotoRepository;
use Core\Database\Database;

class ProfileController extends BaseController
{
    private UserRepository $userRepository;
    private Validator $validator;
    private AuthService $authService;
    private PhotoService $photoService;

    public function __construct()
    {
        parent::__construct();
        $this->userRepository = new UserRepository();
        $this->authService = AuthService::getInstance();
        $this->validator = new Validator();
        $this->photoService = new PhotoService(new PhotoRepository(Database::getInstance()));
    }

    public function initialize(): void
    {
        parent::initialize();
        if (!$this->isAuthenticated()) {
            header('Location: /Plateformeval/public/login');
            exit;
        }
    }

    public function index(Request $request, Response $response): Response
    {
        return $this->show($request, $response);
    }

    /**
     * Affiche le formulaire d'édition du profil
     */
    public function edit(Request $request, Response $response): Response
    {
        $user = $this->authService->user();
        if (!$user) {
            $this->addFlash('error', 'Utilisateur non trouvé');
            return $response->redirect('/Plateformeval/public/dashboard');
        }

        // Récupérer la photo de profil
        $photo = $this->photoService->getUserPhoto($user->getId());

        return $this->render('user/dashboard/profile', [
            'user' => $user,
            'pageTitle' => 'Modifier mon Profil',
            'csrf_token' => CsrfToken::getToken(),
            'photo' => $photo,
            'flash' => FlashMessage::getAll()
        ]);
    }

    public function show(Request $request, Response $response): Response
    {
        $user = $this->authService->user();
        if (!$user) {
            $this->addFlash('error', 'Utilisateur non trouvé');
            return $response->redirect('/Plateformeval/public/dashboard');
        }

        // Récupérer la photo de profil
        $photo = $this->photoService->getUserPhoto($user->getId());

        // Configuration de la réponse avec les en-têtes de sécurité appropriés
        $response->setHeader('X-Frame-Options', 'DENY');
        $response->setHeader('Content-Security-Policy', "default-src 'self'; script-src 'self' 'unsafe-inline' cdnjs.cloudflare.com; style-src 'self' 'unsafe-inline';");

        return $this->render('user/dashboard/profile', [
            'user' => $user,
            'pageTitle' => 'Mon Profil',
            'csrf_token' => CsrfToken::getToken(),
            'photo' => $photo,
            'flash' => FlashMessage::getAll()
        ]);
    }

    public function update(Request $request, Response $response): Response
    {
        try {
            if (!CsrfToken::verify($request->post('csrf_token'))) {
                throw new \Exception('Token CSRF invalide');
            }

            $data = $this->validator->validate($request->post(), [
                'adresse' => ['required', 'string', 'max:255']
            ]);

            $user = $this->authService->user();
            if (!$user) {
                throw new \Exception("Utilisateur non trouvé");
            }

            // Récupérer la photo de profil
            $photo = $this->photoService->getUserPhoto($user->getId());
            
            // Skip update if the address hasn't changed
            if ($user->getAdresse() === $data['adresse']) {
                $this->addFlash('info', 'Aucun changement détecté dans l\'adresse');
                return $this->render('user/dashboard/profile', [
                    'user' => $user,
                    'pageTitle' => 'Mon Profil',
                    'csrf_token' => CsrfToken::getToken(),
                    'photo' => $photo,
                    'flash' => FlashMessage::getAll()
                ]);
            }
            
            try {
                // Update the user
                $updateResult = $this->userRepository->update($user->getId(), $data);
                
                if ($updateResult) {
                    // Refresh the user data
                    $updatedUser = $this->userRepository->findById($user->getId());
                    if ($updatedUser) {
                        // Update session data
                        $_SESSION['user'] = [
                            'id' => $updatedUser->getId(),
                            'email' => $updatedUser->getEmail(),
                            'nom' => $updatedUser->getNom(),
                            'prenom' => $updatedUser->getPrenom(),
                            'role_id' => $updatedUser->getRoleId(),
                            'is_admin' => $updatedUser->isAdmin(),
                            'last_activity' => time()
                        ];

                        $this->addFlash('success', 'Profil mis à jour avec succès');
                        return $response->redirect('/Plateformeval/public/profile');
                    }
                }
                
                throw new \Exception("La mise à jour du profil a échoué");
            } catch (\Exception $e) {
                error_log('Error during profile update: ' . $e->getMessage());
                
                if (strpos($e->getMessage(), "Cette adresse est déjà utilisée") !== false) {
                    throw new \Exception("Cette adresse est déjà utilisée par un autre utilisateur");
                }
                
                throw $e;
            }

        } catch (ValidatorException $e) {
            return $this->render('user/dashboard/profile', [
                'errors' => $e->getErrors(),
                'old' => $request->post(),
                'user' => $this->authService->user(),
                'pageTitle' => 'Mon Profil',
                'csrf_token' => CsrfToken::generate(),
                'photo' => $this->photoService->getUserPhoto($this->authService->user()->getId()),
                'flash' => FlashMessage::getAll()
            ]);
        } catch (\Exception $e) {
            error_log('Error during profile update: ' . $e->getMessage());
            $this->addFlash('error', $e->getMessage());
            return $this->render('user/dashboard/profile', [
                'user' => $this->authService->user(),
                'pageTitle' => 'Mon Profil',
                'csrf_token' => CsrfToken::generate(),
                'photo' => $this->photoService->getUserPhoto($this->authService->user()->getId()),
                'flash' => FlashMessage::getAll()
            ]);
        }
    }

    /**
     * Met à jour le mot de passe de l'utilisateur
     */
    public function updatePassword(Request $request, Response $response): Response
    {
        try {
            if (!CsrfToken::verify($request->post('csrf_token'))) {
                throw new \Exception('Token CSRF invalide');
            }

            $data = $this->validator->validate($request->post(), [
                'current_password' => ['required', 'string'],
                'new_password' => ['required', 'string', 'min:6']
            ]);

            $user = $this->authService->user();
            if (!$user) {
                throw new \Exception("Utilisateur non trouvé");
            }

            if (!$this->authService->updatePassword($data['current_password'], $data['new_password'])) {
                throw new \Exception("Mot de passe actuel incorrect");
            }

            $this->addFlash('success', 'Mot de passe mis à jour avec succès');
            $photo = $this->photoService->getUserPhoto($user->getId());
            return $this->render('user/dashboard/profile', [
                'user' => $user,
                'pageTitle' => 'Mon Profil',
                'csrf_token' => CsrfToken::getToken(),
                'photo' => $photo,
                'flash' => FlashMessage::getAll()
            ]);

        } catch (ValidatorException $e) {
            $this->addFlash('error', 'Erreur de validation : ' . implode(', ', array_map(function($errors) {
                return implode(', ', $errors);
            }, $e->getErrors())));
            return $this->show($request, $response);

        } catch (\Exception $e) {
            $this->addFlash('error', 'Erreur lors de la mise à jour du mot de passe : ' . $e->getMessage());
            return $this->show($request, $response);
        }
    }

    /**
     * Gère l'upload de la photo de profil
     */
    public function uploadPhoto(Request $request, Response $response): Response
    {
        try {
            if (!CsrfToken::verify($request->post('csrf_token'))) {
                throw new \Exception('Token CSRF invalide');
            }

            $user = $this->authService->user();
            if (!$user) {
                throw new \Exception('Utilisateur non trouvé');
            }

            $file = $request->file('photo');
            if (!$file) {
                throw new \Exception('Aucun fichier n\'a été envoyé');
            }

            $photo = $this->photoService->uploadPhoto($user->getId(), $file);
            $this->addFlash('success', 'Photo de profil mise à jour avec succès');

        } catch (\Exception $e) {
            error_log('Error uploading photo: ' . $e->getMessage());
            $this->addFlash('error', 'Erreur lors de l\'upload de la photo : ' . $e->getMessage());
        }

        return $this->show($request, $response);
    }

    /**
     * Supprime la photo de profil
     */
    public function deletePhoto(Request $request, Response $response): Response
    {
        try {
            if (!CsrfToken::verify($request->post('csrf_token'))) {
                throw new \Exception('Token CSRF invalide');
            }

            $user = $this->authService->user();
            if (!$user) {
                throw new \Exception('Utilisateur non trouvé');
            }

            if ($this->photoService->deletePhoto($user->getId())) {
                $this->addFlash('success', 'Photo de profil supprimée avec succès');
            } else {
                $this->addFlash('error', 'Aucune photo à supprimer');
            }

        } catch (\Exception $e) {
            error_log('Error deleting photo: ' . $e->getMessage());
            $this->addFlash('error', 'Erreur lors de la suppression de la photo : ' . $e->getMessage());
        }

        return $this->show($request, $response);
    }

    public function view(Request $request, Response $response): Response
    {
        try {
            $userId = $request->get('id');
            if (!$userId) {
                throw new \Exception('ID utilisateur manquant');
            }

            $user = $this->userRepository->findById($userId);
            if (!$user) {
                throw new \Exception('Utilisateur non trouvé');
            }

            $photo = $this->photoService->getUserPhoto($userId);
            
            // Récupérer les paramètres supplémentaires
            $matiere_id = $request->get('matiere_id');
            $evaluation_id = $request->get('evaluation_id');
            $evaluation_create = $request->get('evaluation_create') === '1';
            $from_notes = in_array($request->get('from_notes'), ['true', '1'], true);
            $from_details = $request->get('from_details') === 'true';
            $from_admin = in_array($request->get('from_admin'), ['true', '1'], true);

            return $this->render('user/profile/view', [
                'pageTitle' => 'Profil de ' . $user->getPrenom() . ' ' . $user->getNom(),
                'user' => $user,
                'photo' => $photo,
                'matiere_id' => $matiere_id,
                'evaluation_id' => $evaluation_id,
                'evaluation_create' => $evaluation_create,
                'from_notes' => $from_notes,
                'from_details' => $from_details,
                'from_admin' => $from_admin
            ]);
        } catch (\Exception $e) {
            $this->addFlash('error', $e->getMessage());
            return $response->redirect('dashboard');
        }
    }
}
