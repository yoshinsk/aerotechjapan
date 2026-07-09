<?php
/**
 * httpdocs/app/routes_admin.php
 * 管理画面側のURLを解決し、CMS編集処理を実行します。
 */

declare(strict_types=1);

$imageService = new ImageService();
$fileService = new FileUploadService();
$legacyImageArchivePath = static fn(): string => HTTPDOCS_ROOT . '/storage/exports/aerotech-legacy-images.zip';
$legacyImageArchiveInfo = static function () use ($legacyImageArchivePath): array {
    $path = $legacyImageArchivePath();
    if (!is_file($path)) {
        return ['exists' => false, 'size' => 0, 'updated_at' => null];
    }
    return [
        'exists' => true,
        'size' => filesize($path) ?: 0,
        'updated_at' => date('Y-m-d H:i:s', filemtime($path) ?: time()),
    ];
};
$openaiConfigForCms = static function () use ($repo): array {
    $openaiConfig = config_value('openai', []);
    $savedApiKey = $repo->setting('openai_api_key');
    $savedModel = $repo->setting('openai_model');
    $savedReasoning = $repo->setting('openai_reasoning_effort');
    if ($savedApiKey !== '') {
        $openaiConfig['api_key'] = $savedApiKey;
    }
    if ($savedModel !== '') {
        $openaiConfig['model'] = $savedModel;
    }
    if ($savedReasoning !== '') {
        $openaiConfig['reasoning_effort'] = $savedReasoning;
    }
    return $openaiConfig;
};

if ($path === '/admin/ai-translate') {
    if (!is_post()) {
        json_response(['ok' => false, 'message' => 'POSTで送信してください。'], 405);
    }

    $csrfToken = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (!is_string($csrfToken) || !csrf_is_valid($csrfToken)) {
        json_response(['ok' => false, 'message' => 'CSRF token mismatch.'], 419);
    }

    $body = json_decode(file_get_contents('php://input') ?: '', true);
    if (!is_array($body)) {
        json_response(['ok' => false, 'message' => 'JSONを解析できませんでした。'], 400);
    }

    $allowedTargets = [
        'name_en', 'model_year_en', 'summary_en', 'notes_en',
        'title_en', 'body_en', 'meta_description_en',
        'description_en',
        'event_name_en',
        'spec_label_en', 'spec_value_en',
    ];
    $fields = [];
    foreach (($body['fields'] ?? []) as $field) {
        if (!is_array($field)) {
            continue;
        }
        $target = trim((string)($field['target'] ?? ''));
        if (!in_array($target, $allowedTargets, true)) {
            continue;
        }
        $fields[] = [
            'label' => trim((string)($field['label'] ?? $target)),
            'target' => $target,
            'source' => trim((string)($field['source'] ?? '')),
        ];
    }

    try {
        $translator = new OpenAITranslator($openaiConfigForCms());
        $result = $translator->translateFields($fields, trim((string)($body['context'] ?? '')));
        json_response(['ok' => true] + $result);
    } catch (Throwable $e) {
        json_response(['ok' => false, 'message' => $e->getMessage()], 400);
    }
}

if ($path === '/admin/ai-clean-html') {
    if (!is_post()) {
        json_response(['ok' => false, 'message' => 'POSTで送信してください。'], 405);
    }

    $csrfToken = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (!is_string($csrfToken) || !csrf_is_valid($csrfToken)) {
        json_response(['ok' => false, 'message' => 'CSRF token mismatch.'], 419);
    }

    $body = json_decode(file_get_contents('php://input') ?: '', true);
    if (!is_array($body)) {
        json_response(['ok' => false, 'message' => 'JSONを解析できませんでした。'], 400);
    }

    try {
        $translator = new OpenAITranslator($openaiConfigForCms());
        $instructionIds = is_array($body['instructions'] ?? null) ? array_values($body['instructions']) : [];
        $html = $translator->cleanHtml((string)($body['html'] ?? ''), trim((string)($body['context'] ?? '')), $instructionIds);
        json_response(['ok' => true, 'html' => sanitize_rich_html($html)]);
    } catch (Throwable $e) {
        json_response(['ok' => false, 'message' => $e->getMessage()], 400);
    }
}

