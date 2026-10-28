/* Wert auf Fundament – public site enhancements. Everything works without this file. */
(() => {
    'use strict';

    const header = document.querySelector('[data-header]');
    const nav = document.querySelector('[data-nav]');
    const toggle = document.querySelector('[data-nav-toggle]');
    const desktop = window.matchMedia('(min-width: 1100px)');

    /* ---------------------------------------------------------------- sticky header shadow */
    if (header) {
        const onScroll = () => header.classList.toggle('is-scrolled', window.scrollY > 8);
        onScroll();
        window.addEventListener('scroll', onScroll, { passive: true });
    }

    /* ---------------------------------------------------------------- mobile navigation */
    const setNav = (open) => {
        if (!nav || !toggle) return;
        toggle.setAttribute('aria-expanded', String(open));
        toggle.querySelector('.nav-toggle__label').textContent = open ? 'Schließen' : 'Menü';
        if (open) {
            const rect = header.getBoundingClientRect();
            nav.style.setProperty('--nav-top', `${Math.max(0, rect.bottom)}px`);
        }
        nav.classList.toggle('is-open', open);
        document.body.classList.toggle('nav-open', open);
    };
    if (toggle && nav) {
        toggle.addEventListener('click', () => setNav(toggle.getAttribute('aria-expanded') !== 'true'));
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && toggle.getAttribute('aria-expanded') === 'true') {
                setNav(false);
                toggle.focus();
            }
        });
        desktop.addEventListener('change', () => setNav(false));
    }

    /* ---------------------------------------------------------------- sub navigation */
    document.querySelectorAll('.nav__sub-toggle').forEach((btn) => {
        const sub = document.getElementById(btn.getAttribute('aria-controls'));
        btn.addEventListener('click', () => {
            const open = btn.getAttribute('aria-expanded') !== 'true';
            document.querySelectorAll('.nav__sub-toggle[aria-expanded="true"]').forEach((other) => {
                if (other !== btn) {
                    other.setAttribute('aria-expanded', 'false');
                    document.getElementById(other.getAttribute('aria-controls'))?.classList.remove('is-open');
                }
            });
            btn.setAttribute('aria-expanded', String(open));
            sub?.classList.toggle('is-open', open);
        });
        btn.parentElement.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && btn.getAttribute('aria-expanded') === 'true') {
                btn.setAttribute('aria-expanded', 'false');
                sub?.classList.remove('is-open');
                btn.focus();
            }
        });
    });
    document.addEventListener('click', (e) => {
        if (!desktop.matches) return;
        document.querySelectorAll('.nav__sub-toggle[aria-expanded="true"]').forEach((btn) => {
            if (!btn.parentElement.contains(e.target)) {
                btn.setAttribute('aria-expanded', 'false');
                document.getElementById(btn.getAttribute('aria-controls'))?.classList.remove('is-open');
            }
        });
    });

    /* ---------------------------------------------------------------- reveal on scroll */
    const reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    if (!reduced && 'IntersectionObserver' in window) {
        const targets = document.querySelectorAll('.section-head, .card, .feature, .step, .checklist__item, .split__figure, .contact-card');
        const io = new IntersectionObserver((entries) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('is-visible');
                    io.unobserve(entry.target);
                }
            });
        }, { rootMargin: '0px 0px -8% 0px' });
        targets.forEach((el) => {
            if (el.getBoundingClientRect().top > window.innerHeight) {
                el.setAttribute('data-reveal', '');
                io.observe(el);
            }
        });
    }

    /* ---------------------------------------------------------------- contact form */
    document.querySelectorAll('[data-contact-form]').forEach((form) => {
        form.addEventListener('submit', () => {
            const btn = form.querySelector('[data-submit]');
            if (btn) {
                btn.classList.add('is-loading');
                btn.setAttribute('aria-busy', 'true');
            }
        });
    });
    const alertBox = document.querySelector('[data-autofocus]');
    if (alertBox) {
        alertBox.focus({ preventScroll: false });
    }

    /* ---------------------------------------------------------------- lightbox */
    const galleries = document.querySelectorAll('[data-lightbox]');
    if (galleries.length) {
        const box = document.createElement('div');
        box.className = 'lightbox';
        box.hidden = true;
        box.setAttribute('role', 'dialog');
        box.setAttribute('aria-modal', 'true');
        box.setAttribute('aria-label', 'Bildansicht');
        box.innerHTML = '<img class="lightbox__img" alt=""><p class="lightbox__caption"></p>'
            + '<button class="lightbox__btn lightbox__close" type="button" aria-label="Schließen">✕</button>'
            + '<button class="lightbox__btn lightbox__prev" type="button" aria-label="Vorheriges Bild">‹</button>'
            + '<button class="lightbox__btn lightbox__next" type="button" aria-label="Nächstes Bild">›</button>';
        document.body.appendChild(box);
        const img = box.querySelector('img');
        const cap = box.querySelector('.lightbox__caption');
        let links = [];
        let index = 0;
        let opener = null;
        const show = (i) => {
            index = (i + links.length) % links.length;
            const link = links[index];
            img.src = link.href;
            img.alt = link.querySelector('img')?.alt || '';
            cap.textContent = link.dataset.caption || '';
        };
        const close = () => {
            box.hidden = true;
            document.body.classList.remove('nav-open');
            opener?.focus();
        };
        galleries.forEach((gallery) => {
            gallery.addEventListener('click', (e) => {
                const link = e.target.closest('.gallery__link');
                if (!link) return;
                e.preventDefault();
                links = Array.from(gallery.querySelectorAll('.gallery__link'));
                opener = link;
                show(links.indexOf(link));
                box.hidden = false;
                document.body.classList.add('nav-open');
                box.querySelector('.lightbox__close').focus();
            });
        });
        box.querySelector('.lightbox__close').addEventListener('click', close);
        box.querySelector('.lightbox__prev').addEventListener('click', () => show(index - 1));
        box.querySelector('.lightbox__next').addEventListener('click', () => show(index + 1));
        box.addEventListener('click', (e) => { if (e.target === box) close(); });
        box.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') close();
            if (e.key === 'ArrowLeft') show(index - 1);
            if (e.key === 'ArrowRight') show(index + 1);
            if (e.key === 'Tab') {
                const focusables = Array.from(box.querySelectorAll('button'));
                const first = focusables[0];
                const last = focusables[focusables.length - 1];
                if (e.shiftKey && document.activeElement === first) { e.preventDefault(); last.focus(); }
                else if (!e.shiftKey && document.activeElement === last) { e.preventDefault(); first.focus(); }
            }
        });
    }
})();
