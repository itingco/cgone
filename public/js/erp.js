(() => {
    const shell = document.querySelector('[data-app-shell]');
    if (!shell) return;
    document.querySelector('[data-sidebar-open]')?.addEventListener('click', () => shell.classList.add('sidebar-open'));
    document.querySelectorAll('[data-sidebar-close]').forEach((node) => node.addEventListener('click', () => shell.classList.remove('sidebar-open')));
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') shell.classList.remove('sidebar-open');
    });
})();
