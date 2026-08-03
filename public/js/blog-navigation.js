(() => {
  const blogPath = '/blogi';
  const homeCardSelector = '.mini-post-grid a[href]';

  const style = document.createElement('style');
  style.id = 'home-blog-card-click-fix';
  style.textContent = `
    .latest-band::before {
      pointer-events: none !important;
    }

    .latest-grid,
    .mini-post-grid {
      position: relative !important;
      z-index: 5 !important;
    }

    .mini-post-grid > a[href] {
      display: block !important;
      position: relative !important;
      z-index: 10 !important;
      height: 100% !important;
      cursor: pointer !important;
      pointer-events: auto !important;
    }

    .mini-post-grid > a[href] article {
      height: 100% !important;
      cursor: pointer !important;
    }
  `;

  if (!document.getElementById(style.id)) {
    document.head.appendChild(style);
  }

  const navigate = (url) => {
    if (url) {
      window.location.assign(url);
    }
  };

  document.addEventListener('click', (event) => {
    const homeCard = event.target.closest?.(homeCardSelector);

    if (homeCard) {
      event.preventDefault();
      event.stopImmediatePropagation();
      navigate(homeCard.href);
      return;
    }

    const trigger = event.target.closest?.('[data-page-target="blog"]');

    if (!trigger) {
      return;
    }

    event.preventDefault();
    event.stopImmediatePropagation();
    navigate(blogPath);
  }, true);

  document.addEventListener('keydown', (event) => {
    if (event.key !== 'Enter' && event.key !== ' ') {
      return;
    }

    const homeCard = event.target.closest?.(homeCardSelector);

    if (!homeCard) {
      return;
    }

    event.preventDefault();
    navigate(homeCard.href);
  }, true);
})();
