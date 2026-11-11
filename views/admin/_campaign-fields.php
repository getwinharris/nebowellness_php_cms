<?php use App\Services\CampaignPageService; ?>
<section class="campaign-editor" aria-labelledby="campaign-fields-heading">
    <h3 id="campaign-fields-heading">Custom enquiry fields</h3>
    <p>Name and email are always required. Use the phone and message settings above to change those fields.</p>
    <ol id="campaign-field-rows" class="campaign-field-rows"></ol>
    <button type="button" id="campaign-field-add" class="btn btn-sm btn-secondary">Add field</button>
    <span id="campaign-field-status" role="status" class="campaign-field-status"></span>
    <noscript><p>Enable JavaScript to edit and save campaign pages.</p></noscript>
    <details id="campaign-form-preview">
        <summary>Preview enquiry form</summary>
        <fieldset disabled class="campaign-preview-fields">
            <legend>Visitor view</legend>
            <div id="campaign-preview-controls" class="campaign-preview-fields"></div>
        </fieldset>
    </details>
</section>
<template id="campaign-field-template">
    <li class="campaign-field-row">
        <fieldset class="campaign-field-controls">
            <legend data-row-title></legend>
            <div class="admin-form__row">
                <label>Field label <input data-label maxlength="<?= CampaignPageService::MAX_LABEL_LENGTH ?>"></label>
                <label>Field type
                    <select data-type>
                        <?php foreach (CampaignPageService::FIELD_TYPES as $value => $label): ?>
                            <option value="<?= e($value) ?>"><?= e($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
            </div>
            <label class="campaign-field-check"><input type="checkbox" data-required> Required</label>
            <div data-choices hidden>
                <div data-options class="campaign-option-list"></div>
                <button type="button" class="btn btn-sm btn-ghost" data-add-option>Add option</button>
            </div>
            <ul data-errors class="campaign-editor-error" hidden></ul>
            <div class="campaign-field-actions">
                <button type="button" class="btn btn-sm btn-ghost" data-move="-1">Move up</button>
                <button type="button" class="btn btn-sm btn-ghost" data-move="1">Move down</button>
                <button type="button" class="btn btn-sm btn-ghost" data-remove>Remove field</button>
            </div>
        </fieldset>
    </li>
</template>
<template id="campaign-option-template">
    <div class="campaign-option">
        <label><span data-option-title></span><input maxlength="<?= CampaignPageService::MAX_OPTION_LENGTH ?>"></label>
        <button type="button" class="btn btn-sm btn-ghost">Remove option</button>
    </div>
</template>
<script>
const campaignFields = (() => {
    const form = document.getElementById('resource-form');
    const rows = document.getElementById('campaign-field-rows');
    const add = document.getElementById('campaign-field-add');
    const status = document.getElementById('campaign-field-status');
    const error = document.getElementById('campaign-save-error');
    const saveButton = () => document.getElementById('save-btn');
    const preview = document.getElementById('campaign-preview-controls');
    const rowTemplate = document.getElementById('campaign-field-template');
    const optionTemplate = document.getElementById('campaign-option-template');
    const defaults = <?= json_encode(CampaignPageService::FORM_DEFAULTS, JSON_HEX_TAG) ?>;
    const formCopy = name => controls(name).value.trim() || defaults[name];
    const maxFields = <?= CampaignPageService::MAX_FIELDS ?>;
    const maxOptions = <?= CampaignPageService::MAX_OPTIONS ?>;
    const controls = id => document.getElementById('field-' + id);
    const optionInputs = row => [...row.querySelectorAll('[data-options] input')];

    function refreshRow(row, index) {
        const number = index + 1;
        row.querySelector('[data-row-title]').textContent = 'Field ' + number;
        row.querySelector('[data-move="-1"]').disabled = index === 0;
        row.querySelector('[data-move="1"]').disabled = number === rows.children.length;
        row.querySelectorAll('[data-move], [data-remove]').forEach(button => {
            button.setAttribute('aria-label', button.textContent + ' ' + number);
        });
        row.querySelector('[data-choices]').hidden = row.querySelector('[data-type]').value !== 'select';
        const options = optionInputs(row);
        row.querySelector('[data-add-option]').disabled = options.length >= maxOptions;
        options.forEach((input, index) => {
            const option = input.closest('.campaign-option');
            option.querySelector('[data-option-title]').textContent = 'Option ' + (index + 1);
            option.querySelector('button').setAttribute('aria-label', 'Remove option ' + (index + 1));
        });
    }

    function fields() {
        return [...rows.children].map(row => ({
            key: row.dataset.key,
            label: row.querySelector('[data-label]').value,
            type: row.querySelector('[data-type]').value,
            required: row.querySelector('[data-required]').checked,
            options: row.querySelector('[data-type]').value === 'select' ? optionInputs(row).map(input => input.value) : row.savedOptions,
        }));
    }

    function previewField({label, type, required, options = []}) {
        const wrapper = document.createElement('label');
        wrapper.textContent = label + (required ? '' : ' (optional)');
        const control = document.createElement(type === 'textarea' || type === 'select' ? type : 'input');
        if (control.tagName === 'INPUT') control.type = type;
        if (type === 'textarea') control.rows = 4;
        if (type === 'select') {
            control.add(new Option('Select', ''));
            options.filter(value => value.trim()).forEach(value => control.add(new Option(value.trim(), value.trim())));
        }
        control.required = required;
        if (type === 'checkbox') wrapper.classList.add('campaign-field-check');
        wrapper.append(control);
        preview.append(wrapper);
    }

    function previewCopy(tag, text) {
        const element = document.createElement(tag);
        element.textContent = text;
        preview.append(element);
        return element;
    }

    function renderPreview() {
        preview.replaceChildren();
        previewCopy('h4', formCopy('form_title'));
        previewCopy('p', formCopy('form_intro'));
        previewCopy('p', <?= json_encode(CampaignPageService::FORM_NOTE, JSON_HEX_TAG) ?>);
        previewField({label: 'Name', type: 'text', required: true});
        previewField({label: 'Email', type: 'email', required: true});
        for (const [label, type] of [['Phone', 'tel'], ['Message', 'textarea']]) {
            const mode = controls('form_' + label.toLowerCase()).value;
            if (mode !== 'hidden') previewField({label, type, required: mode === 'required'});
        }
        fields().forEach((field, index) => previewField({...field, label: field.label.trim() || 'Field ' + (index + 1)}));
        if (controls('form_require_otp').checked) {
            previewField({label: 'Email verification code', type: 'text', required: true});
            previewCopy('button', 'Send code').type = 'button';
        }
        previewCopy('p', 'We use these details to respond to this enquiry. Privacy policy');
        const button = previewCopy('button', formCopy('form_button'));
        button.type = 'button';
        button.className = 'btn btn-primary';
    }

    function refresh() {
        [...rows.children].forEach(refreshRow);
        add.disabled = rows.children.length >= maxFields;
        status.textContent = rows.children.length + ' of ' + maxFields + ' fields';
        renderPreview();
    }

    function addOption(row, value = '', focus = false) {
        const option = optionTemplate.content.firstElementChild.cloneNode(true);
        const input = option.querySelector('input');
        input.value = value;
        option.querySelector('button').addEventListener('click', () => {
            option.remove();
            refresh();
            row.querySelector('[data-add-option]').focus();
        });
        row.querySelector('[data-options]').append(option);
        if (focus) {
            refresh();
            input.focus();
        }
    }

    function addField(field = {}) {
        const row = rowTemplate.content.firstElementChild.cloneNode(true);
        const type = row.querySelector('[data-type]');
        const label = row.querySelector('[data-label]');
        row.dataset.key = field.key ?? '';
        label.value = field.label ?? '';
        type.value = field.type ?? 'text';
        row.querySelector('[data-required]').checked = !!field.required;
        const options = Array.isArray(field.options) ? field.options : [];
        row.savedOptions = type.value === 'select' ? [] : options;
        options.forEach(value => addOption(row, value));
        type.addEventListener('change', () => {
            if (type.value === 'select' && optionInputs(row).length === 0) addOption(row);
            refresh();
        });
        row.querySelector('[data-add-option]').addEventListener('click', () => addOption(row, '', true));
        row.querySelectorAll('[data-move]').forEach(button => button.addEventListener('click', () => {
            if (button.dataset.move === '-1') row.previousElementSibling.before(row);
            else row.nextElementSibling.after(row);
            refresh();
            (button.disabled ? row.querySelector('[data-move]:enabled') : button).focus();
        }));
        row.querySelector('[data-remove]').addEventListener('click', () => {
            const next = row.nextElementSibling || row.previousElementSibling;
            row.remove();
            refresh();
            (next?.querySelector('[data-label]') || add).focus();
        });
        rows.append(row);
        return label;
    }

    function clearErrors() {
        error.hidden = true;
        rows.querySelectorAll('[data-errors]').forEach(list => {
            list.hidden = true;
            list.replaceChildren();
            list.parentElement.removeAttribute('aria-describedby');
        });
    }

    function load(definitions) {
        controls('form_phone').value ||= 'optional';
        controls('form_message').value ||= 'optional';
        clearErrors();
        rows.replaceChildren();
        if (Array.isArray(definitions)) definitions.forEach(field => addField(field ?? {}));
        refresh();
    }

    add.addEventListener('click', () => {
        const label = addField();
        refresh();
        label.focus();
    });
    form.addEventListener('input', renderPreview);
    form.addEventListener('reset', () => {
        document.getElementById('resource-revision').value = '';
        requestAnimationFrame(() => load([]));
    });
    form.addEventListener('submit', async event => {
        event.preventDefault();
        clearErrors();
        const data = new FormData(form);
        data.set('form_fields', JSON.stringify(fields()));
        const save = saveButton();
        const saveLabel = save.textContent;
        save.textContent = 'Saving…';
        form.inert = true;
        try {
            const response = await fetch(form.action, {
                method: 'POST',
                headers: {Accept: 'application/json'},
                body: data,
                redirect: 'error',
            });
            const result = await response.json();
            if (response.ok && result.redirect === '/admin/campaigns') {
                window.location.assign(result.redirect);
                return;
            }
            for (const [index, messages] of Object.entries(result.fieldErrors ?? {})) {
                const list = rows.children[index]?.querySelector('[data-errors]');
                if (!list) continue;
                list.id = 'campaign-field-errors-' + index;
                list.parentElement.setAttribute('aria-describedby', list.id);
                messages.forEach(message => {
                    const item = document.createElement('li');
                    item.textContent = message;
                    list.append(item);
                });
                list.hidden = false;
            }
            error.textContent = result.error || 'The campaign could not be saved. Try again.';
        } catch {
            error.textContent = 'The save could not be confirmed. Your changes are still here.';
        }
        form.inert = false;
        save.textContent = saveLabel;
        error.hidden = false;
        error.focus();
    });
    document.addEventListener('DOMContentLoaded', () => { saveButton().disabled = false; });
    load([]);
    return {load};
})();
</script>
