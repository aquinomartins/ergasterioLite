(function () {
  var root = document.getElementById('liquidity-team-dashboard');
  if (!root) return;

  function fmtMoney(v) {
    return 'R$ ' + Number(v || 0).toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
  }

  function refreshState() {
    fetch('/api/liquidity/team/state', { headers: { 'Accept': 'application/json' } })
      .then(function (r) { return r.json(); })
      .then(function (d) {
        if (!d || !d.pool) return;
        var pool = d.pool;
        var nfts = root.querySelector('[data-pool-nfts]');
        var total = root.querySelector('[data-pool-total]');
        var shares = root.querySelector('[data-pool-shares]');
        var y = root.querySelector('[data-pool-yield]');
        var status = root.querySelector('[data-pool-status]');
        if (nfts) nfts.textContent = pool.pool_nfts || 0;
        if (total) total.textContent = fmtMoney(pool.total_value || 0);
        if (shares) shares.textContent = pool.total_shares || 0;
        if (y) y.textContent = fmtMoney(pool.yield_per_share || 0);
        if (status) status.textContent = pool.status || '-';
      })
      .catch(function () {});
  }

  setInterval(refreshState, 5000);
})();
