document.addEventListener('DOMContentLoaded', () => {
    const form = document.querySelector('form[action$="/admin/content/blog"]');
    if (!form || form.querySelector('[name="article_url"]')) return;

    const grid = form.querySelector('.cms-field-grid');
    if (!grid) return;

    const label = document.createElement('label');
    label.className = 'wide';
    label.innerHTML = `
        <span>სტატიის ბმული (Marketer.ge)</span>
        <input type="url" name="article_url" inputmode="url"
            placeholder="https://www.marketer.ge/ines-bagi-batumshi/"
            value="${window.__oldBlogArticleUrl || ''}">
        <small>ჩასვით სტატიის ბმული — სისტემა ავტომატურად გადმოიტანს სათაურს, მოკლე აღწერას, სრულ ტექსტსა და ქავერს. ქვემოთ ხელით შევსებული ველები უპირატესია.</small>
    `;

    grid.prepend(label);
});
