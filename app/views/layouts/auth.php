<?php

/** 
 * @var \Core\View\Template $this 
 * @var string $pageTitle
 * @var string $site_title
 * @var string $csrfToken
 * @var string $content
 * @var array $assets
 * @var string $bodyClass
 */

// Encapsulation dans une fonction anonyme
(function($template, $pageTitle, $site_title, $csrfToken, $content, $assets, $bodyClass) {
    // Génération du nonce pour la sécurité des scripts
    $nonce = base64_encode(random_bytes(16));
    $_SESSION['nonce'] = $nonce;
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    
    <title><?= $template->e($pageTitle ?? $site_title ?? 'Plateforme d\'évaluation') ?></title>
    
    <!-- Meta tags -->
    <meta name="csrf-token" content="<?= $template->e($csrfToken) ?>">
    <meta name="description" content="Plateforme d'évaluation - Page d'authentification">
    
    <!-- Security headers -->
    <meta http-equiv="Content-Security-Policy" content="
        default-src 'self' cdnjs.cloudflare.com;
        style-src 'self' 'unsafe-inline' cdnjs.cloudflare.com;
        script-src 'self' 'nonce-<?= $nonce ?>' cdnjs.cloudflare.com;
        img-src 'self' data: https:;
        font-src 'self' cdnjs.cloudflare.com;
        connect-src 'self'">
    <meta http-equiv="X-Frame-Options" content="DENY">
    <meta http-equiv="X-Content-Type-Options" content="nosniff">
    <meta http-equiv="Referrer-Policy" content="strict-origin-when-cross-origin">
    
    <!-- CSS -->
    <link rel="stylesheet" href="<?= $template->asset('css/bootstrap.min.css') ?>" type="text/css">
    <link rel="stylesheet" href="<?= $template->asset('css/style.css') ?>" type="text/css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" 
          integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA==" 
          crossorigin="anonymous" 
          referrerpolicy="no-referrer">
    
    <!-- Assets CSS spécifiques -->
    <?php if (!empty($assets['css'])): ?>
        <?php foreach ($assets['css'] as $css): ?>
            <link rel="stylesheet" href="<?= $template->asset($css) ?>" type="text/css">
        <?php endforeach; ?>
    <?php endif; ?>
</head>
<body class="<?= $template->e($bodyClass ?? 'auth-layout') ?> bg-light d-flex flex-column min-vh-100">
    <!-- Header -->
    <?php $template->partial('header', [
        'site_title' => $site_title,
        'user' => $template->get('user'),
        'csrfToken' => $csrfToken
    ]); ?>

    <!-- Flash Messages -->
    <?php include __DIR__ . '/../partials/flash.php'; ?>

    <!-- Main Container -->
    <?php 
    switch($bodyClass) {
        case 'forgot-password-page':
            // Layout horizontal pour la page mot de passe oublié
            ?>
            <main class="flex-grow-1">
                <?= $content ?>
            </main>
            <?php
            break;
            
        case 'login-page':
        case 'register-page':
        default:
            // Layout centré en boîte pour login et register
            ?>
            <main class="flex-grow-1 d-flex align-items-center justify-content-center py-5">
                <div class="container">
                    <div class="row justify-content-center">
                        <div class="col-11 col-sm-9 col-md-7 col-lg-5 col-xl-4">
                            <div class="auth-box bg-white rounded-3 shadow-sm p-4">
                                <div class="text-center mb-4">
                                    <h1 class="h4 text-gray-900"><?= $template->e($pageTitle) ?></h1>
                                </div>
                                <?= $content ?>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
            <?php
            break;
    }
    ?>

    <!-- Footer -->
    <?php $template->partial('footer', [
        'site_title' => $site_title,
        'user' => $template->get('user')
    ]); ?>

    <!-- Scripts -->
    <script nonce="<?= $nonce ?>">
        window.appConfig = {
            basePath: '<?= defined('BASE_PATH') ? BASE_PATH : '/Plateformeval' ?>',
            debug: <?= isset($_ENV['APP_DEBUG']) && $_ENV['APP_DEBUG'] ? 'true' : 'false' ?>,
            csrfToken: '<?= $csrfToken ?>'
        };
    </script>
    <script nonce="<?= $nonce ?>" src="<?= $template->asset('js/bootstrap.bundle.min.js') ?>"></script>
    <script nonce="<?= $nonce ?>" src="<?= $template->asset('js/app.js') ?>"></script>
    
    <!-- Assets JS spécifiques -->
    <?php if (!empty($assets['js'])): ?>
        <?php foreach ($assets['js'] as $js): ?>
            <script nonce="<?= $nonce ?>" src="<?= $template->asset($js) ?>"></script>
        <?php endforeach; ?>
    <?php endif; ?>
</body>
</html>
<?php
})($this, $pageTitle ?? null, $site_title ?? null, $csrfToken ?? null, $content ?? '', $assets ?? [], $bodyClass ?? null);
?>