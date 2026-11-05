(() => {
 'use strict';
 const sidebar = document.getElementById('admin-sidebar');
 if (!sidebar) return;
 const toggle = document.querySelector('.admin-menu-button');
 const close = sidebar.querySelector('.admin-sidebar-close');
 const backdrop = document.querySelector('.admin-sidebar-backdrop');
 const content = document.querySelector('.admin-content');
 const mobile = window.matchMedia('(max-width: 1024px)');
 let opened = false;
 let previousFocus;

 function setOpen(value, restoreFocus = true) {
  opened = value && mobile.matches;
  document.body.classList.toggle('admin-nav-open', opened);
  toggle.setAttribute('aria-expanded', String(opened));
  backdrop.hidden = !opened;
  content.inert = opened;
  sidebar.inert = mobile.matches && !opened;
  if (opened) {
   previousFocus = document.activeElement;
   sidebar.setAttribute('role', 'dialog');
   sidebar.setAttribute('aria-modal', 'true');
   close.focus({preventScroll: true});
   sidebar.querySelector('[aria-current="page"]')?.scrollIntoView({block: 'nearest'});
  } else {
   sidebar.removeAttribute('role');
   sidebar.removeAttribute('aria-modal');
   if (restoreFocus && previousFocus?.isConnected) previousFocus.focus({preventScroll: true});
  }
 }
 toggle.addEventListener('click', () => setOpen(!opened));
 close.addEventListener('click', () => setOpen(false));
 backdrop.addEventListener('click', () => setOpen(false));
 sidebar.querySelectorAll('nav a').forEach(link => link.addEventListener('click', () => setOpen(false)));
 document.addEventListener('keydown', event => {
  if (!opened) return;
  if (event.key === 'Escape') { event.preventDefault(); setOpen(false); }
  if (event.key !== 'Tab') return;
  const items = [...sidebar.querySelectorAll('a[href], button:not([disabled]), [tabindex="0"]')].filter(item => item.getClientRects().length);
  const first = items[0], last = items[items.length - 1];
  if (event.shiftKey && (document.activeElement === first || document.activeElement === sidebar)) { event.preventDefault(); last.focus(); }
  else if (!event.shiftKey && document.activeElement === last) { event.preventDefault(); first.focus(); }
 });
 mobile.addEventListener('change', () => setOpen(false, false));
 window.addEventListener('pageshow', () => setOpen(false, false));
 setOpen(false, false);
})();
document.querySelectorAll('select[data-autosubmit]').forEach(select => select.addEventListener('change', () => { if (select.value) select.form.submit(); }));
