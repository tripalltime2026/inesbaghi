(() => {
  'use strict';

  const qs = (selector, root = document) => root.querySelector(selector);
  const qsa = (selector, root = document) => [...root.querySelectorAll(selector)];

  const pageRoutes = {
    home: '/',
    about: '/chven-shesakheb',
    methodology: '/metodologia',
    groups: '/jgufebi',
    team: '/gundi',
    gallery: '/shesvla',
    blog: '/blogi',
    faq: '/kitkhva-pasukhi',
    contact: '/kontakti',
    admission: '/charetskhva',
  };

  function replaceWithLink(control, href) {
    if (!control) return null;
    if (control.tagName === 'A') {
      control.href = href;
      control.removeAttribute('data-page-target');
      return control;
    }

    const link = document.createElement('a');
    [...control.attributes].forEach((attribute) => {
      if (attribute.name !== 'type' && attribute.name !== 'data-page-target') {
        link.setAttribute(attribute.name, attribute.value);
      }
    });
    link.href = href;
    link.innerHTML = control.innerHTML;
    control.replaceWith(link);

    return link;
  }

  function convertPageControlsToLinks() {
    qsa('[data-page-target]').forEach((control) => {
      const href = pageRoutes[control.dataset.pageTarget];
      if (href) replaceWithLink(control, href);
    });

    qsa('[data-open-login]').forEach((control) => {
      const link = replaceWithLink(control, '/shesvla');
      link?.removeAttribute('data-open-login');
    });
  }

  function stabilizePublicMobileNavigation() {
    const navs = qsa('.public-mobile-nav');
    if (!navs.length) return false;

    const nav = navs[0];
    navs.slice(1).forEach((duplicate) => duplicate.remove());

    const destinations = {
      home: '/',
      groups: '/jgufebi',
      admission: '/charetskhva',
    };

    Object.entries(destinations).forEach(([key, href]) => {
      replaceWithLink(qs(`[data-mobile-key="${key}"]`, nav), href);
    });

    const accountControl = qs('[data-mobile-key="club"], [data-mobile-key="ai"]', nav);
    if (accountControl) {
      const cabinet = qs('.site-actions .pill.navy');
      const authenticatedHref = cabinet?.tagName === 'A' && !cabinet.href.endsWith('/shesvla')
        ? cabinet.href
        : '/shesvla';
      const accountLink = replaceWithLink(accountControl, authenticatedHref);
      accountLink.dataset.mobileKey = 'account';
      accountLink.innerHTML = `<i>●</i><span>${authenticatedHref.endsWith('/shesvla') ? 'შესვლა' : 'კაბინეტი'}</span>`;
    }

    const currentPath = window.location.pathname.replace(/\/$/, '') || '/';
    qsa('[data-mobile-key]', nav).forEach((item) => {
      const href = item.getAttribute('href');
      item.classList.toggle('active', href === currentPath || (href === '/' && currentPath === '/'));
    });

    return true;
  }

  function keepContentVisible(root = document) {
    qsa('.experience-reveal', root).forEach((element) => {
      element.classList.add('is-visible');
      element.style.opacity = '1';
      element.style.transform = 'none';
      element.style.transitionDelay = '0ms';
    });
  }

  function redirectLegacyHash() {
    const key = window.location.hash.replace('#', '').trim();
    const href = pageRoutes[key];
    if (!key || !href || href === '/') return;
    window.location.replace(href);
  }

  function coordinateChat() {
    const panel = qs('[data-ines-ai-panel]');
    const launcher = qs('.ines-ai-launcher');

    const sync = () => {
      const open = Boolean(panel?.classList.contains('open'));
      document.body.classList.toggle('mobile-chat-open', open);
      launcher?.setAttribute('aria-expanded', open ? 'true' : 'false');
    };

    if (panel) {
      new MutationObserver(sync).observe(panel, {
        attributes: true,
        attributeFilter: ['class'],
      });
    }
    sync();
  }

  function installMutationSafety() {
    const observer = new MutationObserver((records) => {
      records.forEach((record) => {
        record.addedNodes.forEach((node) => {
          if (!(node instanceof Element)) return;
          keepContentVisible(node);
        });
      });
      stabilizePublicMobileNavigation();
    });

    observer.observe(document.body, { childList: true, subtree: true });
  }

  function init() {
    if (!document.body.classList.contains('final-site')) return;

    redirectLegacyHash();
    convertPageControlsToLinks();
    keepContentVisible();
    coordinateChat();
    installMutationSafety();

    if (!stabilizePublicMobileNavigation()) {
      window.setTimeout(stabilizePublicMobileNavigation, 100);
      window.setTimeout(stabilizePublicMobileNavigation, 350);
    }
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init, { once: true });
  } else {
    init();
  }
})();