if ($path === '/admin/price-list-ai-assist') {
    if (!is_post()) {
        json_response(['ok' => false, 'message' => 'POSTで送信してください。'], 405);
    }

    verify_csrf();
    if (empty($_FILES['pdf']['name'])) {
        json_response(['ok' => false, 'message' => '価格表PDFを選択してください。'], 400);
    }

    try {
        $translator = new OpenAITranslator($openaiConfigForCms());
        $result = $translator->analyzePriceListPdf($_FILES['pdf'], $repo->categories(false));
        json_response(['ok' => true] + $result);
    } catch (Throwable $e) {
        json_response(['ok' => false, 'message' => $e->getMessage()], 400);
    }
}

if ($path === '/admin/legacy-image-archive-download') {
    $archivePath = $legacyImageArchivePath();
    $exportsRoot = realpath(HTTPDOCS_ROOT . '/storage/exports');
    $archiveRealPath = is_file($archivePath) ? realpath($archivePath) : false;
    if (!$exportsRoot || !$archiveRealPath || !str_starts_with($archiveRealPath, $exportsRoot . DIRECTORY_SEPARATOR)) {
        http_response_code(404);
        admin_render('error', ['message' => '旧サイト画像アーカイブはまだ生成されていません。']);
        return;
    }

    if (ob_get_level() > 0) {
        ob_end_clean();
    }
    header_remove('Content-Type');
    header('Content-Type: application/zip');
    header('Content-Disposition: attachment; filename="aerotech-legacy-images.zip"');
    header('Content-Length: ' . filesize($archiveRealPath));
    header('Cache-Control: private, no-store');
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'HEAD') {
        exit;
    }
    readfile($archiveRealPath);
    exit;
}

if ($path === '/admin') {
    admin_render('dashboard', [
        'user' => $user,
        'counts' => $repo->counts(),
        'inquiries' => array_slice($repo->inquiries(), 0, 5),
        'legacyImageArchive' => $legacyImageArchiveInfo(),
        'title' => 'ダッシュボード',
    ]);
    return;
}

if ($path === '/admin/settings') {
    if (is_post()) {
        verify_csrf();
        $model = trim((string)($_POST['openai_model'] ?? 'gpt-5.4-mini'));
        $reasoningEffort = trim((string)($_POST['openai_reasoning_effort'] ?? 'low'));
        if (!preg_match('/^[a-zA-Z0-9._:-]+$/', $model)) {
            $model = 'gpt-5.4-mini';
        }
        if (!in_array($reasoningEffort, ['low', 'medium', 'high'], true)) {
            $reasoningEffort = 'low';
        }

        if (isset($_POST['clear_openai_api_key'])) {
            $repo->saveSetting('openai_api_key', '');
        } else {
            $apiKey = trim((string)($_POST['openai_api_key'] ?? ''));
            if ($apiKey !== '') {
                $repo->saveSetting('openai_api_key', $apiKey);
            }
        }
        $repo->saveSetting('openai_model', $model);
        $repo->saveSetting('openai_reasoning_effort', $reasoningEffort);
        redirect_to('/admin/settings?saved=1');
    }

    admin_render('settings', [
        'settings' => [
            'openai_api_key' => $repo->setting('openai_api_key'),
            'openai_model' => $repo->setting('openai_model', config_value('openai.model', 'gpt-5.4-mini')),
            'openai_reasoning_effort' => $repo->setting('openai_reasoning_effort', config_value('openai.reasoning_effort', 'low')),
        ],
        'saved' => isset($_GET['saved']),
        'title' => '設定',
    ]);
    return;
}

if ($path === '/admin/products') {
    $categoryFilter = trim((string)($_GET['category'] ?? 'all'));
    $statusFilter = trim((string)($_GET['status'] ?? 'active'));
    if (!in_array($statusFilter, ['active', 'deleted', 'all'], true)) {
        $statusFilter = 'active';
    }
    if ($categoryFilter !== 'all' && $categoryFilter !== 'uncategorized' && (!ctype_digit($categoryFilter) || (int)$categoryFilter < 1)) {
        $categoryFilter = 'all';
    }
    $categories = $repo->categories(false);
    if (ctype_digit($categoryFilter)) {
        $categoryIds = array_map(static fn($category) => (int)$category['id'], $categories);
        if (!in_array((int)$categoryFilter, $categoryIds, true)) {
            $categoryFilter = 'all';
        }
    }
    admin_render('products', [
        'products' => $repo->adminProducts($_GET['q'] ?? null, $categoryFilter, $statusFilter),
        'categories' => $categories,
        'categoryCounts' => $repo->adminProductCategoryCounts($statusFilter),
        'activeCategory' => $categoryFilter,
        'statusFilter' => $statusFilter,
        'keyword' => trim((string)($_GET['q'] ?? '')),
        'title' => '商品管理',
    ]);
    return;
}

