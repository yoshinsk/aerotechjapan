<?php
/**
 * httpdocs/app/views/admin/pages.php
 * 固定ページ一覧を管理画面へ描画します。
 */
?>
<h1>固定ページ管理</h1>
<div class="admin-actions"><a class="button" href="<?= e(url('/admin/page-edit')) ?>">ページを追加</a></div>
<table class="admin-table">
    <thead><tr><th>slug</th><th>タイトル</th><th>状態</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($pages as $page): ?>
        <tr>
            <td><?= e($page['slug']) ?></td>
            <td><?= e($page['title_ja']) ?></td>
            <td><?= $page['is_active'] ? '公開' : '非公開' ?></td>
            <td><a href="<?= e(url('/admin/page-edit?id=' . $page['id'])) ?>">編集</a></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
