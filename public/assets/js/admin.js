/* Wert auf Fundament – admin enhancements (all forms also work without JavaScript, with reduced comfort). */
(() => {
    'use strict';
    const base = document.body.dataset.base || '';
    const csrf = () => document.querySelector('input[name="_csrf"]')?.value || '';
    const $ = (sel, root = document) => root.querySelector(sel);
    const $$ = (sel, root = document) => Array.from(root.querySelectorAll(sel));

    /* ---------------------------------------------------------------- sidebar (mobile) */
    const sideToggle = $('[data-sidebar-toggle]');
    if (sideToggle) {
        sideToggle.addEventListener('click', () => {
            const open = sideToggle.getAttribute('aria-expanded') !== 'true';
            sideToggle.setAttribute('aria-expanded', String(open));
            $('#sidebar-nav').classList.toggle('is-open', open);
        });
    }
    document.addEventListener('click', (e) => {
        const menu = $('.usermenu[open]');
        if (menu && !menu.contains(e.target)) menu.removeAttribute('open');
    });

    /* ---------------------------------------------------------------- confirmations */
    document.addEventListener('submit', (e) => {
        const form = e.target;
        if (form.dataset.confirm && !window.confirm(form.dataset.confirm)) {
            e.preventDefault();
            return;
        }
        $$('[data-richtext-sync]', form).forEach((fn) => fn.dispatchEvent(new Event('sync')));
        const btn = e.submitter;
        if (btn && !form.dataset.noBusy) {
            setTimeout(() => { btn.disabled = true; btn.setAttribute('aria-busy', 'true'); }, 0);
        }
    }, true);

    /* ---------------------------------------------------------------- slug generation */
    const slugify = (s) => s.toLowerCase()
        .replace(/ä/g, 'ae').replace(/ö/g, 'oe').replace(/ü/g, 'ue').replace(/ß/g, 'ss').replace(/&/g, ' und ')
        .normalize('NFD').replace(/[̀-ͯ]/g, '')
        .replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '').slice(0, 80);
    $$('[data-slug-source]').forEach((src) => {
        const target = src.form?.querySelector('[data-slug-target]');
        if (!target || target.readOnly) return;
        let touched = target.value !== '';
        target.addEventListener('input', () => { touched = target.value !== ''; });
        src.addEventListener('input', () => { if (!touched) target.value = slugify(src.value); });
    });

    /* ---------------------------------------------------------------- character counters */
    $$('[data-counter]').forEach((el) => {
        const limit = parseInt(el.dataset.counter, 10);
        const out = document.createElement('span');
        out.className = 'counter';
        out.setAttribute('aria-live', 'polite');
        el.insertAdjacentElement('afterend', out);
        const update = () => {
            out.textContent = `${el.value.length} / ${limit} Zeichen empfohlen`;
            out.classList.toggle('is-over', el.value.length > limit);
        };
        el.addEventListener('input', update);
        update();
    });

    /* ---------------------------------------------------------------- icon preview */
    $$('[data-icon-select]').forEach((sel) => {
        const preview = sel.parentElement.querySelector('[data-icon-preview]');
        sel.addEventListener('change', () => {
            preview.innerHTML = sel.value ? `<svg class="icon" aria-hidden="true"><use href="${base}/assets/icons.svg#i-${sel.value}"></use></svg>` : '';
        });
    });

    /* ---------------------------------------------------------------- navigation link type panels */
    $$('[data-linktype]').forEach((group) => {
        const form = group.closest('form');
        const update = () => {
            const val = group.querySelector('input:checked')?.value || 'page';
            $$('[data-linktype-panel]', form).forEach((p) => { p.hidden = p.dataset.linktypePanel !== val; });
        };
        group.addEventListener('change', update);
        update();
    });

    /* ---------------------------------------------------------------- repeater */
    $$('[data-repeater]').forEach((rep) => {
        const items = $('[data-repeater-items]', rep);
        const blank = $('.repeater__item--blank', items);
        if (!blank) return;
        const template = blank.cloneNode(true);
        let next = $$('[data-repeater-item]', items).length;
        blank.classList.add('is-collapsed');
        const btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'btn btn--secondary btn--sm repeater__add';
        btn.textContent = '+ Eintrag hinzufügen';
        items.insertAdjacentElement('afterend', btn);
        btn.addEventListener('click', () => {
            const hidden = $('.repeater__item--blank.is-collapsed', items);
            if (hidden) {
                hidden.classList.remove('is-collapsed');
                hidden.querySelector('input, textarea, select')?.focus();
            }
            const clone = template.cloneNode(true);
            const oldIndex = clone.querySelector('[data-repeater-order]').name.match(/\[(\d+)\]\[_order\]$/)[1];
            clone.querySelectorAll('[name]').forEach((el) => {
                el.name = el.name.replace(`[${oldIndex}]`, `[${next}]`);
                if (el.id) el.id = `${el.id}-${next}`;
            });
            clone.querySelectorAll('label[for]').forEach((l) => { l.htmlFor = `${l.htmlFor}-${next}`; });
            clone.querySelector('[data-repeater-order]').value = next + 1;
            clone.classList.add('is-collapsed');
            items.appendChild(clone);
            next++;
        });
    });

    /* ---------------------------------------------------------------- rich text editor */
    const RTE_BUTTONS = [
        ['p', 'Absatz', 'formatBlock', 'P'], ['h2', 'Ü2', 'formatBlock', 'H2'], ['h3', 'Ü3', 'formatBlock', 'H3'], ['|'],
        ['b', 'Fett', 'bold'], ['i', 'Kursiv', 'italic'], ['|'],
        ['ul', '• Liste', 'insertUnorderedList'], ['ol', '1. Liste', 'insertOrderedList'], ['q', 'Zitat', 'formatBlock', 'BLOCKQUOTE'], ['|'],
        ['link', 'Link', 'link'], ['unlink', 'Link entfernen', 'unlink'], ['clear', 'Format löschen', 'removeFormat'], ['|'],
        ['src', 'HTML', 'source'],
    ];
    $$('textarea[data-richtext]').forEach((ta) => {
        const wrap = document.createElement('div');
        wrap.className = 'rte';
        const bar = document.createElement('div');
        bar.className = 'rte__toolbar';
        bar.setAttribute('role', 'toolbar');
        bar.setAttribute('aria-label', 'Textformatierung');
        const area = document.createElement('div');
        area.className = 'rte__area';
        area.contentEditable = 'true';
        area.setAttribute('role', 'textbox');
        area.setAttribute('aria-multiline', 'true');
        const label = ta.id ? document.querySelector(`label[for="${ta.id}"]`) : null;
        if (label) {
            if (!label.id) label.id = `${ta.id}-label`;
            area.setAttribute('aria-labelledby', label.id);
            label.addEventListener('click', () => area.focus());
        }
        area.innerHTML = ta.value || '<p><br></p>';
        ta.parentNode.insertBefore(wrap, ta);
        wrap.append(bar, area, ta);
        ta.classList.add('rte__source');
        ta.removeAttribute('required');
        ta.setAttribute('data-richtext-sync', '');
        const sync = () => {
            if (!wrap.classList.contains('is-source')) ta.value = area.innerHTML.replace(/<p><br><\/p>$/, '').trim();
        };
        ta.addEventListener('sync', sync);
        area.addEventListener('input', sync);
        ta.addEventListener('input', () => { area.innerHTML = ta.value; });

        RTE_BUTTONS.forEach(([key, text, cmd, arg]) => {
            if (key === '|') {
                const s = document.createElement('span');
                s.className = 'rte__sep';
                bar.appendChild(s);
                return;
            }
            const b = document.createElement('button');
            b.type = 'button';
            b.className = 'rte__btn';
            b.textContent = text;
            b.title = text;
            b.addEventListener('mousedown', (e) => e.preventDefault());
            b.addEventListener('click', () => {
                if (cmd === 'source') {
                    sync();
                    const on = wrap.classList.toggle('is-source');
                    b.setAttribute('aria-pressed', String(on));
                    if (!on) area.innerHTML = ta.value;
                    (on ? ta : area).focus();
                    return;
                }
                area.focus();
                if (cmd === 'link') {
                    const url = window.prompt('Linkziel (z. B. /kontakt, https://…, mailto:… oder tel:…):', '/');
                    if (!url) return;
                    if (!/^(\/(?!\/)|#|https?:\/\/|mailto:|tel:)/i.test(url.trim())) {
                        window.alert('Dieses Linkziel ist nicht erlaubt.');
                        return;
                    }
                    document.execCommand('createLink', false, url.trim());
                } else {
                    document.execCommand(cmd, false, arg || null);
                }
                sync();
            });
            bar.appendChild(b);
        });
        // Paste as plain text (formatting from Word/web pages would be removed on the server anyway).
        area.addEventListener('paste', (e) => {
            e.preventDefault();
            const text = (e.clipboardData || window.clipboardData).getData('text/plain');
            const html = text.split(/\n{2,}/).map((p) => `<p>${p.replace(/[&<>]/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;' }[c])).replace(/\n/g, '<br>')}</p>`).join('');
            document.execCommand('insertHTML', false, html);
            sync();
        });
        document.execCommand('defaultParagraphSeparator', false, 'p');
    });

    /* ---------------------------------------------------------------- media picker */
    const modal = $('#media-modal');
    if (modal && typeof modal.showModal === 'function') {
        const results = $('[data-media-results]', modal);
        const search = $('[data-media-search]', modal);
        const status = $('[data-media-status]', modal);
        const more = $('[data-media-more]', modal);
        const confirmBtn = $('[data-media-confirm]', modal);
        const upload = $('[data-media-upload]', modal);
        let page = 1;
        let mode = 'single';
        let onPick = null;
        let selected = new Map();
        let timer = null;

        const card = (item) => {
            const li = document.createElement('li');
            const b = document.createElement('button');
            b.type = 'button';
            b.className = 'picker-item';
            b.setAttribute('aria-pressed', String(selected.has(item.id)));
            const img = document.createElement('img');
            img.src = item.thumb;
            img.alt = '';
            img.loading = 'lazy';
            const span = document.createElement('span');
            span.textContent = item.alt || item.name;
            b.append(img, span);
            b.title = `${item.alt || item.name} (${item.w} × ${item.h} px)`;
            b.addEventListener('click', () => {
                if (mode === 'single') {
                    onPick?.(item);
                    modal.close();
                    return;
                }
                if (selected.has(item.id)) selected.delete(item.id); else selected.set(item.id, item);
                b.setAttribute('aria-pressed', String(selected.has(item.id)));
                confirmBtn.textContent = `${selected.size} Bild(er) übernehmen`;
                confirmBtn.hidden = selected.size === 0;
            });
            li.appendChild(b);
            return li;
        };
        const load = async (reset) => {
            if (reset) { page = 1; results.innerHTML = ''; }
            status.textContent = 'Lade …';
            try {
                const res = await fetch(`${base}/admin/media/picker?q=${encodeURIComponent(search.value)}&seite=${page}`, { headers: { Accept: 'application/json' }, credentials: 'same-origin' });
                if (!res.ok) throw new Error();
                const data = await res.json();
                data.items.forEach((it) => results.appendChild(card(it)));
                more.hidden = !data.more;
                status.textContent = results.children.length ? '' : 'Keine Bilder gefunden.';
            } catch {
                status.textContent = 'Die Medien konnten nicht geladen werden. Bitte Seite neu laden.';
            }
        };
        const open = (m, cb) => {
            mode = m;
            onPick = cb;
            selected = new Map();
            confirmBtn.hidden = true;
            $('#media-modal-title').textContent = m === 'multi' ? 'Bilder auswählen (Mehrfachauswahl)' : 'Bild auswählen';
            modal.showModal();
            load(true);
            search.focus();
        };
        search.addEventListener('input', () => { clearTimeout(timer); timer = setTimeout(() => load(true), 300); });
        more.addEventListener('click', () => { page++; load(false); });
        $('[data-modal-close]', modal).addEventListener('click', () => modal.close());
        confirmBtn.addEventListener('click', () => { onPick?.(Array.from(selected.values())); modal.close(); });
        upload.addEventListener('change', async () => {
            const file = upload.files[0];
            if (!file) return;
            status.textContent = 'Wird hochgeladen und optimiert …';
            const fd = new FormData();
            fd.append('file', file);
            fd.append('_csrf', csrf());
            try {
                const res = await fetch(`${base}/admin/media/upload`, { method: 'POST', body: fd, headers: { Accept: 'application/json' }, credentials: 'same-origin' });
                const data = await res.json();
                if (!res.ok) throw new Error(data.error || 'Upload fehlgeschlagen.');
                status.textContent = 'Hochgeladen. Bitte in der Mediathek einen Alternativtext ergänzen.';
                search.value = '';
                await load(true);
                if (mode === 'single') { onPick?.(data); modal.close(); }
            } catch (err) {
                status.textContent = err.message || 'Upload fehlgeschlagen.';
            }
            upload.value = '';
        });

        // single media fields
        $$('[data-media-field]').forEach((field) => {
            const input = $('[data-media-input]', field);
            const preview = $('[data-media-preview]', field);
            const render = (item) => {
                preview.innerHTML = '';
                if (!item) {
                    const s = document.createElement('span');
                    s.className = 'muted';
                    s.textContent = 'Kein Bild ausgewählt';
                    preview.appendChild(s);
                    return;
                }
                const img = document.createElement('img');
                img.src = item.thumb;
                img.alt = '';
                const s = document.createElement('span');
                s.textContent = item.alt || item.name;
                preview.append(img, s);
            };
            $('[data-media-pick]', field).addEventListener('click', () => open('single', (item) => { input.value = item.id; render(item); }));
            $('[data-media-clear]', field).addEventListener('click', () => { input.value = ''; render(null); });
        });

        // gallery: multi select
        $$('[data-media-pick-multi]').forEach((btn) => {
            const form = btn.closest('form');
            btn.addEventListener('click', () => open('multi', (items) => {
                const box = $('[data-gallery-ids]', form);
                box.innerHTML = '';
                items.forEach((it) => {
                    const h = document.createElement('input');
                    h.type = 'hidden';
                    h.name = 'media_ids[]';
                    h.value = it.id;
                    box.appendChild(h);
                });
                if (items.length) form.submit();
            }));
        });
    } else {
        $$('[data-media-pick], [data-media-pick-multi]').forEach((b) => { b.hidden = true; });
        $$('.media-field__id').forEach((i) => { i.style.display = 'block'; });
    }

    /* ---------------------------------------------------------------- drag & drop block ordering */
    $$('[data-reorder]').forEach((list) => {
        let dragged = null;
        list.addEventListener('dragstart', (e) => {
            dragged = e.target.closest('.block-item');
            dragged?.classList.add('is-dragging');
            e.dataTransfer.effectAllowed = 'move';
        });
        list.addEventListener('dragend', () => {
            dragged?.classList.remove('is-dragging');
            $$('.is-over', list).forEach((el) => el.classList.remove('is-over'));
        });
        list.addEventListener('dragover', (e) => {
            e.preventDefault();
            const over = e.target.closest('.block-item');
            $$('.is-over', list).forEach((el) => el.classList.remove('is-over'));
            if (over && over !== dragged) over.classList.add('is-over');
        });
        list.addEventListener('drop', async (e) => {
            e.preventDefault();
            const over = e.target.closest('.block-item');
            if (!dragged || !over || over === dragged) return;
            list.insertBefore(dragged, over);
            const fd = new FormData();
            fd.append('_csrf', csrf());
            fd.append('owner', list.dataset.owner);
            fd.append('owner_id', list.dataset.ownerId);
            $$('.block-item', list).forEach((li) => fd.append('ids[]', li.dataset.id));
            try {
                const res = await fetch(list.dataset.reorder, { method: 'POST', body: fd, headers: { Accept: 'application/json' }, credentials: 'same-origin' });
                if (!res.ok) throw new Error();
                window.location.reload();
            } catch {
                window.alert('Die Reihenfolge konnte nicht gespeichert werden. Bitte Seite neu laden.');
            }
        });
    });

    /* ---------------------------------------------------------------- dropzone */
    $$('[data-dropzone]').forEach((zone) => {
        const input = $('input[type=file]', zone);
        ['dragenter', 'dragover'].forEach((ev) => zone.addEventListener(ev, (e) => { e.preventDefault(); zone.classList.add('is-over'); }));
        ['dragleave', 'drop'].forEach((ev) => zone.addEventListener(ev, () => zone.classList.remove('is-over')));
        zone.addEventListener('drop', (e) => {
            e.preventDefault();
            if (e.dataTransfer.files.length) {
                input.files = e.dataTransfer.files;
                zone.submit();
            }
        });
    });
})();
