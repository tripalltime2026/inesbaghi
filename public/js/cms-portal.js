(() => {
  const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
  const dataEndpoint = '/parent/forum/data';
  const topicEndpoint = '/parent/forum/topics';
  const state = {
    groupId: Number(document.querySelector('[data-club-group].active')?.dataset.clubGroup || 0),
    category: 'general',
    content: null,
  };

  const escapeHtml = (value = '') => String(value).replace(/[&<>'"]/g, (char) => ({
    '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#039;', '"': '&quot;',
  })[char]);

  const safeColor = (value) => /^#[0-9A-Fa-f]{6}$/.test(value || '') ? value : '#A9D3C9';

  const imageStyle = (url, color) => url
    ? `background-image:url('${String(url).replace(/'/g, '%27')}');background-size:cover;background-position:center;background-color:${safeColor(color)}`
    : `background:${safeColor(color)}`;

  const emptyState = (title, text) => `<div class="club-empty"><span>🌱</span><h3>${escapeHtml(title)}</h3><p>${escapeHtml(text)}</p></div>`;

  async function request(url, options = {}) {
    const response = await fetch(url, {
      credentials: 'same-origin',
      cache: 'no-store',
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
      const validation = data.errors ? Object.values(data.errors).flat()[0] : null;
      throw new Error(validation || data.message || 'მოთხოვნა ვერ შესრულდა.');
    }

    return data;
  }

  function setLoading() {
    const messages = {
      '.feed-list': 'ჯგუფის სიახლეები იტვირთება…',
      '.event-list': 'ჯგუფის ღონისძიებები იტვირთება…',
      '.poll-list': 'ჯგუფის გამოკითხვები იტვირთება…',
      '.forum-sidebar': 'ჯგუფის წევრები იტვირთება…',
      '.forum-content': 'ჯგუფის საუბრები იტვირთება…',
    };

    Object.entries(messages).forEach(([selector, message]) => {
      const element = document.querySelector(selector);
      if (element) element.innerHTML = `<div class="club-loading">${message}</div>`;
    });
  }

  function renderGroupButtons(groups = [], activeGroup = null) {
    const holder = document.querySelector('.club-group-buttons');
    if (!holder || !groups.length) return;

    holder.innerHTML = groups.map((group) => `
      <button type="button" data-club-group="${Number(group.id)}" class="${Number(group.id) === Number(activeGroup?.id) ? 'active' : ''}">
        <span>${escapeHtml(group.name)}</span>
        <small>${escapeHtml(group.slug)}</small>
      </button>
    `).join('');

    holder.querySelectorAll('[data-club-group]').forEach((button) => {
      button.addEventListener('click', () => loadGroup(Number(button.dataset.clubGroup)));
    });
  }

  function renderPosts(items = [], activeGroup = null) {
    const list = document.querySelector('.feed-list');
    if (!list) return;

    if (!items.length) {
      list.innerHTML = emptyState(
        'ამ ჯგუფისთვის სიახლე ჯერ არ არის',
        'ადმინისტრაციის მიერ გამოქვეყნებული ახალი ინფორმაცია აქ გამოჩნდება.',
      );
      return;
    }

    list.innerHTML = items.map((item) => `
      <article class="club-post">
        <div class="post-art" style="${imageStyle(item.image_url, item.color)}">${item.image_url ? '' : escapeHtml(item.meta?.art_label || 'სიახლე')}</div>
        <div class="post-body">
          <div class="post-meta">
            <span class="post-avatar" style="background:${safeColor(item.color)}">ი</span>
            <div><strong>ინეს ბაღი</strong><small>${escapeHtml(item.subtitle || '')}</small></div>
            <span class="visibility-chip" style="background:${safeColor(item.color)}">${escapeHtml(item.visibility_label || activeGroup?.name || 'ჯგუფი')}</span>
          </div>
          <h2>${escapeHtml(item.title)}</h2>
          <p>${escapeHtml(item.body || '')}</p>
          <div class="post-actions"><span>❤️ ${Number(item.meta?.likes || 0)}</span><span>💬 ${Number(item.meta?.comments || 0)} კომენტარი</span></div>
        </div>
      </article>
    `).join('');
  }

  function renderEvents(items = [], activeGroup = null) {
    const list = document.querySelector('.event-list');
    if (!list) return;

    if (!items.length) {
      list.innerHTML = emptyState('ღონისძიება ჯერ არ არის', 'ამ ჯგუფისთვის დაგეგმილი ღონისძიებები აქ გამოჩნდება.');
    } else {
      list.innerHTML = items.map((item) => `
        <article class="event-card">
          <div class="event-art" style="${imageStyle(item.image_url, item.color)}">${item.image_url ? '' : escapeHtml(item.meta?.art_label || 'ღონისძიება')}</div>
          <div>
            <div class="event-meta"><span class="visibility-chip" style="background:${safeColor(item.color)}">${escapeHtml(item.visibility_label || activeGroup?.name || 'ჯგუფი')}</span><span>📅 ${escapeHtml(item.subtitle || '')}</span></div>
            <h2>${escapeHtml(item.title)}</h2>
            <p>${escapeHtml(item.body || '')}</p>
            <small>${escapeHtml(item.meta?.attendance || '')}</small>
          </div>
          <div class="event-actions"><button class="yes" type="button">✓ მოვალთ</button><button type="button">ვერ მოვალთ</button></div>
        </article>
      `).join('');
    }

    const nextEvent = document.querySelector('.next-event');
    if (nextEvent) {
      nextEvent.innerHTML = items[0]
        ? `<strong>📌 შემდეგი ღონისძიება</strong><h3>${escapeHtml(items[0].title)}</h3><span>${escapeHtml(items[0].subtitle || '')}</span>`
        : '<strong>📌 შემდეგი ღონისძიება</strong><p>ჯერ არ არის დაგეგმილი.</p>';
    }
  }

  function renderPolls(items = [], activeGroup = null) {
    const list = document.querySelector('.poll-list');
    if (!list) return;

    if (!items.length) {
      list.innerHTML = emptyState('გამოკითხვა ჯერ არ არის', 'ამ ჯგუფისთვის შექმნილი გამოკითხვები აქ გამოჩნდება.');
    } else {
      list.innerHTML = items.map((item) => {
        const options = Array.isArray(item.meta?.options) ? item.meta.options : [];
        const buttons = options.map((option) => {
          const percent = Math.max(0, Math.min(100, Number(option.percent || 0)));
          return `<button type="button"><i style="width:${percent}%;background:${safeColor(item.color)}88"></i><span>${escapeHtml(option.label || '')}</span><strong>${percent}%</strong></button>`;
        }).join('');

        return `<article class="poll-card"><div class="poll-meta"><span class="visibility-chip" style="background:${safeColor(item.color)}">${escapeHtml(item.visibility_label || activeGroup?.name || 'ჯგუფი')}</span><span>⏰ ${escapeHtml(item.subtitle || '')}</span></div><h2>${escapeHtml(item.title)}</h2>${buttons}<small>${Number(item.meta?.votes || 0)} ხმა · ანონიმური</small></article>`;
      }).join('');
    }

    const activePoll = document.querySelector('.active-poll');
    if (activePoll) {
      activePoll.innerHTML = items[0]
        ? `<strong>აქტიური გამოკითხვა</strong><p>${escapeHtml(items[0].title)}</p><button type="button" data-club-tab-link="polls">ხმის მიცემა →</button>`
        : '<strong>აქტიური გამოკითხვა</strong><p>ჯერ არ არის შექმნილი.</p>';

      activePoll.querySelector('[data-club-tab-link]')?.addEventListener('click', () => {
        document.querySelector('[data-club-tab="polls"]')?.click();
      });
    }
  }

  function topicCard(topic) {
    const comments = (topic.comments || []).map((comment) => `
      <div class="forum-comment"><strong>${escapeHtml(comment.author)}</strong><p>${escapeHtml(comment.body)}</p><small>${escapeHtml(comment.created_at || '')}</small></div>
    `).join('');

    const commentForm = topic.is_locked ? '<p class="forum-locked">ამ თემაზე კომენტარები დახურულია.</p>' : `
      <form class="forum-comment-form" data-comment-topic="${Number(topic.id)}">
        <textarea name="body" rows="2" minlength="2" maxlength="2000" required placeholder="დაწერეთ პასუხი ამ ჯგუფის მშობლებისთვის…"></textarea>
        <button type="submit">პასუხის დამატება</button>
        <span class="forum-form-status" aria-live="polite"></span>
      </form>
    `;

    return `
      <article class="forum-topic-card" id="forum-topic-${Number(topic.id)}" data-topic-category="${escapeHtml(topic.category)}">
        <div class="forum-topic-head">
          <div><span>${escapeHtml(topic.category_label || '')}</span><h3>${escapeHtml(topic.title)}</h3><small>${escapeHtml(topic.author)} · ${escapeHtml(topic.created_at || '')}</small></div>
          <strong>💬 ${Number(topic.comments_count || 0)}</strong>
        </div>
        <p>${escapeHtml(topic.body || '')}</p>
        <details class="forum-thread" ${topic.comments_count ? '' : 'open'}>
          <summary>საუბრის გახსნა</summary>
          <div class="forum-comments">${comments || '<p class="forum-no-comments">პირველი პასუხი თქვენ დაწერეთ.</p>'}</div>
          ${commentForm}
        </details>
      </article>
    `;
  }

  function renderForum(data) {
    const sidebar = document.querySelector('.forum-sidebar');
    const content = document.querySelector('.forum-content');
    if (!sidebar || !content) return;

    const categories = Object.entries(data.categories || {});
    const members = data.members || [];
    const activeGroup = data.active_group;

    sidebar.innerHTML = `
      <div class="forum-category-block">
        <strong>საუბრის თემები</strong>
        ${categories.map(([key, label], index) => `<button type="button" data-forum-category="${escapeHtml(key)}" class="${state.category === key || (!state.category && index === 0) ? 'active' : ''}">${escapeHtml(label)}</button>`).join('')}
      </div>
      <div class="group-members">
        <strong>${escapeHtml(activeGroup?.name || 'ჯგუფი')} · მშობლები</strong>
        <p>${escapeHtml(data.contact_policy || '')}</p>
        <div>${members.map((member) => `<span title="${member.is_you ? 'თქვენ' : 'ჯგუფის მშობელი'}"><i>${escapeHtml(member.initial)}</i>${escapeHtml(member.name)}${member.is_you ? ' · თქვენ' : ''}</span>`).join('') || '<small>ჯგუფის წევრები ჯერ არ არიან დამატებული.</small>'}</div>
      </div>
    `;

    content.innerHTML = `
      <div class="forum-head"><div><small>${escapeHtml(activeGroup?.name || '')}</small><h2>ჯგუფის დახურული საუბარი</h2></div><button type="button" data-new-topic>+ ახალი თემა</button></div>
      <form class="forum-topic-form" data-topic-form hidden>
        <label><span>კატეგორია</span><select name="category">${categories.map(([key, label]) => `<option value="${escapeHtml(key)}">${escapeHtml(label)}</option>`).join('')}</select></label>
        <label><span>სათაური</span><input name="title" minlength="4" maxlength="180" required placeholder="რაზე გსურთ საუბარი?"></label>
        <label><span>ტექსტი</span><textarea name="body" minlength="5" maxlength="5000" rows="4" required placeholder="დაწერეთ მხოლოდ ამ ჯგუფის მშობლებისთვის…"></textarea></label>
        <div><button type="submit">თემის გამოქვეყნება</button><button type="button" data-cancel-topic>გაუქმება</button><span class="forum-form-status" aria-live="polite"></span></div>
      </form>
      <div class="topic-list">${(data.topics || []).map(topicCard).join('') || emptyState('საუბარი ჯერ არ დაწყებულა', 'შექმენით პირველი თემა თქვენი ჯგუფის მშობლებისთვის.')}</div>
    `;

    sidebar.querySelectorAll('[data-forum-category]').forEach((button) => {
      button.addEventListener('click', () => {
        state.category = button.dataset.forumCategory;
        sidebar.querySelectorAll('[data-forum-category]').forEach((item) => item.classList.toggle('active', item === button));
        filterTopics();
      });
    });

    content.querySelector('[data-new-topic]')?.addEventListener('click', () => {
      const form = content.querySelector('[data-topic-form]');
      if (form) form.hidden = false;
    });

    content.querySelector('[data-cancel-topic]')?.addEventListener('click', () => {
      const form = content.querySelector('[data-topic-form]');
      if (form) form.hidden = true;
    });

    content.querySelector('[data-topic-form]')?.addEventListener('submit', submitTopic);
    content.querySelectorAll('[data-comment-topic]').forEach((form) => form.addEventListener('submit', submitComment));

    filterTopics();
  }

  function filterTopics() {
    document.querySelectorAll('.forum-topic-card').forEach((topic) => {
      topic.hidden = Boolean(state.category) && topic.dataset.topicCategory !== state.category;
    });
  }

  async function submitTopic(event) {
    event.preventDefault();
    const form = event.currentTarget;
    const status = form.querySelector('.forum-form-status');
    const data = new FormData(form);

    try {
      if (status) status.textContent = 'იგზავნება…';
      await request(topicEndpoint, {
        method: 'POST',
        body: JSON.stringify({
          kindergarten_group_id: state.groupId,
          category: data.get('category'),
          title: data.get('title'),
          body: data.get('body'),
        }),
      });
      form.reset();
      form.hidden = true;
      await loadGroup(state.groupId);
    } catch (error) {
      if (status) status.textContent = error.message;
    }
  }

  async function submitComment(event) {
    event.preventDefault();
    const form = event.currentTarget;
    const topicId = Number(form.dataset.commentTopic);
    const status = form.querySelector('.forum-form-status');
    const textarea = form.querySelector('textarea[name="body"]');

    try {
      if (status) status.textContent = 'იგზავნება…';
      await request(`${topicEndpoint}/${topicId}/comments`, {
        method: 'POST',
        body: JSON.stringify({ body: textarea?.value || '' }),
      });
      await loadGroup(state.groupId);
    } catch (error) {
      if (status) status.textContent = error.message;
    }
  }

  async function loadGroup(groupId) {
    if (!groupId) return;

    state.groupId = Number(groupId);
    setLoading();

    document.querySelectorAll('[data-club-group]').forEach((button) => {
      button.classList.toggle('active', Number(button.dataset.clubGroup) === state.groupId);
    });

    try {
      const data = await request(`${dataEndpoint}?group_id=${encodeURIComponent(state.groupId)}`);
      state.content = data;
      state.groupId = Number(data.active_group?.id || state.groupId);

      renderGroupButtons(data.groups, data.active_group);
      renderPosts(data.club_post, data.active_group);
      renderEvents(data.club_event, data.active_group);
      renderPolls(data.club_poll, data.active_group);
      renderForum(data);
      document.documentElement.dataset.clubCmsReady = 'true';
    } catch (error) {
      const message = emptyState('ჯგუფის სივრცე ვერ ჩაიტვირთა', error.message);
      ['.feed-list', '.event-list', '.poll-list', '.forum-content'].forEach((selector) => {
        const element = document.querySelector(selector);
        if (element) element.innerHTML = message;
      });
      document.documentElement.dataset.clubCmsReady = 'error';
    }
  }

  if (state.groupId) {
    loadGroup(state.groupId);
  } else {
    const message = emptyState('აქტიური ჯგუფი ვერ მოიძებნა', 'კლუბის სივრცე გაიხსნება ბავშვის აქტიურ ჯგუფში ჩარიცხვის შემდეგ.');
    ['.feed-list', '.event-list', '.poll-list', '.forum-content', '.forum-sidebar'].forEach((selector) => {
      const element = document.querySelector(selector);
      if (element) element.innerHTML = message;
    });
  }
})();