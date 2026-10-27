<?php
/**
 * httpdocs/app/views/admin/inquiries.php
 * 受信した問い合わせ履歴を管理画面へ表示します。
 */
?>
<header class="admin-page-head">
    <div>
        <p class="eyebrow">INQUIRIES</p>
        <h1>問い合わせ</h1>
        <p class="admin-help"><?= e(count($inquiries)) ?>件を表示しています。</p>
    </div>
</header>
<?php if ($deleted): ?><div class="notice">問い合わせ履歴を削除しました。</div><?php endif; ?>
<?php if ($inquiries): ?>
    <div class="inquiry-list">
        <?php foreach ($inquiries as $inquiry): ?>
            <?php
            $message = trim((string)$inquiry['message']);
            $excerpt = mb_strimwidth($message, 0, 150, '…', 'UTF-8');
            ?>
            <article class="inquiry-card">
                <div class="inquiry-card-head">
                    <time datetime="<?= e($inquiry['created_at']) ?>"><?= e($inquiry['created_at']) ?></time>
                    <?php if (($inquiry['locale'] ?? 'ja') !== 'ja'): ?><span class="badge text-bg-secondary"><?= e(strtoupper((string)$inquiry['locale'])) ?></span><?php endif; ?>
                </div>
                <div class="inquiry-card-main">
                    <h2><?= e($inquiry['name']) ?></h2>
                    <?php if ($inquiry['company'] !== ''): ?><p class="inquiry-company"><?= e($inquiry['company']) ?></p><?php endif; ?>
                    <p class="inquiry-product"><?= e($inquiry['product_name'] ?: '商品指定なし') ?></p>
                    <p class="inquiry-message"><?= e($excerpt) ?></p>
                </div>
                <dl class="inquiry-contact">
                    <div><dt>メール</dt><dd><a href="mailto:<?= e($inquiry['email']) ?>"><?= e($inquiry['email']) ?></a></dd></div>
                    <?php if ($inquiry['phone'] !== ''): ?><div><dt>電話</dt><dd><a href="tel:<?= e($inquiry['phone']) ?>"><?= e($inquiry['phone']) ?></a></dd></div><?php endif; ?>
                    <?php if ($inquiry['country'] !== ''): ?><div><dt>国・地域</dt><dd><?= e($inquiry['country']) ?></dd></div><?php endif; ?>
                </dl>
                <div class="admin-row-actions inquiry-actions">
                    <a class="btn btn-sm btn-outline-info" href="<?= e(url('/admin/inquiry?id=' . $inquiry['id'])) ?>">詳細を見る</a>
                    <form method="post" action="<?= e(url('/admin/inquiry-delete')) ?>" onsubmit="return confirm('この問い合わせ履歴を完全に削除します。元に戻せません。よろしいですか？');">
                        <?= csrf_field() ?><input type="hidden" name="id" value="<?= e($inquiry['id']) ?>"><button type="submit" class="btn btn-sm btn-outline-danger">削除</button>
                    </form>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
<?php else: ?>
    <p class="muted">問い合わせ履歴はありません。</p>
<?php endif; ?>
