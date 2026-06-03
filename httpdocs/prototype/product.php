<?php
/**
 * 商品詳細ページ（レスポンシブ／データ駆動）。
 * 旧 garage-file/<商品>.html に相当する画面を、データから自動生成する。
 *
 * 使い方:  product.php?slug=GUN125_HILUX_GR_SPORT
 */

require_once __DIR__ . '/lib/repository.php';
require_once __DIR__ . '/templates/layout.php';

$repo = new ProductRepository();
$slug = $_GET['slug'] ?? '';
$product = $slug !== '' ? $repo->find($slug) : null;

if (!$product) {
    http_response_code(404);
    layout_header('商品が見つかりません');
    echo '<p>指定された商品が見つかりませんでした。</p>';
    echo '<p><a href="index.php">商品一覧へ戻る</a></p>';
    layout_footer();
    exit;
}

$imgBase = SITE_BASE . rtrim($product['image_dir'], '/') . '/';
// 画像ファイルの実体パス（実寸を調べるために使う）。
$fsBase  = __DIR__ . '/../' . rtrim($product['image_dir'], '/') . '/';

/**
 * 画像の実寸（横幅px）を返す。取得できなければ 0。
 * 取得できた場合、その横幅を上限にすることで「元サイズより拡大しない」を保証する。
 */
function image_width(string $path): int
{
    if (!is_file($path)) {
        return 0;
    }
    $size = @getimagesize($path);
    return $size ? (int) $size[0] : 0;
}

layout_header($product['name']);
?>

<div class="d-flex flex-wrap justify-content-between align-items-end mb-3">
    <div>
        <h1 class="product-title mb-0"><?= e($product['name']) ?></h1>
        <div class="product-year"><?= e($product['model_year']) ?></div>
        <div class="text-secondary small"><?= e($product['category']) ?></div>
    </div>
    <a class="btn btn-outline-info btn-sm" href="admin/edit.php?slug=<?= urlencode($product['slug']) ?>">この商品を編集</a>
</div>

<?php if (!empty($product['notes'])): ?>
    <p class="text-secondary"><?= e($product['notes']) ?></p>
<?php endif; ?>

<!-- 画像ギャラリー（縦のリスト）。
     各画像の実寸を getimagesize() で調べ、その横幅を上限にする。
     これにより小さい画像が引き伸ばされることはなく、画面が狭いときだけ縮小される。
     クリックでモーダル（ライトボックス）に拡大画像を表示する。 -->
<div class="gallery mb-4">
    <?php foreach ($product['images'] as $i => $img): ?>
        <?php
            $large = $product['images_large'][$i] ?? $img;
            $w     = image_width($fsBase . $img);
            // 実寸が取れたらその幅を上限に（枠の余白ぶんを少し加味）。取れなければ上限なし。
            $style = $w > 0 ? ' style="max-width:' . ($w + 12) . 'px"' : '';
        ?>
        <a class="gallery-item"<?= $style ?> href="<?= e($imgBase . $large) ?>"
           data-bs-toggle="modal" data-bs-target="#imgModal"
           data-img="<?= e($imgBase . $large) ?>">
            <img src="<?= e($imgBase . $img) ?>" alt="<?= e($product['name']) ?>" loading="lazy">
        </a>
    <?php endforeach; ?>
</div>

<!-- 拡大画像モーダル -->
<div class="modal fade" id="imgModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content bg-dark border-secondary">
            <div class="modal-body p-0 text-center">
                <img id="imgModalTarget" src="" alt="<?= e($product['name']) ?>" class="img-fluid">
            </div>
        </div>
    </div>
</div>
<script>
document.getElementById('imgModal').addEventListener('show.bs.modal', function (ev) {
    var src = ev.relatedTarget.getAttribute('data-img');
    document.getElementById('imgModalTarget').src = src;
});
</script>

<!-- SPEC表 -->
<?php if (!empty($product['specs'])): ?>
    <h2 class="h5">SPEC ＆ Special Thanks</h2>
    <table class="table table-dark table-bordered spec-table">
        <tbody>
        <?php foreach ($product['specs'] as $spec): ?>
            <tr>
                <th><?= e($spec['label'] ?? '') ?></th>
                <td><?= e($spec['value'] ?? '') ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>

<?php
layout_footer();
