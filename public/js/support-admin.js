(() => {
  const button = document.querySelector('[data-support-draft]');
  const textarea = document.querySelector('[data-support-reply]');
  if (!button || !textarea) return;

  const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
  button.addEventListener('click', async () => {
    if (button.disabled) return;
    button.disabled = true;
    const original = button.textContent;
    button.textContent = 'მონახაზი მზადდება…';
    try {
      const response = await fetch(button.dataset.supportDraft, {
        method: 'POST',
        credentials: 'same-origin',
        headers: {
          Accept: 'application/json',
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': csrf,
        },
        body: JSON.stringify({}),
      });
      const data = await response.json();
      if (!response.ok) throw new Error(data.message || 'მონახაზი ვერ მომზადდა.');
      textarea.value = data.draft || '';
      textarea.focus();
    } catch (error) {
      window.alert(error.message);
    } finally {
      button.disabled = false;
      button.textContent = original;
    }
  });

  const thread = document.querySelector('[data-support-thread]');
  if (thread) thread.scrollTop = thread.scrollHeight;
})();
