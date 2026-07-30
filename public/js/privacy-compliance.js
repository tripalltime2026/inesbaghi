(() => {
  'use strict';

  const POLICY_VERSION = '2026-07-30';
  const q = (selector, root = document) => root.querySelector(selector);

  function consentRow(name, text, options = {}) {
    const label = document.createElement('label');
    label.className = `legal-consent-box ${options.required ? 'required' : 'optional'}`;
    label.innerHTML = `<input type="checkbox" name="${name}" value="1" ${options.required ? 'required' : ''}><span>${text}</span>`;
    return label;
  }

  function consentStack() {
    const stack = document.createElement('div');
    stack.className = 'legal-consent-stack';
    return stack;
  }

  function installAdmissionConsent() {
    const form = q('#registrationForm');
    if (!form) return;

    let stack = q('[data-admission-privacy]', form);
    if (!stack) {
      stack = consentStack();
      stack.dataset.admissionPrivacy = 'true';
      stack.append(
        consentRow('guardian_authority_confirmed', 'ვადასტურებ, რომ ვარ ბავშვის მშობელი ან სხვა კანონიერი წარმომადგენელი და უფლებამოსილი ვარ ბავშვის მონაცემების მიწოდებაზე.', { required: true }),
        consentRow('privacy_accepted', 'გავეცანი <a href="/privacy" target="_blank" rel="noopener">კონფიდენციალურობის პოლიტიკას</a> და <a href="/terms" target="_blank" rel="noopener">სარგებლობის პირობებს</a>. ვადასტურებ განაცხადის განხილვისთვის აუცილებელი ჩემი და ბავშვის მონაცემების დამუშავებას.', { required: true }),
        consentRow('special_category_consent', 'ვაძლევ წერილობით ელექტრონულ თანხმობას ჩემ მიერ ნებაყოფლობით მითითებული ბავშვის ჯანმრთელობის ან სხვა განსაკუთრებული კატეგორიის მონაცემების დამუშავებაზე ბავშვის უსაფრთხოებისა და ინდივიდუალური საჭიროებების გათვალისწინებისთვის.', { required: true }),
        consentRow('marketing_consent', 'მსურს მივიღო ბაღის სიახლეები, ღონისძიებების ინფორმაცია და შეთავაზებები მითითებულ ნომერზე.'),
      );
      const note = document.createElement('p');
      note.className = 'legal-consent-summary';
      note.textContent = 'სარეკლამო შეტყობინებების მიღება არჩევითია. ფოტო/ვიდეომასალის გამოყენება საჭიროებს ცალკე თანხმობას და ჩარიცხვის პირობა არ არის.';
      stack.appendChild(note);
      const error = document.createElement('p');
      error.className = 'legal-field-error';
      error.textContent = 'გასაგრძელებლად მონიშნეთ ყველა სავალდებულო დადასტურება.';
      stack.appendChild(error);
      const formNote = q('.form-note', form);
      (formNote || q('#registrationStatus', form))?.insertAdjacentElement('beforebegin', stack);
    }

    enforceRequiredConsent(
      form,
      ['guardian_authority_confirmed', 'privacy_accepted', 'special_category_consent'],
      q('.legal-field-error', stack),
    );
  }

  function installAccountConsent() {
    const form = q('#otpRequest');
    if (!form) return;

    let stack = q('[data-account-privacy]', form);
    let toggle = q('[data-account-registration-toggle]', form);

    if (!stack) {
      toggle = document.createElement('button');
      toggle.type = 'button';
      toggle.className = 'registration-consent-toggle';
      toggle.dataset.accountRegistrationToggle = 'true';
      toggle.textContent = 'ახალი მომხმარებელი ხართ? რეგისტრაციის პირობები';

      stack = consentStack();
      stack.classList.add('account-registration-consent');
      stack.dataset.accountPrivacy = 'true';
      stack.hidden = true;
      stack.append(
        consentRow('privacy_accepted', 'გავეცანი <a href="/privacy" target="_blank" rel="noopener">კონფიდენციალურობის პოლიტიკას</a> და <a href="/terms" target="_blank" rel="noopener">სარგებლობის პირობებს</a>. ვადასტურებ ანგარიშის შექმნისა და მომსახურებისთვის აუცილებელი მონაცემების დამუშავებას.'),
        consentRow('marketing_consent', 'მსურს მივიღო ბაღის სიახლეები და ღონისძიებების ინფორმაცია მითითებულ ნომერზე. ეს არჩევითია და მოგვიანებით ანგარიშიდან შეიცვლება.'),
      );
      const note = document.createElement('p');
      note.className = 'legal-consent-summary';
      note.innerHTML = '<strong>ეს ნაწილი საჭიროა მხოლოდ ახალი ანგარიშის შექმნისას.</strong> უკვე რეგისტრირებულ მომხმარებელს ხელახალი თანხმობა არ მოეთხოვება.';
      stack.prepend(note);
      const error = document.createElement('p');
      error.className = 'legal-field-error';
      error.textContent = 'ახალი ანგარიშის შესაქმნელად დაადასტურეთ კონფიდენციალურობის პირობები.';
      stack.appendChild(error);

      q('#demoAuthNote', form)?.insertAdjacentElement('beforebegin', stack);
      stack.insertAdjacentElement('beforebegin', toggle);
    }

    const privacyInput = q('[name="privacy_accepted"]', stack);
    privacyInput?.removeAttribute('required');
    stack.hidden = true;
    stack.dataset.registrationRequired = 'false';

    const setRegistrationRequired = (required, focus = false) => {
      stack.hidden = !required;
      stack.dataset.registrationRequired = required ? 'true' : 'false';
      if (privacyInput) privacyInput.required = required;
      toggle?.classList.toggle('active', required);
      if (toggle) toggle.textContent = required ? 'რეგისტრაციის პირობების დამალვა' : 'ახალი მომხმარებელი ხართ? რეგისტრაციის პირობები';
      if (required && focus) {
        stack.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        privacyInput?.focus({ preventScroll: true });
      }
    };

    toggle?.addEventListener('click', () => setRegistrationRequired(stack.hidden, false));
    form.addEventListener('submit', (event) => {
      if (stack.dataset.registrationRequired !== 'true' || privacyInput?.checked) return;
      event.preventDefault();
      event.stopImmediatePropagation();
      q('.legal-field-error', stack)?.classList.add('show');
      privacyInput?.focus();
    }, true);
    privacyInput?.addEventListener('change', () => q('.legal-field-error', stack)?.classList.remove('show'));

    window.inesPrivacy = {
      ...(window.inesPrivacy || {}),
      requireAccountConsent: () => setRegistrationRequired(true, true),
      hideAccountConsent: () => setRegistrationRequired(false, false),
    };
  }

  function enforceRequiredConsent(form, names, error) {
    if (!error || form.dataset.privacyValidationBound === 'true') return;
    form.dataset.privacyValidationBound = 'true';
    form.addEventListener('submit', (event) => {
      const missingName = names.find((name) => !q(`[name="${name}"]`, form)?.checked);
      error.classList.toggle('show', Boolean(missingName));
      if (!missingName) return;
      event.preventDefault();
      event.stopImmediatePropagation();
      q(`[name="${missingName}"]`, form)?.focus();
    }, true);
    names.forEach((name) => q(`[name="${name}"]`, form)?.addEventListener('change', () => error.classList.remove('show')));
  }

  function checked(form, name) {
    return Boolean(q(`[name="${name}"]`, form)?.checked);
  }

  function installFetchConsentBridge() {
    if (window.__inesPrivacyFetchInstalled) return;
    window.__inesPrivacyFetchInstalled = true;
    const originalFetch = window.fetch.bind(window);

    window.fetch = (input, init = {}) => {
      const url = typeof input === 'string' ? input : input?.url || '';
      const contentType = new Headers(init.headers || {}).get('Content-Type') || '';
      if (!init.body || !contentType.includes('application/json')) return originalFetch(input, init);

      let payload;
      try { payload = JSON.parse(init.body); } catch (_) { return originalFetch(input, init); }

      if (url.includes('/admissions')) {
        const form = q('#registrationForm');
        if (form) {
          payload.privacy_accepted = checked(form, 'privacy_accepted');
          payload.guardian_authority_confirmed = checked(form, 'guardian_authority_confirmed');
          payload.special_category_consent = checked(form, 'special_category_consent');
          payload.marketing_consent = checked(form, 'marketing_consent');
          payload.privacy_policy_version = POLICY_VERSION;
        }
      }

      if (url.includes('/auth/demo/login') || url.includes('/auth/phone/request')) {
        const form = q('#otpRequest');
        if (form) {
          payload.privacy_accepted = checked(form, 'privacy_accepted');
          payload.marketing_consent = checked(form, 'marketing_consent');
          payload.privacy_policy_version = POLICY_VERSION;
        }
      }

      return originalFetch(input, { ...init, body: JSON.stringify(payload) });
    };
  }

  function installFooterLinks() {
    const siteFooter = q('.site-footer');
    const footerRow = q('.footer-row', siteFooter || document);
    if (!siteFooter || !footerRow || q('.legal-footer-links', siteFooter)) return;
    const links = document.createElement('nav');
    links.className = 'legal-footer-links';
    links.setAttribute('aria-label', 'სამართლებრივი ინფორმაცია');
    links.innerHTML = '<a href="/privacy">კონფიდენციალურობა</a><a href="/terms">სარგებლობის პირობები</a><a href="/privacy/request">მონაცემთა მოთხოვნა</a><span>შპს ინეს ბაღი · ს/კ 445602465</span>';
    footerRow.insertAdjacentElement('afterend', links);
  }

  function installCookieNotice() {
    if (!document.body.classList.contains('final-site') || localStorage.getItem('ines-cookie-info') === 'accepted') return;
    const banner = document.createElement('aside');
    banner.className = 'cookie-info-banner';
    banner.setAttribute('role', 'status');
    banner.innerHTML = '<p>ვებგვერდი იყენებს მხოლოდ ფუნქციურად აუცილებელ cookies-ს ავტორიზაციის, უსაფრთხოებისა და ფორმების მუშაობისთვის. <a href="/privacy#cookies">დეტალურად</a></p><button type="button">გასაგებია</button>';
    q('button', banner).addEventListener('click', () => {
      localStorage.setItem('ines-cookie-info', 'accepted');
      banner.remove();
    });
    document.body.appendChild(banner);
  }

  function install() {
    installFetchConsentBridge();
    installAdmissionConsent();
    installAccountConsent();
    installFooterLinks();
    installCookieNotice();
  }

  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', install);
  else install();
})();
