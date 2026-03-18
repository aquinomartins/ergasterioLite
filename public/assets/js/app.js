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
});
