document.addEventListener('DOMContentLoaded', () => {
  const navToggle = document.querySelector('[data-nav-toggle]');
  const nav = document.querySelector('[data-nav]');

  if (navToggle && nav) {
    navToggle.addEventListener('click', () => {
      nav.classList.toggle('is-open');
    });
  }

  document.querySelectorAll('[data-flash-close]').forEach((button) => {
    button.addEventListener('click', () => {
      button.closest('[data-flash]')?.remove();
    });
  });

  const marketForm = document.querySelector('[data-market-form]');
  const optionList = document.querySelector('[data-option-list]');
  const template = document.querySelector('#market-option-template');
  const addOptionButton = document.querySelector('[data-add-option]');
  const marketTypeSelect = document.querySelector('[data-market-type-select]');

  const refreshOptionIndexes = () => {
    if (!optionList) {
      return;
    }

    optionList.querySelectorAll('[data-option-item]').forEach((item, index) => {
      const title = item.querySelector('h3');
      if (title) {
        title.textContent = `Opção ${index + 1}`;
      }
    });
  };

  const syncEntityBlocks = () => {
    if (!optionList || !marketTypeSelect) {
      return;
    }

    const selectedType = marketTypeSelect.value;
    optionList.querySelectorAll('[data-option-item]').forEach((item) => {
      item.querySelectorAll('[data-option-entity]').forEach((block) => {
        const entityType = block.getAttribute('data-option-entity');
        const isActive = entityType === selectedType;
        block.classList.toggle('is-hidden', !isActive);

        block.querySelectorAll('select').forEach((select) => {
          select.disabled = !isActive;
        });
      });
    });
  };

  if (marketForm && optionList && template && addOptionButton) {
    optionList.dataset.nextIndex = String(optionList.querySelectorAll('[data-option-item]').length);

    addOptionButton.addEventListener('click', () => {
      const index = Number(optionList.dataset.nextIndex || optionList.querySelectorAll('[data-option-item]').length);
      const html = template.innerHTML
        .replaceAll('__INDEX__', String(index))
        .replaceAll('__NUMBER__', String(optionList.querySelectorAll('[data-option-item]').length + 1));
      optionList.insertAdjacentHTML('beforeend', html);
      optionList.dataset.nextIndex = String(index + 1);
      refreshOptionIndexes();
      syncEntityBlocks();
    });

    optionList.addEventListener('click', (event) => {
      const button = event.target.closest('[data-remove-option]');
      if (!button) {
        return;
      }

      const items = optionList.querySelectorAll('[data-option-item]');
      if (items.length <= 2) {
        return;
      }

      button.closest('[data-option-item]')?.remove();
      refreshOptionIndexes();
    });

    marketTypeSelect?.addEventListener('change', syncEntityBlocks);
    syncEntityBlocks();
    refreshOptionIndexes();
  }
});

(() => {
  const form = document.querySelector('[data-position-form]');
  if (!form) return;

  const optionSelect = form.querySelector('[data-position-option]');
  const sharesInput = form.querySelector('[data-position-shares]');
  const preview = form.querySelector('[data-position-preview]');

  const parse = (value) => {
    const number = Number(value);
    return Number.isFinite(number) ? number : 0;
  };

  const updatePreview = () => {
    if (!optionSelect || !sharesInput || !preview) return;
    const selected = optionSelect.selectedOptions[0];
    const shares = parse(sharesInput.value);
    if (!selected || !selected.value || shares <= 0) {
      preview.textContent = 'Escolha uma opção e uma quantidade para visualizar o impacto estimado da participação.';
      return;
    }

    const currentWeight = parse(selected.getAttribute('data-current-weight'));
    const rows = [...optionSelect.options].filter((item) => item.value !== '');
    const totalWeight = rows.reduce((acc, item) => acc + parse(item.getAttribute('data-current-weight')), 0);
    const projectedTotal = totalWeight + shares;
    const projectedOptionWeight = currentWeight + shares;
    const projectedProbability = projectedTotal > 0 ? (projectedOptionWeight / projectedTotal) * 100 : 0;

    preview.textContent = `Custo estimado: ${shares.toFixed(2)} créditos. Probabilidade projetada da opção: ${projectedProbability.toFixed(2)}%.`;
  };

  optionSelect?.addEventListener('change', updatePreview);
  sharesInput?.addEventListener('input', updatePreview);
})();
