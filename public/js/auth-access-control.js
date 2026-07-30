(() => {
  'use strict';

  const q = (selector, root = document) => root.querySelector(selector);
  const csrf = () => q('meta[name="csrf-token"]')?.content || '';
  let mode = { demo_enabled: false, admin_phone: '555411831' };
  let requestId = null;
  let loginName = '';
  let loginPhone = '';

  async function post(url, payload) {
    const response = await fetch(url, {
      method: 'POST',
      credentials: 'same-origin',
      headers: {
        'Content-Type': 'application/json',
        Accept: 'application/json',
        'X-CSRF-TOKEN': csrf(),
      },
      body: JSON.stringify(payload),
    });
    const data = await response.json().catch(() => ({}));
    if (!response.ok) {
      const error = new Error(data.errors ? Object.values(data.errors).flat()[0] : data.message || 'მოთხოვნა ვერ შესრულდა');
      error.status = response.status;
      error.errors = data.errors || {};
      throw error;
    }
    return data;
  }

  function showStatus(message, type = 'error') {
    const status = q('#loginStatus');
    if (!status) return;
    status.textContent = message;
    status.className = `form-status ${type} show`;
  }

  function privacyPayload(form) {
    return {
      privacy_accepted: Boolean(q('[name="privacy_accepted"]', form)?.checked),
      marketing_consent: Boolean(q('[name="marketing_consent"]', form)?.checked),
      privacy_policy_version: '2026-07-30',
    };
  }

  function handlePrivacyRequired(error) {
    if (!error.errors?.privacy_accepted) return false;
    window.inesPrivacy?.requireAccountConsent?.();
    showStatus('ეს ნომერი ჯერ რეგისტრირებული არ არის. ახალი ანგარიშის შესაქმნელად გაეცანით რეგისტრაციის პირობებს.', 'error');
    return true;
  }

  async function loadMode() {
    try {
      const response = await fetch(window.ines.routes.demoStatus, {
        credentials: 'same-origin',
        headers: { Accept: 'application/json' },
      });
      mode = await response.json();
      if (!mode.demo_enabled) return;
      const lead = q('#loginLead');
      const submit = q('#loginSubmit');
      const note = q('#demoAuthNote');
      if (lead) lead.textContent = 'შეიყვანეთ სახელი და ტელეფონის ნომერი. არსებული მომხმარებელი შევა ანგარიშში, ახალი ნომერი კი ჯერ რეგისტრაციას გაივლის.';
      if (submit) submit.textContent = 'შესვლა / რეგისტრაცია →';
      if (note) {
        note.hidden = false;
        note.textContent = `დემო ადმინისტრატორი: ${mode.admin_phone}. სხვა ნომერი ქმნის ჩვეულებრივ ანგარიშს; მშობელთა კლუბი მხოლოდ აქტიური ჩარიცხვის შემდეგ გაიხსნება.`;
      }
    } catch (_) {
      // Real OTP mode remains active.
    }
  }

  function bindRequestForm() {
    const form = q('#otpRequest');
    if (!form || form.dataset.accountAwareAuth === 'true') return;
    form.dataset.accountAwareAuth = 'true';

    form.addEventListener('submit', async (event) => {
      event.preventDefault();
      event.stopImmediatePropagation();
      const data = new FormData(form);
      loginName = String(data.get('name') || '');
      loginPhone = String(data.get('phone') || '');
      q('#loginStatus')?.setAttribute('class', 'form-status');

      try {
        const payload = {
          name: loginName,
          phone: loginPhone,
          ...privacyPayload(form),
        };

        if (mode.demo_enabled) {
          const result = await post(window.ines.routes.demoLogin, payload);
          showStatus(
            result.user.parent_club_access
              ? 'დადასტურებული მშობლის კაბინეტი იხსნება.'
              : 'ანგარიშის სტატუსის გვერდი იხსნება.',
            'ok',
          );
          setTimeout(() => { window.location.href = result.redirect_to || '/account'; }, 350);
          return;
        }

        const result = await post(window.ines.routes.request, payload);
        requestId = result.request_id;
        q('#loginStepOne').hidden = true;
        q('#loginStepTwo').hidden = false;
        q('#otpPhone').textContent = loginPhone;
        if (result.debug_code) {
          const debug = q('#debugCode');
          debug.hidden = false;
          debug.textContent = `სადემონსტრაციო კოდი: ${result.debug_code}`;
        }
      } catch (error) {
        if (!handlePrivacyRequired(error)) showStatus(error.message);
      }
    }, true);
  }

  function bindVerifyForm() {
    const form = q('#otpVerify');
    if (!form || form.dataset.accountAwareAuth === 'true') return;
    form.dataset.accountAwareAuth = 'true';

    form.addEventListener('submit', async (event) => {
      event.preventDefault();
      event.stopImmediatePropagation();
      const code = String(new FormData(form).get('code') || '');
      try {
        const result = await post(window.ines.routes.verify, {
          request_id: requestId,
          name: loginName,
          phone: loginPhone,
          code,
        });
        showStatus('შესვლა წარმატებულია.', 'ok');
        setTimeout(() => { window.location.href = result.redirect_to || '/account'; }, 350);
      } catch (error) {
        showStatus(error.message);
      }
    }, true);
  }

  function install() {
    bindRequestForm();
    bindVerifyForm();
    loadMode();
  }

  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', install);
  else install();
})();
