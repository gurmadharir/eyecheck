(() => {
  'use strict';

  function init() {
    const btn = document.getElementById('sidebarToggle');
    const backdrop = document.getElementById('sidebarBackdrop');
    const links = document.querySelectorAll('.sidebar .nav-links a');

    // If the page has no sidebar bits, no-op
    if (!btn && !backdrop) return;

    const toggle = (open) => {
      const isOpen = open ?? !document.body.classList.contains('is-sidebar-open');
      document.body.classList.toggle('is-sidebar-open', isOpen);
      if (btn) btn.setAttribute('aria-expanded', String(isOpen));
    };

    btn && btn.addEventListener('click', () => toggle());
    backdrop && backdrop.addEventListener('click', () => toggle(false));
    links.forEach(a => a.addEventListener('click', () => toggle(false)));

    // Close on ESC
    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape') toggle(false);
    });

    // Optional: expose helpers if you ever need to control it manually
    window.SidebarToggle = {
      open: () => toggle(true),
      close: () => toggle(false),
      toggle: () => toggle()
    };
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();