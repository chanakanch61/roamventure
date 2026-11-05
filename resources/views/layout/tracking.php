<?php if (empty($tracking)) return; ?>
<script>
(() => {
 window.dataLayer = window.dataLayer || [];
 function push() { window.dataLayer.push(arguments); }
 const consent = (command, state) => push('consent', command, {ad_storage:state, analytics_storage:state, ad_user_data:state, ad_personalization:state});
 let choice = null;
 try { const saved = JSON.parse(localStorage.getItem('rv.tracking.consent.v1')); if (saved && saved.expires > Date.now()) choice = saved.choice; } catch (_) {}
 consent('default', 'denied');
 if (choice === 'granted') consent('update', 'granted');
 // SDKs read the address themselves: skip addresses containing arbitrary user input.
 // Route cards link with ?program_type=, whose values are a fixed list rather than input.
 const safeAddress = [...new URLSearchParams(location.search)].every(([key, value]) =>
  key === 'program_type' ? /^(?:fixed|flight|land|private|all)$/.test(value)
   : /^(?:utm_source|utm_medium|utm_campaign|utm_content|utm_term|gclid|fbclid|msclkid|ttclid|gtm_debug)$/.test(key) ? /^[a-zA-Z0-9_.~-]{1,200}$/.test(value)
   : /^(?:departure|page)$/.test(key) && /^\d{1,10}$/.test(value));
 const safeReferrer = !/\/(?:admin|booking|passports)(?:\/|$)/i.test(document.referrer) && !/[?#]/.test(document.referrer.replace(/\?program_type=(?:fixed|flight|land|private|all)$/, ''));
 window.rvTrackingSafe = safeAddress && safeReferrer;
<?php if ($tracking['mode'] === 'gtm' && ! empty($tracking['gtm_id'])): ?>
 // Google Consent Mode gates the tags, so the container loads before the visitor chooses unless they already refused.
 if (window.rvTrackingSafe && choice !== 'denied') {
  window.dataLayer.push({'gtm.start':Date.now(),event:'gtm.js'});
  const script = document.createElement('script'); script.id = 'rv-tag-gtm'; script.async = true;
  script.src = 'https://www.googletagmanager.com/gtm.js?id=<?= e($tracking['gtm_id']) ?>';
  document.head.appendChild(script);
 }
<?php endif ?>
})();
</script>
<script type="application/json" id="rv-tracking-config"><?= json_encode($tracking,JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_UNESCAPED_UNICODE|JSON_INVALID_UTF8_SUBSTITUTE) ?></script>
<script src="<?= base_url().'public/tracking.js?v='.filemtime(base_path()."/".'public/tracking.js') ?>" defer></script>
<link rel="stylesheet" href="<?= base_url().'public/tracking.css?v='.filemtime(base_path()."/".'public/tracking.css') ?>">
