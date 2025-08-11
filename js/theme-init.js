// sets theme before CSS paints
(function () {
  const KEY = 'eyecheck-theme';
  let t = null;
  try { t = localStorage.getItem(KEY); } catch {}
  if (!t && window.matchMedia && matchMedia('(prefers-color-scheme: dark)').matches) t = 'dark';
  document.documentElement.setAttribute('data-theme', t || 'light');
})();
