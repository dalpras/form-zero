/**
 * Optional progressive enhancement for FormZero fragments (native JS, no dependencies).
 *
 * Forms opt in with data-fz-token; triggers use data-fz-action="create|save".
 * FormZero never persists entities or assumes a particular controller/framework.
 */
export default class FormZeroAjax {
    constructor({
        translate = (message) => message,
        invalidResponse = 'Invalid server response',
        requestError = 'Request failed',
        tokenField = 'fragment_token',
        fetchImpl = (...args) => fetch(...args),
        statusClasses = {
            base: 'fz-status', success: 'fz-status--success', error: 'fz-status--error',
        },
    } = {}) {
        this.translate = translate;
        this.invalidResponse = invalidResponse;
        this.requestError = requestError;
        this.tokenField = tokenField;
        this.fetchImpl = fetchImpl;
        this.statusClasses = statusClasses;
        this.roots = new WeakSet();
        this.queues = new WeakMap();
        this.pending = new WeakSet();
    }

    /** A delegated listener also handles controls inserted after initialization. */
    init(root = document) {
        if (this.roots.has(root)) return;
        this.roots.add(root);
        root.addEventListener('click', (event) => {
            const trigger = event.target.closest?.('[data-fz-action][data-fz-url]');
            if (!trigger || !root.contains(trigger)) return;
            const form = trigger.closest('form[data-fz-token]');
            if (!form || (root !== form && !root.contains(form))) return;
            if (!['create', 'save'].includes(trigger.dataset.fzAction)) return;
            // An anchor retains its normal GET fallback with JS disabled.
            event.preventDefault();
            void this.execute(form, trigger);
        });
    }

