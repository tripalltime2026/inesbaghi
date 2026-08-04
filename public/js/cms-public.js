(() => {
  const endpoint = new URL('/content/public', window.location.origin).toString();
  const blogPath = '/blogi';
  const escapeHtml = (value = '') => String(value).replace(/[&<>'"]/g, (char) => ({
    '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#039;', '"': '&quot;',
  })[char]);
  const safeColor = (value) => /^#[0-9A-Fa-f]{6}$/.test(value || '') ? value : '#A9D3C9';
  const imageStyle = (url, color) => url
    ? `background-image:url('${String(url).replace(/'/g, '%27')}');background-size:cover;background-position:center;background-color:${safeColor(color)}`
    : `background:${safeColor(color)}`;
  const blogUrl = (post = {}) => {
    const slug = String(post.slug || '').trim();
    return slug ? `${blogPath}/${encodeURIComponent(slug)}` : blogPath;
  };

  async function loadContent() {
    const response = await fetch(endpoint, {
      credentials: 'same-origin',
      headers: { Accept: 'application/json' },
      cache: 'no-store',
    });
    if (!response.ok) throw new Error('Content request failed');
    return response.json();
  }

  function renderGroups(groups = []) {
    if (!groups.length) return;
    const tabs = document.getElementById('groupTabs');
    const summary = document.getElementById('groupSummary');
    const schedule = document.getElementById('groupSchedule');
    const admissions = document.getElementById('admissionGroups');
    let active = groups[1]?.badge || groups[0]?.badge || String(groups[0]?.id || 'group');

    const draw = () => {
      const group = groups.find((item) => (item.badge || String(item.id)) === active) || groups[0];
      if (!group) return;

      if (tabs) {
        tabs.innerHTML = groups.map((item) => {
          const key = item.badge || String(item.id);
          return `<button type="button" class="${key === active ? 'active' : ''}" data-cms-group="${escapeHtml(key)}">${escapeHtml(item.title)}</button>`;
        }).join('');
        tabs.querySelectorAll('[data-cms-group]').forEach((button) => button.addEventListener('click', () => {
          active = button.dataset.cmsGroup;
          draw();
        }));
      }

      if (summary) {
        const free = Number(group.meta?.free ?? 0);
        const total = Number(group.meta?.total ?? 0);
        summary.style.background = safeColor(group.color);
        summary.innerHTML = `<span class="section-badge" style="background:#fff">${escapeHtml(group.title)}</span><h2>ჯგუფის შესახებ</h2><p>${escapeHtml(group.body || '')}</p><div class="group-meta"><div><span>ხელმისაწვდომი ადგილები</span><strong>${free} / ${total}</strong></div><div><span>აღმზრდელი</span><strong>${escapeHtml(group.subtitle || 'დასაზუსტებელია')}</strong></div></div>`;
      }

      if (schedule) {
        const rows = Array.isArray(group.meta?.schedule) ? group.meta.schedule : [];
        schedule.innerHTML = rows.map((row) => `<div class="schedule-row"><time>${escapeHtml(row?.[0] || '')}</time><strong>${escapeHtml(row?.[1] || '')}</strong></div>`).join('');
      }
    };

    if (admissions) {
      admissions.innerHTML = groups.map((item, index) => {
        const key = item.badge || String(item.id);
        return `<label class="choice ${index === 1 ? 'active' : ''}"><input type="radio" name="preferred_group" value="${escapeHtml(key)}" ${index === 1 ? 'checked' : ''}>${escapeHtml(item.title)}</label>`;
      }).join('');
      admissions.querySelectorAll('.choice').forEach((label) => label.addEventListener('click', () => {
        admissions.querySelectorAll('.choice').forEach((item) => item.classList.remove('active'));
        label.classList.add('active');
      }));
    }

    draw();
  }

  function renderTeam(team = []) {
    const grid = document.getElementById('teamGrid');
    if (!grid || !team.length) return;
    grid.innerHTML = team.map((item) => {
      const avatar = item.image_url
        ? `<div class="team-avatar" style="${imageStyle(item.image_url, item.color)}" role="img" aria-label="${escapeHtml(item.image_alt || item.title)}"></div>`
        : `<div class="team-avatar" style="background:${safeColor(item.color)}">${escapeHtml(item.badge || item.title?.charAt(0) || '')}</div>`;
      return `<article class="team-card">${avatar}<h3>${escapeHtml(item.title)}</h3><small>${escapeHtml(item.subtitle || '')}</small><p>${escapeHtml(item.body || '')}</p></article>`;
    }).join('');
  }

  function renderGallery(gallery = []) {
    const grid = document.getElementById('galleryGrid');
    if (!grid || !gallery.length) return;
    grid.innerHTML = gallery.map((item) => `<article class="gallery-card"><div class="gallery-art" style="${imageStyle(item.image_url, item.color)}" role="img" aria-label="${escapeHtml(item.image_alt || item.title)}">${item.image_url ? '' : 'ფოტო'}</div><div class="gallery-copy"><small>${escapeHtml(item.subtitle || '')}${item.badge ? ` · ${escapeHtml(item.badge)}` : ''}</small><h3>${escapeHtml(item.title)}</h3></div></article>`).join('');
  }

  function blogCard(post) {
    const url = escapeHtml(blogUrl(post));
    const label = escapeHtml(`სრულად წაიკითხეთ: ${post.title || 'ბლოგის სტატია'}`);

    return `<a href="${url}" aria-label="${label}" style="display:block;height:100%;color:inherit;text-decoration:none"><article class="blog-card"><div class="blog-art" style="${imageStyle(post.cover_url, post.color)}" role="img" aria-label="${escapeHtml(post.cover_alt || post.title)}">${post.cover_url ? '' : escapeHtml(post.category || 'ბლოგი')}</div><div class="blog-copy"><small>${escapeHtml(post.published_at || '')}${post.category ? ` · ${escapeHtml(post.category)}` : ''}</small><h3>${escapeHtml(post.title)}</h3><p>${escapeHtml(post.excerpt || '')}</p></div></article></a>`;
  }

  function miniBlogCard(post) {
    const url = escapeHtml(blogUrl(post));
    const label = escapeHtml(`სრულად წაიკითხეთ: ${post.title || 'ბლოგის სტატია'}`);

    return `<a href="${url}" aria-label="${label}" style="display:block;height:100%;color:inherit;text-decoration:none"><article><i style="${imageStyle(post.cover_url, post.color)}"></i><small>${escapeHtml(post.published_at || '')}</small><strong>${escapeHtml(post.title)}</strong></article></a>`;
  }

  function renderBlog(posts = []) {
    if (!posts.length) return;
    const grid = document.getElementById('blogGrid');
    if (grid) grid.innerHTML = posts.map(blogCard).join('');

    const miniGrid = document.querySelector('.mini-post-grid');
    if (miniGrid && !miniGrid.querySelector('a[href]')) {
      miniGrid.innerHTML = posts.slice(0, 3).map(miniBlogCard).join('');
    }
  }

  function renderFaq(items = []) {
    const list = document.getElementById('faqList');
    if (!list || !items.length) return;
    list.innerHTML = items.map((item, index) => `<article class="faq-item ${index === 0 ? 'open' : ''}"><button type="button"><span>${escapeHtml(item.title)}</span><span class="faq-plus">+</span></button><div class="faq-answer">${escapeHtml(item.body || '')}</div></article>`).join('');
    list.querySelectorAll('.faq-item').forEach((item) => item.querySelector('button')?.addEventListener('click', () => {
      list.querySelectorAll('.faq-item').forEach((other) => { if (other !== item) other.classList.remove('open'); });
      item.classList.toggle('open');
    }));
  }

  loadContent().then((content) => {
    renderGroups(content.group);
    renderTeam(content.team);
    renderGallery(content.gallery);
    renderBlog(content.blog);
    renderFaq(content.faq);
    document.documentElement.dataset.cmsReady = 'true';
  }).catch(() => {
    document.documentElement.dataset.cmsReady = 'fallback';
  });
})();
