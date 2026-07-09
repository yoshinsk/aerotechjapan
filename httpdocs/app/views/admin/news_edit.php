<?php
/**
 * httpdocs/app/views/admin/news_edit.php
 * ニュース記事の日本語/英語本文と画像を編集します。
 */
?>
<h1><?= e($post ? 'ニュース編集' : 'ニュース追加') ?></h1>
<?php if ($saved): ?><div class="notice">保存しました。</div><?php endif; ?>
<?php $richEditorAttrs = 'data-rich-editor data-rich-ai-endpoint="' . e(url('/admin/ai-clean-html')) . '" data-rich-ai-csrf="' . e(csrf_token()) . '"'; ?>
<form class="admin-form" method="post" enctype="multipart/form-data" action="<?= e(url('/admin/news-edit')) ?>">
    <?= csrf_field() ?>
    <input type="hidden" name="id" value="<?= e($post['id'] ?? '') ?>">
    <label>日付<input type="date" name="published_at" value="<?= e(substr($post['published_at'] ?? date('Y-m-d'), 0, 10)) ?>"></label>
    <label>slug<input name="slug" value="<?= e($post['slug'] ?? '') ?>"></label>
    <label>タイトル（日本語）<input name="title_ja" required value="<?= e($post['title_ja'] ?? '') ?>"></label>
    <label>Title（英語）<input name="title_en" value="<?= e($post['title_en'] ?? '') ?>"></label>
    <label>本文（日本語・HTML可）<textarea name="body_ja" rows="8" <?= $richEditorAttrs ?>><?= e($post['body_ja'] ?? '') ?></textarea></label>
    <label>Body（英語・HTML可）<textarea name="body_en" rows="8" <?= $richEditorAttrs ?>><?= e($post['body_en'] ?? '') ?></textarea></label>
    <?php
    $translationPairs = [
        ['label' => 'タイトル', 'source' => 'title_ja', 'target' => 'title_en'],
        ['label' => '本文', 'source' => 'body_ja', 'target' => 'body_en'],
    ];
    require APP_ROOT . '/views/admin/partials/translation_helper.php';
    ?>
    <label>既存画像パス<input name="image_path" value="<?= e($post['image_path'] ?? '') ?>"></label>
    <label>画像アップロード<input type="file" name="image" accept="image/*"></label>
    <label><input type="checkbox" name="is_active" value="1" <?= ($post['is_active'] ?? 1) ? 'checked' : '' ?>> 公開</label>
    <button class="button" type="submit">保存</button>
</form>
