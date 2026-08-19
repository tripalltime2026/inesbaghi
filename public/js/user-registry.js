(() => {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

    document.querySelectorAll('.registry-card-actions').forEach((actions) => {
        if (actions.querySelector('form input[name="child_first_name"]')) {
            return;
        }

        const childActionForm = Array.from(actions.querySelectorAll('form')).find((form) => {
            try {
                return /\/admin\/users\/\d+\/children\/?$/.test(new URL(form.action, window.location.origin).pathname);
            } catch (_) {
                return false;
            }
        });

        if (!childActionForm) {
            return;
        }

        const sourceGroupSelect = childActionForm.querySelector('select[name="group_id"]');
        if (!sourceGroupSelect) {
            return;
        }

        const details = document.createElement('details');
        details.className = 'registry-manage registry-additional-child';

        const summary = document.createElement('summary');
        summary.textContent = '+ ახალი ბავშვის დამატება';
        details.appendChild(summary);

        const form = document.createElement('form');
        form.method = 'post';
        form.action = childActionForm.action;
        form.className = 'registry-manage-form';

        const csrf = document.createElement('input');
        csrf.type = 'hidden';
        csrf.name = '_token';
        csrf.value = csrfToken;
        form.appendChild(csrf);

        const field = (labelText, input) => {
            const label = document.createElement('label');
            const span = document.createElement('span');
            span.textContent = labelText;
            label.appendChild(span);
            label.appendChild(input);
            return label;
        };

        const firstName = document.createElement('input');
        firstName.name = 'child_first_name';
        firstName.required = true;
        firstName.minLength = 2;
        firstName.maxLength = 100;
        firstName.autocomplete = 'off';
        form.appendChild(field('ბავშვის სახელი', firstName));

        const lastName = document.createElement('input');
        lastName.name = 'child_last_name';
        lastName.required = true;
        lastName.minLength = 2;
        lastName.maxLength = 100;
        lastName.autocomplete = 'off';
        form.appendChild(field('ბავშვის გვარი', lastName));

        const birthDate = document.createElement('input');
        birthDate.type = 'date';
        birthDate.name = 'child_birth_date';
        birthDate.required = true;
        birthDate.max = new Date().toISOString().slice(0, 10);
        form.appendChild(field('დაბადების თარიღი', birthDate));

        const groupLabel = document.createElement('label');
        groupLabel.className = 'wide';
        const groupSpan = document.createElement('span');
        groupSpan.textContent = 'ჯგუფი — თუ უკვე ჩაირიცხა';
        groupLabel.appendChild(groupSpan);

        const groupSelect = sourceGroupSelect.cloneNode(true);
        groupSelect.name = 'group_id';
        groupSelect.required = false;
        groupSelect.selectedIndex = 0;
        if (!groupSelect.options.length || groupSelect.options[0].value !== '') {
            const emptyOption = document.createElement('option');
            emptyOption.value = '';
            emptyOption.textContent = 'ჯერ მხოლოდ ბავშვის დამატება';
            groupSelect.insertBefore(emptyOption, groupSelect.firstChild);
            groupSelect.selectedIndex = 0;
        } else {
            groupSelect.options[0].textContent = 'ჯერ მხოლოდ ბავშვის დამატება';
        }
        groupLabel.appendChild(groupSelect);
        form.appendChild(groupLabel);

        const info = document.createElement('div');
        info.className = 'empty-account wide';
        info.textContent = 'შეგიძლიათ მხოლოდ დაამატოთ ბავშვი, ან აირჩიოთ ჯგუფი და იმავე მოქმედებით ჩარიცხოთ. ჯგუფში ჩარიცხვისას Parent Club-ის წვდომაც ავტომატურად განახლდება.';
        form.appendChild(info);

        const linkOnly = document.createElement('button');
        linkOnly.type = 'submit';
        linkOnly.name = 'enroll_now';
        linkOnly.value = '0';
        linkOnly.textContent = 'მხოლოდ ბავშვის დამატება';
        form.appendChild(linkOnly);

        const enroll = document.createElement('button');
        enroll.type = 'submit';
        enroll.name = 'enroll_now';
        enroll.value = '1';
        enroll.className = 'registry-primary';
        enroll.textContent = 'დამატება და ჯგუფში ჩარიცხვა';
        form.appendChild(enroll);

        form.addEventListener('submit', (event) => {
            if (event.submitter?.value === '1' && !groupSelect.value) {
                event.preventDefault();
                groupSelect.focus();
                groupSelect.setCustomValidity('აირჩიეთ ჯგუფი.');
                groupSelect.reportValidity();
                return;
            }
            groupSelect.setCustomValidity('');
        });
        groupSelect.addEventListener('change', () => groupSelect.setCustomValidity(''));

        details.appendChild(form);
        actions.insertBefore(details, actions.firstChild);
    });
})();
