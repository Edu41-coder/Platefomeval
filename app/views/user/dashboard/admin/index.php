<?php
/**
 * @var \Core\View\Template $this
 * @var string $pageTitle
 * @var array $users
 * @var array $stats
 * @var string|null $currentRole
 * @var int $currentPage
 * @var int $totalPages
 * @var int $itemsPerPage
 * @var int $totalResults
 * @var string $sort
 * @var string $order
 * @var string $csrfToken
 */

// Configuration du layout
$this->layout('default', [
    'title' => $pageTitle ?? 'Gestion des utilisateurs',
    'user' => $user ?? null,
    'csrfToken' => $csrfToken ?? null,
    'site_title' => $_ENV['APP_NAME'] ?? 'Plateforme Eval',
    'assets' => [
        'css' => ['assets/css/dashboard.css'],
        'js' => [
            'assets/js/dashboard.js',
            'assets/js/admin/users.js'
        ]
    ]
]);
?>

<div class="container mt-4">
    <div class="row mb-4">
        <div class="col-12">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item">
                        <a href="<?= $this->url('dashboard') ?>" class="text-decoration-none">
                            <i class="fas fa-tachometer-alt"></i> Tableau de bord
                        </a>
                    </li>
                    <li class="breadcrumb-item active">
                        <i class="fas fa-users"></i> Gestion des utilisateurs
                    </li>
                </ol>
            </nav>
        </div>
    </div>

    <!-- Statistiques -->
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">
                        <i class="fas fa-users"></i> Total utilisateurs
                    </h5>
                    <p class="card-text h2"><?= $stats['total'] ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">
                        <i class="fas fa-chalkboard-teacher"></i> Professeurs
                    </h5>
                    <p class="card-text h2"><?= $stats['professeurs'] ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">
                        <i class="fas fa-user-graduate"></i> Étudiants
                    </h5>
                    <p class="card-text h2"><?= $stats['etudiants'] ?></p>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Liste des utilisateurs</h5>
                    
                    <!-- Filtre par rôle -->
                    <div class="btn-group">
                        <a href="<?= $this->url('admin') ?>" 
                           class="btn btn-outline-primary <?= !$currentRole ? 'active' : '' ?>">
                            Tous
                        </a>
                        <a href="<?= $this->url('admin', ['role' => 'professeur']) ?>" 
                           class="btn btn-outline-primary <?= $currentRole === 'professeur' ? 'active' : '' ?>">
                            Professeurs
                        </a>
                        <a href="<?= $this->url('admin', ['role' => 'etudiant']) ?>" 
                           class="btn btn-outline-primary <?= $currentRole === 'etudiant' ? 'active' : '' ?>">
                            Étudiants
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <?php if (empty($users)): ?>
                        <div class="alert alert-info">
                            Aucun utilisateur trouvé.
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>
                                            <?php
                                            $currentParams = ['sort' => 'nom', 'order' => $sort === 'nom' && $order === 'ASC' ? 'DESC' : 'ASC'];
                                            if (isset($currentRole) && $currentRole) {
                                                $currentParams['role'] = $currentRole;
                                            }
                                            ?>
                                            <a href="<?= $this->url('admin', $currentParams) ?>" 
                                               class="text-decoration-none text-dark">
                                                Nom
                                                <?php if ($sort === 'nom'): ?>
                                                    <i class="fas fa-sort-<?= $order === 'ASC' ? 'up' : 'down' ?>"></i>
                                                <?php else: ?>
                                                    <i class="fas fa-sort text-muted"></i>
                                                <?php endif; ?>
                                            </a>
                                        </th>
                                        <th>
                                            <?php
                                            $currentParams = ['sort' => 'prenom', 'order' => $sort === 'prenom' && $order === 'ASC' ? 'DESC' : 'ASC'];
                                            if (isset($currentRole) && $currentRole) {
                                                $currentParams['role'] = $currentRole;
                                            }
                                            ?>
                                            <a href="<?= $this->url('admin', $currentParams) ?>"
                                               class="text-decoration-none text-dark">
                                                Prénom
                                                <?php if ($sort === 'prenom'): ?>
                                                    <i class="fas fa-sort-<?= $order === 'ASC' ? 'up' : 'down' ?>"></i>
                                                <?php else: ?>
                                                    <i class="fas fa-sort text-muted"></i>
                                                <?php endif; ?>
                                            </a>
                                        </th>
                                        <th>Email</th>
                                        <th>
                                            <?php
                                            $currentParams = ['sort' => 'role_id', 'order' => $sort === 'role_id' && $order === 'ASC' ? 'DESC' : 'ASC'];
                                            if (isset($currentRole) && $currentRole) {
                                                $currentParams['role'] = $currentRole;
                                            }
                                            ?>
                                            <a href="<?= $this->url('admin', $currentParams) ?>"
                                               class="text-decoration-none text-dark">
                                                Rôle
                                                <?php if ($sort === 'role_id'): ?>
                                                    <i class="fas fa-sort-<?= $order === 'ASC' ? 'up' : 'down' ?>"></i>
                                                <?php else: ?>
                                                    <i class="fas fa-sort text-muted"></i>
                                                <?php endif; ?>
                                            </a>
                                        </th>
                                        <th>
                                            <?php
                                            $currentParams = ['sort' => 'created_at', 'order' => $sort === 'created_at' && $order === 'ASC' ? 'DESC' : 'ASC'];
                                            if (isset($currentRole) && $currentRole) {
                                                $currentParams['role'] = $currentRole;
                                            }
                                            ?>
                                            <a href="<?= $this->url('admin', $currentParams) ?>"
                                               class="text-decoration-none text-dark">
                                                Date d'inscription
                                                <?php if ($sort === 'created_at'): ?>
                                                    <i class="fas fa-sort-<?= $order === 'ASC' ? 'up' : 'down' ?>"></i>
                                                <?php else: ?>
                                                    <i class="fas fa-sort text-muted"></i>
                                                <?php endif; ?>
                                            </a>
                                        </th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($users as $user): ?>
                                        <tr>
                                            <td><?= $this->e($user['nom']) ?></td>
                                            <td><?= $this->e($user['prenom']) ?></td>
                                            <td><?= $this->e($user['email']) ?></td>
                                            <td>
                                                <?php if ($user['role'] === 'professeur'): ?>
                                                    <span class="badge bg-primary">
                                                        <i class="fas fa-chalkboard-teacher"></i> Professeur
                                                    </span>
                                                <?php elseif ($user['role'] === 'etudiant'): ?>
                                                    <span class="badge bg-success">
                                                        <i class="fas fa-user-graduate"></i> Étudiant
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?= (new DateTime($user['created_at']))->format('d/m/Y') ?></td>
                                            <td>
                                                <div class="btn-group">
                                                    <a href="<?= $this->url('profile.view', [
                                                        'id' => $user['id'],
                                                        'from_admin' => true
                                                    ]) ?>" 
                                                       class="btn btn-outline-info btn-sm" 
                                                       title="Voir le profil">
                                                        <i class="fas fa-user"></i>
                                                    </a>
                                                    <button type="button" 
                                                            class="btn btn-outline-danger btn-sm" 
                                                            data-bs-toggle="modal" 
                                                            data-bs-target="#deleteModal<?= $user['id'] ?>">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>

                                                <!-- Modal de confirmation de suppression -->
                                                <div class="modal fade" id="deleteModal<?= $user['id'] ?>" tabindex="-1">
                                                    <div class="modal-dialog">
                                                        <div class="modal-content">
                                                            <div class="modal-header">
                                                                <h5 class="modal-title">Confirmer la suppression</h5>
                                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                            </div>
                                                            <div class="modal-body">
                                                                Êtes-vous sûr de vouloir supprimer l'utilisateur 
                                                                <strong><?= $this->e($user['prenom']) ?> <?= $this->e($user['nom']) ?></strong> ? 
                                                                Cette action est irréversible.
                                                            </div>
                                                            <div class="modal-footer">
                                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                                                                <form action="<?= $this->url('admin.users.delete', ['id' => $user['id']]) ?>" 
                                                                      method="POST" 
                                                                      style="display: inline;">
                                                                    <input type="hidden" name="csrf_token" value="<?= $this->e($csrfToken) ?>">
                                                                    <button type="submit" class="btn btn-danger">Supprimer</button>
                                                                </form>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <?php 
                        $paginationParams = [
                            'sort' => $sort ?? 'created_at',
                            'order' => $order ?? 'DESC'
                        ];
                        if (isset($currentRole) && $currentRole) {
                            $paginationParams['role'] = $currentRole;
                        }

                        // Variables pour le composant de pagination
                        $_currentPage = $currentPage;
                        $_totalPages = $totalPages;
                        $_itemsPerPage = $itemsPerPage;
                        $_totalResults = $totalResults;
                        $_params = $paginationParams;
                        $_route = 'admin';
                        $template = $this;

                        include __DIR__ . '/../../../components/pagination.php';
                        ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>