    /**
     * Serialize only direct child fields of a nested form namespace.
     * E.g. scope sections[5][contents][9] and fields title,body must never
     * include sections[5][contents][9][links][3][title].
     *
     * Unchecked inputs and empty multiselects are deliberately omitted: the
     * scoped PHP endpoint must normalize omitted multi-valued fields to [].
     */
    static collect(root, scope, fields) {
        if (!root || typeof scope !== 'string' || !scope || !Array.isArray(fields)) {
            throw new Error('FormZero AJAX requires a valid field scope and whitelist');
        }
        const allowed = new Set(fields.map((field) => field.trim()).filter(Boolean));
        if (!allowed.size) throw new Error('FormZero AJAX requires permitted fields');
        const data = new FormData();
        const prefix = `${scope}[`;
        root.querySelectorAll('input[name], select[name], textarea[name]').forEach((control) => {
            if (control.disabled || !control.name?.startsWith(prefix)) return;
            const remainder = control.name.slice(prefix.length);
            const match = /^([^\[\]]+)\](\[\])?$/.exec(remainder);
            if (!match || !allowed.has(match[1])) return;
            if ((control.type === 'checkbox' || control.type === 'radio') && !control.checked) return;
            if (control.tagName === 'SELECT' && control.multiple) {
                for (const option of control.selectedOptions) data.append(control.name, option.value);
            } else if (control.type === 'file') {
                for (const file of control.files ?? []) data.append(control.name, file);
            } else {
                data.append(control.name, control.value);
            }
        });
        return data;
    }

    errorMessage(result) {
        if (!result || typeof result !== 'object') return this.translate(this.invalidResponse);
        const errors = result.errors && typeof result.errors === 'object'
            ? Object.values(result.errors).flat(Infinity).filter((value) => typeof value === 'string')
            : [];
        return errors.length ? errors.join(' \u00b7 ')
            : (typeof result.error === 'string' && result.error || this.translate(this.requestError));
    }

    async request(form, url, data = new FormData()) {
        const token = form.dataset.fzToken;
        if (!token || !url) throw new Error(this.translate(this.invalidResponse));
        data.set(this.tokenField, token);
        let response;
        try {
            response = await this.fetchImpl(url, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: data,
            });
        } catch (_) {
            throw new Error(this.translate(this.requestError));
        }
        const contentType = response.headers?.get?.('content-type');
        if (contentType && !/\bapplication\/(?:[\w.+-]*\+)?json\b/i.test(contentType)) {
            throw new Error(this.translate(this.invalidResponse));
        }
        let result;
        try {
            result = await response.json();
        } catch (_) {
            throw new Error(this.translate(this.invalidResponse));
        }
        if (!result || typeof result !== 'object' || Array.isArray(result)) {
            throw new Error(this.translate(this.invalidResponse));
        }
        // Refresh on success *and* 422, since the server may rotate after a
        // failed validation. This occurs before checking response.ok.
        if (typeof result.csrfToken === 'string' && result.csrfToken) {
            form.dataset.fzToken = result.csrfToken;
        }
        if (!response.ok || result.success !== true) {
            throw new Error(this.errorMessage(result));
        }
        return result;
    }

    /** No concurrent requests may use the same rotating token on a given form. */
    enqueue(form, task) {
        const previous = this.queues.get(form) ?? Promise.resolve();
        const job = previous.catch(() => {}).then(task);
        this.queues.set(form, job.catch(() => {}));
        return job;
    }

    fragment(html, expectedId) {
        if (typeof html !== 'string' || !html || !/^[a-zA-Z][\w-]*$/.test(expectedId)) {
            throw new Error(this.translate(this.invalidResponse));
        }
        const template = document.createElement('template');
        template.innerHTML = html;
        const element = [...template.content.querySelectorAll('[id]')]
            .find((candidate) => candidate.id === expectedId);
        if (!element) throw new Error(this.translate(this.invalidResponse));
        return element;
    }

    validateFragment(trigger, root) {
        const selector = trigger.dataset.fzFragmentMatch;
        if (selector && !root.matches(selector)) {
            throw new Error(this.translate(this.invalidResponse));
        }
    }

    /** JSON context guards prevent appending a valid row under the wrong owner. */
    checkContext(trigger, result) {
        const key = trigger.dataset.fzContextKey;
        if (key && String(result[key]) !== trigger.dataset.fzContextValue) {
            throw new Error(this.translate(this.invalidResponse));
        }
    }

    fragmentId(trigger, result) {
        if (!/^[1-9]\d*$/.test(String(result.id))) {
            throw new Error(this.translate(this.invalidResponse));
        }
        const prefix = trigger.dataset.fzFragmentPrefix;
        if (!prefix || !/^[a-zA-Z][\w-]*-$/.test(prefix)) {
            throw new Error(this.translate(this.invalidResponse));
        }
        return prefix + result.id;
    }

    emit(name, detail) {
        document.dispatchEvent(new CustomEvent(`formzero:${name}`, {detail}));
    }

    status(trigger, message, error = false) {
        let element = trigger.nextElementSibling;
        if (!element?.hasAttribute('data-fz-status')) {
            element = document.createElement('span');
            element.dataset.fzStatus = '';
            trigger.insertAdjacentElement('afterend', element);
        }
        element.className = [
            this.statusClasses.base,
            error ? this.statusClasses.error : this.statusClasses.success,
        ].filter(Boolean).join(' ');
        element.textContent = message;
        element.setAttribute('role', error ? 'alert' : 'status');
        element.setAttribute('aria-live', error ? 'assertive' : 'polite');
    }

    async execute(form, trigger) {
        if (this.pending.has(trigger)) return;
        this.pending.add(trigger);
        const originalTrigger = trigger;
        const wasDisabled = trigger.disabled === true;
        const previousAriaDisabled = trigger.getAttribute('aria-disabled');
        if ('disabled' in trigger) trigger.disabled = true;
        trigger.setAttribute('aria-disabled', 'true');
        try {
            return await this.enqueue(form, async () => {
                try {
                    const action = trigger.dataset.fzAction;
                    const url = trigger.dataset.fzUrl;
                    let data = new FormData();
                    let target;
                    if (action === 'create') {
                        // Check the DOM before creating an entity on the server.
                        target = form.querySelector(trigger.dataset.fzTarget || '#fz-no-target');
                        if (!target) throw new Error(this.translate(this.invalidResponse));
                    } else if (action === 'save') {
                        target = form.querySelector(trigger.dataset.fzRow || '#fz-no-row');
                        if (!target || !target.contains(trigger)) {
                            throw new Error(this.translate(this.invalidResponse));
                        }
                        this.emit('before-submit', {form, trigger, root: target});
                        const fields = (trigger.dataset.fzFields || '').split(',');
                        data = FormZeroAjax.collect(target, trigger.dataset.fzScope, fields);
                    } else {
                        throw new Error(this.translate(this.invalidResponse));
                    }
                    const result = await this.request(form, url, data);
                    this.checkContext(trigger, result);
                    let root = target;
                    if (action === 'create') {
                        root = this.fragment(result.html, this.fragmentId(trigger, result));
                        this.validateFragment(trigger, root);
                        target.append(root);
                        this.emit('fragment-added', {form, trigger, root, result});
                    } else {
                        const captionSelector = trigger.dataset.fzLabelTarget;
                        if (captionSelector && typeof result.label === 'string') {
                            const caption = target.querySelector(captionSelector);
                            if (caption) caption.textContent = result.label;
                        }
                        if (trigger.dataset.fzReplace === 'if-html' && result.html) {
                            root = this.fragment(result.html, this.fragmentId(trigger, result));
                            this.validateFragment(trigger, root);
                            target.replaceWith(root);
                            this.emit('fragment-replaced', {form, trigger, root, result});
                            // The previous trigger was detached along with the row.
                            trigger = [...root.querySelectorAll('[data-fz-action="save"]')]
                                .find((item) => item.dataset.fzUrl === url) ?? trigger;
                        }
                    }
                    this.emit('fragment-saved', {form, trigger, root, result, action});
                    if (trigger.dataset.fzSuccess) {
                        this.status(trigger, this.translate(trigger.dataset.fzSuccess));
                    }
                    return result;
                } catch (error) {
                    this.status(trigger, error.message || this.translate(this.requestError), true);
                    this.emit('fragment-error', {form, trigger, error});
                    return null;
                }
            });
        } finally {
            this.pending.delete(originalTrigger);
            if ('disabled' in originalTrigger) originalTrigger.disabled = wasDisabled;
            if (previousAriaDisabled === null) originalTrigger.removeAttribute('aria-disabled');
            else originalTrigger.setAttribute('aria-disabled', previousAriaDisabled);
        }
    }
}
