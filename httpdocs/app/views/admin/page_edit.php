<?php
/**
 * httpdocs/app/views/admin/page_edit.php
 * 固定ページ本文とSEO説明文を編集するフォームです。
 */
?>
<h1><?= e($page ? '固定ページ編集' : '固定ページ追加') ?></h1>
<?php if ($saved): ?><div class="notice">保存しました。</div><?php endif; ?>
<?php $richEditorAttrs = 'data-rich-editor data-rich-ai-endpoint="' . e(url('/admin/ai-clean-html')) . '" data-rich-ai-csrf="' . e(csrf_token()) . '"'; ?>
<form class="admin-form" method="post" action="<?= e(url('/admin/page-edit')) ?>">
    <?= csrf_field() ?>
    <input type="hidden" name="id" value="<?= e($page['id'] ?? '') ?>">
    <label>slug<input name="slug" value="<?= e($page['slug'] ?? '') ?>"></label>
    <label>タイトル（日本語）<input name="title_ja" required value="<?= e($page['title_ja'] ?? '') ?>"></label>
    <label>Title（英語）<input name="title_en" value="<?= e($page['title_en'] ?? '') ?>"></label>
    <label>本文（日本語・HTML可）<textarea name="body_ja" rows="14" <?= $richEditorAttrs ?>><?= e($page['body_ja'] ?? '') ?></textarea></label>
    <label>Body（英語・HTML可）<textarea name="body_en" rows="14" <?= $richEditorAttrs ?>><?= e($page['body_en'] ?? '') ?></textarea></label>
    <label>SEO説明（日本語）<textarea name="meta_description_ja" rows="3"><?= e($page['meta_description_ja'] ?? '') ?></textarea></label>
    <label>SEO description（英語）<textarea name="meta_description_en" rows="3"><?= e($page['meta_description_en'] ?? '') ?></textarea></label>
    <?php
    $translationPairs = [
        ['label' => 'タイトル', 'source' => 'title_ja', 'target' => 'title_en'],
        ['label' => '本文', 'source' => 'body_ja', 'target' => 'body_en'],
        ['label' => 'SEO説明', 'source' => 'meta_description_ja', 'target' => 'meta_description_en'],
    ];
    require APP_ROOT . '/views/admin/partials/translation_helper.php';
    ?>
    <label>並び順<input type="number" name="sort_order" value="<?= e($page['sort_order'] ?? 100) ?>"></label>
    <label><input type="checkbox" name="is_active" value="1" <?= ($page['is_active'] ?? 1) ? 'checked' : '' ?>> 公開</label>
    <button class="button" type="submit">保存</button>
</form>
