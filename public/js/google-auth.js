(() => {
  const modal = document.getElementById('loginModal');
  if (!modal) return;

  const open = () => {
    modal.classList.add('open');
    document.body.classList.add('body-lock', 'mobile-auth-open');
  };
  const close = () => {
    modal.classList.remove('open');
    document.body.classList.remove('body-lock', 'mobile-auth-open');
  };

  document.querySelectorAll('[data-open-login]').forEach((button) => {
    button.addEventListener('click', (event) => {
      event.preventDefault();
      open();
    });
  });
  modal.querySelector('[data-close-login]')?.addEventListener('click', close);
  modal.addEventListener('click', (event) => {
    if (event.target === modal) close();
  });
  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape' && modal.classList.contains('open')) close();
  });

  modal.querySelector('[data-google-auth-start]')?.addEventListener('click', () => {
    document.body.classList.add('google-auth-redirecting');
  });

  if (modal.querySelector('[data-google-auth-error]')) open();
})();
