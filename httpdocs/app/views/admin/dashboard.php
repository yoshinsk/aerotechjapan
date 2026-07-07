<?php
/**
 * httpdocs/app/views/admin/dashboard.php
 * CMSの主要件数と直近問い合わせを表示します。
 */
?>
<h1>ダッシュボード</h1>
<div class="grid">
    <?php foreach ($counts as $label => $count): ?>
        <div class="admin-card card-body">
            <div class="card-meta"><?= e($label) ?></div>
            <h2><?= e((string)$count) ?></h2>
        </div>
    <?php endforeach; ?>
</div>
<section class="admin-panel" style="margin-top:18px;">
    <h2>直近の問い合わせ</h2>
    <table class="admin-table">
        <thead><tr><th>日時</th><th>商品</th><th>名前</th><th>メール</th></tr></thead>
        <tbody>
        <?php foreach ($inquiries as $inquiry): ?>
            <tr>
                <td><?= e($inquiry['created_at']) ?></td>
                <td><?= e($inquiry['product_name'] ?? '-') ?></td>
                <td><?= e($inquiry['name']) ?></td>
                <td><?= e($inquiry['email']) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</section>
