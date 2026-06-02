(function () {
  var forms = document.querySelectorAll('[data-admin-action-form]');
  if (!forms.length) return;

  var marketActions = ['buy_btc', 'sell_btc', 'buy_nft', 'sell_nft', 'buy_share', 'sell_share'];

  forms.forEach(function (form) {
    var actionSelect = form.querySelector('[data-action-select]');
    var quantityField = form.querySelector('[data-quantity-field]');
    var paymentField = form.querySelector('[data-payment-field]');
    var targetField = form.querySelector('[data-target-field]');
    var priceField = form.querySelector('[data-price-field]');
    var quantityInput = quantityField ? quantityField.querySelector('input[name="quantity"]') : null;
    var paymentSelect = paymentField ? paymentField.querySelector('select[name="payment_method"]') : null;
    var targetSelect = targetField ? targetField.querySelector('select[name="target_team_id"]') : null;
    var priceInput = priceField ? priceField.querySelector('input[name="price"]') : null;

    function syncActionFields() {
      var actionType = actionSelect ? actionSelect.value : '';
      var usesMarket = marketActions.indexOf(actionType) !== -1;
      var usesPayment = actionType === 'withdraw_nft';

      if (quantityField) quantityField.hidden = !usesMarket;
      if (quantityInput) {
        quantityInput.disabled = !usesMarket;
        quantityInput.required = usesMarket;
        if (!quantityInput.value) quantityInput.value = '1';
      }

      if (targetField) targetField.hidden = !usesMarket;
      if (targetSelect) {
        targetSelect.disabled = !usesMarket;
        targetSelect.required = usesMarket;
        if (!usesMarket) targetSelect.value = '';
      }

      if (priceField) priceField.hidden = !usesMarket;
      if (priceInput) {
        priceInput.disabled = !usesMarket;
        priceInput.required = usesMarket;
        if (!usesMarket) priceInput.value = '';
      }

      if (paymentField) paymentField.hidden = !usesPayment;
      if (paymentSelect) {
        paymentSelect.disabled = !usesPayment;
        paymentSelect.required = usesPayment;
      }
    }

    if (actionSelect) {
      actionSelect.addEventListener('change', syncActionFields);
      syncActionFields();
    }

    form.addEventListener('submit', function () {
      var btn = form.querySelector('button[type="submit"]');
      if (btn) {
        btn.disabled = true;
        btn.textContent = 'Processando...';
      }
    });
  });
})();
