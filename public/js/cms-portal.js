(() => {
  const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
  const dataEndpoint = '/parent/forum/data';
  const topicEndpoint = '/parent/forum/topics';
  const pollEndpoint = '/parent/polls';
  const state = {
    groupId: Number(document.querySelector('[data-club-group].active')?.dataset.clubGroup || 0),
    filter: 'all',
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
      '.forum-sidebar': 'ჯგუფის სივრცე იტვირთება…',
      '.forum-content': 'კითხვები და გამოკითხვები იტვირთება…',
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
        <small>${escapeHtml(group.academic_year || group.slug || '')}</small>
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
        'ადმინისტრაციის მიერ კონკრეტულად ამ ჯგუფისთვის გამოქვეყნებული ინფორმაცია აქ გამოჩნდება.',
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
        </div>
      </article>
    `).join('');
  }

  function pollCard(poll) {
    const options = (poll.options || []).map((option) => `
      <button type="submit" name="option_id" value="${Number(option.id)}" class="club-feed-poll-option ${option.selected ? 'selected' : ''}" ${poll.can_vote ? '' : 'disabled'}>
        <i style="width:${Math.max(0, Math.min(100, Number(option.percent || 0)))}%"></i>
        <span>${escapeHtml(option.label)}</span>
        <strong>${Number(option.percent || 0)}%</strong>
      </button>
    `).join('');

    return `
      <article class="club-feed-card club-feed-poll" data-feed-type="poll" id="club-poll-${Number(poll.id)}">
        <header><span class="club-feed-avatar poll">?</span><div><strong>ჯგუფის გამოკითხვა</strong><small>${escapeHtml(poll.published_at || '')}</small></div><b>${poll.can_vote ? 'აქტიური' : 'დახურული'}</b></header>
        <h3>${escapeHtml(poll.question)}</h3>
        ${poll.description ? `<p>${escapeHtml(poll.description)}</p>` : ''}
        <form data-poll-vote="${Number(poll.id)}">${options}<span class="forum-form-status" aria-live="polite"></span></form>
        <footer><span>${Number(poll.total_votes || 0)} ხმა</span><span>${poll.closes_at ? `დახურვა: ${escapeHtml(poll.closes_at)}` : 'დასრულების დრო არ არის მითითებული'}</span></footer>
      </article>
    `;
  }

  function topicCard(topic) {
    const comments = (topic.comments || []).map((comment) => `
      <div class="forum-comment ${comment.is_official_answer ? 'official' : ''}">
        <strong>${comment.is_official_answer ? 'ინეს ბაღი · ' : ''}${escapeHtml(comment.author)}</strong>
        <p>${escapeHtml(comment.body)}</p>
        <small>${escapeHtml(comment.created_at || '')}</small>
      </div>
    `).join('');

    const commentForm = topic.is_locked ? '<p class="forum-locked">ამ თემაზე პასუხები დახურულია.</p>' : `
      <form class="forum-comment-form" data-comment-topic="${Number(topic.id)}">
        <textarea name="body" rows="2" minlength="2" maxlength="2000" required placeholder="დაწერეთ პასუხი ამ ჯგუფის მშობლებისთვის…"></textarea>
        <div><button type="submit">პასუხის დამატება</button><span class="forum-form-status" aria-live="polite"></span></div>
      </form>
    `;

    return `
      <article class="club-feed-card club-feed-question" id="forum-topic-${Number(topic.id)}" data-feed-type="question" data-topic-category="${escapeHtml(topic.category)}">
        <header><span class="club-feed-avatar">${escapeHtml((topic.author || 'მ').slice(0, 1))}</span><div><strong>${escapeHtml(topic.author)}</strong><small>${escapeHtml(topic.created_at || '')}</small></div><b class="status-${escapeHtml(topic.status)}">${escapeHtml(topic.status_label || '')}</b></header>
        <div class="club-feed-labels"><span>${escapeHtml(topic.category_label || '')}</span>${topic.is_pinned ? '<span>დამაგრებული</span>' : ''}</div>
        <h3>${escapeHtml(topic.title)}</h3>
        <p>${escapeHtml(topic.body || '')}</p>
        <details class="forum-thread" ${topic.comments_count ? '' : 'open'}>
          <summary>${Number(topic.comments_count || 0)} პასუხი · საუბრის გახსნა</summary>
          <div class="forum-comments">${comments || '<p class="forum-no-comments">პირველი პასუხი თქვენ დაწერეთ.</p>'}</div>
          ${commentForm}
        </details>
      </article>
    `;
  }

  function renderForumFeed(data) {
    const sidebar = document.querySelector('.forum-sidebar');
    const content = document.querySelector('.forum-content');
    if (!sidebar || !content) return;

    const categories = Object.entries(data.categories || {});
    const members = data.members || [];
    const activeGroup = data.active_group;
    const entries = [
      ...(data.polls || []).map((item) => ({ type: 'poll', sortAt: Number(item.sort_at || 0), html: pollCard(item) })),
      ...(data.topics || []).map((item) => ({ type: 'question', sortAt: Number(item.sort_at || 0), html: topicCard(item) })),
    ].sort((a, b) => b.sortAt - a.sortAt);

    sidebar.innerHTML = `
      <div class="club-feed-scope">
        <small>დახურული სივრცე</small>
        <h3>${escapeHtml(activeGroup?.name || 'ჯგუფი')}</h3>
        <p>${escapeHtml(data.contact_policy || '')}</p>
        <strong>${members.length} მშობელი</strong>
        <div>${members.slice(0, 8).map((member) => `<span title="${escapeHtml(member.name)}">${escapeHtml(member.initial)}</span>`).join('')}</div>
      </div>
      <div class="club-feed-filters">
        <strong>ფიდის ფილტრი</strong>
        <button type="button" data-feed-filter="all" class="${state.filter === 'all' ? 'active' : ''}">ყველაფერი</button>
        <button type="button" data-feed-filter="question" class="${state.filter === 'question' ? 'active' : ''}">მშობლების კითხვები</button>
        <button type="button" data-feed-filter="poll" class="${state.filter === 'poll' ? 'active' : ''}">გამოკითხვები</button>
      </div>
    `;

    content.innerHTML = `
      <div class="club-feed-head">
        <div><small>${escapeHtml(activeGroup?.name || '')}</small><h2>ჯგუფის ფიდი</h2><p>კითხვები, პასუხები და სწრაფი გამოკითხვები ერთ მარტივ ნაკადში.</p></div>
        <button type="button" data-new-topic>+ კითხვის დასმა</button>
      </div>
      <form class="forum-topic-form club-feed-composer" data-topic-form hidden>
        <label><span>თემა</span><select name="category">${categories.map(([key, label]) => `<option value="${escapeHtml(key)}">${escapeHtml(label)}</option>`).join('')}</select></label>
        <label><span>სათაური</span><input name="title" minlength="4" maxlength="180" required placeholder="რას ეკითხებით მშობლებს ან ადმინისტრაციას?"></label>
        <label class="wide"><span>ტექსტი</span><textarea name="body" minlength="5" maxlength="5000" rows="4" required placeholder="დაწერეთ მხოლოდ ${escapeHtml(activeGroup?.name || 'ამ ჯგუფის')} წევრებისთვის…"></textarea></label>
        <div class="wide"><button type="submit">ფიდში გამოქვეყნება</button><button type="button" data-cancel-topic>გაუქმება</button><span class="forum-form-status" aria-live="polite"></span></div>
      </form>
      <div class="club-feed-list">${entries.map((entry) => entry.html).join('') || emptyState('ფიდი ჯერ ცარიელია', 'დასვით პირველი კითხვა ან დაელოდეთ ჯგუფის გამოკითხვას.')}</div>
    `;

    sidebar.querySelectorAll('[data-feed-filter]').forEach((button) => {
      button.addEventListener('click', () => {
        state.filter = button.dataset.feedFilter || 'all';
        sidebar.querySelectorAll('[data-feed-filter]').forEach((item) => item.classList.toggle('active', item === button));
        filterFeed();
      });
    });

    content.querySelector('[data-new-topic]')?.addEventListener('click', () => {
      const form = content.querySelector('[data-topic-form]');
      if (form) {
        form.hidden = false;
        form.querySelector('input[name="title"]')?.focus();
      }
    });

    content.querySelector('[data-cancel-topic]')?.addEventListener('click', () => {
      const form = content.querySelector('[data-topic-form]');
      if (form) form.hidden = true;
    });

    content.querySelector('[data-topic-form]')?.addEventListener('submit', submitTopic);
    content.querySelectorAll('[data-comment-topic]').forEach((form) => form.addEventListener('submit', submitComment));
    content.querySelectorAll('[data-poll-vote]').forEach((form) => form.addEventListener('submit', submitPollVote));

    filterFeed();
    scrollToDeepLink();
  }

  function filterFeed() {
    document.querySelectorAll('[data-feed-type]').forEach((entry) => {
      entry.hidden = state.filter !== 'all' && entry.dataset.feedType !== state.filter;
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

  async function submitPollVote(event) {
    event.preventDefault();
    const form = event.currentTarget;
    const pollId = Number(form.dataset.pollVote);
    const status = form.querySelector('.forum-form-status');
    const submitter = event.submitter;

    if (!submitter?.value) return;

    try {
      if (status) status.textContent = 'პასუხი ინახება…';
      await request(`${pollEndpoint}/${pollId}/vote`, {
        method: 'POST',
        body: JSON.stringify({ option_id: Number(submitter.value) }),
      });
      await loadGroup(state.groupId);
    } catch (error) {
      if (status) status.textContent = error.message;
    }
  }

  function scrollToDeepLink() {
    const id = location.hash.replace('#', '');
    if (!id.startsWith('forum-topic-') && !id.startsWith('club-poll-')) return;
    window.setTimeout(() => document.getElementById(id)?.scrollIntoView({ behavior: 'smooth', block: 'center' }), 50);
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
      renderForumFeed(data);
      document.documentElement.dataset.clubCmsReady = 'true';
    } catch (error) {
      const message = emptyState('ჯგუფის სივრცე ვერ ჩაიტვირთა', error.message);
      ['.feed-list', '.forum-content', '.forum-sidebar'].forEach((selector) => {
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
    ['.feed-list', '.forum-content', '.forum-sidebar'].forEach((selector) => {
      const element = document.querySelector(selector);
      if (element) element.innerHTML = message;
    });
  }
})();
