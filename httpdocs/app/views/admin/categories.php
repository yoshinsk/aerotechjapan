<?php
/**
 * httpdocs/app/views/admin/categories.php
 * 商品カテゴリ兼ブランドの一覧、編集フォーム、ブランドロゴアップロードを描画します。
 */
?>
<h1>カテゴリ・ブランド管理</h1>
<?php if ($saved): ?><div class="notice">保存しました。</div><?php endif; ?>
<section class="admin-panel">
    <form class="admin-form" method="post" enctype="multipart/form-data" action="<?= e(url('/admin/categories')) ?>">
        <?= csrf_field() ?>
        <input type="hidden" name="id" value="<?= e($edit['id'] ?? '') ?>">
        <label>カテゴリ名・ブランド名（日本語）<input name="name_ja" required value="<?= e($edit['name_ja'] ?? '') ?>"></label>
        <label>カテゴリ名・ブランド名（英語）<input name="name_en" value="<?= e($edit['name_en'] ?? '') ?>"></label>
        <label>slug<input name="slug" value="<?= e($edit['slug'] ?? '') ?>"></label>
        <label>説明（日本語）<textarea name="description_ja" rows="3"><?= e($edit['description_ja'] ?? '') ?></textarea></label>
        <label>Description（英語）<textarea name="description_en" rows="3"><?= e($edit['description_en'] ?? '') ?></textarea></label>
        <?php if (!empty($edit['logo_path'])): ?>
            <div class="brand-logo-preview">
                <span>現在のロゴ</span>
                <img src="<?= e(media_url($edit['logo_path'])) ?>" alt="" loading="lazy">
            </div>
        <?php endif; ?>
        <label>ブランドロゴ
            <input type="file" name="logo" accept="image/*">
        </label>
        <?php
        $translationPairs = [
            ['label' => 'カテゴリ名', 'source' => 'name_ja', 'target' => 'name_en'],
            ['label' => '説明', 'source' => 'description_ja', 'target' => 'description_en'],
        ];
        require APP_ROOT . '/views/admin/partials/translation_helper.php';
        ?>
        <label>並び順<input type="number" name="sort_order" value="<?= e($edit['sort_order'] ?? 100) ?>"></label>
        <label><input type="checkbox" name="is_active" value="1" <?= ($edit['is_active'] ?? 1) ? 'checked' : '' ?>> 有効</label>
        <button class="button" type="submit">保存</button>
    </form>
</section>
<table class="admin-table">
    <thead><tr><th>ID</th><th>ロゴ</th><th>名前</th><th>slug</th><th>並び</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($categories as $category): ?>
        <tr>
            <td><?= e($category['id']) ?></td>
            <td>
                <?php if (!empty($category['logo_path'])): ?>
                    <img class="admin-brand-logo-thumb" src="<?= e(media_url($category['logo_path'])) ?>" alt="" loading="lazy">
                <?php endif; ?>
            </td>
            <td><?= e($category['name_ja']) ?></td>
            <td><?= e($category['slug']) ?></td>
            <td><?= e($category['sort_order']) ?></td>
            <td><a href="<?= e(url('/admin/categories?id=' . $category['id'])) ?>">編集</a></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
