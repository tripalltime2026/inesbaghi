(() => {
  const endpoint = new URL('/content/public', window.location.origin).toString();
  const escapeHtml = (value = '') => String(value).replace(/[&<>'"]/g, (char) => ({
    '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#039;', '"': '&quot;',
  })[char]);
  const safeColor = (value) => /^#[0-9A-Fa-f]{6}$/.test(value || '') ? value : '#A9D3C9';
  const imageStyle = (url, color) => url
    ? `background-image:url('${String(url).replace(/'/g, '%27')}');background-size:cover;background-position:center;background-color:${safeColor(color)}`
    : `background:${safeColor(color)}`;

  async function load() {
    const response = await fetch(endpoint, { credentials: 'same-origin', headers: { Accept: 'application/json' }, cache: 'no-store' });
    if (!response.ok) throw new Error('Content request failed');
    return response.json();
  }

  function renderPosts(items = []) {
    const list = document.querySelector('.feed-list');
    if (!list || !items.length) return;
    list.innerHTML = items.map((item) => `<article class="club-post"><div class="post-art" style="${imageStyle(item.image_url, item.color)}">${item.image_url ? '' : escapeHtml(item.meta?.art_label || 'სიახლე')}</div><div class="post-body"><div class="post-meta"><span class="post-avatar" style="background:${safeColor(item.color)}">ი</span><div><strong>ინეს ბაღი</strong><small>${escapeHtml(item.subtitle || '')}</small></div><span class="visibility-chip" style="background:${safeColor(item.color)}">${escapeHtml(item.badge || 'კლუბის წევრები')}</span></div><h2>${escapeHtml(item.title)}</h2><p>${escapeHtml(item.body || '')}</p><div class="post-actions"><span>❤️ ${Number(item.meta?.likes || 0)}</span><span>💬 ${Number(item.meta?.comments || 0)} კომენტარი</span></div></div></article>`).join('');
  }

  function renderEvents(items = []) {
    const list = document.querySelector('.event-list');
    if (!list || !items.length) return;
    list.innerHTML = items.map((item) => `<article class="event-card"><div class="event-art" style="${imageStyle(item.image_url, item.color)}">${item.image_url ? '' : escapeHtml(item.meta?.art_label || 'ღონისძიება')}</div><div><div class="event-meta"><span class="visibility-chip" style="background:${safeColor(item.color)}">${escapeHtml(item.badge || 'კლუბის წევრები')}</span><span>📅 ${escapeHtml(item.subtitle || '')}</span></div><h2>${escapeHtml(item.title)}</h2><p>${escapeHtml(item.body || '')}</p><small>✓ ${escapeHtml(item.meta?.attendance || '')}</small></div><div class="event-actions"><button class="yes" type="button">✓ მოვალთ</button><button type="button">ვერ მოვალთ</button></div></article>`).join('');

    const nextEvent = document.querySelector('.next-event');
    if (nextEvent && items[0]) {
      nextEvent.innerHTML = `<strong>📌 შემდეგი ღონისძიება</strong><h3>${escapeHtml(items[0].title)}</h3><span>${escapeHtml(items[0].subtitle || '')}</span>`;
    }
  }

  function renderTopics(items = []) {
    const list = document.querySelector('.topic-list');
    if (!list || !items.length) return;
    list.innerHTML = items.map((item) => `<article><div><strong>${escapeHtml(item.title)}</strong><small>${escapeHtml(item.subtitle || '')}</small></div><span>💬 ${Number(item.meta?.comments || 0)}</span></article>`).join('');
  }

  function renderPolls(items = []) {
    const list = document.querySelector('.poll-list');
    if (!list || !items.length) return;
    list.innerHTML = items.map((item) => {
      const options = Array.isArray(item.meta?.options) ? item.meta.options : [];
      const buttons = options.map((option) => {
        const percent = Math.max(0, Math.min(100, Number(option.percent || 0)));
        return `<button type="button"><i style="width:${percent}%;background:${safeColor(item.color)}88"></i><span>${escapeHtml(option.label || '')}</span><strong>${percent}%</strong></button>`;
      }).join('');
      return `<article class="poll-card"><div class="poll-meta"><span class="visibility-chip" style="background:${safeColor(item.color)}">${escapeHtml(item.badge || 'კლუბის წევრები')}</span><span>⏰ ${escapeHtml(item.subtitle || '')}</span></div><h2>${escapeHtml(item.title)}</h2>${buttons}<small>${Number(item.meta?.votes || 0)} ხმა · ანონიმური</small></article>`;
    }).join('');

    const activePoll = document.querySelector('.active-poll');
    if (activePoll && items[0]) {
      activePoll.innerHTML = `<strong>აქტიური გამოკითხვა</strong><p>${escapeHtml(items[0].title)}</p><button type="button" data-club-tab-link="polls">ხმის მიცემა →</button>`;
      activePoll.querySelector('button')?.addEventListener('click', () => {
        document.querySelector('[data-club-tab="polls"]')?.click();
      });
    }
  }

  load().then((content) => {
    renderPosts(content.club_post);
    renderEvents(content.club_event);
    renderTopics(content.club_topic);
    renderPolls(content.club_poll);
    document.documentElement.dataset.clubCmsReady = 'true';
  }).catch(() => {
    document.documentElement.dataset.clubCmsReady = 'fallback';
  });
})();
