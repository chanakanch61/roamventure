<section class="section container channel-links" aria-labelledby="channel-links-title">
 <h2 id="channel-links-title">หาการเดินทางแบบอื่นอยู่ไหม</h2>
 <div class="channel-links-list"><?php foreach (program_channels() as $channelKey=>$channelLink): if ($channelKey===$listing_type) continue; ?><a href="<?= e(base_url().$channelLink['path']) ?>"><span><b><?= e($channelLink['label']) ?></b><small><?= e($channelLink['blurb']) ?></small></span><i class="material-icons" aria-hidden="true">north_east</i></a><?php endforeach ?></div>
</section>
