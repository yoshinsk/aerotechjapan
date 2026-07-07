<?php
/**
 * httpdocs/app/views/admin/settings.php
 * CMS全体設定とOpenAI API連携設定を編集する管理画面です。
 */

$openaiApiKeySaved = trim((string)($settings['openai_api_key'] ?? '')) !== '';
$openaiApiKeyTail = $openaiApiKeySaved ? mb_substr((string)$settings['openai_api_key'], -4) : '';
?>
<h1>設定</h1>
<?php if ($saved): ?><div class="notice">保存しました。</div><?php endif; ?>

<section class="admin-panel">
    <h2>AI英訳設定</h2>
    <p class="admin-help">OpenAI APIキーは英語入力補助の「AIで英訳」で使用します。キー全体は画面に再表示しません。</p>
    <form class="admin-form" method="post" action="<?= e(url('/admin/settings')) ?>">
        <?= csrf_field() ?>
        <label>OpenAI APIキー
            <input type="password" name="openai_api_key" value="" autocomplete="new-password" placeholder="<?= $openaiApiKeySaved ? e('保存済み: ****' . $openaiApiKeyTail) : 'sk-...' ?>">
        </label>
        <?php if ($openaiApiKeySaved): ?>
            <label class="checkbox-label">
                <input type="checkbox" name="clear_openai_api_key" value="1">
                OpenAI APIキーを削除
            </label>
        <?php endif; ?>
        <label>使用モデル
            <input name="openai_model" value="<?= e($settings['openai_model'] ?? 'gpt-5.4-mini') ?>">
        </label>
        <label>推論量
            <select name="openai_reasoning_effort">
                <?php foreach (['low', 'medium', 'high'] as $effort): ?>
                    <option value="<?= e($effort) ?>" <?= ($settings['openai_reasoning_effort'] ?? 'low') === $effort ? 'selected' : '' ?>><?= e($effort) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <div class="admin-actions">
            <button class="button" type="submit">保存</button>
        </div>
    </form>
</section>
