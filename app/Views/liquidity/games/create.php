<section class="liquidity-card">
    <p class="liquidity-eyebrow">Piscina de Liquidez</p>
    <h1>Criar novo jogo</h1>
    <p>Crie uma partida e compartilhe o código com os participantes. Depois que eles solicitarem entrada, você poderá aprovar cada um no Painel do Professor.</p>
    <form method="post" action="/liquidity" class="stacked-form">
        <?= \App\Core\Csrf::input() ?>
        <label>Título do jogo <input name="title" required placeholder="Ex.: Turma de sexta"></label>
        <label>Número máximo de rodadas <input type="number" name="max_rounds" min="1" value="6" required></label>
        <label>Modo <input value="Individual" disabled><input type="hidden" name="mode" value="individual"></label>
        <label>Quantidade máxima de participantes <input type="number" name="max_participants" min="1" placeholder="Opcional"></label>
        <button type="submit">Criar novo jogo</button>
    </form>
</section>
