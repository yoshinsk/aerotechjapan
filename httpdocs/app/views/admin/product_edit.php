<?php
/**
 * httpdocs/app/views/admin/product_edit.php
 * 商品の基本情報、SPEC、画像アップロードを編集するフォームです。
 */
?>
<h1><?= e($product ? '商品編集' : '商品追加') ?></h1>
<?php if ($saved): ?><div class="notice">保存しました。</div><?php endif; ?>
<?php if ($imageDeleted): ?><div class="notice">画像を削除しました。</div><?php endif; ?>
<form class="admin-form" method="post" enctype="multipart/form-data" action="<?= e(url('/admin/product-edit')) ?>">
    <?= csrf_field() ?>
    <input type="hidden" name="id" value="<?= e($product['id'] ?? '') ?>">
    <label>カテゴリ
        <select name="category_id">
            <option value="">未分類</option>
            <?php foreach ($categories as $category): ?>
                <option value="<?= e($category['id']) ?>" <?= (int)($product['category_id'] ?? 0) === (int)$category['id'] ? 'selected' : '' ?>>
                    <?= e($category['name_ja']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </label>
    <label>slug
        <input name="slug" value="<?= e($product['slug'] ?? '') ?>">
    </label>
    <label>商品名（日本語）
        <input name="name_ja" required value="<?= e($product['name_ja'] ?? '') ?>">
    </label>
    <label>商品名（英語）
        <input name="name_en" value="<?= e($product['name_en'] ?? '') ?>">
    </label>
    <label>適合年式（日本語）
        <input name="model_year_ja" value="<?= e($product['model_year_ja'] ?? '') ?>">
    </label>
    <label>Model year / fitment（英語）
        <input name="model_year_en" value="<?= e($product['model_year_en'] ?? '') ?>">
    </label>
    <label>概要（日本語）
        <textarea name="summary_ja" rows="3"><?= e($product['summary_ja'] ?? '') ?></textarea>
    </label>
    <label>Summary（英語）
        <textarea name="summary_en" rows="3"><?= e($product['summary_en'] ?? '') ?></textarea>
    </label>
    <label>補足（日本語）
        <textarea name="notes_ja" rows="3"><?= e($product['notes_ja'] ?? '') ?></textarea>
    </label>
    <label>Notes（英語）
        <textarea name="notes_en" rows="3"><?= e($product['notes_en'] ?? '') ?></textarea>
    </label>
    <?php
    $translationPairs = [
        ['label' => '商品名', 'source' => 'name_ja', 'target' => 'name_en'],
        ['label' => '適合年式', 'source' => 'model_year_ja', 'target' => 'model_year_en'],
        ['label' => '概要', 'source' => 'summary_ja', 'target' => 'summary_en'],
        ['label' => '補足', 'source' => 'notes_ja', 'target' => 'notes_en'],
    ];
    require APP_ROOT . '/views/admin/partials/translation_helper.php';
    ?>
    <label>SPEC（1行: 日本語ラベル|日本語値|英語ラベル|英語値）
        <textarea name="specs_text" rows="12"><?= e($specsText) ?></textarea>
    </label>
    <label>画像追加
        <input type="file" name="images[]" multiple accept="image/*">
    </label>
    <label>状態
        <select name="status">
            <option value="published" <?= ($product['status'] ?? 'published') === 'published' ? 'selected' : '' ?>>公開</option>
            <option value="draft" <?= ($product['status'] ?? '') === 'draft' ? 'selected' : '' ?>>下書き</option>
        </select>
    </label>
    <label><input type="checkbox" name="is_featured" value="1" <?= !empty($product['is_featured']) ? 'checked' : '' ?>> 注目商品</label>
    <label>並び順
        <input type="number" name="sort_order" value="<?= e($product['sort_order'] ?? 100) ?>">
    </label>
    <div class="admin-actions">
        <button class="button" type="submit">保存</button>
        <?php if ($product): ?><a class="button secondary" target="_blank" href="<?= e(url('/products/' . $product['slug'])) ?>">公開画面</a><?php endif; ?>
    </div>
</form>

<?php if ($product && $images): ?>
    <section class="admin-panel product-image-manager">
        <h2>登録画像</h2>
        <p class="admin-help">削除すると、この商品ページの画像一覧から外れます。旧サイト流用画像の元ファイルは削除しません。</p>
        <div class="grid">
            <?php foreach ($images as $image): ?>
                <article class="admin-card card-body product-image-item">
                    <img src="<?= e(media_url($image['path'])) ?>" alt="" loading="lazy">
                    <small><?= e($image['path']) ?></small>
                    <form method="post" action="<?= e(url('/admin/product-image-delete')) ?>" onsubmit="return confirm('この画像を商品から削除します。よろしいですか？');">
                        <?= csrf_field() ?>
                        <input type="hidden" name="product_id" value="<?= e($product['id']) ?>">
                        <input type="hidden" name="image_id" value="<?= e($image['id']) ?>">
                        <button class="button danger" type="submit">削除</button>
                    </form>
                </article>
            <?php endforeach; ?>
        </div>
    </section>
<?php endif; ?>
