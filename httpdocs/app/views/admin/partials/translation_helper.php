<?php
/**
 * httpdocs/app/views/admin/partials/translation_helper.php
 * 日本語入力からAI英訳を実行し、英語欄へ反映する管理画面部品です。
 */

if (empty($translationPairs) || !is_array($translationPairs)) {
    return;
}

$jsonOptions = JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT;
?>
<section class="translation-helper" data-translation-helper data-ai-endpoint="<?= e(url('/admin/ai-translate')) ?>" data-csrf="<?= e(csrf_token()) ?>">
    <div class="translation-helper-head">
        <div>
            <h2>AI英訳補助</h2>
            <p>日本語欄から英語欄とslugを自動生成します。OpenAI APIキーは管理画面の設定で登録できます。</p>
        </div>
        <div class="translation-helper-actions">
            <button class="button" type="button" data-ai-translate>AIで英訳</button>
            <button class="button secondary" type="button" data-translation-build>下書き更新</button>
            <button class="button secondary" type="button" data-translation-apply>下書きを反映</button>
        </div>
    </div>
    <div class="translation-helper-status" data-translation-status aria-live="polite"></div>
    <div class="translation-drafts" data-translation-drafts lang="ja" translate="yes"></div>
    <script type="application/json" data-translation-pairs><?= json_encode($translationPairs, $jsonOptions) ?></script>
</section>
