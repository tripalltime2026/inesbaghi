(() => {
  'use strict';

  function cleanAdminMobileNavigation() {
    const nav = document.querySelector('.admin-mobile-nav');
    if (!nav) return;
    nav.querySelectorAll('a[href="#"],a:not([href])').forEach((link) => link.remove());
    const items = nav.querySelectorAll('a,button');
    nav.style.setProperty('--nav-count', String(Math.max(1, items.length)));
    if (!nav.querySelector('.active') && items[0]) items[0].classList.add('active');
  }

  function simplifyCmsStickyNavigation() {
    const navigation = document.querySelector('.cms-section-nav');
    if (!navigation) return;
    navigation.style.position = 'relative';
    navigation.style.top = 'auto';
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
      cleanAdminMobileNavigation();
      simplifyCmsStickyNavigation();
    });
  } else {
    cleanAdminMobileNavigation();
    simplifyCmsStickyNavigation();
  }
})();
