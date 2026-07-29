(() => {
  'use strict';

  const qs = (selector, root = document) => root.querySelector(selector);
  const qsa = (selector, root = document) => [...root.querySelectorAll(selector)];
  const body = document.body;

  const pulse = () => {
    if (navigator.vibrate && window.matchMedia('(pointer: coarse)').matches) navigator.vibrate(8);
  };

  function createProgress() {
    if (qs('.experience-progress')) return;
    const bar = document.createElement('div');
    bar.className = 'experience-progress';
    bar.innerHTML = '<i></i>';
    document.body.appendChild(bar);
    const fill = qs('i', bar);
    const update = () => {
      const scrollable = Math.max(1, document.documentElement.scrollHeight - window.innerHeight);
      fill.style.width = `${Math.min(100, (window.scrollY / scrollable) * 100)}%`;
    };
    update();
    window.addEventListener('scroll', update, { passive: true });
    window.addEventListener('resize', update, { passive: true });
  }

  function installReveal() {
    const targets = qsa('.offer-card,.method-card,.team-card,.gallery-card,.blog-card,.contact-grid article,.club-post,.event-card,.poll-card,.metric-card,.panel,.ops-panel,.cms-item-card');
    if (!targets.length) return;
    targets.forEach((element, index) => {
      element.classList.add('experience-reveal');
      element.style.transitionDelay = `${Math.min(index % 5, 4) * 45}ms`;
    });
    if (!('IntersectionObserver' in window)) {
      targets.forEach((element) => element.classList.add('is-visible'));
      return;
    }
    const observer = new IntersectionObserver((entries) => {
      entries.forEach((entry) => {
        if (!entry.isIntersecting) return;
        entry.target.classList.add('is-visible');
        observer.unobserve(entry.target);
      });
    }, { threshold: 0.08, rootMargin: '0px 0px -28px' });
    targets.forEach((element) => observer.observe(element));
  }

  function makeMobileNav(items, className = '') {
    const nav = document.createElement('nav');
    nav.className = `mobile-app-nav ${className}`.trim();
    nav.style.setProperty('--nav-count', String(items.length));
    nav.setAttribute('aria-label', 'მობილური სწრაფი ნავიგაცია');
    items.forEach((item) => {
      const control = item.href ? document.createElement('a') : document.createElement('button');
      if (item.href) control.href = item.href;
      else control.type = 'button';
      control.dataset.mobileKey = item.key;
      if (item.accent) control.classList.add('accent');
      control.innerHTML = `<i>${item.icon}</i><span>${item.label}</span>`;
      if (item.onClick) control.addEventListener('click', (event) => {
        event.preventDefault();
        pulse();
        item.onClick();
      });
      nav.appendChild(control);
    });
    document.body.appendChild(nav);
    return nav;
  }

  function setMobileActive(nav, key) {
    qsa('[data-mobile-key]', nav).forEach((item) => item.classList.toggle('active', item.dataset.mobileKey === key));
  }

  function installPublicExperience() {
    if (!body.classList.contains('final-site')) return;
    createProgress();

    const heroText = qs('.hero-copy p');
    if (heroText && !qs('.experience-trust-row')) {
      const trust = document.createElement('div');
      trust.className = 'experience-trust-row';
      trust.innerHTML = '<span>✓ ინდივიდუალური მიდგომა</span><span>🌱 ეკომეგობრული სივრცე</span><span>💬 მშობლებთან კავშირი</span>';
      heroText.insertAdjacentElement('afterend', trust);
    }

    const goToPage = (page) => {
      const existing = qs(`[data-page-target="${page}"]`);
      if (existing) existing.click();
      else window.location.hash = page;
    };
    const cabinet = qs('.site-actions .pill.navy');
    const loginButton = qs('[data-open-login]');
    const items = [
      { key: 'home', icon: '⌂', label: 'მთავარი', onClick: () => goToPage('home') },
      { key: 'groups', icon: '◫', label: 'ჯგუფები', onClick: () => goToPage('groups') },
      { key: 'admission', icon: '＋', label: 'ჩარიცხვა', accent: true, onClick: () => goToPage('admission') },
      cabinet?.tagName === 'A'
        ? { key: 'club', icon: '●', label: cabinet.textContent.trim() || 'კლუბი', href: cabinet.href }
        : { key: 'club', icon: '●', label: 'შესვლა', onClick: () => loginButton?.click() },
    ];
    const nav = makeMobileNav(items, 'public-mobile-nav');
    const update = () => setMobileActive(nav, window.location.hash.replace('#', '') || 'home');
    update();
    window.addEventListener('hashchange', update);
    document.addEventListener('click', (event) => {
      const target = event.target.closest('[data-page-target]');
      if (target) setTimeout(update, 0);
    });
  }

  function installParentExperience() {
    if (!body.classList.contains('club-body')) return;
    createProgress();
    const tabButton = (tab) => qs(`[data-club-tab="${tab}"]`);
    const nav = makeMobileNav([
      { key: 'feed', icon: '⌂', label: 'ლენტა', onClick: () => tabButton('feed')?.click() },
      { key: 'events', icon: '◷', label: 'ღონისძიებები', onClick: () => tabButton('events')?.click() },
      { key: 'forum', icon: '◌', label: 'ფორუმი', onClick: () => tabButton('forum')?.click() },
      { key: 'polls', icon: '▥', label: 'გამოკითხვა', onClick: () => tabButton('polls')?.click() },
      { key: 'profile', icon: '●', label: 'პროფილი', onClick: () => tabButton('profile')?.click() },
    ], 'parent-mobile-nav');
    setMobileActive(nav, 'feed');
    document.addEventListener('click', (event) => {
      const target = event.target.closest('[data-club-tab],[data-club-tab-link]');
      const key = target?.dataset.clubTab || target?.dataset.clubTabLink;
      if (key) setMobileActive(nav, key);
    });
  }

  function findAdminHref(routeFragment) {
    const link = qsa('.admin-nav-stack a').find((item) => item.href.includes(routeFragment));
    return link?.href || '#';
  }

  function openAdminSidebar() {
    qs('.admin-sidebar')?.classList.add('open');
    qs('.admin-mobile-overlay')?.classList.add('open');
    document.body.classList.add('body-lock');
  }

  function closeAdminSidebar() {
    qs('.admin-sidebar')?.classList.remove('open');
    qs('.admin-mobile-overlay')?.classList.remove('open');
    document.body.classList.remove('body-lock');
  }

  function installAdminQuickActions() {
    if (window.location.pathname !== '/admin' && window.location.pathname !== '/admin/') return;
    if (window.location.search) return;
    const workspace = qs('.admin-workspace');
    const title = qs('.admin-title-row', workspace);
    if (!workspace || !title || qs('.admin-command-grid')) return;
    const grid = document.createElement('section');
    grid.className = 'admin-command-grid';
    const actions = [
      ['✦', 'პლატფორმის მართვა', 'ტექსტები, ბლოგი, ფოტოები და კლუბი', findAdminHref('/admin/content'), 'rgba(169,211,201,.6)'],
      ['＋', 'ახალი განაცხადები', 'გახსენით ჩარიცხვების ვორონკა', findAdminHref('/admin/admissions'), 'rgba(239,230,169,.7)'],
      ['◫', 'ბავშვები და ჯგუფები', 'პროფილები, ადგილები და ჩარიცხვები', findAdminHref('/admin/children'), 'rgba(211,189,211,.62)'],
      ['₾', 'ფინანსები', 'დარიცხვები, გადახდები და დავალიანება', findAdminHref('/admin/payments'), 'rgba(239,196,154,.68)'],
    ];
    grid.innerHTML = actions.map(([icon, titleText, description, href, color]) => `<a class="admin-command-card" href="${href}" style="--command-color:${color}"><i>${icon}</i><strong>${titleText}</strong><small>${description}</small></a>`).join('');
    title.insertAdjacentElement('afterend', grid);
  }

  function installAdminNavigation() {
    if (!body.classList.contains('admin-body')) return;
    const sidebar = qs('.admin-sidebar');
    if (!sidebar) return;
    qs('[data-admin-sidebar-open]')?.addEventListener('click', () => { pulse(); openAdminSidebar(); });
    qs('[data-admin-sidebar-close]')?.addEventListener('click', closeAdminSidebar);
    qs('.admin-mobile-overlay')?.addEventListener('click', closeAdminSidebar);
    qsa('.admin-nav-stack a').forEach((link) => link.addEventListener('click', closeAdminSidebar));
    document.addEventListener('keydown', (event) => {
      if (event.key === 'Escape') closeAdminSidebar();
    });

    const search = qs('[data-admin-nav-search]');
    search?.addEventListener('input', () => {
      const term = search.value.trim().toLocaleLowerCase('ka');
      qsa('.admin-nav-stack a').forEach((link) => {
        link.hidden = term !== '' && !link.textContent.toLocaleLowerCase('ka').includes(term);
      });
      qsa('.admin-nav-group-label').forEach((label) => {
        let sibling = label.nextElementSibling;
        let hasVisible = false;
        while (sibling && !sibling.classList.contains('admin-nav-group-label')) {
          if (sibling.matches('a') && !sibling.hidden) hasVisible = true;
          sibling = sibling.nextElementSibling;
        }
        label.hidden = term !== '' && !hasVisible;
      });
    });

    const activePath = window.location.pathname;
    const nav = makeMobileNav([
      { key: 'overview', icon: '⌂', label: 'მთავარი', href: findAdminHref('/admin') },
      { key: 'content', icon: '✦', label: 'მართვა', href: findAdminHref('/admin/content') },
      { key: 'admissions', icon: '＋', label: 'ჩარიცხვა', href: findAdminHref('/admin/admissions') },
      { key: 'payments', icon: '₾', label: 'გადახდები', href: findAdminHref('/admin/payments') },
      { key: 'menu', icon: '☰', label: 'მენიუ', onClick: openAdminSidebar },
    ], 'admin-mobile-nav');
    let active = 'overview';
    if (activePath.includes('/content')) active = 'content';
    else if (activePath.includes('/admissions')) active = 'admissions';
    else if (activePath.includes('/payments')) active = 'payments';
    setMobileActive(nav, active);
    installAdminQuickActions();
  }

  function normalize(text) {
    return text.toLocaleLowerCase('ka').replace(/\s+/g, ' ').trim();
  }

  function installCmsWorkbench() {
    const intro = qs('.cms-intro');
    const navigation = qs('.cms-section-nav');
    if (!intro || !navigation || qs('.cms-workbench')) return;
    const toolbar = document.createElement('div');
    toolbar.className = 'cms-workbench';
    toolbar.innerHTML = '<label><span>⌕</span><input type="search" placeholder="მოძებნე ტექსტი, ბლოგი, ჯგუფი..." aria-label="CMS ძებნა"></label><button type="button" data-cms-expand>ყველას გახსნა</button><button type="button" data-cms-collapse>ყველას დახურვა</button>';
    navigation.insertAdjacentElement('beforebegin', toolbar);
    const empty = document.createElement('div');
    empty.className = 'cms-search-empty';
    empty.textContent = 'ამ სიტყვით კონტენტი ვერ მოიძებნა.';
    toolbar.insertAdjacentElement('afterend', empty);
    const input = qs('input', toolbar);
    const blocks = qsa('.cms-block');
    input.addEventListener('input', () => {
      const term = normalize(input.value);
      let visibleBlocks = 0;
      blocks.forEach((block) => {
        const cards = qsa('.cms-item-card', block);
        if (cards.length && term) {
          let visibleCards = 0;
          cards.forEach((card) => {
            const matches = normalize(card.textContent).includes(term);
            card.classList.toggle('is-filtered-out', !matches);
            if (matches) visibleCards += 1;
          });
          const blockMatches = normalize(qs('.cms-block-head', block)?.textContent || '').includes(term) || visibleCards > 0;
          block.classList.toggle('is-filtered-out', !blockMatches);
          if (blockMatches) visibleBlocks += 1;
        } else {
          const matches = !term || normalize(block.textContent).includes(term);
          block.classList.toggle('is-filtered-out', !matches);
          if (matches) visibleBlocks += 1;
        }
      });
      empty.classList.toggle('show', visibleBlocks === 0);
    });
    qs('[data-cms-expand]', toolbar).addEventListener('click', () => qsa('.cms-accordion,.cms-create-box').forEach((detail) => { detail.open = true; }));
    qs('[data-cms-collapse]', toolbar).addEventListener('click', () => qsa('.cms-accordion,.cms-create-box').forEach((detail) => { detail.open = false; }));

    const dock = document.createElement('div');
    dock.className = 'cms-unsaved-dock';
    dock.innerHTML = '<span>შენახული არ არის</span><button type="button">შენახვაზე გადასვლა</button>';
    document.body.appendChild(dock);
    const forms = qsa('.cms-text-form,.cms-item-form');
    const dirtyForms = () => forms.filter((form) => form.classList.contains('is-dirty'));
    const updateDock = () => {
      const count = dirtyForms().length;
      dock.classList.toggle('show', count > 0);
      qs('span', dock).textContent = count > 1 ? `${count} ფორმაში ცვლილებაა` : 'ცვლილება ჯერ არ არის შენახული';
    };
    forms.forEach((form) => {
      form.addEventListener('input', () => { form.classList.add('is-dirty'); updateDock(); }, { passive: true });
      form.addEventListener('change', () => { form.classList.add('is-dirty'); updateDock(); }, { passive: true });
      form.addEventListener('submit', () => { form.classList.remove('is-dirty'); updateDock(); });
    });
    qs('button', dock).addEventListener('click', () => {
      const form = dirtyForms()[0];
      if (!form) return;
      form.scrollIntoView({ behavior: 'smooth', block: 'center' });
      const submit = qs('button[type="submit"]', form);
      submit?.focus({ preventScroll: true });
    });
    document.addEventListener('keydown', (event) => {
      if (!(event.ctrlKey || event.metaKey) || event.key.toLowerCase() !== 's') return;
      const form = document.activeElement?.closest('form') || dirtyForms()[0];
      if (!form?.classList.contains('is-dirty')) return;
      event.preventDefault();
      form.requestSubmit();
    });
    qsa('.cms-danger').forEach((button) => button.addEventListener('click', (event) => {
      if (!window.confirm('ნამდვილად გსურთ ამ ჩანაწერის წაშლა?')) event.preventDefault();
    }));
    qsa('input[type="file"][accept*="image"]').forEach((inputFile) => inputFile.addEventListener('change', () => {
      const file = inputFile.files?.[0];
      qs('.experience-image-preview', inputFile.parentElement)?.remove();
      if (!file) return;
      const preview = document.createElement('img');
      preview.className = 'experience-image-preview';
      preview.alt = 'არჩეული სურათის preview';
      preview.src = URL.createObjectURL(file);
      inputFile.parentElement.appendChild(preview);
    }));
  }

  function installTapFeedback() {
    document.addEventListener('pointerdown', (event) => {
      const target = event.target.closest('button,.primary-button,.secondary-button,.offer-card,.admin-command-card,.mobile-app-nav a');
      if (!target || event.pointerType !== 'touch') return;
      target.animate([{ transform: 'scale(1)' }, { transform: 'scale(.975)' }, { transform: 'scale(1)' }], { duration: 170, easing: 'ease-out' });
    }, { passive: true });
  }

  function init() {
    document.documentElement.classList.add('experience-v2');
    installPublicExperience();
    installParentExperience();
    installAdminNavigation();
    installCmsWorkbench();
    installReveal();
    installTapFeedback();
  }

  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init, { once: true });
  else init();
})();
