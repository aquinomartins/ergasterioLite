<?php if (! empty($flash)): ?>
    <div class="flash-stack">
        <?php foreach ($flash as $type => $messages): ?>
            <?php foreach ($messages as $message): ?>
                <div class="flash flash-<?= htmlspecialchars($type, ENT_QUOTES, 'UTF-8') ?>" data-flash>
                    <span><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></span>
                    <button type="button" aria-label="Fechar" data-flash-close>×</button>
                </div>
            <?php endforeach; ?>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
