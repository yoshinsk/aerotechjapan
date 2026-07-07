<?php
/**
 * httpdocs/app/views/admin/news.php
 * ニュース一覧を管理画面へ描画します。
 */
?>
<h1>ニュース管理</h1>
<div class="admin-actions"><a class="button" href="<?= e(url('/admin/news-edit')) ?>">ニュースを追加</a></div>
<table class="admin-table">
    <thead><tr><th>日付</th><th>タイトル</th><th>状態</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($posts as $post): ?>
        <tr>
            <td><?= e($post['published_at']) ?></td>
            <td><?= e($post['title_ja']) ?></td>
            <td><?= $post['is_active'] ? '公開' : '非公開' ?></td>
            <td><a href="<?= e(url('/admin/news-edit?id=' . $post['id'])) ?>">編集</a></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