if ($path === '/admin/product-image-delete') {
    if (!is_post()) {
        redirect_to('/admin/products');
    }

    verify_csrf();
    $productId = (int)($_POST['product_id'] ?? 0);
    $imageId = (int)($_POST['image_id'] ?? 0);
    if ($productId > 0 && $imageId > 0) {
        $image = $repo->productImage($imageId, $productId);
        if ($repo->deleteProductImage($imageId, $productId) && $image) {
            $imageService->deleteProductImageFiles($image);
        }
    }
    redirect_to('/admin/product-edit?id=' . $productId . '&image_deleted=1');
}

if ($path === '/admin/product-images-update') {
    if (!is_post()) {
        redirect_to('/admin/products');
    }

    verify_csrf();
    $productId = (int)($_POST['product_id'] ?? 0);
    if ($productId < 1 || !$repo->productById($productId)) {
        redirect_to('/admin/products');
    }

    $deleteIds = array_map('intval', (array)($_POST['delete_image_ids'] ?? []));
    foreach ($deleteIds as $imageId) {
        $image = $repo->productImage($imageId, $productId);
        if ($image && $repo->deleteProductImage($imageId, $productId)) {
            $imageService->deleteProductImageFiles($image);
        }
    }

    $replaceFiles = $_FILES['replace_images'] ?? null;
    if (is_array($replaceFiles)) {
        foreach (($replaceFiles['name'] ?? []) as $imageId => $name) {
            $imageId = (int)$imageId;
            if ($imageId < 1 || in_array($imageId, $deleteIds, true)) {
                continue;
            }
            $file = [
                'name' => $name,
                'type' => $replaceFiles['type'][$imageId] ?? '',
                'tmp_name' => $replaceFiles['tmp_name'][$imageId] ?? '',
                'error' => $replaceFiles['error'][$imageId] ?? UPLOAD_ERR_NO_FILE,
                'size' => $replaceFiles['size'][$imageId] ?? 0,
            ];
            $stored = $imageService->storeUploadSet($file, 'products');
            if ($stored) {
                $oldImage = $repo->productImage($imageId, $productId);
                $repo->updateProductImageFiles($imageId, $productId, $stored);
                if ($oldImage && ($oldImage['source_type'] ?? '') === 'upload') {
                    $imageService->deleteProductImageFiles($oldImage);
                }
            }
        }
    }

    $orderedIds = array_values(array_diff(array_map('intval', (array)($_POST['image_order'] ?? [])), $deleteIds));
    $repo->updateProductImages(
        $productId,
        $orderedIds,
        (int)($_POST['main_image_id'] ?? 0),
        (array)($_POST['image_alt'] ?? [])
    );

    redirect_to('/admin/product-edit?id=' . $productId . '&images_saved=1');
}

if ($path === '/admin/product-delete') {
    if (!is_post()) {
        redirect_to('/admin/products');
    }

    verify_csrf();
    $productId = (int)($_POST['product_id'] ?? 0);
    $action = (string)($_POST['delete_action'] ?? 'soft');
    $product = $productId > 0 ? $repo->productById($productId) : null;
    if (!$product) {
        redirect_to('/admin/products');
    }

    if ($action === 'restore') {
        $repo->restoreProduct($productId);
        redirect_to('/admin/product-edit?id=' . $productId . '&restored=1');
    }

    if ($action === 'permanent') {
        foreach ($repo->productImages($productId) as $image) {
            $imageService->deleteProductImageFiles($image);
        }
        $repo->permanentlyDeleteProduct($productId);
        redirect_to('/admin/products?status=deleted&permanent_deleted=1');
    }

    $repo->softDeleteProduct($productId);
    redirect_to('/admin/products?status=deleted&deleted=1');
}

