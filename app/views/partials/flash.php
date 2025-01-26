<?php
/**
 * @var \Core\View\Template $this
 */

use Core\Session\FlashMessage;

// Récupérer les messages flash
$flashMessages = FlashMessage::getAll();
error_log('Messages flash dans le partial: ' . print_r($flashMessages, true));

if (!empty($flashMessages)): ?>
    <div class="flash-messages">
        <?php foreach ($flashMessages as $message): ?>
            <div class="alert alert-<?= $message['type'] ?> alert-dismissible fade show animate__animated animate__fadeIn" role="alert">
                <?= $message['message'] ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php error_log('Affichage du message: ' . print_r($message, true)); ?>
        <?php endforeach; ?>
    </div>

    <?php if (!$this->isFlashScriptRendered()): ?>
        <script nonce="<?= $_SESSION['nonce'] ?? '' ?>">
            document.addEventListener('DOMContentLoaded', function() {
                setTimeout(function() {
                    document.querySelectorAll('.alert').forEach(function(alert) {
                        var bsAlert = new bootstrap.Alert(alert);
                        setTimeout(function() {
                            bsAlert.close();
                        }, 5000);
                    });
                }, 0);
            });
        </script>
        <?php $this->setFlashScriptRendered(); ?>
    <?php endif; ?>

    <?php
    // Marquer les messages comme affichés après les avoir rendus
    FlashMessage::markAsDisplayed();
    ?>
<?php endif; ?>
