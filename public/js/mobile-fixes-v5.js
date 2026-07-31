(() => {
  'use strict';

  const qs = (selector, root = document) => root.querySelector(selector);
  const qsa = (selector, root = document) => [...root.querySelectorAll(selector)];
  const publicPages = () => qsa('.public-page[data-page]');

  function replaceWithLink(control, href) {
    if (!control || control.tagName === 'A') {
      if (control) control.href = href;
      return control;
    }

    const link = document.createElement('a');
    [...control.attributes].forEach((attribute) => {
      if (attribute.name !== 'type') link.setAttribute(attribute.name, attribute.value);
    });
    link.href = href;
    link.innerHTML = control.innerHTML;
    control.replaceWith(link);

    return link;
  }

  function showRevealContent(root = document) {
    qsa('.experience-reveal', root).forEach((element) => {
      element.classList.add('is-visible');
      element.style.transitionDelay = '0ms';
    });
  }

  function scrollPublicPageToTop() {
    const reset = () => {
      window.scrollTo(0, 0);
      document.documentElement.scrollTop = 0;
      document.body.scrollTop = 0;
    };

    reset();
    window.requestAnimationFrame(reset);
    window.setTimeout(reset, 80);
  }

  function activatePublicPage(pageName, updateHistory = true) {
    const pages = publicPages();
    const requested = typeof pageName === 'string' ? pageName.trim() : '';
    const page = pages.find((item) => item.dataset.page === requested) || pages[0];
    if (!page) return false;

    pages.forEach((item) => {
      const active = item === page;
      item.classList.toggle('active', active);
      item.hidden = !active;
      item.setAttribute('aria-hidden', active ? 'false' : 'true');
    });

    qsa('[data-page-target]').forEach((control) => {
      control.classList.toggle('active', control.dataset.pageTarget === page.dataset.page);
    });
    qsa('[data-mobile-key]').forEach((control) => {
      if (['home', 'groups', 'admission'].includes(control.dataset.mobileKey)) {
        control.classList.toggle('active', control.dataset.mobileKey === page.dataset.page);
      }
    });

    qs('#siteNav')?.classList.remove('open');
    const menuToggle = qs('#menuToggle');
    if (menuToggle) menuToggle.textContent = '☰';

    showRevealContent(page);
    if (updateHistory) {
      history.replaceState(null, '', `#${page.dataset.page}`);
    }
    scrollPublicPageToTop();

    return true;
  }

  function installDirectPageNavigation() {
    document.addEventListener('click', (event) => {
      if (!document.body.classList.contains('final-site')) return;

      const control = event.target.closest('[data-page-target]');
      if (!control) return;

      const pageName = control.dataset.pageTarget;
      if (!pageName || !qs(`.public-page[data-page="${CSS.escape(pageName)}"]`)) return;

      event.preventDefault();
      event.stopImmediatePropagation();
      activatePublicPage(pageName);
    }, true);

    window.addEventListener('hashchange', () => {
      activatePublicPage(window.location.hash.replace('#', '') || 'home', false);
    });
  }

  function installPublicMobileNavigation() {
    const nav = qs('.public-mobile-nav');
    if (!nav) return false;

    if (document.body.classList.contains('seo-page-body')) {
      replaceWithLink(qs('[data-mobile-key="home"]', nav), '/');
      replaceWithLink(qs('[data-mobile-key="groups"]', nav), '/jgufebi');
      replaceWithLink(qs('[data-mobile-key="admission"]', nav), '/charetskhva');
      return true;
    }

    const oldClubControl = qs('[data-mobile-key="club"]', nav);
    if (oldClubControl && !qs('[data-mobile-key="ai"]', nav)) {
      const aiControl = document.createElement('button');
      aiControl.type = 'button';
      aiControl.dataset.mobileKey = 'ai';
      aiControl.setAttribute('aria-label', 'Ines AI ჩატის გახსნა');
      aiControl.innerHTML = '<i>✦</i><span>Ines AI</span>';
      oldClubControl.replaceWith(aiControl);
    }

    if (nav.dataset.stableNavigation !== '1') {
      nav.dataset.stableNavigation = '1';
      nav.addEventListener('click', (event) => {
        const control = event.target.closest('[data-mobile-key]');
        if (!control) return;

        const key = control.dataset.mobileKey;
        if (['home', 'groups', 'admission'].includes(key)) {
          event.preventDefault();
          event.stopImmediatePropagation();
          activatePublicPage(key);
          return;
        }

        if (key === 'ai') {
          event.preventDefault();
          event.stopImmediatePropagation();
          qs('.ines-ai-launcher')?.click();
        }
      }, true);
    }

    return true;
  }

  function scrubPublicDemoDetails() {
    const note = qs('#demoAuthNote');
    if (!note) return;

    const scrub = () => {
      if (note.textContent !== '') note.textContent = '';
      if (!note.hidden) note.hidden = true;
      note.setAttribute('aria-hidden', 'true');
    };

    scrub();
    new MutationObserver(scrub).observe(note, {
      attributes: true,
      childList: true,
      characterData: true,
      subtree: true,
    });
  }

  function coordinateOverlays() {
    const modal = qs('#loginModal');
    const panel = qs('[data-ines-ai-panel]');

    const sync = () => {
      const authOpen = Boolean(modal?.classList.contains('open'));
      const chatOpen = Boolean(panel?.classList.contains('open'));
      document.body.classList.toggle('mobile-auth-open', authOpen);
      document.body.classList.toggle('mobile-chat-open', chatOpen);
      qs('[data-mobile-key="ai"]')?.classList.toggle('active', chatOpen);

      if (authOpen) {
        qs('.modal-card', modal)?.scrollTo({ top: 0, behavior: 'auto' });
      }
    };

    if (modal) new MutationObserver(sync).observe(modal, { attributes: true, attributeFilter: ['class'] });
    if (panel) new MutationObserver(sync).observe(panel, { attributes: true, attributeFilter: ['class'] });
    sync();
  }

  function installRevealSafety() {
    const mobile = window.matchMedia('(max-width: 900px)');
    const reveal = () => {
      if (mobile.matches) showRevealContent(document);
      const activePage = qs('.public-page.active');
      if (activePage) showRevealContent(activePage);
    };

    reveal();
    mobile.addEventListener?.('change', reveal);

    new MutationObserver((records) => {
      if (!mobile.matches) return;
      records.forEach((record) => {
        record.addedNodes.forEach((node) => {
          if (!(node instanceof Element)) return;
          if (node.classList.contains('experience-reveal')) showRevealContent(node.parentElement || document);
          else showRevealContent(node);
        });
      });
    }).observe(document.body, { childList: true, subtree: true });
  }

  function init() {
    if (!document.body.classList.contains('final-site')) return;

    installDirectPageNavigation();
    installRevealSafety();
    scrubPublicDemoDetails();
    coordinateOverlays();

    if (!installPublicMobileNavigation()) {
      window.setTimeout(installPublicMobileNavigation, 100);
    }

    const initialPage = window.location.hash.replace('#', '') || 'home';
    activatePublicPage(initialPage, false);
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init, { once: true });
  } else {
    init();
  }
})();
