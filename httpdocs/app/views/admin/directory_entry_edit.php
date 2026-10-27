<?php
/**
 * httpdocs/app/views/admin/directory_entry_edit.php
 * 販売代理店または車種別サイトマップ項目を編集するフォームです。
 */
$isSiteMap = $type === 'sitemap';
$base = $isSiteMap ? 'site-map' : 'distributor';
?>
<h1><?= e($title) ?></h1>
<?php if ($saved): ?><div class="notice">保存しました。</div><?php endif; ?>
<form class="admin-form" method="post" action="<?= e(url('/admin/' . $base . '-edit')) ?>">
    <?= csrf_field() ?>
    <input type="hidden" name="id" value="<?= e($entry['id'] ?? '') ?>">
    <label><?= $isSiteMap ? 'メーカー' : '区分' ?><input name="group_name" required value="<?= e($entry['group_name'] ?? ($isSiteMap ? '' : '販売代理店')) ?>"></label>
    <label><?= $isSiteMap ? '車種・型式' : '会社名' ?><input name="title" required value="<?= e($entry['title'] ?? '') ?>"></label>
    <?php if ($isSiteMap): ?>
        <label>ブランド<input name="detail" value="<?= e($entry['detail'] ?? '') ?>"></label>
        <label>商品slug<input name="product_slug" value="<?= e($entry['product_slug'] ?? '') ?>"><small class="muted">公開中の商品slugを指定すると、商品画像と商品ページへのリンクを表示します。</small></label>
    <?php else: ?>
        <label>補足<input name="detail" value="<?= e($entry['detail'] ?? '') ?>"></label>
    <?php endif; ?>
    <label>並び順<input type="number" name="sort_order" value="<?= e($entry['sort_order'] ?? 100) ?>"></label>
    <label class="checkbox-label"><input type="checkbox" name="is_active" value="1" <?= ($entry['is_active'] ?? 1) ? 'checked' : '' ?>> 公開</label>
    <button class="button" type="submit">保存</button>
</form>
