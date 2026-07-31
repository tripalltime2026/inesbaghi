(() => {
  'use strict';

  function replaceLoginControl(control) {
    if (!control || control.tagName === 'A') {
      if (control) {
        control.href = '/shesvla';
        control.removeAttribute('data-open-login');
      }
      return;
    }

    const link = document.createElement('a');
    [...control.attributes].forEach((attribute) => {
      if (attribute.name !== 'type' && attribute.name !== 'data-open-login') {
        link.setAttribute(attribute.name, attribute.value);
      }
    });
    link.href = '/shesvla';
    link.innerHTML = control.innerHTML;
    control.replaceWith(link);
  }

  function install() {
    document.querySelectorAll('[data-open-login]').forEach(replaceLoginControl);
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', install, { once: true });
  } else {
    install();
  }
})();
