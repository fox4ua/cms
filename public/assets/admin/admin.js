document.addEventListener('click', function(e){
  const sidebarBtn = e.target.closest('[data-toggle-sidebar]');
  if (sidebarBtn) document.getElementById('cmsSidebar')?.classList.toggle('show');
  const btn = e.target.closest('.cms-sidebar-toggle');
  if (btn) {
    const id = btn.getAttribute('data-target');
    const el = id ? document.getElementById(id) : null;
    if (el) { el.classList.toggle('show'); btn.setAttribute('aria-expanded', el.classList.contains('show') ? 'true' : 'false'); }
  }
});
