(function () {
  var forms = document.querySelectorAll('[data-admin-action-form]');
  if (!forms.length) return;

  forms.forEach(function (form) {
    var actionSelect = form.querySelector('[data-action-select]');
    var quantityField = form.querySelector('[data-quantity-field]');
    var paymentField = form.querySelector('[data-payment-field]');
    var quantityInput = quantityField ? quantityField.querySelector('input[name="quantity"]') : null;
    var paymentSelect = paymentField ? paymentField.querySelector('select[name="payment_method"]') : null;

    function syncActionFields() {
      var actionType = actionSelect ? actionSelect.value : '';
      var usesQuantity = actionType === 'buy_btc' || actionType === 'sell_btc';
      var usesPayment = actionType === 'withdraw_nft';

      if (quantityField) quantityField.hidden = !usesQuantity;
      if (quantityInput) {
        quantityInput.disabled = !usesQuantity;
        quantityInput.required = usesQuantity;
        if (!quantityInput.value) quantityInput.value = '1';
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
