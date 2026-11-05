<?php if (empty($tracking)) return; ?>
<script type="application/json" id="rv-tracking-config"><?= json_encode($tracking,JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_UNESCAPED_UNICODE|JSON_INVALID_UTF8_SUBSTITUTE) ?></script>
<script src="<?= base_url().'public/tracking.js?v='.filemtime(base_path()."/".'public/tracking.js') ?>" defer></script>
<link rel="stylesheet" href="<?= base_url().'public/tracking.css?v='.filemtime(base_path()."/".'public/tracking.css') ?>">
