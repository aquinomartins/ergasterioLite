<section class="liquidity-card">
    <p class="liquidity-eyebrow">Piscina de Liquidez</p>
    <h1>Entrar com código</h1>
    <p>Digite o código fornecido pelo professor. Depois disso, sua entrada ficará aguardando aprovação.</p>
    <form method="post" action="/liquidity/join" class="stacked-form">
        <?= \App\Core\Csrf::input() ?>
        <label>Código de convite <input name="invite_code" required placeholder="PL-ABC123"></label>
        <button type="submit">Solicitar entrada</button>
    </form>
    <p><a href="/liquidity/my-games">Voltar para Meus jogos</a></p>
</section>
