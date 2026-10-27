<?php
/**
 * httpdocs/app/views/admin/directory_entries.php
 * 販売代理店または車種別サイトマップ項目の管理一覧を表示します。
 */
$isSiteMap = $type === 'sitemap';
$base = $isSiteMap ? 'site-map' : 'distributor';
$addLabel = $isSiteMap ? 'サイトマップ項目を追加' : '販売代理店を追加';
?>
<h1><?= e($title) ?></h1>
<div class="admin-actions"><a class="button" href="<?= e(url('/admin/' . $base . '-edit')) ?>"><?= e($addLabel) ?></a></div>
<table class="admin-table">
    <thead><tr><th><?= $isSiteMap ? 'メーカー' : '区分' ?></th><th><?= $isSiteMap ? '車種・型式' : '会社名' ?></th><?php if ($isSiteMap): ?><th>ブランド</th><th>商品slug</th><?php endif; ?><th>並び順</th><th>状態</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($entries as $entry): ?>
        <tr>
            <td><?= e($entry['group_name']) ?></td><td><?= e($entry['title']) ?></td>
            <?php if ($isSiteMap): ?><td><?= e($entry['detail']) ?></td><td><?= e($entry['product_slug']) ?></td><?php endif; ?>
            <td><?= e($entry['sort_order']) ?></td><td><?= $entry['is_active'] ? '公開' : '非公開' ?></td>
            <td class="admin-row-actions">
                <a href="<?= e(url('/admin/' . $base . '-edit?id=' . $entry['id'])) ?>">編集</a>
                <form method="post" action="<?= e(url('/admin/' . $base . '-delete')) ?>" onsubmit="return confirm('この項目を削除しますか？');">
                    <?= csrf_field() ?><input type="hidden" name="id" value="<?= e($entry['id']) ?>"><button type="submit" class="button danger">削除</button>
                </form>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
