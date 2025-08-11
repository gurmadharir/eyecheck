// same style as your index main.js, but safe on any page
(function(){
  const STORAGE_KEY = 'eyecheck-theme';
  const root = document.documentElement;

  function apply(theme){
    root.setAttribute('data-theme', theme);
    // swap icon if the button exists
    const btn = document.getElementById('themeToggle');
    const i = btn ? btn.querySelector('i') : null;
    if (i) i.className = (theme === 'dark') ? 'bi bi-sun' : 'bi bi-moon-stars';
  }

  function init(){
    const prefersDark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
    const saved = (()=>{ try { return localStorage.getItem(STORAGE_KEY); } catch { return null; } })();
    const initial = saved || (prefersDark ? 'dark' : 'light');
    apply(initial);

    const btn = document.getElementById('themeToggle');
    if (btn) {
      btn.addEventListener('click', ()=>{
        const next = (root.getAttribute('data-theme') === 'dark') ? 'light' : 'dark';
        apply(next);
        try { localStorage.setItem(STORAGE_KEY, next); } catch {}
      });
    }

    if(!saved && window.matchMedia){
      const mq = window.matchMedia('(prefers-color-scheme: dark)');
      (mq.addEventListener ? mq.addEventListener.bind(mq) : mq.addListener)( 'change', e=>{
        apply(e.matches ? 'dark' : 'light');
      });
    }
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