if ($path === '/admin/product-edit') {
    $id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
    $product = $id > 0 ? $repo->productById($id) : null;

    if (is_post()) {
        verify_csrf();
        $payload = [
            'id' => $id ?: null,
            'category_id' => (int)($_POST['category_id'] ?? 0) ?: null,
            'slug' => slugify((string)($_POST['slug'] ?? $_POST['name_ja'] ?? '')),
            'name_ja' => trim((string)($_POST['name_ja'] ?? '')),
            'name_en' => trim((string)($_POST['name_en'] ?? '')),
            'model_year_ja' => trim((string)($_POST['model_year_ja'] ?? '')),
            'model_year_en' => trim((string)($_POST['model_year_en'] ?? '')),
            'summary_ja' => trim((string)($_POST['summary_ja'] ?? '')),
            'summary_en' => trim((string)($_POST['summary_en'] ?? '')),
            'notes_ja' => trim((string)($_POST['notes_ja'] ?? '')),
            'notes_en' => trim((string)($_POST['notes_en'] ?? '')),
            'status' => ($_POST['status'] ?? 'draft') === 'published' ? 'published' : 'draft',
            'is_featured' => isset($_POST['is_featured']) ? 1 : 0,
            'sort_order' => (int)($_POST['sort_order'] ?? 100),
        ];
        $productId = $repo->saveProduct($payload);

        $specs = [];
        if (isset($_POST['spec_label_ja']) || isset($_POST['spec_value_ja']) || isset($_POST['spec_label_en']) || isset($_POST['spec_value_en'])) {
            $specLabelJa = is_array($_POST['spec_label_ja'] ?? null) ? array_values($_POST['spec_label_ja']) : [];
            $specValueJa = is_array($_POST['spec_value_ja'] ?? null) ? array_values($_POST['spec_value_ja']) : [];
            $specLabelEn = is_array($_POST['spec_label_en'] ?? null) ? array_values($_POST['spec_label_en']) : [];
            $specValueEn = is_array($_POST['spec_value_en'] ?? null) ? array_values($_POST['spec_value_en']) : [];
            $specCount = max(count($specLabelJa), count($specValueJa), count($specLabelEn), count($specValueEn));
            for ($index = 0; $index < $specCount; $index++) {
                $labelJa = trim((string)($specLabelJa[$index] ?? ''));
                $valueJa = trim((string)($specValueJa[$index] ?? ''));
                $labelEn = trim((string)($specLabelEn[$index] ?? ''));
                $valueEn = trim((string)($specValueEn[$index] ?? ''));
                if ($labelJa === '' && $valueJa === '' && $labelEn === '' && $valueEn === '') {
                    continue;
                }
                $specs[] = [
                    'label_ja' => $labelJa,
                    'value_ja' => $valueJa,
                    'label_en' => $labelEn !== '' ? $labelEn : $labelJa,
                    'value_en' => $valueEn !== '' ? $valueEn : $valueJa,
                ];
            }
        } else {
            foreach (preg_split('/\r\n|\r|\n/', (string)($_POST['specs_text'] ?? '')) as $line) {
                $line = trim($line);
                if ($line === '') {
                    continue;
                }
                $parts = array_map('trim', explode('|', $line, 4));
                $specs[] = [
                    'label_ja' => $parts[0] ?? '',
                    'value_ja' => $parts[1] ?? '',
                    'label_en' => $parts[2] ?? ($parts[0] ?? ''),
                    'value_en' => $parts[3] ?? ($parts[1] ?? ''),
                ];
            }
        }
        $repo->replaceSpecs($productId, $specs);

        if (!empty($_FILES['images']['name'][0])) {
            foreach ($_FILES['images']['name'] as $index => $name) {
                $file = [
                    'name' => $name,
                    'type' => $_FILES['images']['type'][$index] ?? '',
                    'tmp_name' => $_FILES['images']['tmp_name'][$index] ?? '',
                    'error' => $_FILES['images']['error'][$index] ?? UPLOAD_ERR_NO_FILE,
                    'size' => $_FILES['images']['size'][$index] ?? 0,
                ];
                $pathStored = $imageService->storeUploadSet($file, 'products');
                if ($pathStored) {
                    $repo->addProductImage($productId, $pathStored, $payload['name_ja']);
                }
            }
        }

        redirect_to('/admin/product-edit?id=' . $productId . '&saved=1');
    }

    $productSpecs = $product ? $repo->productSpecs((int)$product['id']) : [];
    $specLines = [];
    if ($product) {
        foreach ($productSpecs as $spec) {
            $specLines[] = implode('|', [
                $spec['label_ja'],
                $spec['value_ja'],
                $spec['label_en'],
                $spec['value_en'],
            ]);
        }
    }
    admin_render('product_edit', [
        'product' => $product,
        'categories' => $repo->categories(false),
        'images' => $product ? $repo->productImages((int)$product['id']) : [],
        'specs' => $productSpecs,
        'specsText' => implode("\n", $specLines),
        'saved' => isset($_GET['saved']),
        'imageDeleted' => isset($_GET['image_deleted']),
        'imagesSaved' => isset($_GET['images_saved']),
        'restored' => isset($_GET['restored']),
        'title' => $product ? '商品編集' : '商品追加',
    ]);
    return;
}

