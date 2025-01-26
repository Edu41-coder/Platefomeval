<?php
/**
 * Variables attendues :
 * $_currentPage - Page actuelle
 * $_totalPages - Nombre total de pages
 * $_itemsPerPage - Nombre d'éléments par page
 * $_totalResults - Nombre total de résultats
 * $_params - Paramètres supplémentaires à conserver dans l'URL
 * $_route - Nom de la route actuelle
 * $template - L'objet template (passé depuis la vue principale)
 */

// Nombre de pages à afficher de chaque côté de la page courante
$range = 2;

// Calcul des pages à afficher
$showFirst = $_currentPage > ($range + 1);
$showLast = $_currentPage < ($_totalPages - $range);
$showDots1 = $_currentPage > ($range + 2);
$showDots2 = $_currentPage < ($_totalPages - $range - 1);

// Fonction pour générer l'URL avec les paramètres
function buildPaginationUrl($template, $route, $page, $params = []) {
    $urlParams = array_merge(['page' => $page], $params);
    return $template->url($route, $urlParams);
}
?>

<?php if ($_totalPages > 1): ?>
    <nav aria-label="Navigation des pages">
        <div class="d-flex justify-content-between align-items-center">
            <div class="text-muted">
                Affichage de <?= ($_currentPage - 1) * $_itemsPerPage + 1 ?> 
                à <?= min($_currentPage * $_itemsPerPage, $_totalResults) ?> 
                sur <?= $_totalResults ?> résultats
            </div>
            <ul class="pagination mb-0">
                <?php if ($_currentPage > 1): ?>
                    <li class="page-item">
                        <a class="page-link" href="<?= buildPaginationUrl($template, $_route, 1, $_params) ?>" aria-label="Première page">
                            <i class="fas fa-angle-double-left"></i>
                        </a>
                    </li>
                    <li class="page-item">
                        <a class="page-link" href="<?= buildPaginationUrl($template, $_route, $_currentPage - 1, $_params) ?>" aria-label="Page précédente">
                            <i class="fas fa-angle-left"></i>
                        </a>
                    </li>
                <?php endif; ?>

                <?php if ($showFirst): ?>
                    <li class="page-item">
                        <a class="page-link" href="<?= buildPaginationUrl($template, $_route, 1, $_params) ?>">1</a>
                    </li>
                <?php endif; ?>

                <?php if ($showDots1): ?>
                    <li class="page-item disabled">
                        <span class="page-link">...</span>
                    </li>
                <?php endif; ?>

                <?php for ($i = max(1, $_currentPage - $range); $i <= min($_totalPages, $_currentPage + $range); $i++): ?>
                    <li class="page-item <?= $i === $_currentPage ? 'active' : '' ?>">
                        <a class="page-link" href="<?= buildPaginationUrl($template, $_route, $i, $_params) ?>"><?= $i ?></a>
                    </li>
                <?php endfor; ?>

                <?php if ($showDots2): ?>
                    <li class="page-item disabled">
                        <span class="page-link">...</span>
                    </li>
                <?php endif; ?>

                <?php if ($showLast): ?>
                    <li class="page-item">
                        <a class="page-link" href="<?= buildPaginationUrl($template, $_route, $_totalPages, $_params) ?>"><?= $_totalPages ?></a>
                    </li>
                <?php endif; ?>

                <?php if ($_currentPage < $_totalPages): ?>
                    <li class="page-item">
                        <a class="page-link" href="<?= buildPaginationUrl($template, $_route, $_currentPage + 1, $_params) ?>" aria-label="Page suivante">
                            <i class="fas fa-angle-right"></i>
                        </a>
                    </li>
                    <li class="page-item">
                        <a class="page-link" href="<?= buildPaginationUrl($template, $_route, $_totalPages, $_params) ?>" aria-label="Dernière page">
                            <i class="fas fa-angle-double-right"></i>
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </nav>
<?php endif; ?> 