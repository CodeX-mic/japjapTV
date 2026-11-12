(() => {
    'use strict';

    document.documentElement.classList.add('js');

    // Sticky header shadow -------------------------------------------------
    const header = document.querySelector('[data-site-header]');
    const onScroll = () => {
        if (header) {
            header.classList.toggle('is-scrolled', window.scrollY > 8);
        }
        const backTop = document.querySelector('[data-back-top]');
        if (backTop) {
            backTop.classList.toggle('is-visible', window.scrollY > 600);
        }
    };
    window.addEventListener('scroll', onScroll, { passive: true });
    onScroll();

    // Mobile navigation ----------------------------------------------------
    const navToggle = document.querySelector('[data-nav-toggle]');
    const mobileNav = document.getElementById('mobileNav');
    if (navToggle && mobileNav) {
        const setOpen = (open) => {
            navToggle.setAttribute('aria-expanded', open ? 'true' : 'false');
            navToggle.setAttribute('aria-label', open ? 'Close menu' : 'Open menu');
            mobileNav.hidden = !open;
            if (open) {
                const firstLink = mobileNav.querySelector('a, input, button');
                if (firstLink) {
                    window.setTimeout(() => firstLink.focus({ preventScroll: true }), 60);
                }
            }
        };

        navToggle.addEventListener('click', () => {
            setOpen(navToggle.getAttribute('aria-expanded') !== 'true');
        });

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && navToggle.getAttribute('aria-expanded') === 'true') {
                setOpen(false);
                navToggle.focus();
            }
        });

        document.addEventListener('click', (event) => {
            if (
                navToggle.getAttribute('aria-expanded') === 'true' &&
                !mobileNav.contains(event.target) &&
                !navToggle.contains(event.target)
            ) {
                setOpen(false);
            }
        });

        mobileNav.querySelectorAll('a').forEach((link) => {
            link.addEventListener('click', () => setOpen(false));
        });

        window.addEventListener('resize', () => {
            if (window.innerWidth > 1060 && navToggle.getAttribute('aria-expanded') === 'true') {
                setOpen(false);
            }
        });
    }

    // Dismiss flash messages ----------------------------------------------
    document.querySelectorAll('[data-dismiss-flash]').forEach((button) => {
        button.addEventListener('click', () => {
            const flash = button.closest('.flash');
            if (flash) {
                flash.style.transition = 'opacity 160ms ease, transform 160ms ease';
                flash.style.opacity = '0';
                flash.style.transform = 'translateY(-4px)';
                window.setTimeout(() => flash.remove(), 170);
            }
        });
    });

    // Back to top ----------------------------------------------------------
    document.querySelectorAll('[data-back-top]').forEach((button) => {
        button.addEventListener('click', () => {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });
    });

    // Copy and share helpers ----------------------------------------------
    const copyText = async (text) => {
        if (navigator.clipboard && window.isSecureContext) {
            await navigator.clipboard.writeText(text);
            return true;
        }

        const area = document.createElement('textarea');
        area.value = text;
        area.setAttribute('readonly', '');
        area.style.position = 'fixed';
        area.style.opacity = '0';
        document.body.appendChild(area);
        area.select();
        let ok = false;
        try {
            ok = document.execCommand('copy');
        } catch (error) {
            ok = false;
        }
        area.remove();
        return ok;
    };

    document.querySelectorAll('[data-copy-url]').forEach((button) => {
        button.addEventListener('click', async () => {
            const url = button.getAttribute('data-copy-url') || window.location.href;
            const label = button.querySelector('[data-copy-label]');
            const original = label ? label.textContent : '';
            const ok = await copyText(url);
            if (label) {
                label.textContent = ok ? 'Copied!' : 'Copy failed';
                window.setTimeout(() => { label.textContent = original; }, 2200);
            }
            button.classList.toggle('is-copied', ok);
        });
    });

    document.querySelectorAll('[data-share]').forEach((button) => {
        button.addEventListener('click', async () => {
            const url = button.getAttribute('data-share-url') || window.location.href;
            const title = button.getAttribute('data-share-title') || document.title;
            if (navigator.share) {
                try {
                    await navigator.share({ title, url });
                    return;
                } catch (error) {
                    if (error && error.name === 'AbortError') {
                        return;
                    }
                }
            }

            const label = button.querySelector('[data-copy-label]');
            const original = label ? label.textContent : '';
            const ok = await copyText(url);
            if (label) {
                label.textContent = ok ? 'Link copied' : 'Copy failed';
                window.setTimeout(() => { label.textContent = original; }, 2200);
            }
        });
    });

    // Press "/" to search --------------------------------------------------
    document.addEventListener('keydown', (event) => {
        if (event.key !== '/' || event.metaKey || event.ctrlKey || event.altKey) {
            return;
        }
        const target = event.target;
        if (
            target instanceof HTMLElement &&
            (target.matches('input, textarea, select') || target.isContentEditable)
        ) {
            return;
        }

        const search = document.querySelector('.nav-search input, .hero-search input, .mobile-search input');
        if (search) {
            event.preventDefault();
            search.focus();
        }
    });

    // Confirmation hooks used by admin screens.
    document.querySelectorAll('form[data-confirm]').forEach((form) => {
        form.addEventListener('submit', (event) => {
            const message = form.getAttribute('data-confirm') || 'Are you sure?';
            if (!window.confirm(message)) {
                event.preventDefault();
            }
        });
    });
})();
