(() => {
  'use strict';

  const qs = (selector, root = document) => root.querySelector(selector);

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

  function installPublicMobileNavigation() {
    const nav = qs('.public-mobile-nav');
    if (!nav) return;

    if (document.body.classList.contains('seo-page-body')) {
      replaceWithLink(qs('[data-mobile-key="home"]', nav), '/');
      replaceWithLink(qs('[data-mobile-key="groups"]', nav), '/jgufebi');
      replaceWithLink(qs('[data-mobile-key="admission"]', nav), '/charetskhva');
    }

    const oldClubControl = qs('[data-mobile-key="club"]', nav);
    if (!oldClubControl || qs('[data-mobile-key="ai"]', nav)) return;

    const aiControl = document.createElement('button');
    aiControl.type = 'button';
    aiControl.dataset.mobileKey = 'ai';
    aiControl.setAttribute('aria-label', 'Ines AI ჩატის გახსნა');
    aiControl.innerHTML = '<i>✦</i><span>Ines AI</span>';
    aiControl.addEventListener('click', () => {
      qs('.ines-ai-launcher')?.click();
    });
    oldClubControl.replaceWith(aiControl);
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
    const aiNavControl = qs('[data-mobile-key="ai"]');

    const sync = () => {
      const authOpen = Boolean(modal?.classList.contains('open'));
      const chatOpen = Boolean(panel?.classList.contains('open'));
      document.body.classList.toggle('mobile-auth-open', authOpen);
      document.body.classList.toggle('mobile-chat-open', chatOpen);
      aiNavControl?.classList.toggle('active', chatOpen);

      if (authOpen) {
        qs('.modal-card', modal)?.scrollTo({ top: 0, behavior: 'instant' });
      }
    };

    if (modal) new MutationObserver(sync).observe(modal, { attributes: true, attributeFilter: ['class'] });
    if (panel) new MutationObserver(sync).observe(panel, { attributes: true, attributeFilter: ['class'] });
    sync();
  }

  function init() {
    if (!document.body.classList.contains('final-site')) return;
    installPublicMobileNavigation();
    scrubPublicDemoDetails();
    coordinateOverlays();
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init, { once: true });
  } else {
    init();
  }
})();
