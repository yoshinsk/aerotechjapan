<?php
/**
 * httpdocs/app/views/admin/inquiries.php
 * 受信した問い合わせ履歴を管理画面へ表示します。
 */
?>
<h1>問い合わせ</h1>
<table class="admin-table">
    <thead><tr><th>日時</th><th>商品</th><th>名前</th><th>連絡先</th><th>内容</th></tr></thead>
    <tbody>
    <?php foreach ($inquiries as $inquiry): ?>
        <tr>
            <td><?= e($inquiry['created_at']) ?></td>
            <td><?= e($inquiry['product_name'] ?? '-') ?></td>
            <td><?= e($inquiry['name']) ?><br><?= e($inquiry['company']) ?></td>
            <td><?= e($inquiry['email']) ?><br><?= e($inquiry['phone']) ?></td>
            <td><?= nl2br(e($inquiry['message'])) ?></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
