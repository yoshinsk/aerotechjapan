<?php
/**
 * httpdocs/database/seed.php
 * 旧サイトの商品JSONを読み込み、CMS初期データと管理者ユーザーを投入します。
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/app/bootstrap.php';

if (realpath($_SERVER['SCRIPT_FILENAME'] ?? '') === __FILE__) {
    run_seed();
}

function run_seed(): void
{
    $pdo = Database::pdo();
    $pdo->beginTransaction();

    try {
        seed_settings($pdo);
        $categoryIds = seed_categories($pdo);
        seed_pages($pdo);
        seed_news($pdo);
        seed_products($pdo, $categoryIds);
        seed_admin_user($pdo);
        $pdo->commit();
        echo "Seed completed.\n";
    } catch (Throwable $e) {
        $pdo->rollBack();
        fwrite(STDERR, "Seed failed: {$e->getMessage()}\n");
        exit(1);
    }
}

function seed_settings(PDO $pdo): void
{
    $settings = [
        'site_name' => 'AERO TECH JAPAN',
        'contact_email' => config_value('mail.to', 'rando@aero-tech.co.jp'),
    ];
    $stmt = $pdo->prepare('INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)');
    foreach ($settings as $key => $value) {
        $stmt->execute([$key, $value]);
    }
}

function seed_categories(PDO $pdo): array
{
    $categories = [
        ['rando', '乱人 / RANDO', 'RANDO', 'エアロテックジャパンの基幹ブランド。', 'Core AERO TECH JAPAN brand.', 10],
        ['rando-black-edition', '乱人 Black Edition', 'RANDO Black Edition', 'ブラックエディション系ボディキット。', 'Black Edition body kits.', 20],
        ['direct', 'DIRect', 'DIRect', 'スポーツモデル向けブランド。', 'Aero parts for sports models.', 30],
        ['avant', 'AVANT', 'AVANT', '上質なスタイリングを重視したブランド。', 'Premium styling-focused body kits.', 40],
        ['rando-style', 'RANDO Style', 'RANDO Style', '幅広い車種に対応するスタイルライン。', 'Styling line for a wide range of vehicles.', 50],
        ['rando-sports', 'RANDO SPORTS', 'RANDO SPORTS', '競技・スポーツ志向の製品群。', 'Sport-oriented product line.', 60],
        ['rando-ryu-sports', '乱人流 SPORTS', 'Rando Ryu SPORTS', '乱人流スポーツライン。', 'Rando Ryu sports line.', 70],
        ['rando-ryu-lux', 'Rando Ryu LUX', 'Rando Ryu LUX', 'ラグジュアリースタイルの製品群。', 'Luxury styling product line.', 80],
        ['uncategorized', '未分類', 'Uncategorized', '分類確認中の商品です。', 'Products awaiting category review.', 999],
    ];

    $stmt = $pdo->prepare(
        'INSERT INTO categories (slug, name_ja, name_en, description_ja, description_en, sort_order, is_active)
         VALUES (?, ?, ?, ?, ?, ?, 1)
         ON DUPLICATE KEY UPDATE name_ja = VALUES(name_ja), name_en = VALUES(name_en), description_ja = VALUES(description_ja), description_en = VALUES(description_en), sort_order = VALUES(sort_order)'
    );
    foreach ($categories as $category) {
        $stmt->execute($category);
    }

    $ids = [];
    foreach ($pdo->query('SELECT id, name_ja, slug FROM categories') as $row) {
        $ids[$row['name_ja']] = (int)$row['id'];
        $ids[$row['slug']] = (int)$row['id'];
    }
    return $ids;
}

function seed_pages(PDO $pdo): void
{
    $pages = [
        ['home', 'AERO TECH JAPAN', 'AERO TECH JAPAN',
            "自動車のエアロパーツ、ボディキット、クレイモデル製作、マスター成型、量産、OEM製品製作まで対応します。\n大阪から日本国内・海外へ、実車に合わせた確かな造形を届けます。",
            "AERO TECH JAPAN develops aero parts, body kits, clay models, master molds, production parts, and OEM products.\nFrom Osaka, we deliver vehicle-focused styling for Japan and overseas markets.",
            10],
        ['about', '会社情報', 'About',
            about_body_ja(),
            about_body_en(),
            20],
        ['privacy', 'プライバシーポリシー', 'Privacy Policy',
            "当サイトでは、お問い合わせやご注文の際に取得した個人情報を、回答・連絡・発送など必要な目的にのみ利用します。\n本人の同意がある場合または法令に基づく場合を除き、第三者へ開示しません。\n個人情報の開示、訂正、追加、削除、利用停止のご希望には、本人確認の上で速やかに対応します。\n当サイトから外部サイトへ移動した場合、移動先サイトで提供される情報・サービス等について当社は責任を負いません。",
            "This website uses personal information submitted through inquiries or orders only for necessary responses, communication, and fulfillment.\nWe do not disclose personal information to third parties except with consent or as required by law.\nRequests for disclosure, correction, addition, deletion, or suspension of use will be handled after identity confirmation.\nWe are not responsible for information or services provided by external websites linked from this site.",
            30],
        ['events', 'イベント情報', 'Events',
            "東京オートサロン、全日本ジムカーナ選手権など、出展・協力車両・競技サポート情報を掲載します。\n旧サイトのイベントアーカイブはCMS上で順次整理します。",
            "Event information for Tokyo Auto Salon, All Japan Gymkhana, display vehicles, and support activities.\nLegacy event archives will be organized in the CMS.",
            40],
        ['magazine', '雑誌掲載', 'Magazine Features',
            "STYLE WAGON、WAGONIST等の掲載実績を掲載します。\n過去PDF・画像資料はCMS上で順次整理します。",
            "Magazine and media features including STYLE WAGON and WAGONIST.\nLegacy PDFs and images will be organized in the CMS.",
            50],
        ['links', 'リンク', 'Links',
            "RAYS、BRIDGESTONE、DIXCEL、RS★R、TOYO TIRES、WALDなど、関連メーカー・協力会社へのリンクを掲載します。",
            "Links to related manufacturers and partners including RAYS, BRIDGESTONE, DIXCEL, RS★R, TOYO TIRES, and WALD.",
            60],
        ['distributors', '販売店・海外代理店', 'Distributors',
            "国内販売店および海外代理店情報を掲載します。掲載内容はCMSで更新できます。",
            "Distributor information for Japan and overseas markets. Entries can be updated through the CMS.",
            70],
    ];

    $stmt = $pdo->prepare(
        'INSERT INTO pages (slug, title_ja, title_en, body_ja, body_en, sort_order, is_active)
         VALUES (?, ?, ?, ?, ?, ?, 1)
         ON DUPLICATE KEY UPDATE title_ja = VALUES(title_ja), title_en = VALUES(title_en), body_ja = VALUES(body_ja), body_en = VALUES(body_en), sort_order = VALUES(sort_order)'
    );
    foreach ($pages as $page) {
        $stmt->execute($page);
    }
}

function about_body_ja(): string
{
    return <<<'HTML'
<section>
  <h2>AERO TECH JAPAN CO., LTD.</h2>
  <h3>通信販売法に基づく表示</h3>
  <table>
    <tbody>
      <tr><th>販売業者</th><td>株式会社エアロテックジャパン</td></tr>
      <tr><th>運営統括責任者</th><td>吉川 寛志</td></tr>
      <tr><th>所在地</th><td>〒569-0062<br>大阪府高槻市下田部町2丁目54番1号<br><a href="{{media:img/about-img/map.pdf}}" target="_blank" rel="noopener">新社屋地図 PDF</a></td></tr>
      <tr><th>電話/FAX</th><td>TEL 072-690-7704<br>FAX 072-690-7754</td></tr>
      <tr><th>お問い合わせ</th><td><a href="{{url:/contact}}">お問い合わせフォーム</a></td></tr>
      <tr><th>営業時間</th><td>AM8:30-PM6:00（土曜のみPM3:00まで）</td></tr>
      <tr><th>定休日</th><td>日曜・祝日・イベント開催日</td></tr>
      <tr><th>商品代金以外の必要料金</th><td>消費税、国内送料、振込手数料、代引き手数料</td></tr>
      <tr><th>申し込みの有効期限</th><td>7日</td></tr>
      <tr><th>不良品</th><td>弊社から出荷時にチェックを行います。<br>輸送途中に破損があった場合に関しては保証します。</td></tr>
      <tr><th>引渡し時期</th><td>振込み確認後、在庫のあるものは2、3日で発送。<br>欠品中のものは製作後発送、ただし申し込み時に確認。</td></tr>
      <tr><th>お支払い方法</th><td>銀行振込による前払い、現金書留等</td></tr>
      <tr><th>お支払い期限</th><td>原則的に前払い</td></tr>
      <tr><th>適格請求書発行事業者登録番号</th><td>T4120001147469</td></tr>
    </tbody>
  </table>
</section>

<section>
  <h2>取り扱いブランド</h2>
  <p><img src="{{media:img/about-img/logo-itiran-siroji.gif}}" alt="AERO TECH JAPAN 取り扱いブランド"></p>
  <div class="brand-links">
    <a href="{{url:/category/rando}}">乱人 / RANDO</a>
    <a href="{{url:/category/rando-sports}}">RANDO SPORTS</a>
    <a href="{{url:/category/rando-ryu-sports}}">乱人流 SPORTS</a>
    <a href="{{url:/category/rando-style}}">RANDO Style</a>
    <a href="{{url:/category/avant}}">AVANT</a>
    <a href="{{url:/category/rando-ryu-lux}}">Rando Ryu LUX</a>
  </div>
</section>

<section>
  <h2>会社案内</h2>
  <div class="about-docs">
    <img src="{{media:img/about-img/kaisyaannai01.jpg}}" alt="会社案内 表紙">
    <p><a href="{{media:img/about-img/kaisyaannai_01.pdf}}" target="_blank" rel="noopener">会社案内 表紙 PDF</a></p>
    <p><a href="{{media:img/about-img/kaisyaannai.pdf}}" target="_blank" rel="noopener">会社案内 中面 PDF</a></p>
  </div>
</section>

<section>
  <h2>取引銀行</h2>
  <table>
    <tbody>
      <tr><th>銀行名</th><td>関西みらい銀行 寝屋川支店</td></tr>
      <tr><th>口座</th><td>普通口座 0804514</td></tr>
      <tr><th>口座名義</th><td>株式会社エアロテックジャパン</td></tr>
    </tbody>
  </table>
</section>

<section>
  <h2>FACTORY</h2>
  <p>自動車のクレイモデル製作・マスター成型・量産・OEM製品製作等</p>
  <div class="factory-grid">
    <img src="{{media:img/about-img/factory/factory-1.jpg}}" alt="FACTORY 1">
    <img src="{{media:img/about-img/factory/factory-2.jpg}}" alt="FACTORY 2">
    <img src="{{media:img/about-img/factory/factory-3.jpg}}" alt="FACTORY 3">
    <img src="{{media:img/about-img/factory/factory-4.jpg}}" alt="FACTORY 4">
  </div>
</section>
HTML;
}

function about_body_en(): string
{
    return <<<'HTML'
<section>
  <h2>AERO TECH JAPAN CO., LTD.</h2>
  <h3>Legal Notice for Mail Order Sales</h3>
  <table>
    <tbody>
      <tr><th>Seller</th><td>AERO TECH JAPAN CO., LTD.</td></tr>
      <tr><th>Responsible Manager</th><td>Hiroshi Yoshikawa</td></tr>
      <tr><th>Address</th><td>2-54-1 Shimotanabe-cho, Takatsuki, Osaka 569-0062, Japan<br><a href="{{media:img/about-img/map.pdf}}" target="_blank" rel="noopener">New office map PDF</a></td></tr>
      <tr><th>TEL/FAX</th><td>TEL +81-72-690-7704<br>FAX +81-72-690-7754</td></tr>
      <tr><th>Contact</th><td><a href="{{url:/contact}}">Contact form</a></td></tr>
      <tr><th>Business Hours</th><td>8:30-18:00, Saturday until 15:00</td></tr>
      <tr><th>Closed</th><td>Sundays, national holidays, and event days</td></tr>
      <tr><th>Additional Charges</th><td>Consumption tax, domestic shipping, bank transfer fees, and cash-on-delivery fees</td></tr>
      <tr><th>Application Validity</th><td>7 days</td></tr>
      <tr><th>Defective Products</th><td>Products are checked before shipment. Damage during transportation is covered.</td></tr>
      <tr><th>Delivery Time</th><td>In-stock items ship 2 to 3 days after payment confirmation. Backordered items ship after production, subject to confirmation at order time.</td></tr>
      <tr><th>Payment Method</th><td>Advance bank transfer, registered cash mail, and related methods</td></tr>
      <tr><th>Payment Due</th><td>Advance payment in principle</td></tr>
      <tr><th>Qualified Invoice Issuer Number</th><td>T4120001147469</td></tr>
    </tbody>
  </table>
</section>

<section>
  <h2>Brands</h2>
  <p><img src="{{media:img/about-img/logo-itiran-siroji.gif}}" alt="AERO TECH JAPAN brands"></p>
  <div class="brand-links">
    <a href="{{url:/category/rando}}">RANDO</a>
    <a href="{{url:/category/rando-sports}}">RANDO SPORTS</a>
    <a href="{{url:/category/rando-ryu-sports}}">Rando Ryu SPORTS</a>
    <a href="{{url:/category/rando-style}}">RANDO Style</a>
    <a href="{{url:/category/avant}}">AVANT</a>
    <a href="{{url:/category/rando-ryu-lux}}">Rando Ryu LUX</a>
  </div>
</section>

<section>
  <h2>Company Brochure</h2>
  <div class="about-docs">
    <img src="{{media:img/about-img/kaisyaannai01.jpg}}" alt="Company brochure cover">
    <p><a href="{{media:img/about-img/kaisyaannai_01.pdf}}" target="_blank" rel="noopener">Brochure cover PDF</a></p>
    <p><a href="{{media:img/about-img/kaisyaannai.pdf}}" target="_blank" rel="noopener">Brochure inside PDF</a></p>
  </div>
</section>

<section>
  <h2>Bank Account</h2>
  <table>
    <tbody>
      <tr><th>Bank</th><td>Kansai Mirai Bank, Neyagawa Branch</td></tr>
      <tr><th>Account</th><td>Ordinary account 0804514</td></tr>
      <tr><th>Account Name</th><td>AERO TECH JAPAN CO., LTD.</td></tr>
    </tbody>
  </table>
</section>

<section>
  <h2>FACTORY</h2>
  <p>Clay model production, master molding, production parts, and OEM product manufacturing for automobiles.</p>
  <div class="factory-grid">
    <img src="{{media:img/about-img/factory/factory-1.jpg}}" alt="Factory 1">
    <img src="{{media:img/about-img/factory/factory-2.jpg}}" alt="Factory 2">
    <img src="{{media:img/about-img/factory/factory-3.jpg}}" alt="Factory 3">
    <img src="{{media:img/about-img/factory/factory-4.jpg}}" alt="Factory 4">
  </div>
</section>
HTML;
}

function seed_news(PDO $pdo): void
{
    $posts = [
        ['cz4a-lancer-evolution-x-20260602', 'CZ4A LANCER EVOLUTION X 発売開始', 'CZ4A LANCER EVOLUTION X is now available', 'RANDO SPORTS CZ4A LANCER EVOLUTION Xを発売開始しました。', 'RANDO SPORTS CZ4A LANCER EVOLUTION X is now available.', '', '2026-06-02'],
        ['price-revision-20260520', 'PARTS・CARBON BONNET等 各車種価格改定', 'Price revision for parts and carbon bonnets', 'PARTS・CARBON BONNET等、各車種の価格を改定しました。', 'Prices have been revised for parts, carbon bonnets, and related items.', '', '2026-05-20'],
        ['zc33s-swift-sport-20260311', 'ZC33S SUZUKI SWIFT SPORT 発売開始', 'ZC33S SUZUKI SWIFT SPORT is now available', 'RANDO SPORTS ZC33S SUZUKI SWIFT SPORTを発売開始しました。', 'RANDO SPORTS ZC33S SUZUKI SWIFT SPORT is now available.', '', '2026-03-11'],
        ['gr-corolla-20260204', 'GR COROLLA 発売開始', 'GR COROLLA is now available', 'DIRect GR COROLLA(GZEA14H)を発売開始しました。', 'DIRect GR COROLLA (GZEA14H) is now available.', '', '2026-02-04'],
        ['fl5-civic-type-r-20250627', 'FL5 CIVIC TYPE-R 発売開始', 'FL5 CIVIC TYPE-R is now available', 'DIRect FL5 CIVIC TYPE-R(FL5)を発売開始しました。', 'DIRect FL5 CIVIC TYPE-R (FL5) is now available.', '', '2025-06-27'],
        ['head-office-relocation-20231215', '本社移転のお知らせ', 'Head office relocation notice', '本社移転のお知らせを掲載しました。', 'A head office relocation notice has been posted.', 'img/top-img/info20231215.pdf', '2023-12-15'],
    ];

    $stmt = $pdo->prepare(
        'INSERT INTO news_posts (slug, title_ja, title_en, body_ja, body_en, image_path, published_at, is_active)
         VALUES (?, ?, ?, ?, ?, ?, ?, 1)
         ON DUPLICATE KEY UPDATE title_ja = VALUES(title_ja), title_en = VALUES(title_en), body_ja = VALUES(body_ja), body_en = VALUES(body_en), image_path = VALUES(image_path), published_at = VALUES(published_at)'
    );
    foreach ($posts as $post) {
        $stmt->execute($post);
    }
}

function seed_products(PDO $pdo, array $categoryIds): void
{
    $source = dirname(__DIR__) . '/prototype/data/products.json';
    if (!is_file($source)) {
        echo "Product JSON not found: {$source}\n";
        return;
    }
    $products = json_decode((string)file_get_contents($source), true, 512, JSON_THROW_ON_ERROR);
    $categoryMap = [
        '乱人 / RANDO' => 'rando',
        '乱人 Black Edition' => 'rando-black-edition',
        'DIRect' => 'direct',
        'AVANT' => 'avant',
        'RANDO Style' => 'rando-style',
        'RANDO SPORTS' => 'rando-sports',
        '乱人流 SPORTS' => 'rando-ryu-sports',
        'Rando Ryu LUX' => 'rando-ryu-lux',
        '未分類' => 'uncategorized',
    ];

    $productStmt = $pdo->prepare(
        'INSERT INTO products (category_id, slug, name_ja, name_en, model_year_ja, model_year_en, summary_ja, summary_en, notes_ja, notes_en, status, is_featured, sort_order)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, "published", ?, ?)
         ON DUPLICATE KEY UPDATE category_id = VALUES(category_id), name_ja = VALUES(name_ja), name_en = VALUES(name_en), model_year_ja = VALUES(model_year_ja), model_year_en = VALUES(model_year_en), notes_ja = VALUES(notes_ja), notes_en = VALUES(notes_en), is_featured = VALUES(is_featured), sort_order = VALUES(sort_order)'
    );
    $imageStmt = $pdo->prepare(
        'INSERT INTO product_images (product_id, path, alt_ja, alt_en, source_type, sort_order, is_main)
         VALUES (?, ?, ?, ?, "legacy", ?, ?)'
    );
    $specStmt = $pdo->prepare(
        'INSERT INTO product_specs (product_id, label_ja, label_en, value_ja, value_en, sort_order)
         VALUES (?, ?, ?, ?, ?, ?)'
    );

    $featured = ['GUN125_HILUX_GR_SPORT', 'direct_HONDA-CIVIC-TYPE-R', 'GR_COROLLA_1', 'ZC33S_SWIFT_SPORT', 'avant_alpine_a110s_gt'];
    $sort = 1;
    foreach ($products as $product) {
        $categorySlug = $categoryMap[$product['category'] ?? '未分類'] ?? 'uncategorized';
        $categoryId = $categoryIds[$categorySlug] ?? $categoryIds['uncategorized'];
        $slug = (string)$product['slug'];
        $name = (string)($product['name'] ?: $slug);
        $modelYear = (string)($product['model_year'] ?? '');
        $notes = (string)($product['notes'] ?? '');
        $isFeatured = in_array($slug, $featured, true) ? 1 : 0;

        $productStmt->execute([
            $categoryId,
            $slug,
            $name,
            $name,
            $modelYear,
            $modelYear,
            '',
            '',
            $notes,
            $notes,
            $isFeatured,
            $sort++,
        ]);
        $productId = (int)$pdo->lastInsertId();
        if ($productId === 0) {
            $lookup = $pdo->prepare('SELECT id FROM products WHERE slug = ?');
            $lookup->execute([$slug]);
            $productId = (int)$lookup->fetchColumn();
        }

        $pdo->prepare('DELETE FROM product_images WHERE product_id = ? AND source_type = "legacy"')->execute([$productId]);
        $imageDir = trim((string)($product['image_dir'] ?? ''), '/');
        foreach (($product['images'] ?? []) as $index => $image) {
            $path = $imageDir !== '' ? $imageDir . '/' . ltrim((string)$image, '/') : (string)$image;
            $imageStmt->execute([$productId, $path, $name, $name, $index + 1, $index === 0 ? 1 : 0]);
        }

        $pdo->prepare('DELETE FROM product_specs WHERE product_id = ?')->execute([$productId]);
        foreach (($product['specs'] ?? []) as $index => $spec) {
            $label = (string)($spec['label'] ?? '');
            $value = (string)($spec['value'] ?? '');
            $specStmt->execute([$productId, $label, $label, $value, $value, $index + 1]);
        }
    }
}

function seed_admin_user(PDO $pdo): void
{
    $email = getenv('AEROTECH_ADMIN_EMAIL') ?: 'admin@aero-tech.co.jp';
    $password = getenv('AEROTECH_ADMIN_PASSWORD') ?: bin2hex(random_bytes(6));
    $hash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare(
        'INSERT INTO users (email, password_hash, name, role, is_active)
         VALUES (?, ?, "Administrator", "admin", 1)
         ON DUPLICATE KEY UPDATE password_hash = VALUES(password_hash), is_active = 1'
    );
    $stmt->execute([$email, $hash]);
    echo "Admin user: {$email}\n";
    if (!getenv('AEROTECH_ADMIN_PASSWORD')) {
        echo "Generated admin password: {$password}\n";
    }
}
