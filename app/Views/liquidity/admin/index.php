<h1>Piscina de Liquidez</h1>
<p><a href="/liquidity/my-games">Meus jogos</a> · <a href="/liquidity/create">Criar novo jogo</a> · <a href="/liquidity/join">Entrar com código</a></p>
<h2>Arena pública</h2>
<?php foreach (($sessions ?? []) as $s): ?>
    <div><a href="/liquidity/<?= (int)$s['id'] ?>"><?= htmlspecialchars($s['name'], ENT_QUOTES, 'UTF-8') ?></a></div>
<?php endforeach; ?>
