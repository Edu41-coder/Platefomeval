<?php
/** 
 * @var \Core\View\Template $this 
 * @var string $pageTitle
 * @var string $site_title
 * @var string $csrfToken
 * @var string $pageDescription
 * @var array $flash_messages
 * @var string $content
 * @var array $assets
 * @var string $bodyClass
 * @var string $additionalHeadContent
 */

use Core\Session\FlashMessage;

// Génération du nonce pour la sécurité des scripts
$nonce = base64_encode(random_bytes(16));
$_SESSION['nonce'] = $nonce;
?>
<!DOCTYPE html>
<html lang="fr" class="h-100">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">

    <title><?= $this->e($pageTitle ?? $site_title ?? 'Plateforme d\'évaluation') ?></title>

    <!-- Meta tags -->
    <meta name="csrf-token" content="<?= $this->e($csrfToken ?? '') ?>">
    <meta name="description" content="<?= $this->e($pageDescription ?? 'Plateforme d\'évaluation en ligne') ?>">
    
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

    <!-- Preload critical resources -->
    <link rel="preload" href="<?= $this->asset('css/bootstrap.min.css') ?>" as="style">
    <link rel="preload" href="<?= $this->asset('js/bootstrap.bundle.min.js') ?>" as="script">

    <!-- CSS -->
    <link rel="stylesheet" href="<?= $this->asset('css/bootstrap.min.css') ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" 
          integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA==" 
          crossorigin="anonymous" 
          referrerpolicy="no-referrer">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"
          integrity="sha512-c42qTSw/wPZ3/5LBzD+Bw5f7bSF2oxou6wEb+I/lqeaKV5FDIfMvvRp772y4jcJLKuGUOpbJMdg/BTl50fJYAw==" 
          crossorigin="anonymous" 
          referrerpolicy="no-referrer">
    <link rel="stylesheet" href="<?= $this->asset('css/style.css') ?>">

    <!-- CSS additionnels -->
    <?php if (!empty($assets['css'] ?? null)): ?>
        <!-- Debug: Assets dans le layout -->
        <!-- <?php 
        var_dump([
            'layout_assets' => $assets,
            'css_files' => $assets['css'] ?? [],
            'js_files' => $assets['js'] ?? []
        ]); 
        ?> -->
        <?php foreach ($assets['css'] as $css): ?>
            <?php $fullPath = "/Plateformeval/public/assets/{$css}"; ?>
            <!-- Loading CSS: <?= $fullPath ?> -->
            <link rel="stylesheet" href="<?= $fullPath ?>">
        <?php endforeach; ?>
    <?php endif; ?>

    <?= $additionalHeadContent ?? '' ?>
</head>

<body class="d-flex flex-column min-vh-100 <?= $this->e($bodyClass ?? '') ?>">
    <!-- Header -->
    <?php $this->partial('header', compact('site_title', 'csrfToken')) ?>

    <!-- Add flash messages right after header -->
    <?php $this->partial('flash') ?>

    <!-- Main Content -->
    <main class="flex-grow-1 py-4">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-12 col-md-10 col-lg-8">
                    <?= $content ?? '' ?>
                </div>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <?php $this->partial('footer', ['site_title' => $site_title ?? '']) ?>

    <!-- Scripts -->
    <script nonce="<?= $nonce ?>" src="<?= $this->asset('js/bootstrap.bundle.min.js') ?>"></script>
    <script nonce="<?= $nonce ?>" src="<?= $this->asset('js/app.js') ?>" defer></script>

    <!-- Scripts additionnels -->
    <?php if (!empty($assets['js'] ?? null)): ?>
        <?php foreach ($assets['js'] as $js): ?>
            <!-- Debug: Loading JS: <?= $js ?> -->
            <script nonce="<?= $nonce ?>" src="/Plateformeval/public/assets/<?= $js ?>" defer></script>
        <?php endforeach; ?>
    <?php endif; ?>
</body>
</html>
<?php
// Nettoyer les messages flash affichés à la fin de la requête
FlashMessage::clearDisplayed();
?>