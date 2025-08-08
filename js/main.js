// =============================
// Reveal-on-scroll animation
// =============================
(function(){
  const items = document.querySelectorAll('.reveal');
  if (!('IntersectionObserver' in window)) {
    items.forEach(el => el.classList.add('in-view'));
    return;
  }
  const io = new IntersectionObserver((entries)=>{
    entries.forEach(entry=>{
      if(entry.isIntersecting){
        entry.target.classList.add('in-view');
        io.unobserve(entry.target);
      }
    });
  }, { rootMargin: '0px 0px -10% 0px', threshold: 0.15 });
  items.forEach(el => io.observe(el));
})();

// =============================
// Light/Dark Theme Toggle
// =============================
(function(){
  const STORAGE_KEY = 'eyecheck-theme';
  const prefersDark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
  const saved = localStorage.getItem(STORAGE_KEY);
  const initial = saved || (prefersDark ? 'dark' : 'light');

  const root = document.documentElement;
  const btn = document.getElementById('themeToggle');
  const icon = () => btn && btn.querySelector('i');

  function apply(theme){
    root.setAttribute('data-theme', theme);
    if(icon()){
      icon().className = theme === 'dark' ? 'bi bi-sun' : 'bi bi-moon-stars';
    }
  }
  apply(initial);

  if(btn){
    btn.addEventListener('click', ()=>{
      const next = (root.getAttribute('data-theme') === 'dark') ? 'light' : 'dark';
      apply(next);
      localStorage.setItem(STORAGE_KEY, next);
    });
  }

  if(!saved && window.matchMedia){
    window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', e=>{
      apply(e.matches ? 'dark' : 'light');
    });
  }
})();
