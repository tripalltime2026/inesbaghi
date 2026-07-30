(() => {
  const root = document.querySelector('[data-ines-ai-widget]');
  if (!root) return;

  const launcher = root.querySelector('[data-ines-ai-open]');
  const panel = root.querySelector('[data-ines-ai-panel]');
  const close = root.querySelector('[data-ines-ai-close]');
  const messages = root.querySelector('[data-ines-ai-messages]');
  const quick = root.querySelector('[data-ines-ai-quick]');
  const form = root.querySelector('[data-ines-ai-form]');
  const input = root.querySelector('[data-ines-ai-input]');
  const typing = root.querySelector('[data-ines-ai-typing]');
  const stateLabel = root.querySelector('[data-ines-ai-state]');
  const errorBox = root.querySelector('[data-ines-ai-error]');
  const humanButton = root.querySelector('[data-ines-ai-human]');
  const contact = root.querySelector('[data-ines-ai-contact]');
  const contactForm = root.querySelector('[data-ines-ai-contact-form]');
  const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
  const storageKey = 'ines_support_token';
  let token = localStorage.getItem(storageKey) || '';
  let conversation = null;
  let loading = false;
  let pollTimer = null;

  function endpoint(path = '') {
    return `/support/chat${path}`;
  }

  async function request(url, options = {}) {
    const response = await fetch(url, {
      credentials: 'same-origin',
      headers: {
        Accept: 'application/json',
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': csrf,
        ...(options.headers || {}),
      },
      ...options,
    });
    const data = await response.json().catch(() => ({}));
    if (!response.ok) {
      const validation = data.errors ? Object.values(data.errors).flat().join(' ') : '';
      throw new Error(validation || data.message || 'დროებითი შეცდომა დაფიქსირდა.');
    }
    return data;
  }

  function escapeHtml(value) {
    const node = document.createElement('div');
    node.textContent = value ?? '';
    return node.innerHTML;
  }

  function formatTime(value) {
    try {
      return new Intl.DateTimeFormat('ka-GE', { hour: '2-digit', minute: '2-digit' }).format(new Date(value));
    } catch (_) {
      return '';
    }
  }

  function showError(message = '') {
    errorBox.textContent = message;
    errorBox.classList.toggle('show', Boolean(message));
  }

  function setLoading(value) {
    loading = value;
    input.disabled = value;
    form.querySelector('button[type="submit"]').disabled = value;
    typing.classList.toggle('show', value);
  }

  function render(payload) {
    if (!payload) return;
    conversation = payload;
    stateLabel.textContent = payload.status_label || 'Ines AI მზადაა';
    messages.innerHTML = (payload.messages || []).map((message) => `
      <article class="ines-ai-message ${escapeHtml(message.sender_type)}">
        <span class="ines-ai-message-label">${escapeHtml(message.sender_label)}</span>
        ${escapeHtml(message.body)}
        <time>${escapeHtml(formatTime(message.created_at))}</time>
      </article>
    `).join('');
    messages.scrollTop = messages.scrollHeight;
    const needsContact = payload.status === 'waiting_admin' && !(payload.contact?.phone);
    contact.classList.toggle('show', needsContact);
  }

  function renderQuickActions(actions = []) {
    quick.innerHTML = actions.map((label) => `<button type="button" data-quick-message="${escapeHtml(label)}">${escapeHtml(label)}</button>`).join('');
    quick.querySelectorAll('[data-quick-message]').forEach((button) => {
      button.addEventListener('click', () => {
        const text = button.dataset.quickMessage || '';
        if (text.includes('ადმინისტრატორთან')) requestHuman();
        else sendMessage(text);
      });
    });
  }

  async function createConversation() {
    const data = await request(endpoint('/conversations'), {
      method: 'POST',
      body: JSON.stringify({}),
    });
    token = data.conversation.token;
    localStorage.setItem(storageKey, token);
    renderQuickActions(data.quick_actions || []);
    render(data.conversation);
  }

  async function bootstrap() {
    if (loading) return;
    setLoading(true);
    showError('');
    try {
      const query = token ? `?token=${encodeURIComponent(token)}` : '';
      const data = await request(endpoint(`/bootstrap${query}`), { method: 'GET', body: undefined });
      renderQuickActions(data.quick_actions || []);
      if (data.conversation) render(data.conversation);
      else await createConversation();
    } catch (error) {
      if (token) {
        token = '';
        localStorage.removeItem(storageKey);
        try {
          await createConversation();
          return;
        } catch (secondError) {
          showError(secondError.message);
        }
      } else {
        showError(error.message);
      }
    } finally {
      setLoading(false);
    }
  }

  async function refresh() {
    if (!token || loading || !panel.classList.contains('open')) return;
    try {
      const data = await request(endpoint(`/conversations/${encodeURIComponent(token)}`), { method: 'GET', body: undefined });
      render(data.conversation);
    } catch (_) {
      // Polling failures stay silent; the next user action shows an actionable error.
    }
  }

  async function sendMessage(text) {
    const body = (text || '').trim();
    if (!body || loading) return;
    if (!token) await createConversation();
    setLoading(true);
    showError('');
    input.value = '';
    try {
      const data = await request(endpoint(`/conversations/${encodeURIComponent(token)}/messages`), {
        method: 'POST',
        body: JSON.stringify({ body }),
      });
      render(data.conversation);
    } catch (error) {
      showError(error.message);
      input.value = body;
    } finally {
      setLoading(false);
      input.focus();
    }
  }

  async function requestHuman() {
    if (loading) return;
    if (!token) await createConversation();
    setLoading(true);
    showError('');
    try {
      const data = await request(endpoint(`/conversations/${encodeURIComponent(token)}/human`), {
        method: 'POST',
        body: JSON.stringify({}),
      });
      render(data.conversation);
    } catch (error) {
      showError(error.message);
    } finally {
      setLoading(false);
    }
  }

  launcher.addEventListener('click', async () => {
    panel.classList.add('open');
    panel.setAttribute('aria-hidden', 'false');
    launcher.setAttribute('aria-expanded', 'true');
    if (!conversation) await bootstrap();
    input.focus();
    if (!pollTimer) pollTimer = window.setInterval(refresh, 7000);
  });

  close.addEventListener('click', () => {
    panel.classList.remove('open');
    panel.setAttribute('aria-hidden', 'true');
    launcher.setAttribute('aria-expanded', 'false');
  });

  form.addEventListener('submit', (event) => {
    event.preventDefault();
    sendMessage(input.value);
  });

  input.addEventListener('keydown', (event) => {
    if (event.key === 'Enter' && !event.shiftKey) {
      event.preventDefault();
      sendMessage(input.value);
    }
  });

  humanButton.addEventListener('click', requestHuman);

  contactForm.addEventListener('submit', async (event) => {
    event.preventDefault();
    if (!token || loading) return;
    const data = new FormData(contactForm);
    setLoading(true);
    showError('');
    try {
      const response = await request(endpoint(`/conversations/${encodeURIComponent(token)}/contact`), {
        method: 'PATCH',
        body: JSON.stringify({ name: data.get('name'), phone: data.get('phone') }),
      });
      render(response.conversation);
    } catch (error) {
      showError(error.message);
    } finally {
      setLoading(false);
    }
  });
})();
