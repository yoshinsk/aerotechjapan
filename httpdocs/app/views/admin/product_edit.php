<?php
/**
 * httpdocs/app/views/admin/product_edit.php
 * 商品の基本情報、SPEC、画像アップロードを編集するフォームです。
 */
?>
<h1><?= e($product ? '商品編集' : '商品追加') ?></h1>
<?php if ($saved): ?><div class="notice">保存しました。</div><?php endif; ?>
<?php if ($imageDeleted): ?><div class="notice">画像を削除しました。</div><?php endif; ?>
<?php if (!empty($imagesSaved)): ?><div class="notice">画像設定を保存しました。</div><?php endif; ?>
<?php if (!empty($restored)): ?><div class="notice">商品を下書きへ復元しました。</div><?php endif; ?>
<?php $isDeleted = ($product['status'] ?? '') === 'deleted'; ?>
<?php if ($isDeleted): ?><div class="error-box">この商品は削除済みです。公開サイトには表示されません。</div><?php endif; ?>
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
            <?php if ($isDeleted): ?><option value="deleted" selected>削除済み</option><?php endif; ?>
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
        <p class="admin-help">画像はドラッグアンドドロップで並び替えできます。アップロード画像を削除・差し替えた場合、生成済みの画像ファイルも更新します。</p>
        <form class="admin-form" method="post" enctype="multipart/form-data" action="<?= e(url('/admin/product-images-update')) ?>">
            <?= csrf_field() ?>
            <input type="hidden" name="product_id" value="<?= e($product['id']) ?>">
            <div class="product-image-sort-grid" data-sortable-images>
            <?php $hasMainImage = (bool)array_filter($images, static fn($item) => !empty($item['is_main'])); ?>
            <?php foreach ($images as $index => $image): ?>
                <article class="admin-card card-body product-image-item" draggable="true" data-image-sort-item>
                    <input type="hidden" name="image_order[]" value="<?= e($image['id']) ?>">
                    <button class="drag-handle" type="button" aria-label="画像をドラッグして並び替え">並び替え</button>
                    <img src="<?= e(image_variant_url($image, 'thumb')) ?>" alt="" loading="lazy">
                    <small><?= e(image_variant_path($image, 'large')) ?></small>
                    <label class="checkbox-label">
                        <input type="radio" name="main_image_id" value="<?= e($image['id']) ?>" <?= (!empty($image['is_main']) || (!$hasMainImage && $index === 0)) ? 'checked' : '' ?>>
                        メイン画像
                    </label>
                    <label>代替テキスト
                        <input name="image_alt[<?= e($image['id']) ?>]" value="<?= e($image['alt_ja'] ?? '') ?>">
                    </label>
                    <label>画像を差し替え
                        <input type="file" name="replace_images[<?= e($image['id']) ?>]" accept="image/*">
                    </label>
                    <label class="checkbox-label image-delete-check">
                        <input type="checkbox" name="delete_image_ids[]" value="<?= e($image['id']) ?>">
                        この画像を削除
                    </label>
                </article>
            <?php endforeach; ?>
            </div>
            <button class="button" type="submit">画像設定を保存</button>
        </form>
    </section>
<?php endif; ?>

<?php if ($product): ?>
    <section class="admin-panel danger-zone">
        <h2>商品削除</h2>
        <p class="admin-help">通常削除は削除済みに移動します。完全削除は商品、SPEC、アップロード画像ファイルを削除します。</p>
        <div class="admin-actions">
            <?php if ($isDeleted): ?>
                <form method="post" action="<?= e(url('/admin/product-delete')) ?>">
                    <?= csrf_field() ?>
                    <input type="hidden" name="product_id" value="<?= e($product['id']) ?>">
                    <input type="hidden" name="delete_action" value="restore">
                    <button class="button secondary" type="submit">下書きへ復元</button>
                </form>
            <?php else: ?>
                <form method="post" action="<?= e(url('/admin/product-delete')) ?>" onsubmit="return confirm('この商品を削除済みに移動します。公開サイトには表示されなくなります。');">
                    <?= csrf_field() ?>
                    <input type="hidden" name="product_id" value="<?= e($product['id']) ?>">
                    <input type="hidden" name="delete_action" value="soft">
                    <button class="button danger" type="submit">削除済みに移動</button>
                </form>
            <?php endif; ?>
            <form method="post" action="<?= e(url('/admin/product-delete')) ?>" onsubmit="return confirm('完全削除します。商品データとアップロード画像ファイルは戻せません。よろしいですか？');">
                <?= csrf_field() ?>
                <input type="hidden" name="product_id" value="<?= e($product['id']) ?>">
                <input type="hidden" name="delete_action" value="permanent">
                <button class="button danger" type="submit">完全削除</button>
            </form>
        </div>
    </section>
<?php endif; ?>
