<?php
/**
 * httpdocs/app/views/admin/price_lists.php
 * ブランド別価格表PDFのアップロード、編集、一覧表示を行います。
 */
$isEdit = !empty($edit);
?>
<h1>価格表リスト</h1>
<?php if ($saved): ?><div class="notice">保存しました。</div><?php endif; ?>
<?php if (isset($_GET['deleted'])): ?><div class="notice">削除しました。</div><?php endif; ?>
<?php if ($error): ?><div class="error-box"><?= e($error) ?></div><?php endif; ?>

<section class="admin-panel">
    <div class="admin-page-head">
        <div>
            <p class="eyebrow">PRICE LIST CMS</p>
            <h2><?= e($isEdit ? '価格表を入れ替え・編集' : '価格表を追加') ?></h2>
        </div>
        <?php if ($isEdit): ?><a class="button secondary" href="<?= e(url('/admin/price-lists')) ?>">新規追加へ</a><?php endif; ?>
    </div>
    <p class="admin-help">まず価格表PDFをアップロードしてください。PDF選択後にAI補助を実行すると、ブランド・タイトル・公開日の候補をフォームへ反映できます。</p>
    <form class="admin-form" method="post" enctype="multipart/form-data" action="<?= e(url('/admin/price-lists')) ?>" data-price-list-form data-price-list-ai-endpoint="<?= e(url('/admin/price-list-ai-assist')) ?>">
        <?= csrf_field() ?>
        <input type="hidden" name="id" value="<?= e($edit['id'] ?? '') ?>">
        <label>ブランド
            <select name="category_id">
                <option value="">未分類</option>
                <?php foreach ($categories as $category): ?>
                    <option value="<?= e($category['id']) ?>" <?= (int)($edit['category_id'] ?? 0) === (int)$category['id'] ? 'selected' : '' ?>>
                        <?= e($category['name_ja']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>タイトル（日本語）
            <input name="title_ja" required value="<?= e($edit['title_ja'] ?? '') ?>" placeholder="例: RANDO 価格表 2026">
        </label>
        <label>Title（英語）
            <input name="title_en" value="<?= e($edit['title_en'] ?? '') ?>">
        </label>
        <label><?= e($isEdit ? 'PDFを入れ替える' : '価格表PDFをアップロード') ?>
            <input type="file" name="pdf" accept=".pdf,application/pdf">
        </label>
        <?php if (!empty($edit['pdf_path'])): ?>
            <p class="admin-help">現在のPDF: <a href="<?= e(media_url($edit['pdf_path'])) ?>" target="_blank" rel="noopener"><?= e($edit['pdf_path']) ?></a>。PDF未選択で保存すると現在のPDFを保持します。</p>
        <?php endif; ?>
        <div class="price-list-ai-box">
            <button class="button secondary" type="button" data-price-list-ai-assist>PDFから入力補助</button>
            <p class="admin-help" data-price-list-ai-status>PDFを選択するとAIで登録項目を推定できます。</p>
        </div>
        <label>公開日
            <input type="date" name="published_at" value="<?= e($edit['published_at'] ?? date('Y-m-d')) ?>">
        </label>
        <label>並び順
            <input type="number" name="sort_order" value="<?= e($edit['sort_order'] ?? 100) ?>">
        </label>
        <label><input type="checkbox" name="is_active" value="1" <?= ($edit['is_active'] ?? 1) ? 'checked' : '' ?>> 公開</label>
        <button class="button" type="submit">保存</button>
    </form>
</section>

<section class="admin-panel">
    <h2>登録済み価格表</h2>
    <p class="admin-help">既存価格表のPDFを差し替える場合は「入れ替え・編集」から開き、新しいPDFをアップロードして保存します。新しい価格表は上の「価格表を追加」フォームから登録します。</p>
    <div class="table-responsive">
        <table class="table table-hover align-middle admin-table">
            <thead><tr><th>ID</th><th>ブランド</th><th>タイトル</th><th>PDF</th><th>状態</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($priceLists as $priceList): ?>
                <tr>
                    <td><?= e($priceList['id']) ?></td>
                    <td><?= e($priceList['category_name_ja'] ?? '未分類') ?></td>
                    <td><?= e($priceList['title_ja']) ?></td>
                    <td><a href="<?= e(media_url($priceList['pdf_path'])) ?>" target="_blank" rel="noopener">開く</a></td>
                    <td><span class="badge <?= !empty($priceList['is_active']) ? 'text-bg-success' : 'text-bg-secondary' ?>"><?= !empty($priceList['is_active']) ? '公開' : '非公開' ?></span></td>
                    <td class="text-end">
                        <a class="btn btn-sm btn-outline-info" href="<?= e(url('/admin/price-lists?id=' . $priceList['id'])) ?>">入れ替え・編集</a>
                        <form class="d-inline" method="post" action="<?= e(url('/admin/price-list-delete')) ?>" onsubmit="return confirm('この価格表PDFを削除します。よろしいですか？');">
                            <?= csrf_field() ?>
                            <input type="hidden" name="id" value="<?= e($priceList['id']) ?>">
                            <button class="btn btn-sm btn-outline-danger" type="submit">削除</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$priceLists): ?>
                <tr><td class="text-center muted" colspan="6">価格表PDFはまだ登録されていません。</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