if ($path === '/admin/categories') {
    if (is_post()) {
        verify_csrf();
        $categoryId = (int)($_POST['id'] ?? 0);
        $currentCategory = $categoryId > 0 ? $repo->categoryById($categoryId) : null;
        $logoPath = trim((string)($currentCategory['logo_path'] ?? ''));
        if (!empty($_FILES['logo']['name'])) {
            $uploadedLogo = $fileService->storeImageOriginal($_FILES['logo'], 'brands');
            if ($uploadedLogo) {
                if ($logoPath !== '') {
                    $imageService->deletePublicUpload($logoPath);
                }
                $logoPath = $uploadedLogo;
            }
        }
        $repo->saveCategory([
            'id' => $categoryId ?: null,
            'slug' => slugify((string)($_POST['slug'] ?? $_POST['name_en'] ?? $_POST['name_ja'] ?? '')),
            'name_ja' => trim((string)($_POST['name_ja'] ?? '')),
            'name_en' => trim((string)($_POST['name_en'] ?? '')),
            'description_ja' => trim((string)($_POST['description_ja'] ?? '')),
            'description_en' => trim((string)($_POST['description_en'] ?? '')),
            'logo_path' => $logoPath,
            'sort_order' => (int)($_POST['sort_order'] ?? 100),
            'is_active' => isset($_POST['is_active']) ? 1 : 0,
        ]);
        redirect_to('/admin/categories?saved=1');
    }
    $edit = isset($_GET['id']) ? $repo->categoryById((int)$_GET['id']) : null;
    admin_render('categories', [
        'categories' => $repo->categories(false),
        'edit' => $edit,
        'saved' => isset($_GET['saved']),
        'title' => 'カテゴリ管理',
    ]);
    return;
}

if ($path === '/admin/price-lists') {
    $edit = isset($_GET['id']) ? $repo->priceListById((int)$_GET['id']) : null;
    if (is_post()) {
        verify_csrf();
        $id = (int)($_POST['id'] ?? 0);
        $current = $id > 0 ? $repo->priceListById($id) : null;
        $pdfPath = trim((string)($current['pdf_path'] ?? ''));
        if (!empty($_FILES['pdf']['name'])) {
            $uploadedPdf = $fileService->storePdf($_FILES['pdf'], 'price-lists');
            if ($uploadedPdf) {
                if ($pdfPath !== '') {
                    $imageService->deletePublicUpload($pdfPath);
                }
                $pdfPath = $uploadedPdf;
            }
        }
        if ($pdfPath === '') {
            admin_render('price_lists', [
                'priceLists' => $repo->priceLists(false),
                'categories' => $repo->categories(false),
                'edit' => $current,
                'saved' => false,
                'error' => 'PDFを選択してください。',
                'title' => '価格表リスト',
            ]);
            return;
        }

        $priceListId = $repo->savePriceList([
            'id' => $id ?: null,
            'category_id' => (int)($_POST['category_id'] ?? 0) ?: null,
            'title_ja' => trim((string)($_POST['title_ja'] ?? '')),
            'title_en' => trim((string)($_POST['title_en'] ?? '')),
            'pdf_path' => $pdfPath,
            'sort_order' => (int)($_POST['sort_order'] ?? 100),
            'is_active' => isset($_POST['is_active']) ? 1 : 0,
            'published_at' => trim((string)($_POST['published_at'] ?? '')) ?: null,
        ]);
        redirect_to('/admin/price-lists?id=' . $priceListId . '&saved=1');
    }

    admin_render('price_lists', [
        'priceLists' => $repo->priceLists(false),
        'categories' => $repo->categories(false),
        'edit' => $edit,
        'saved' => isset($_GET['saved']),
        'error' => null,
        'title' => '価格表リスト',
    ]);
    return;
}

if ($path === '/admin/price-list-delete') {
    if (!is_post()) {
        redirect_to('/admin/price-lists');
    }

    verify_csrf();
    $priceList = $repo->priceListById((int)($_POST['id'] ?? 0));
    if ($priceList && $repo->deletePriceList((int)$priceList['id'])) {
        $imageService->deletePublicUpload((string)$priceList['pdf_path']);
    }
    redirect_to('/admin/price-lists?deleted=1');
}

