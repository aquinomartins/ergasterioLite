(function () {
  var forms = document.querySelectorAll('[data-admin-action-form]');
  if (!forms.length) return;
  forms.forEach(function (form) {
    form.addEventListener('submit', function () {
      var btn = form.querySelector('button[type="submit"]');
      if (btn) {
        btn.disabled = true;
        btn.textContent = 'Processando...';
      }
    });
  });
})();
