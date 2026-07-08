<?php
/**
 * httpdocs/app/routes_admin.php
 * 管理画面側のURLを解決し、CMS編集処理を実行します。
 */

declare(strict_types=1);

$imageService = new ImageService();

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

    try {
        $translator = new OpenAITranslator($openaiConfig);
        $result = $translator->translateFields($fields, trim((string)($body['context'] ?? '')));
        json_response(['ok' => true] + $result);
    } catch (Throwable $e) {
        json_response(['ok' => false, 'message' => $e->getMessage()], 400);
    }
}

if ($path === '/admin') {
    admin_render('dashboard', [
        'user' => $user,
        'counts' => $repo->counts(),
        'inquiries' => array_slice($repo->inquiries(), 0, 5),
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
        'products' => $repo->adminProducts($_GET['q'] ?? null, $categoryFilter),
        'categories' => $categories,
        'categoryCounts' => $repo->adminProductCategoryCounts(),
        'activeCategory' => $categoryFilter,
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
        $repo->deleteProductImage($imageId, $productId);
    }
    redirect_to('/admin/product-edit?id=' . $productId . '&image_deleted=1');
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
                $pathStored = $imageService->storeUpload($file, 'products');
                if ($pathStored) {
                    $repo->addProductImage($productId, $pathStored, $payload['name_ja']);
                }
            }
        }

        redirect_to('/admin/product-edit?id=' . $productId . '&saved=1');
    }

    $specLines = [];
    if ($product) {
        foreach ($repo->productSpecs((int)$product['id']) as $spec) {
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
        'specsText' => implode("\n", $specLines),
        'saved' => isset($_GET['saved']),
        'imageDeleted' => isset($_GET['image_deleted']),
        'title' => $product ? '商品編集' : '商品追加',
    ]);
    return;
}

if ($path === '/admin/categories') {
    if (is_post()) {
        verify_csrf();
        $repo->saveCategory([
            'id' => (int)($_POST['id'] ?? 0) ?: null,
            'slug' => slugify((string)($_POST['slug'] ?? $_POST['name_en'] ?? $_POST['name_ja'] ?? '')),
            'name_ja' => trim((string)($_POST['name_ja'] ?? '')),
            'name_en' => trim((string)($_POST['name_en'] ?? '')),
            'description_ja' => trim((string)($_POST['description_ja'] ?? '')),
            'description_en' => trim((string)($_POST['description_en'] ?? '')),
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
