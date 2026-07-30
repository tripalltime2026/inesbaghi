(() => {
  'use strict';

  const panel = document.querySelector('[data-club-panel="forum"]');
  const legacyLayout = panel?.querySelector('.forum-layout');
  if (!panel || !legacyLayout) return;

  const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
  const state = {
    groups: [],
    categories: {},
    topics: [],
    group: 'all',
    category: 'all',
    expanded: new Set(),
  };

  const escapeHtml = (value = '') => String(value)
    .replaceAll('&', '&amp;')
    .replaceAll('<', '&lt;')
    .replaceAll('>', '&gt;')
    .replaceAll('"', '&quot;')
    .replaceAll("'", '&#039;');

  const withBreaks = (value = '') => escapeHtml(value).replace(/\n/g, '<br>');

  const app = document.createElement('div');
  app.className = 'group-forum-app';
  app.innerHTML = '<div class="group-forum-loading"><i></i><strong>ფორუმი იტვირთება...</strong></div>';
  legacyLayout.replaceWith(app);

  async function request(url, options = {}) {
    const response = await fetch(url, {
      credentials: 'same-origin',
      ...options,
      headers: {
        Accept: 'application/json',
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': csrf,
        'X-Requested-With': 'XMLHttpRequest',
        ...(options.headers || {}),
      },
    });

    const payload = await response.json().catch(() => ({}));
    if (!response.ok) {
      const errors = payload.errors ? Object.values(payload.errors).flat().join(' ') : '';
      throw new Error(errors || payload.message || 'მოქმედება ვერ შესრულდა.');
    }
    return payload;
  }

  function filteredTopics() {
    return state.topics.filter((topic) => {
      const groupMatch = state.group === 'all' || String(topic.group_id) === state.group;
      const categoryMatch = state.category === 'all' || topic.category === state.category;
      return groupMatch && categoryMatch;
    });
  }

  function renderFilters() {
    const groupButtons = [
      '<button type="button" class="active" data-forum-group="all">ყველა ჩემი ჯგუფი</button>',
      ...state.groups.map((group) => `<button type="button" data-forum-group="${group.id}">${escapeHtml(group.name)}</button>`),
    ].join('');

    const categoryButtons = [
      '<button type="button" class="active" data-forum-category="all">ყველა თემა</button>',
      ...Object.entries(state.categories).map(([key, label]) => `<button type="button" data-forum-category="${escapeHtml(key)}">${escapeHtml(label)}</button>`),
    ].join('');

    return `
      <div class="group-forum-filters">
        <div><small>ბავშვის ჯგუფი</small><div class="group-forum-filter-row">${groupButtons}</div></div>
        <div><small>კატეგორია</small><div class="group-forum-filter-row compact">${categoryButtons}</div></div>
      </div>`;
  }

  function renderCreateForm() {
    if (!state.groups.length) {
      return `
        <div class="group-forum-no-access">
          <span>🌱</span>
          <div><strong>ფორუმის ჯგუფი ჯერ არ არის ხელმისაწვდომი</strong><p>თემის შესაქმნელად ბავშვს აქტიური ჩარიცხვა უნდა ჰქონდეს კონკრეტულ ასაკობრივ ჯგუფში.</p></div>
        </div>`;
    }

    const groupOptions = state.groups.map((group) => `<option value="${group.id}">${escapeHtml(group.name)}</option>`).join('');
    const categoryOptions = Object.entries(state.categories).map(([key, label]) => `<option value="${escapeHtml(key)}">${escapeHtml(label)}</option>`).join('');

    return `
      <form class="group-forum-create" data-forum-create hidden>
        <div class="group-forum-create-head"><div><small>ახალი დისკუსია</small><strong>შექმენი თემა შენი ბავშვის ჯგუფისთვის</strong></div><button type="button" data-forum-create-close aria-label="დახურვა">×</button></div>
        <div class="group-forum-form-grid">
          <label><span>ვის გამოუჩნდეს</span><select name="kindergarten_group_id" required>${groupOptions}</select><small>თემას მხოლოდ ამ ჯგუფის მშობლები ნახავენ.</small></label>
          <label><span>კატეგორია</span><select name="category" required>${categoryOptions}</select></label>
          <label class="wide"><span>თემის სათაური</span><input name="title" minlength="4" maxlength="180" required placeholder="მაგალითად: ბავშვების შეხვედრა შაბათს"></label>
          <label class="wide"><span>რას გინდა გაუზიარო სხვა მშობლებს?</span><textarea name="body" minlength="5" maxlength="5000" required rows="5" placeholder="დაწერე დეტალურად..."></textarea></label>
        </div>
        <div class="group-forum-form-actions"><span data-forum-create-status></span><button type="submit">თემის გამოქვეყნება</button></div>
      </form>`;
  }

  function renderComments(topic) {
    const comments = topic.comments.length
      ? topic.comments.map((comment) => `
          <article class="group-forum-comment">
            <i>${escapeHtml(comment.author.charAt(0) || 'მ')}</i>
            <div><div><strong>${escapeHtml(comment.author)}</strong><time>${escapeHtml(comment.created_at || '')}</time></div><p>${withBreaks(comment.body)}</p></div>
          </article>`).join('')
      : '<p class="group-forum-no-comments">კომენტარი ჯერ არ არის — პირველი პასუხი შენ დაწერე.</p>';

    const form = topic.is_locked
      ? '<div class="group-forum-locked">🔒 კომენტარები დახურულია.</div>'
      : `
          <form class="group-forum-comment-form" data-comment-form="${topic.id}">
            <textarea name="body" minlength="2" maxlength="2000" required rows="2" placeholder="დაწერე კომენტარი..."></textarea>
            <div><span data-comment-status></span><button type="submit">გაგზავნა</button></div>
          </form>`;

    return `<div class="group-forum-comments" ${state.expanded.has(topic.id) ? '' : 'hidden'}>${comments}${form}</div>`;
  }

  function renderTopic(topic) {
    return `
      <article class="group-forum-topic" id="forum-topic-${topic.id}">
        <div class="group-forum-topic-meta">
          <span class="group-badge">${escapeHtml(topic.group_name || 'ჯგუფი')}</span>
          <span>${escapeHtml(topic.category_label)}</span>
          <time>${escapeHtml(topic.created_at || '')}</time>
        </div>
        <div class="group-forum-author"><i>${escapeHtml(topic.author.charAt(0) || 'მ')}</i><span><strong>${escapeHtml(topic.author)}</strong><small>მშობელი · ${escapeHtml(topic.group_name || '')}</small></span></div>
        <h3>${escapeHtml(topic.title)}</h3>
        <p>${withBreaks(topic.body)}</p>
        <button class="group-forum-comment-toggle" type="button" data-topic-toggle="${topic.id}"><span>💬 ${topic.comments_count} კომენტარი</span><strong>${state.expanded.has(topic.id) ? 'დახურვა ↑' : 'ნახვა და პასუხი →'}</strong></button>
        ${renderComments(topic)}
      </article>`;
  }

  function syncActiveFilters() {
    app.querySelectorAll('[data-forum-group]').forEach((button) => button.classList.toggle('active', button.dataset.forumGroup === state.group));
    app.querySelectorAll('[data-forum-category]').forEach((button) => button.classList.toggle('active', button.dataset.forumCategory === state.category));
  }

  function renderTopicList() {
    const container = app.querySelector('[data-forum-topics]');
    if (!container) return;
    const topics = filteredTopics();
    container.innerHTML = topics.length
      ? topics.map(renderTopic).join('')
      : '<div class="group-forum-empty"><span>💬</span><strong>ამ ჯგუფში თემა ჯერ არ არის</strong><p>შექმენი პირველი დისკუსია და გაიცანი სხვა მშობლები.</p></div>';
  }

  function render() {
    app.innerHTML = `
      <section class="group-forum-hero">
        <div><span>დაცული ჯგუფური სივრცე</span><h2>ისაუბრე შენი ბავშვის ჯგუფის მშობლებთან</h2><p>თითოეული თემა ჩანს მხოლოდ იმ მშობლებისთვის, რომელთა ბავშვებიც იმავე ასაკობრივ ჯგუფში არიან.</p></div>
        ${state.groups.length ? '<button type="button" data-forum-create-open>＋ ახალი თემა</button>' : ''}
      </section>
      ${renderCreateForm()}
      ${renderFilters()}
      <section class="group-forum-list" data-forum-topics></section>`;
    renderTopicList();
    syncActiveFilters();
  }

  async function loadForum({ preserveMessage = '' } = {}) {
    try {
      const payload = await request('/parent/forum/data', { method: 'GET', headers: { 'Content-Type': 'application/json' } });
      state.groups = payload.groups || [];
      state.categories = payload.categories || {};
      state.topics = payload.topics || [];
      render();
      if (preserveMessage) showToast(preserveMessage);
    } catch (error) {
      app.innerHTML = `<div class="group-forum-error"><strong>ფორუმი ვერ ჩაიტვირთა</strong><p>${escapeHtml(error.message)}</p><button type="button" data-forum-retry>თავიდან ცდა</button></div>`;
    }
  }

  function showToast(message) {
    let toast = document.querySelector('.group-forum-toast');
    if (!toast) {
      toast = document.createElement('div');
      toast.className = 'group-forum-toast';
      document.body.appendChild(toast);
    }
    toast.textContent = message;
    toast.classList.add('show');
    window.setTimeout(() => toast.classList.remove('show'), 3200);
  }

  app.addEventListener('click', (event) => {
    const group = event.target.closest('[data-forum-group]');
    if (group) {
      state.group = group.dataset.forumGroup;
      renderTopicList();
      syncActiveFilters();
      return;
    }

    const category = event.target.closest('[data-forum-category]');
    if (category) {
      state.category = category.dataset.forumCategory;
      renderTopicList();
      syncActiveFilters();
      return;
    }

    if (event.target.closest('[data-forum-create-open]')) {
      const form = app.querySelector('[data-forum-create]');
      form.hidden = false;
      form.scrollIntoView({ behavior: 'smooth', block: 'center' });
      form.querySelector('input')?.focus();
      return;
    }

    if (event.target.closest('[data-forum-create-close]')) {
      app.querySelector('[data-forum-create]').hidden = true;
      return;
    }

    const toggle = event.target.closest('[data-topic-toggle]');
    if (toggle) {
      const id = Number(toggle.dataset.topicToggle);
      state.expanded.has(id) ? state.expanded.delete(id) : state.expanded.add(id);
      renderTopicList();
      document.getElementById(`forum-topic-${id}`)?.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
      return;
    }

    if (event.target.closest('[data-forum-retry]')) loadForum();
  });

  app.addEventListener('submit', async (event) => {
    const createForm = event.target.closest('[data-forum-create]');
    if (createForm) {
      event.preventDefault();
      const status = createForm.querySelector('[data-forum-create-status]');
      const button = createForm.querySelector('button[type="submit"]');
      const data = Object.fromEntries(new FormData(createForm));
      status.textContent = 'ინახება...';
      button.disabled = true;
      try {
        const payload = await request('/parent/forum/topics', { method: 'POST', body: JSON.stringify(data) });
        createForm.reset();
        state.expanded.add(Number(payload.topic_id));
        await loadForum({ preserveMessage: payload.message });
      } catch (error) {
        status.textContent = error.message;
      } finally {
        button.disabled = false;
      }
      return;
    }

    const commentForm = event.target.closest('[data-comment-form]');
    if (commentForm) {
      event.preventDefault();
      const topicId = Number(commentForm.dataset.commentForm);
      const status = commentForm.querySelector('[data-comment-status]');
      const button = commentForm.querySelector('button[type="submit"]');
      const body = new FormData(commentForm).get('body');
      status.textContent = 'იგზავნება...';
      button.disabled = true;
      try {
        const payload = await request(`/parent/forum/topics/${topicId}/comments`, { method: 'POST', body: JSON.stringify({ body }) });
        state.expanded.add(topicId);
        await loadForum({ preserveMessage: payload.message });
      } catch (error) {
        status.textContent = error.message;
      } finally {
        button.disabled = false;
      }
    }
  });

  loadForum();
})();
