(() => {
  const blogPath = '/blogi';

  document.addEventListener('click', (event) => {
    const trigger = event.target.closest('[data-page-target="blog"]');

    if (!trigger) {
      return;
    }

    event.preventDefault();
    event.stopImmediatePropagation();
    window.location.assign(blogPath);
  }, true);
})();
