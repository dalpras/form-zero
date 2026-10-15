/**
 * Optional progressive enhancement for FormZero fragments (native JS, no dependencies).
 *
 * Forms opt in with data-fz-token; triggers use data-fz-action="create|save|delete|mutate".
 * FormZero never persists entities or assumes a particular controller/framework.
 */
export default class FormZeroAjax {
    constructor({
        translate = (message) => message,
        invalidResponse = 'Invalid server response',
        requestError = 'Request failed',
        dirtyMessage = 'Unsaved changes',
        savedMessage = 'Saved',
        tokenField = 'fragment_token',
        fetchImpl = (...args) => fetch(...args),
        // Optional application-owned notifications (e.g. a Bootstrap toaster).
        // Without it, FormZero keeps its backwards-compatible inline messages.
        notify = null,
        // Optional async confirmation for destructive actions (browser confirm by default).
        confirm = null,
        inlineDirty = true,
        // Optional UI callback for independently saved components. FormZero does
        // not prescribe any layout or visual framework for this feedback.
        onRowStatus = null,
        statusClasses = {
            base: 'fz-status', success: 'fz-status--success', error: 'fz-status--error', warning: 'fz-status--warning',
        },
    } = {}) {
        this.translate = translate;
        this.invalidResponse = invalidResponse;
        this.requestError = requestError;
        this.dirtyMessage = dirtyMessage;
        this.savedMessage = savedMessage;
        this.tokenField = tokenField;
        this.fetchImpl = fetchImpl;
        this.statusClasses = statusClasses;
        this.notify = typeof notify === 'function' ? notify : null;
        this.confirm = typeof confirm === 'function' ? confirm : (message) =>
            typeof window !== 'undefined' && window.confirm?.(message) === true;
        this.inlineDirty = inlineDirty;
        this.onRowStatus = typeof onRowStatus === 'function' ? onRowStatus : null;
        this.originalSaveLabels = new WeakMap();
        this.roots = new WeakSet();
        this.queues = new WeakMap();
        this.pending = new WeakSet();
        this.confirming = new WeakSet();
        this.snapshots = new WeakMap();
        this.dirtyRows = new Set();
        this.pendingUploads = 0;
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
            if (!['create', 'save', 'delete', 'mutate'].includes(trigger.dataset.fzAction)) return;
            // An anchor retains its normal GET fallback with JS disabled.
            event.preventDefault();
            void this.execute(form, trigger);
        });
        // Delegated tracking works for rows inserted by future AJAX requests.
        const changed = (event) => {
            const row = event.target?.closest?.('[data-dragger="item"]');
            if (row && (root === row || root.contains?.(row))) this.refreshDirty(row);
        };
        root.addEventListener('input', changed);
        root.addEventListener('change', changed);
        // An opt-in file input progressively uploads on selection. Without
        // JavaScript it remains a normal multipart field for the full POST.
        root.addEventListener('change', (event) => {
            const input = event.target;
            if (!input?.matches?.('input[type="file"][data-fz-action="upload"][data-fz-url]')) return;
            const form = input.closest('form[data-fz-token]');
            if (!form || (root !== form && !root.contains(form))) return;
            void this.upload(form, input);
        });
        this.rememberAll(root);
        if (typeof window !== 'undefined') {
            window.addEventListener('beforeunload', (event) => {
                if (!this.pendingUploads && ![...this.dirtyRows].some((row) => row.isConnected !== false && root.contains?.(row))) return;
                event.preventDefault();
                event.returnValue = '';
            });
        }
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

    /** A consistent fingerprint of only the fields controlled by this row. */
    fingerprint(row, trigger) {
        try {
            const fields = (trigger.dataset.fzFields || '').split(',');
            return JSON.stringify([...FormZeroAjax.collect(row, trigger.dataset.fzScope, fields).entries()]);
        } catch (_) {
            return null;
        }
    }

    saveTrigger(row) {
        if (!row?.id) return null;
        return [...(row.querySelectorAll?.('[data-fz-action="save"]') || [])]
            .find((trigger) => trigger.dataset?.fzRow === `#${row.id}`) ?? null;
    }

    /** Row + Save-button state, without adding elements to the button toolbar. */
    markDirty(row, trigger, dirty) {
        if (dirty) row.setAttribute?.('data-fz-dirty', 'true');
        else row.removeAttribute?.('data-fz-dirty');
        if (this.inlineDirty || !trigger) return;
        if (dirty) {
            if (!this.originalSaveLabels.has(trigger)) {
                this.originalSaveLabels.set(trigger, {
                    aria: trigger.getAttribute?.('aria-label') ?? null,
                    title: trigger.getAttribute?.('title') ?? null,
                });
            }
            const original = this.originalSaveLabels.get(trigger);
            const label = original.aria || original.title || trigger.textContent?.trim() || 'Save';
            const warning = this.translate(this.dirtyMessage);
            trigger.setAttribute?.('data-fz-dirty', 'true');
            trigger.setAttribute?.('aria-label', `${label} - ${warning}`);
            trigger.setAttribute?.('title', `${original.title || label} - ${warning}`);
        } else {
            trigger.removeAttribute?.('data-fz-dirty');
            if (this.originalSaveLabels.has(trigger)) {
                const original = this.originalSaveLabels.get(trigger);
                for (const [name, value] of [['aria-label', original.aria], ['title', original.title]]) {
                    if (value === null) trigger.removeAttribute?.(name);
                    else trigger.setAttribute?.(name, value);
                }
                this.originalSaveLabels.delete(trigger);
            }
        }
    }

    rowStatus(row, trigger, state, message = '') {
        if (!this.onRowStatus || !row || !trigger) return;
        try { this.onRowStatus({row, trigger, state, message}); }
        catch (_) { /* A presentation error must never prevent persistence. */ }
    }

    remember(row) {
        const trigger = this.saveTrigger(row);
        if (trigger) this.snapshots.set(row, this.fingerprint(row, trigger));
        this.dirtyRows.delete(row);
        this.markDirty(row, trigger, false);
        this.rowStatus(row, trigger, 'idle');
    }

    rememberAll(root) {
        for (const row of root.querySelectorAll?.('[data-dragger="item"]') ?? []) this.remember(row);
    }

    refreshDirty(row) {
        const trigger = this.saveTrigger(row);
        if (!trigger) return;
        if (this.fingerprint(row, trigger) !== this.snapshots.get(row)) {
            const wasDirty = this.dirtyRows.has(row);
            if (!wasDirty && this.inlineDirty && !this.onRowStatus) {
                this.status(trigger, this.translate(this.dirtyMessage), 'warning');
            }
            this.dirtyRows.add(row);
            this.markDirty(row, trigger, true);
            // Reassert dirty after a failed save as soon as the user edits again.
            // The presentation callback deduplicates unchanged statuses.
            this.rowStatus(row, trigger, 'dirty', this.translate(this.dirtyMessage));
            this.emit('dirty', {row, trigger});
        } else {
            const wasDirty = this.dirtyRows.delete(row);
            this.markDirty(row, trigger, false);
            if (wasDirty) this.rowStatus(row, trigger, 'idle');
            const status = trigger.nextElementSibling;
            if (status?.hasAttribute?.('data-fz-status')) status.textContent = '';
        }
    }

    errorMessage(result) {
        if (!result || typeof result !== 'object') return this.translate(this.invalidResponse);
        const errors = result.errors && typeof result.errors === 'object'
            ? Object.values(result.errors).flat(Infinity).filter((value) => typeof value === 'string')
            : [];
        return errors.length ? errors.join(' \u00b7 ')
            : (typeof result.error === 'string' && result.error
                ? this.translate(result.error) : this.translate(this.requestError));
    }

    async request(form, url, data = new FormData()) {
        const token = form.dataset.fzToken;
        if (!token || !url) throw new Error(this.translate(this.invalidResponse));
        data.set(this.tokenField, token);
        if (form.dataset.fzRevision !== undefined) {
            if (!/^[1-9]\d*$/.test(form.dataset.fzRevision)) {
                throw new Error(this.translate(this.invalidResponse));
            }
            data.set('editor_revision', form.dataset.fzRevision);
        }
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
            const error = new Error(this.errorMessage(result));
            error.conflict = response.status === 409 || result.conflict === true;
            error.status = response.status;
            throw error;
        }
        if (typeof result.editorRevision === 'number' && Number.isSafeInteger(result.editorRevision)) {
            form.dataset.fzRevision = String(result.editorRevision);
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

    /**
     * One uploaded File per request: every commit rotates the fragment token
     * and article revision before the next item is sent. The server owns
     * validation and persistence; this code only manages transport and DOM.
     */
    async upload(form, input) {
        if (this.pending.has(input)) return null;
        const files = Array.from(input.files ?? []);
        if (!files.length) return null;
        if (files.length > 20) {
            this.status(input, this.translate('Select no more than 20 files per upload'), true);
            input.value = '';
            return null;
        }
        const disabled = input.disabled;
        this.pending.add(input);
        this.pendingUploads++;
        input.disabled = true;
        try {
            return await this.enqueue(form, async () => {
                let completed = 0;
                try {
                    const target = form.querySelector(input.dataset.fzTarget || '#fz-no-target');
                    if (!target) throw new Error(this.translate(this.invalidResponse));
                    for (const file of files) {
                        const data = new FormData();
                        data.append('file', file, file.name);
                        const result = await this.request(form, input.dataset.fzUrl, data);
                        this.emit('committed', {form, trigger: input, result, action: 'upload'});
                        this.checkContext(input, result);
                        const root = this.fragment(result.html, this.fragmentId(input, result));
                        this.validateFragment(input, root);
                        target.append(root);
                        this.rememberAll(root);
                        this.remember(root);
                        this.emit('fragment-added', {form, trigger: input, root, result});
                        completed++;
                        // Inline consumers keep the progress counter; toast
                        // consumers see one final notification, not one per file.
                        this.status(input, `${completed} / ${files.length}`, 'warning', {progress: true});
                    }
                    this.status(input, this.translate(input.dataset.fzSuccess || 'Uploaded'));
                    this.emit('uploaded', {form, trigger: input, completed});
                    return completed;
                } catch (error) {
                    const message = error.message || this.translate(this.requestError);
                    this.status(input, completed ? `${completed} / ${files.length} - ${message}` : message, true);
                    this.emit('fragment-error', {form, trigger: input, error});
                    if (error.conflict) this.emit('conflict', {form, trigger: input, error});
                    return null;
                }
            });
        } finally {
            // Clearing also allows selecting the same file again after a failed
            // upload; any successfully inserted rows are intentionally retained.
            input.value = '';
            input.disabled = disabled;
            this.pendingUploads--;
            this.pending.delete(input);
        }
    }

    /** Generic ordered mutation; applications decide owner type and endpoint. */
    reorder(form, url, type, ownerId, ids) {
        if (!url || !/^[1-9]\d*$/.test(String(ownerId)) || !Array.isArray(ids) ||
            ids.some((id) => !/^[1-9]\d*$/.test(String(id))) || new Set(ids).size !== ids.length) {
            return Promise.reject(new Error(this.translate(this.invalidResponse)));
        }
        const data = new FormData();
        data.set('type', type);
        data.set('owner_id', String(ownerId));
        ids.forEach((id) => data.append('ids[]', String(id)));
        return this.enqueue(form, async () => {
            const result = await this.request(form, url, data);
            this.emit('reordered', {form, type, ownerId, ids, result});
            return result;
        });
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

    status(trigger, message, error = false, {progress = false} = {}) {
        if (this.notify) {
            // Remove a stale inline notice left by older code/deployments.
            const old = trigger.nextElementSibling;
            if (old?.hasAttribute?.('data-fz-status')) old.remove?.();
            if (!progress && message) {
                const type = error === true ? 'error' : error === 'warning' ? 'warning' : 'success';
                try { this.notify({type, message, trigger}); }
                catch (_) { /* UI feedback cannot turn a committed save into a failure. */ }
            }
            return;
        }
        let element = trigger.nextElementSibling;
        if (!element?.hasAttribute('data-fz-status')) {
            element = document.createElement('span');
            element.dataset.fzStatus = '';
            trigger.insertAdjacentElement('afterend', element);
        }
        element.className = [
            this.statusClasses.base,
            error === true ? this.statusClasses.error :
                error === 'warning' ? this.statusClasses.warning : this.statusClasses.success,
        ].filter(Boolean).join(' ');
        element.textContent = message;
        element.setAttribute('role', error === true ? 'alert' : 'status');
        element.setAttribute('aria-live', error === true ? 'assertive' : 'polite');
    }

    async execute(form, trigger) {
        if (this.pending.has(trigger) || this.confirming.has(trigger)) return;
        if (['delete', 'mutate'].includes(trigger.dataset.fzAction) && trigger.dataset.fzConfirm) {
            // Keep the same confirm dialog from opening twice during rapid clicks.
            this.confirming.add(trigger);
            let approved = false;
            try { approved = await this.confirm(trigger.dataset.fzConfirm, trigger); }
            catch (_) { approved = false; }
            finally { this.confirming.delete(trigger); }
            if (!approved) return;
        }
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
                    } else if (action === 'save' || action === 'delete' || action === 'mutate') {
                        target = form.querySelector(trigger.dataset.fzRow || '#fz-no-row');
                        if (!target || !target.contains(trigger)) {
                            throw new Error(this.translate(this.invalidResponse));
                        }
                        if (action === 'mutate' && this.dirtyRows.has(target)) {
                            throw new Error(this.translate(this.dirtyMessage));
                        }
                        if (action === 'save') {
                            this.emit('before-submit', {form, trigger, root: target});
                            const fields = (trigger.dataset.fzFields || '').split(',');
                            data = FormZeroAjax.collect(target, trigger.dataset.fzScope, fields);
                        }
                    } else {
                        throw new Error(this.translate(this.invalidResponse));
                    }
                    const snapshotSent = action === 'save' ? JSON.stringify([...data.entries()]) : null;
                    if (action === 'save') this.rowStatus(target, trigger, 'saving');
                    const result = await this.request(form, url, data);
                    // Persistence has succeeded even if the following DOM
                    // validation/insertion fails: applications must invalidate
                    // any old whole-page snapshot immediately.
                    this.emit('committed', {form, trigger, result, action});
                    this.checkContext(trigger, result);
                    let root = target;
                    if (action === 'create') {
                        root = this.fragment(result.html, this.fragmentId(trigger, result));
                        this.validateFragment(trigger, root);
                        target.append(root);
                        this.rememberAll(root);
                        this.remember(root);
                        this.emit('fragment-added', {form, trigger, root, result});
                    } else if (action === 'delete') {
                        this.dirtyRows.delete(target);
                        // Descendants may have unsaved fields too.
                        for (const row of target.querySelectorAll?.('[data-dragger="item"]') ?? []) this.dirtyRows.delete(row);
                        target.remove();
                        this.emit('fragment-removed', {form, trigger, root: target, result});
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
                            this.dirtyRows.delete(target);
                            this.rememberAll(root);
                            this.remember(root);
                            this.emit('fragment-replaced', {form, trigger, root, result});
                            // The previous trigger was detached along with the row.
                            trigger = [...root.querySelectorAll('[data-fz-action="save"]')]
                                .find((item) => item.dataset.fzUrl === url) ?? trigger;
                        }
                    }
                    if (action === 'save' && root === target) {
                        const current = this.fingerprint(target, trigger);
                        if (current === snapshotSent) this.remember(target);
                        else {
                            // Compare against the values actually persisted,
                            // not the pre-request baseline. A user who types
                            // during the request must still see Unsaved.
                            this.snapshots.set(target, snapshotSent);
                            this.refreshDirty(target);
                        }
                    }
                    this.emit('fragment-saved', {form, trigger, root, result, action});
                    if (typeof result.warning === 'string' && result.warning) {
                        this.emit('fragment-warning', {form, trigger, root, result});
                    }
                    // A successful save must replace any previous dirty/error notice,
                    // even if the caller did not configure data-fz-success. Do not
                    // claim the row is fully saved if it changed during the request.
                    if (action === 'save' && !this.dirtyRows.has(root)) {
                        const message = this.translate(trigger.dataset.fzSuccess || this.savedMessage);
                        if (this.onRowStatus) this.rowStatus(root, trigger, 'saved', message);
                        else this.status(trigger, message);
                    } else if (action === 'save' && this.onRowStatus) {
                        this.rowStatus(root, trigger, 'dirty', this.translate(this.dirtyMessage));
                    } else if (action === 'delete' && this.notify && !result.warning) {
                        this.status(trigger, this.translate(trigger.dataset.fzSuccess || 'Deleted'));
                    } else if (action !== 'save' && action !== 'delete' && trigger.dataset.fzSuccess) {
                        this.status(trigger, this.translate(trigger.dataset.fzSuccess));
                    }
                    return result;
                } catch (error) {
                    const message = error.message || this.translate(this.requestError);
                    const row = trigger.dataset.fzAction === 'save'
                        ? form.querySelector(trigger.dataset.fzRow || '#fz-no-row') : null;
                    if (row && this.onRowStatus) {
                        this.rowStatus(row, trigger, 'error', message);
                        // Errors warrant a global toast as well (the panel may be closed).
                        // Never create a second inline notice beside Save.
                        if (this.notify) this.status(trigger, message, true);
                    } else {
                        this.status(trigger, message, true);
                    }
                    this.emit('fragment-error', {form, trigger, error});
                    if (error.conflict) this.emit('conflict', {form, trigger, error});
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
