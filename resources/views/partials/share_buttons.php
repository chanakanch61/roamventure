<?php
/**
 * Social share row with brand logos. Expects $share_url (absolute) and $share_title.
 * Copy and native share buttons start hidden and are revealed by share-buttons.js when the browser supports them.
 */
$shareLink = rawurlencode($share_url);
$shareText = rawurlencode($share_title);
$shareTargets = [
    'facebook' => ['Facebook', 'https://www.facebook.com/sharer/sharer.php?u='.$shareLink, 'M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z'],
    'line' => ['LINE', 'https://social-plugins.line.me/lineit/share?url='.$shareLink, 'M19.365 9.863c.349 0 .63.285.63.631 0 .345-.281.63-.63.63H17.61v1.125h1.755c.349 0 .63.283.63.63 0 .344-.281.629-.63.629h-2.386c-.345 0-.627-.285-.627-.629V8.108c0-.345.282-.63.63-.63h2.386c.346 0 .627.285.627.63 0 .349-.281.63-.63.63H17.61v1.125h1.755zm-3.855 3.016c0 .27-.174.51-.432.596-.064.021-.133.031-.199.031-.211 0-.391-.09-.51-.25l-2.443-3.317v2.94c0 .344-.279.629-.631.629-.346 0-.626-.285-.626-.629V8.108c0-.27.173-.51.43-.595.06-.023.136-.033.194-.033.195 0 .375.104.495.254l2.462 3.33V8.108c0-.345.282-.63.63-.63.345 0 .63.285.63.63v4.771zm-5.741 0c0 .344-.282.629-.631.629-.345 0-.627-.285-.627-.629V8.108c0-.345.282-.63.63-.63.346 0 .628.285.628.63v4.771zm-2.466.629H4.917c-.345 0-.63-.285-.63-.629V8.108c0-.345.285-.63.63-.63.348 0 .63.285.63.63v4.141h1.756c.348 0 .629.283.629.63 0 .344-.282.629-.629.629M24 10.314C24 4.943 18.615.572 12 .572S0 4.943 0 10.314c0 4.811 4.27 8.842 10.035 9.608.391.082.923.258 1.058.59.12.301.079.766.038 1.08l-.164 1.02c-.045.301-.24 1.186 1.049.645 1.291-.539 6.916-4.078 9.436-6.975C23.176 14.393 24 12.458 24 10.314'],
    'x' => ['X', 'https://x.com/intent/post?url='.$shareLink.'&text='.$shareText, 'M18.901 1.153h3.68l-8.04 9.19L24 22.846h-7.406l-5.8-7.584-6.638 7.584H.474l8.6-9.83L0 1.154h7.594l5.243 6.932ZM17.61 20.644h2.039L6.486 3.24H4.298Z'],
];
?>
<div class="share-buttons" data-share data-share-url="<?= e($share_url) ?>" data-share-title="<?= e($share_title) ?>">
 <span class="share-label">แชร์</span>
 <?php foreach ($shareTargets as $key => [$name, $href, $path]): ?><a class="share-btn share-<?= $key ?>" href="<?= e($href) ?>" target="_blank" rel="noopener noreferrer" aria-label="แชร์ไปยัง <?= $name ?>" title="<?= $name ?>"><svg viewBox="0 0 24 24" width="16" height="16" aria-hidden="true" focusable="false"><path fill="currentColor" d="<?= $path ?>"/></svg></a><?php endforeach ?>
 <button class="share-btn share-copy" type="button" data-share-copy hidden aria-label="คัดลอกลิงก์" title="คัดลอกลิงก์"><i class="material-icons" aria-hidden="true">link</i></button>
 <button class="share-btn share-native" type="button" data-share-native hidden aria-label="แชร์ผ่านแอปอื่น" title="แชร์ผ่านแอปอื่น"><i class="material-icons" aria-hidden="true">ios_share</i></button>
 <small class="share-status" role="status" aria-live="polite"></small>
</div>
