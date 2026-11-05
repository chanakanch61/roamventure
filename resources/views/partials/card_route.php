<?php $routeText=implode(' → ',array_column($item['destinations'] ?? [],'name')); if ($routeText): ?><p class="card-route" title="<?= e($routeText) ?>"><?= e($routeText) ?></p><?php endif ?>
