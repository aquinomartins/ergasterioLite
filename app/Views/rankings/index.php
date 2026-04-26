<?php
$leaderboard = $leaderboard ?? [];
?>
<section class="section-heading">
    <div>
        <span class="eyebrow">Desempenho</span>
        <h1>Ranking geral</h1>
        <p>Classificação simplificada por reputação, payoff acumulado e vitórias.</p>
    </div>
</section>

<section class="card">
    <?php if ($leaderboard === []): ?>
        <div class="empty-state inline-state"><p>Ainda não há dados de ranking.</p></div>
    <?php else: ?>
        <div class="table-responsive">
            <table>
                <thead>
                <tr>
                    <th>#</th>
                    <th>Usuário</th>
                    <th>Payoff total</th>
                    <th>Mercados participados</th>
                    <th>Mercados vencidos</th>
                    <th>Reputação</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($leaderboard as $index => $row): ?>
                    <tr>
                        <td><?= (int) $index + 1 ?></td>
                        <td><?= htmlspecialchars((string) ($row['user_name'] ?? $row['email'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= number_format((float) ($row['total_payoff'] ?? 0), 2, ',', '.') ?></td>
                        <td><?= (int) ($row['total_markets_participated'] ?? 0) ?></td>
                        <td><?= (int) ($row['total_markets_won'] ?? 0) ?></td>
                        <td><?= number_format((float) ($row['reputation_score'] ?? 0), 2, ',', '.') ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>
