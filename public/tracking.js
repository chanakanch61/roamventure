(() => {
 'use strict';
 if (window.rvTracking) return;
 const node = document.getElementById('rv-tracking-config');
 if (!node) return;
 let config;
 try { config = JSON.parse(node.textContent); } catch (_) { return; }
 const storageKey = 'rv.tracking.consent.v1';
 const banner = document.getElementById('tracking-consent');
 const preferences = document.getElementById('tracking-preferences');
 let choice = null, started = false, checkout = false;
 try { const saved = JSON.parse(localStorage.getItem(storageKey)); if (saved && saved.expires > Date.now()) choice = saved.choice; } catch (_) {}
 window.dataLayer = window.dataLayer || [];
 const consent = (command, state) => {
  function push() { window.dataLayer.push(arguments); }
  push('consent', command, {ad_storage:state, analytics_storage:state, ad_user_data:state, ad_personalization:state});
 };
 consent('default', 'denied');
 const cleanUrl = location.origin + location.pathname;
 const publicReferrer = (() => { try { const u = new URL(document.referrer); return /\/(?:admin|booking|passports)(?:\/|$)/i.test(u.pathname) ? '' : u.origin + u.pathname; } catch (_) { return ''; } })();
 // SDKs read the address themselves: skip addresses containing arbitrary user input.
 // Route cards link with ?program_type=, whose values are a fixed list rather than input.
 const safeAddress = [...new URLSearchParams(location.search)].every(([key, value]) =>
  key === 'program_type' ? /^(?:fixed|flight|land|private|all)$/.test(value)
   : /^(?:utm_source|utm_medium|utm_campaign|utm_content|utm_term|gclid|fbclid|msclkid|ttclid)$/.test(key) ? /^[a-zA-Z0-9_.~-]{1,200}$/.test(value)
   : /^(?:departure|page)$/.test(key) && /^\d{1,10}$/.test(value));
 const safeReferrer = !/\/(?:admin|booking|passports)(?:\/|$)/i.test(document.referrer) && !/[?#]/.test(document.referrer.replace(/\?program_type=(?:fixed|flight|land|private|all)$/, ''));
 const load = (name, src) => {
  if (document.getElementById('rv-tag-' + name)) return;
  const script = document.createElement('script'); script.id = 'rv-tag-' + name; script.async = true; script.src = src;
  document.head.appendChild(script);
 };
 const items = config.context?.items || [];
 function event(name, extra = {}) {
  if (choice !== 'granted' || !started) return;
  window.dataLayer.push({ecommerce:null});
  window.dataLayer.push({event:name, page_location:cleanUrl, page_referrer:publicReferrer, page_type:config.context.page_type, ...extra});
  if (config.mode !== 'direct') return;
  const product = extra.ecommerce?.items || items;
  const meta = {content_ids:product.map(item => item.item_id), content_type:'product'};
  if (extra.contact_channel) meta.contact_channel = extra.contact_channel;
  if (extra.currency) meta.currency = extra.currency;
  if (Number.isFinite(extra.value)) meta.value = extra.value;
  const standard = {page_view:'PageView',view_item:'ViewContent',begin_checkout:'InitiateCheckout',contact_click:'Contact',generate_lead:'Lead'}[name];
  if (config.meta_id && window.fbq) {
   if (standard) window.fbq('trackSingle', config.meta_id, standard, meta, extra.event_id ? {eventID:extra.event_id} : {});
   else window.fbq('trackSingleCustom', config.meta_id, name, {});
  }
  if (config.line_id && window._lt) {
   if (name === 'page_view') window._lt('send', 'pv', [config.line_id]);
   else window._lt('send', 'cv', {type:name === 'generate_lead' ? 'Conversion' : ({view_item:'ViewItem',begin_checkout:'BeginCheckout',contact_click:'ContactClick',booking_submit:'BookingSubmit',search:'Search'}[name] || name)}, [config.line_id]);
  }
 }
 function start() {
  if (started || choice !== 'granted' || !safeAddress || !safeReferrer) return;
  started = true;
  consent('update', 'granted');
  if (config.mode === 'gtm' && config.gtm_id) {
   window.dataLayer.push({'gtm.start':Date.now(),event:'gtm.js'});
   load('gtm', 'https://www.googletagmanager.com/gtm.js?id=' + encodeURIComponent(config.gtm_id));
  } else if (config.mode === 'direct') {
   if (config.meta_id) {
    if (!window.fbq) {
     const fbq = window.fbq = function() { fbq.callMethod ? fbq.callMethod.apply(fbq, arguments) : fbq.queue.push(arguments); };
     window._fbq = fbq; fbq.push = fbq; fbq.loaded = true; fbq.version = '2.0'; fbq.queue = [];
    }
    window.fbq('consent', 'grant');
    window.fbq('set', 'autoConfig', false, config.meta_id);
    window.fbq('init', config.meta_id);
    load('meta', 'https://connect.facebook.net/en_US/fbevents.js');
   }
   if (config.line_id) {
    window._ltq = window._ltq || [];
    window._lt = window._lt || function() { window._ltq.push(arguments); };
    window._lt('init', {customerType:'lap',tagId:config.line_id});
    load('line', 'https://d.line-scdn.net/n/line_tag/public/release/v1/lt.js');
   }
  }
  event('page_view');
  if (items.length) event('view_item', {ecommerce:{items}});
  if (config.lead) {
   const key = 'rv.tracking.lead.' + config.lead.event_id;
   let seen = false;
   try { seen = sessionStorage.getItem(key) === '1'; } catch (_) {}
   if (!seen) {
    event('generate_lead', {event_id:config.lead.event_id,currency:config.lead.currency,value:config.lead.value,ecommerce:{currency:config.lead.currency,value:config.lead.value,items:config.lead.items}});
    try { sessionStorage.setItem(key, '1'); } catch (_) {}
   }
  }
 }
 function choose(value) {
  const hadStarted = started;
  choice = value;
  try { localStorage.setItem(storageKey, JSON.stringify({choice, expires:Date.now() + 180 * 86400000})); } catch (_) {}
  if (banner) banner.hidden = true;
  if (value === 'granted') start();
  else {
   consent('update', 'denied');
   if (window.fbq) window.fbq('consent', 'revoke');
   // Reload to stop all previously loaded vendor libraries after withdrawal.
   if (hadStarted) location.reload();
  }
 }
 window.rvTracking = {get consent() { return choice; },get active() { return started && choice === 'granted'; }};
 document.querySelectorAll('[data-tracking-consent]').forEach(button => button.addEventListener('click', () => choose(button.dataset.trackingConsent)));
 preferences?.addEventListener('click', () => { if (banner) { banner.hidden = false; banner.querySelector('button')?.focus(); } });
 if (banner) banner.hidden = choice === 'granted' || choice === 'denied';
 document.addEventListener('click', e => {
  const link = e.target.closest('a[href]');
  if (!link) return;
  let url; try { url = new URL(link.href); } catch (_) { return; }
  const host = url.hostname.toLowerCase();
  const channel = url.protocol === 'tel:' ? 'phone' : url.protocol === 'mailto:' ? 'email'
   : /(^|\.)(?:line\.me|lin\.ee)$/.test(host) ? 'line'
    : /(^|\.)(?:facebook\.com|fb\.com)$/.test(host) ? 'facebook'
     : /(^|\.)(?:m\.me|messenger\.com)$/.test(host) ? 'messenger' : '';
  if (channel) event('contact_click', {contact_channel:channel});
 });
 document.addEventListener('rv:contact', e => { if (['line','email'].includes(e.detail?.channel)) event('contact_click', {contact_channel:e.detail.channel}); });
 const form = document.getElementById('booking-form');
 form?.addEventListener('change', () => {
  if (!checkout && form.elements.departure_id?.value && form.elements.quote?.value && window.rvTracking.active) {
   event('begin_checkout', {ecommerce:{items}}); checkout = true;
  }
 });
 document.addEventListener('submit', e => {
  if (!e.target.checkValidity()) return;
  if (e.target === form) event('booking_submit', {ecommerce:{items}});
  else if (e.target.id !== 'rv-trip-form' && e.target.querySelector('[name=q]') && e.target.method.toLowerCase() === 'get') event('search');
 });
 start();
})();