if ($path === '/admin/business-calendar') {
    $requestedMonth = trim((string)($_GET['month'] ?? date('Y-m')));
    if (!preg_match('/^\d{4}-\d{2}$/', $requestedMonth)) {
        $requestedMonth = date('Y-m');
    }
    [$year, $month] = array_map('intval', explode('-', $requestedMonth));
    $calendar = new BusinessCalendar($repo);

    if (is_post()) {
        verify_csrf();
        $repo->saveBusinessDayExceptions(
            (array)($_POST['status'] ?? []),
            (array)($_POST['note_ja'] ?? []),
            (array)($_POST['note_en'] ?? []),
            (array)($_POST['event_name_ja'] ?? []),
            (array)($_POST['event_name_en'] ?? []),
            (array)($_POST['event_url'] ?? [])
        );
        redirect_to('/admin/business-calendar?month=' . sprintf('%04d-%02d', $year, $month) . '&saved=1');
    }

    admin_render('business_calendar', [
        'calendarMonth' => $calendar->month($year, $month),
        'statusLabels' => BusinessCalendar::statusLabels(),
        'saved' => isset($_GET['saved']),
        'title' => '営業日カレンダー',
    ]);
    return;
}

if ($path === '/admin/news') {
    admin_render('news', [
        'posts' => $repo->news(null, false),
        'title' => 'ニュース管理',
    ]);
    return;
}

if ($path === '/admin/news-edit') {
    $id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
    $post = $id > 0 ? $repo->newsById($id) : null;
    if (is_post()) {
        verify_csrf();
        $imagePath = trim((string)($_POST['image_path'] ?? ''));
        if (!empty($_FILES['image']['name'])) {
            $uploaded = $imageService->storeUpload($_FILES['image'], 'news');
            if ($uploaded) {
                $imagePath = $uploaded;
            }
        }
        $postId = $repo->saveNews([
            'id' => $id ?: null,
            'slug' => slugify((string)($_POST['slug'] ?? $_POST['title_en'] ?? $_POST['title_ja'] ?? '')),
            'title_ja' => trim((string)($_POST['title_ja'] ?? '')),
            'title_en' => trim((string)($_POST['title_en'] ?? '')),
            'body_ja' => trim((string)($_POST['body_ja'] ?? '')),
            'body_en' => trim((string)($_POST['body_en'] ?? '')),
            'image_path' => $imagePath,
            'published_at' => trim((string)($_POST['published_at'] ?? date('Y-m-d'))),
            'is_active' => isset($_POST['is_active']) ? 1 : 0,
        ]);
        redirect_to('/admin/news-edit?id=' . $postId . '&saved=1');
    }
    admin_render('news_edit', [
        'post' => $post,
        'saved' => isset($_GET['saved']),
        'title' => $post ? 'ニュース編集' : 'ニュース追加',
    ]);
    return;
}

if ($path === '/admin/pages') {
    admin_render('pages', [
        'pages' => $repo->pages(false),
        'title' => '固定ページ管理',
    ]);
    return;
}

if ($path === '/admin/page-edit') {
    $id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
    $page = $id > 0 ? $repo->pageById($id) : null;
    if (is_post()) {
        verify_csrf();
        $pageId = $repo->savePage([
            'id' => $id ?: null,
            'slug' => slugify((string)($_POST['slug'] ?? $_POST['title_en'] ?? $_POST['title_ja'] ?? '')),
            'title_ja' => trim((string)($_POST['title_ja'] ?? '')),
            'title_en' => trim((string)($_POST['title_en'] ?? '')),
            'body_ja' => trim((string)($_POST['body_ja'] ?? '')),
            'body_en' => trim((string)($_POST['body_en'] ?? '')),
            'meta_description_ja' => trim((string)($_POST['meta_description_ja'] ?? '')),
            'meta_description_en' => trim((string)($_POST['meta_description_en'] ?? '')),
            'sort_order' => (int)($_POST['sort_order'] ?? 100),
            'is_active' => isset($_POST['is_active']) ? 1 : 0,
        ]);
        redirect_to('/admin/page-edit?id=' . $pageId . '&saved=1');
    }
    admin_render('page_edit', [
        'page' => $page,
        'saved' => isset($_GET['saved']),
        'title' => $page ? '固定ページ編集' : '固定ページ追加',
    ]);
    return;
}

if ($path === '/admin/inquiries') {
    admin_render('inquiries', [
        'inquiries' => $repo->inquiries(),
        'title' => '問い合わせ',
    ]);
    return;
}

http_response_code(404);
admin_render('error', ['message' => '管理画面のページが見つかりません。']);
