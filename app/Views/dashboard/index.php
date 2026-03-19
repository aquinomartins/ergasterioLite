<?php $user = $user ?? []; ?>
<section class="card">
    <span class="eyebrow">Área autenticada</span>
    <h1>Olá, <?= htmlspecialchars((string) ($user['display_name'] ?? $user['email'] ?? 'usuário'), ENT_QUOTES, 'UTF-8') ?></h1>
    <p>Seu acesso está ativo e a base do produto já oferece sessão autenticada, perfil editável e fluxo protegido.</p>
</section>

<section class="grid-two dashboard-grid">
    <article class="card">
        <h2>Resumo da conta</h2>
        <dl class="definition-list">
            <div>
                <dt>E-mail</dt>
                <dd><?= htmlspecialchars((string) ($user['email'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></dd>
            </div>
            <div>
                <dt>Status</dt>
                <dd><?= htmlspecialchars((string) ($user['status'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></dd>
            </div>
            <div>
                <dt>Username</dt>
                <dd>@<?= htmlspecialchars((string) ($user['username'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></dd>
            </div>
        </dl>
    </article>

    <article class="card">
        <h2>Próximas extensões</h2>
        <ul>
            <li>Cadastro de artistas com slug automático</li>
            <li>Cadastro de obras com upload de imagem</li>
            <li>Criação de mercados preditivos</li>
        </ul>
        <a class="button button-secondary" href="/profile/edit">Editar perfil</a>
    </article>
</section>
