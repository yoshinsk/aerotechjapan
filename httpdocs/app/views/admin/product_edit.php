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
<?php $richEditorAttrs = 'data-rich-editor data-rich-ai-endpoint="' . e(url('/admin/ai-clean-html')) . '" data-rich-ai-csrf="' . e(csrf_token()) . '"'; ?>
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
        <textarea name="summary_ja" rows="3" <?= $richEditorAttrs ?>><?= e($product['summary_ja'] ?? '') ?></textarea>
    </label>
    <label>Summary（英語）
        <textarea name="summary_en" rows="3" <?= $richEditorAttrs ?>><?= e($product['summary_en'] ?? '') ?></textarea>
    </label>
    <label>補足（日本語）
        <textarea name="notes_ja" rows="3" <?= $richEditorAttrs ?>><?= e($product['notes_ja'] ?? '') ?></textarea>
    </label>
    <label>Notes（英語）
        <textarea name="notes_en" rows="3" <?= $richEditorAttrs ?>><?= e($product['notes_en'] ?? '') ?></textarea>
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
    <?php
    $specRows = $specs ?? [];
    if (!$specRows) {
        $specRows = [['label_ja' => '', 'value_ja' => '', 'label_en' => '', 'value_en' => '']];
    }
    $renderSpecEditorCell = static function (mixed $value): string {
        $value = (string)$value;
        if ($value === '') {
            return '';
        }
        return $value !== strip_tags($value) ? sanitize_rich_html($value) : e($value);
    };
    ?>
    <section class="admin-panel product-spec-editor" data-spec-editor data-spec-ai-endpoint="<?= e(url('/admin/ai-clean-html')) ?>" data-spec-ai-csrf="<?= e(csrf_token()) ?>">
        <div class="spec-editor-head">
            <div>
                <h2>SPEC</h2>
                <p class="admin-help">各セルを直接編集できます。文字色・太字・リンク・AI整形は、編集したいセルをクリックしてから使ってください。</p>
            </div>
            <button class="button secondary" type="button" data-spec-add-row>行を追加</button>
        </div>
        <div class="spec-editor-toolbar" data-spec-toolbar>
            <button class="button secondary" type="button" data-spec-command="bold">B</button>
            <button class="button secondary" type="button" data-spec-command="italic">I</button>
            <button class="button secondary" type="button" data-spec-command="insertUnorderedList">箇条書き</button>
            <button class="button secondary" type="button" data-spec-link>リンク</button>
            <label class="rich-editor-color">文字色<input type="color" value="#e12d2d" data-spec-color></label>
            <button class="button secondary" type="button" data-spec-apply-color>文字色を適用</button>
            <button class="button secondary" type="button" data-spec-ai-clean>AIでセルHTML整形</button>
            <span class="rich-editor-status" data-spec-status>セルをクリックして編集してください。</span>
        </div>
        <div class="spec-editor-table" data-spec-rows>
            <div class="spec-editor-row spec-editor-row-head">
                <span>日本語ラベル</span>
                <span>日本語値</span>
                <span>英語ラベル</span>
                <span>英語値</span>
                <span>操作</span>
            </div>
            <?php foreach ($specRows as $spec): ?>
                <article class="spec-editor-row" data-spec-row>
                    <div class="spec-editor-cell">
                        <span class="spec-editor-cell-label">日本語ラベル</span>
                        <textarea name="spec_label_ja[]" hidden data-spec-source><?= e($spec['label_ja'] ?? '') ?></textarea>
                        <div class="spec-rich-field rich-content" contenteditable="true" role="textbox" aria-multiline="true" data-spec-field><?= $renderSpecEditorCell($spec['label_ja'] ?? '') ?></div>
                    </div>
                    <div class="spec-editor-cell">
                        <span class="spec-editor-cell-label">日本語値</span>
                        <textarea name="spec_value_ja[]" hidden data-spec-source><?= e($spec['value_ja'] ?? '') ?></textarea>
                        <div class="spec-rich-field rich-content" contenteditable="true" role="textbox" aria-multiline="true" data-spec-field><?= $renderSpecEditorCell($spec['value_ja'] ?? '') ?></div>
                    </div>
                    <div class="spec-editor-cell">
                        <span class="spec-editor-cell-label">英語ラベル</span>
                        <textarea name="spec_label_en[]" hidden data-spec-source><?= e($spec['label_en'] ?? '') ?></textarea>
                        <div class="spec-rich-field rich-content" contenteditable="true" role="textbox" aria-multiline="true" data-spec-field><?= $renderSpecEditorCell($spec['label_en'] ?? '') ?></div>
                    </div>
                    <div class="spec-editor-cell">
                        <span class="spec-editor-cell-label">英語値</span>
                        <textarea name="spec_value_en[]" hidden data-spec-source><?= e($spec['value_en'] ?? '') ?></textarea>
                        <div class="spec-rich-field rich-content" contenteditable="true" role="textbox" aria-multiline="true" data-spec-field><?= $renderSpecEditorCell($spec['value_en'] ?? '') ?></div>
                    </div>
                    <div class="spec-editor-actions">
                        <button class="button secondary" type="button" data-spec-move-up>上へ</button>
                        <button class="button secondary" type="button" data-spec-move-down>下へ</button>
                        <button class="button danger" type="button" data-spec-remove>削除</button>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
        <template data-spec-template>
            <article class="spec-editor-row" data-spec-row>
                <div class="spec-editor-cell">
                    <span class="spec-editor-cell-label">日本語ラベル</span>
                    <textarea name="spec_label_ja[]" hidden data-spec-source></textarea>
                    <div class="spec-rich-field rich-content" contenteditable="true" role="textbox" aria-multiline="true" data-spec-field></div>
                </div>
                <div class="spec-editor-cell">
                    <span class="spec-editor-cell-label">日本語値</span>
                    <textarea name="spec_value_ja[]" hidden data-spec-source></textarea>
                    <div class="spec-rich-field rich-content" contenteditable="true" role="textbox" aria-multiline="true" data-spec-field></div>
                </div>
                <div class="spec-editor-cell">
                    <span class="spec-editor-cell-label">英語ラベル</span>
                    <textarea name="spec_label_en[]" hidden data-spec-source></textarea>
                    <div class="spec-rich-field rich-content" contenteditable="true" role="textbox" aria-multiline="true" data-spec-field></div>
                </div>
                <div class="spec-editor-cell">
                    <span class="spec-editor-cell-label">英語値</span>
                    <textarea name="spec_value_en[]" hidden data-spec-source></textarea>
                    <div class="spec-rich-field rich-content" contenteditable="true" role="textbox" aria-multiline="true" data-spec-field></div>
                </div>
                <div class="spec-editor-actions">
                    <button class="button secondary" type="button" data-spec-move-up>上へ</button>
                    <button class="button secondary" type="button" data-spec-move-down>下へ</button>
                    <button class="button danger" type="button" data-spec-remove>削除</button>
                </div>
            </article>
        </template>
    </section>
    <label>SPEC（1行: 日本語ラベル|日本語値|英語ラベル|英語値 / HTML可）
        <textarea name="specs_text" rows="12" data-html-fragment-helper data-fragment-ai-endpoint="<?= e(url('/admin/ai-clean-html')) ?>" data-fragment-ai-csrf="<?= e(csrf_token()) ?>"><?= e($specsText) ?></textarea>
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
        <p class="admin-help">画像は「並び替え」ボタンをドラッグして並び替えできます。キーボードでは矢印キーでも移動できます。</p>
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
