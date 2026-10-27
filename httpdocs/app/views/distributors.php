<?php
/**
 * httpdocs/app/views/distributors.php
 * 公開サイトで販売代理店一覧を表示します。
 */
?>
<section class="section narrow container directory-page">
    <p class="eyebrow">AERO TECH JAPAN</p>
    <h1><?= e(t('販売代理店', 'Distributors')) ?></h1>
    <p class="lead"><?= e(t('AERO TECH JAPAN製品をお取り扱いいただいている販売代理店です。', 'These distributors handle AERO TECH JAPAN products.')) ?></p>
    <?php if ($entries): ?>
        <div class="distributor-list">
            <?php foreach ($entries as $entry): ?>
                <article class="distributor-item">
                    <h2><?= e($entry['title']) ?></h2>
                    <?php if ($entry['detail'] !== ''): ?><p><?= e($entry['detail']) ?></p><?php endif; ?>
                </article>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <p class="muted"><?= e(t('販売代理店情報は準備中です。', 'Distributor information is being prepared.')) ?></p>
    <?php endif; ?>
</section>
