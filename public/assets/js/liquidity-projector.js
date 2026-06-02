(function () {
  var root = document.getElementById('projector');
  if (!root) return;
  var sessionId = root.getAttribute('data-session-id');

  function money(v) {
    return 'R$ ' + Number(v || 0).toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
  }

  function poll() {
    Promise.all([
      fetch('/api/liquidity/' + sessionId + '/session').then(function (r) { return r.json(); }),
      fetch('/api/liquidity/' + sessionId + '/ranking').then(function (r) { return r.json(); }),
      fetch('/api/liquidity/' + sessionId + '/feed').then(function (r) { return r.json(); }),
      fetch('/api/liquidity/' + sessionId + '/pool').then(function (r) { return r.json(); }),
      fetch('/api/liquidity/' + sessionId + '/predictions').then(function (r) { return r.json(); }).catch(function () { return []; })
    ]).then(function (res) {
      var session = res[0] || {}, ranking = res[1] || [], feed = res[2] || [], pool = res[3] || {}, markets = res[4] || [];
      var r = root.querySelector('[data-session-round]');
      var t = root.querySelector('[data-session-total]');
      if (r) r.textContent = session.current_round || 0;
      if (t) t.textContent = session.total_rounds || 0;

      root.querySelector('[data-pool-status]').textContent = pool.status || '-';
      root.querySelector('[data-pool-nfts]').textContent = pool.nft_reserve || 0;
      root.querySelector('[data-pool-shares]').textContent = Number(pool.share_supply || 0).toLocaleString('pt-BR', { minimumFractionDigits: 4, maximumFractionDigits: 4 });
      root.querySelector('[data-pool-total]').textContent = money(pool.total_value_locked || 0);
      root.querySelector('[data-pool-yield]').textContent = Number(pool.yield_per_share || 0).toLocaleString('pt-BR', { minimumFractionDigits: 4, maximumFractionDigits: 4 });

      var rankBody = root.querySelector('[data-ranking-body]');
      rankBody.innerHTML = ranking.slice(0, 10).map(function (row, i) {
        return '<tr><td>' + (row.general_position || (i + 1)) + '</td><td>' + (row.name || 'Equipe') + '</td><td>' + money(row.estimated_wealth || row.score || 0) + '</td><td>' + (row.display_status || 'Em jogo') + '</td></tr>';
      }).join('');

      var feedList = root.querySelector('[data-feed-list]');
      feedList.innerHTML = feed.slice(0, 12).map(function (ev) {
        return '<li>' + (ev.event_type || 'evento') + ' — ' + (ev.description || '') + '</li>';
      }).join('');

      var wrap = root.querySelector('[data-markets-wrap]');
      var list = root.querySelector('[data-markets-list]');
      if (markets.length) {
        wrap.hidden = false;
        list.innerHTML = markets.map(function (m) {
          var top = (m.options || []).slice().sort(function (a, b) { return (b.probability_value || 0) - (a.probability_value || 0); })[0];
          var prob = top ? Number(top.probability_value || 0) * 100 : 0;
          return '<div class="prediction-market-card"><strong>' + (m.question || '') + '</strong> · ' + prob.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + '%</div>';
        }).join('');
      } else {
        wrap.hidden = true;
      }
    }).catch(function () {});
  }

  poll();
  setInterval(poll, 3000);
})();
