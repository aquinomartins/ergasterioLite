<?php
use App\Core\Csrf;

$old = $old ?? [];
$errors = $errors ?? [];
$artworks = $artworks ?? [];
$artists = $artists ?? [];
$selectedType = (string) ($old['market_type'] ?? 'artwork_outcome');
$options = $old['options'] ?? [
    ['label' => '', 'artwork_id' => null, 'artist_id' => null],
    ['label' => '', 'artwork_id' => null, 'artist_id' => null],
];
?>
<section class="card narrow market-create-card">
    <span class="eyebrow">Novo mercado</span>
    <h1>Criar mercado</h1>
    <p>Cadastre um mercado em rascunho, defina ao menos duas opções e prepare a base para o próximo módulo de participações.</p>

    <?php foreach ($errors['market'] ?? [] as $message): ?>
        <p class="error-text"><?= htmlspecialchars((string) $message, ENT_QUOTES, 'UTF-8') ?></p>
    <?php endforeach; ?>

    <form method="POST" action="/markets" class="form-grid" data-market-form>
        <?= Csrf::input() ?>
        <label>
            <span>Título</span>
            <input type="text" name="title" value="<?= htmlspecialchars((string) ($old['title'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" maxlength="180" required>
            <?php foreach ($errors['title'] ?? [] as $message): ?>
                <small class="error-text"><?= htmlspecialchars((string) $message, ENT_QUOTES, 'UTF-8') ?></small>
            <?php endforeach; ?>
        </label>

        <label>
            <span>Descrição</span>
            <textarea name="description" rows="6" required><?= htmlspecialchars((string) ($old['description'] ?? ''), ENT_QUOTES, 'UTF-8') ?></textarea>
            <?php foreach ($errors['description'] ?? [] as $message): ?>
                <small class="error-text"><?= htmlspecialchars((string) $message, ENT_QUOTES, 'UTF-8') ?></small>
            <?php endforeach; ?>
        </label>

        <div class="grid-two form-grid-tight">
            <label>
                <span>Tipo de mercado</span>
                <select name="market_type" required data-market-type-select>
                    <option value="artwork_outcome" <?= $selectedType === 'artwork_outcome' ? 'selected' : '' ?>>Resultado por obra</option>
                    <option value="artist_outcome" <?= $selectedType === 'artist_outcome' ? 'selected' : '' ?>>Resultado por artista</option>
                </select>
                <?php foreach ($errors['market_type'] ?? [] as $message): ?>
                    <small class="error-text"><?= htmlspecialchars((string) $message, ENT_QUOTES, 'UTF-8') ?></small>
                <?php endforeach; ?>
            </label>

            <label>
                <span>Data de fechamento</span>
                <input type="datetime-local" name="closes_at" value="<?= htmlspecialchars(substr(str_replace(' ', 'T', (string) ($old['closes_at'] ?? '')), 0, 16), ENT_QUOTES, 'UTF-8') ?>" required>
                <?php foreach ($errors['closes_at'] ?? [] as $message): ?>
                    <small class="error-text"><?= htmlspecialchars((string) $message, ENT_QUOTES, 'UTF-8') ?></small>
                <?php endforeach; ?>
            </label>
        </div>

        <input type="hidden" name="resolution_mode" value="manual">

        <section class="market-options-builder">
            <div class="section-heading compact-heading market-inline-heading">
                <div>
                    <h2>Opções do mercado</h2>
                    <p>Começam com peso 1 e terão a probabilidade recalculada automaticamente.</p>
                </div>
                <button type="button" class="button button-secondary" data-add-option>Adicionar opção</button>
            </div>

            <?php foreach ($errors['options'] ?? [] as $message): ?>
                <small class="error-text"><?= htmlspecialchars((string) $message, ENT_QUOTES, 'UTF-8') ?></small>
            <?php endforeach; ?>

            <div class="option-builder-list" data-option-list>
                <?php foreach ($options as $index => $option): ?>
                    <article class="card option-builder-item" data-option-item>
                        <div class="option-builder-header">
                            <h3>Opção <?= $index + 1 ?></h3>
                            <button type="button" class="link-button danger-link" data-remove-option>Remover</button>
                        </div>

                        <label>
                            <span>Rótulo</span>
                            <input type="text" name="options[<?= $index ?>][label]" value="<?= htmlspecialchars((string) ($option['label'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" required>
                            <?php foreach ($errors['options.' . $index . '.label'] ?? [] as $message): ?>
                                <small class="error-text"><?= htmlspecialchars((string) $message, ENT_QUOTES, 'UTF-8') ?></small>
                            <?php endforeach; ?>
                        </label>

                        <div class="option-entity-block <?= $selectedType === 'artwork_outcome' ? '' : 'is-hidden' ?>" data-option-entity="artwork_outcome">
                            <label>
                                <span>Obra relacionada</span>
                                <select name="options[<?= $index ?>][artwork_id]">
                                    <option value="">Selecione uma obra</option>
                                    <?php foreach ($artworks as $artwork): ?>
                                        <option value="<?= (int) $artwork['id'] ?>" <?= (string) ($option['artwork_id'] ?? '') === (string) $artwork['id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars((string) $artwork['title'], ENT_QUOTES, 'UTF-8') ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <?php foreach ($errors['options.' . $index . '.artwork_id'] ?? [] as $message): ?>
                                    <small class="error-text"><?= htmlspecialchars((string) $message, ENT_QUOTES, 'UTF-8') ?></small>
                                <?php endforeach; ?>
                            </label>
                        </div>

                        <div class="option-entity-block <?= $selectedType === 'artist_outcome' ? '' : 'is-hidden' ?>" data-option-entity="artist_outcome">
                            <label>
                                <span>Artista relacionado</span>
                                <select name="options[<?= $index ?>][artist_id]">
                                    <option value="">Selecione um artista</option>
                                    <?php foreach ($artists as $artist): ?>
                                        <option value="<?= (int) $artist['id'] ?>" <?= (string) ($option['artist_id'] ?? '') === (string) $artist['id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars((string) $artist['display_name'], ENT_QUOTES, 'UTF-8') ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <?php foreach ($errors['options.' . $index . '.artist_id'] ?? [] as $message): ?>
                                    <small class="error-text"><?= htmlspecialchars((string) $message, ENT_QUOTES, 'UTF-8') ?></small>
                                <?php endforeach; ?>
                            </label>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>

        <button type="submit" class="button">Salvar mercado</button>
    </form>
</section>

<template id="market-option-template">
    <article class="card option-builder-item" data-option-item>
        <div class="option-builder-header">
            <h3>Opção __NUMBER__</h3>
            <button type="button" class="link-button danger-link" data-remove-option>Remover</button>
        </div>

        <label>
            <span>Rótulo</span>
            <input type="text" name="options[__INDEX__][label]" required>
        </label>

        <div class="option-entity-block" data-option-entity="artwork_outcome">
            <label>
                <span>Obra relacionada</span>
                <select name="options[__INDEX__][artwork_id]">
                    <option value="">Selecione uma obra</option>
                    <?php foreach ($artworks as $artwork): ?>
                        <option value="<?= (int) $artwork['id'] ?>"><?= htmlspecialchars((string) $artwork['title'], ENT_QUOTES, 'UTF-8') ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
        </div>

        <div class="option-entity-block is-hidden" data-option-entity="artist_outcome">
            <label>
                <span>Artista relacionado</span>
                <select name="options[__INDEX__][artist_id]">
                    <option value="">Selecione um artista</option>
                    <?php foreach ($artists as $artist): ?>
                        <option value="<?= (int) $artist['id'] ?>"><?= htmlspecialchars((string) $artist['display_name'], ENT_QUOTES, 'UTF-8') ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
        </div>
    </article>
</template>
