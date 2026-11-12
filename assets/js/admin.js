(() => {
    'use strict';

    const sidebar = document.getElementById('adminSidebar');
    const toggle = document.querySelector('[data-admin-menu]');

    if (sidebar && toggle) {
        const setOpen = (open) => {
            sidebar.classList.toggle('is-open', open);
            document.body.classList.toggle('admin-nav-open', open);
            toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        };

        toggle.addEventListener('click', () => {
            setOpen(!sidebar.classList.contains('is-open'));
        });

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && sidebar.classList.contains('is-open')) {
                setOpen(false);
                toggle.focus();
            }
        });

        document.addEventListener('click', (event) => {
            if (
                sidebar.classList.contains('is-open') &&
                !sidebar.contains(event.target) &&
                !toggle.contains(event.target)
            ) {
                setOpen(false);
            }
        });

        window.addEventListener('resize', () => {
            if (window.innerWidth > 980) {
                setOpen(false);
            }
        });
    }

    // Keep the sidebar in view while scrolling long admin tables on desktop.
    if (sidebar) {
        const active = sidebar.querySelector('.admin-nav-link.is-active');
        if (active && typeof active.scrollIntoView === 'function') {
            active.scrollIntoView({ block: 'nearest' });
        }
    }
})();
