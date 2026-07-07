<?php
/**
 * httpdocs/app/CmsRepository.php
 * 商品、カテゴリ、ニュース、固定ページ、問い合わせのDB操作を集約します。
 */

declare(strict_types=1);

final class CmsRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    public function setting(string $key, string $default = ''): string
    {
        $stmt = $this->pdo->prepare('SELECT setting_value FROM settings WHERE setting_key = ?');
        $stmt->execute([$key]);
        $value = $stmt->fetchColumn();
        return $value === false ? $default : (string)$value;
    }

    public function saveSetting(string $key, string $value): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO settings (setting_key, setting_value) VALUES (?, ?)
             ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)'
        );
        $stmt->execute([$key, $value]);
    }

    public function counts(): array
    {
        return [
            'products' => (int)$this->pdo->query('SELECT COUNT(*) FROM products')->fetchColumn(),
            'categories' => (int)$this->pdo->query('SELECT COUNT(*) FROM categories')->fetchColumn(),
            'news' => (int)$this->pdo->query('SELECT COUNT(*) FROM news_posts')->fetchColumn(),
            'inquiries' => (int)$this->pdo->query('SELECT COUNT(*) FROM inquiries')->fetchColumn(),
        ];
    }

    public function categories(bool $activeOnly = true): array
    {
        $where = $activeOnly ? 'WHERE is_active = 1' : '';
        return $this->pdo->query("SELECT * FROM categories {$where} ORDER BY sort_order, name_ja")->fetchAll();
    }

    public function categoryBySlug(string $slug): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM categories WHERE slug = ? AND is_active = 1');
        $stmt->execute([$slug]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function categoryById(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM categories WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function products(array $filters = [], ?int $limit = null): array
    {
        $where = ['p.status = "published"'];
        $params = [];

        if (!empty($filters['category_id'])) {
            $where[] = 'p.category_id = ?';
            $params[] = (int)$filters['category_id'];
        }
        if (!empty($filters['keyword'])) {
            $where[] = '(p.name_ja LIKE ? OR p.name_en LIKE ? OR p.model_year_ja LIKE ?)';
            $keyword = '%' . $filters['keyword'] . '%';
            array_push($params, $keyword, $keyword, $keyword);
        }
        if (!empty($filters['featured'])) {
            $where[] = 'p.is_featured = 1';
        }

        $sql = 'SELECT p.*, c.slug AS category_slug, c.name_ja AS category_name_ja, c.name_en AS category_name_en,
                (SELECT path FROM product_images WHERE product_id = p.id ORDER BY is_main DESC, sort_order, id LIMIT 1) AS main_image
                FROM products p LEFT JOIN categories c ON c.id = p.category_id
                WHERE ' . implode(' AND ', $where) . '
                ORDER BY p.is_featured DESC, p.sort_order, p.updated_at DESC, p.id DESC';
        if ($limit !== null) {
            $sql .= ' LIMIT ' . max(1, $limit);
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function adminProducts(?string $keyword = null): array
    {
        $params = [];
        $where = '';
        if ($keyword !== null && trim($keyword) !== '') {
            $where = 'WHERE p.name_ja LIKE ? OR p.name_en LIKE ? OR p.slug LIKE ?';
            $needle = '%' . trim($keyword) . '%';
            $params = [$needle, $needle, $needle];
        }
        $stmt = $this->pdo->prepare(
            "SELECT p.*, c.name_ja AS category_name_ja
             FROM products p LEFT JOIN categories c ON c.id = p.category_id
             {$where}
             ORDER BY p.updated_at DESC, p.id DESC
             LIMIT 300"
        );
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function productBySlug(string $slug): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT p.*, c.slug AS category_slug, c.name_ja AS category_name_ja, c.name_en AS category_name_en
             FROM products p LEFT JOIN categories c ON c.id = p.category_id
             WHERE p.slug = ? AND p.status = "published" LIMIT 1'
        );
        $stmt->execute([$slug]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function productById(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM products WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function productImages(int $productId): array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM product_images WHERE product_id = ? ORDER BY is_main DESC, sort_order, id');
        $stmt->execute([$productId]);
        return $stmt->fetchAll();
    }

    public function deleteProductImage(int $imageId, int $productId): bool
    {
        $stmt = $this->pdo->prepare('DELETE FROM product_images WHERE id = ? AND product_id = ?');
        $stmt->execute([$imageId, $productId]);
        return $stmt->rowCount() > 0;
    }

    public function productSpecs(int $productId): array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM product_specs WHERE product_id = ? ORDER BY sort_order, id');
        $stmt->execute([$productId]);
        return $stmt->fetchAll();
    }

    public function saveProduct(array $data): int
    {
        $fields = [
            'category_id', 'slug', 'name_ja', 'name_en', 'model_year_ja', 'model_year_en',
            'summary_ja', 'summary_en', 'notes_ja', 'notes_en', 'status', 'is_featured', 'sort_order',
        ];
        $values = [];
        foreach ($fields as $field) {
            $values[$field] = $data[$field] ?? null;
        }

        if (!empty($data['id'])) {
            $assignments = implode(', ', array_map(fn($field) => "{$field} = :{$field}", $fields));
            $sql = "UPDATE products SET {$assignments}, updated_at = NOW() WHERE id = :id";
            $stmt = $this->pdo->prepare($sql);
            $values['id'] = (int)$data['id'];
            $stmt->execute($values);
            return (int)$data['id'];
        }

        $columns = implode(', ', $fields);
        $placeholders = ':' . implode(', :', $fields);
        $stmt = $this->pdo->prepare("INSERT INTO products ({$columns}) VALUES ({$placeholders})");
        $stmt->execute($values);
        return (int)$this->pdo->lastInsertId();
    }

    public function replaceSpecs(int $productId, array $specs): void
    {
        $this->pdo->prepare('DELETE FROM product_specs WHERE product_id = ?')->execute([$productId]);
        $stmt = $this->pdo->prepare(
            'INSERT INTO product_specs (product_id, label_ja, label_en, value_ja, value_en, sort_order)
             VALUES (?, ?, ?, ?, ?, ?)'
        );
        foreach ($specs as $index => $spec) {
            $stmt->execute([
                $productId,
                $spec['label_ja'] ?? '',
                $spec['label_en'] ?? ($spec['label_ja'] ?? ''),
                $spec['value_ja'] ?? '',
                $spec['value_en'] ?? ($spec['value_ja'] ?? ''),
                $index + 1,
            ]);
        }
    }

    public function addProductImage(int $productId, string $path, string $alt = '', bool $main = false): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO product_images (product_id, path, alt_ja, alt_en, source_type, sort_order, is_main)
             VALUES (?, ?, ?, ?, "upload", 999, ?)'
        );
        $stmt->execute([$productId, $path, $alt, $alt, $main ? 1 : 0]);
    }

    public function news(?int $limit = null, bool $activeOnly = true): array
    {
        $where = $activeOnly ? 'WHERE is_active = 1' : '';
        $sql = "SELECT * FROM news_posts {$where} ORDER BY published_at DESC, id DESC";
        if ($limit !== null) {
            $sql .= ' LIMIT ' . max(1, $limit);
        }
        return $this->pdo->query($sql)->fetchAll();
    }

    public function newsBySlug(string $slug): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM news_posts WHERE slug = ? AND is_active = 1 LIMIT 1');
        $stmt->execute([$slug]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function newsById(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM news_posts WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function saveNews(array $data): int
    {
        $fields = ['slug', 'title_ja', 'title_en', 'body_ja', 'body_en', 'image_path', 'published_at', 'is_active'];
        $values = [];
        foreach ($fields as $field) {
            $values[$field] = $data[$field] ?? null;
        }

        if (!empty($data['id'])) {
            $assignments = implode(', ', array_map(fn($field) => "{$field} = :{$field}", $fields));
            $values['id'] = (int)$data['id'];
            $stmt = $this->pdo->prepare("UPDATE news_posts SET {$assignments}, updated_at = NOW() WHERE id = :id");
            $stmt->execute($values);
            return (int)$data['id'];
        }

        $stmt = $this->pdo->prepare('INSERT INTO news_posts (' . implode(', ', $fields) . ') VALUES (:' . implode(', :', $fields) . ')');
        $stmt->execute($values);
        return (int)$this->pdo->lastInsertId();
    }

    public function pages(bool $activeOnly = false): array
    {
        $where = $activeOnly ? 'WHERE is_active = 1' : '';
        return $this->pdo->query("SELECT * FROM pages {$where} ORDER BY sort_order, title_ja")->fetchAll();
    }

    public function page(string $slug): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM pages WHERE slug = ? AND is_active = 1 LIMIT 1');
        $stmt->execute([$slug]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function pageById(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM pages WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function savePage(array $data): int
    {
        $fields = ['slug', 'title_ja', 'title_en', 'body_ja', 'body_en', 'meta_description_ja', 'meta_description_en', 'sort_order', 'is_active'];
        $values = [];
        foreach ($fields as $field) {
            $values[$field] = $data[$field] ?? null;
        }
        if (!empty($data['id'])) {
            $assignments = implode(', ', array_map(fn($field) => "{$field} = :{$field}", $fields));
            $values['id'] = (int)$data['id'];
            $stmt = $this->pdo->prepare("UPDATE pages SET {$assignments}, updated_at = NOW() WHERE id = :id");
            $stmt->execute($values);
            return (int)$data['id'];
        }
        $stmt = $this->pdo->prepare('INSERT INTO pages (' . implode(', ', $fields) . ') VALUES (:' . implode(', :', $fields) . ')');
        $stmt->execute($values);
        return (int)$this->pdo->lastInsertId();
    }

    public function saveCategory(array $data): int
    {
        $fields = ['slug', 'name_ja', 'name_en', 'description_ja', 'description_en', 'sort_order', 'is_active'];
        $values = [];
        foreach ($fields as $field) {
            $values[$field] = $data[$field] ?? null;
        }
        if (!empty($data['id'])) {
            $assignments = implode(', ', array_map(fn($field) => "{$field} = :{$field}", $fields));
            $values['id'] = (int)$data['id'];
            $stmt = $this->pdo->prepare("UPDATE categories SET {$assignments} WHERE id = :id");
            $stmt->execute($values);
            return (int)$data['id'];
        }
        $stmt = $this->pdo->prepare('INSERT INTO categories (' . implode(', ', $fields) . ') VALUES (:' . implode(', :', $fields) . ')');
        $stmt->execute($values);
        return (int)$this->pdo->lastInsertId();
    }

    public function saveInquiry(array $data): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO inquiries (product_id, locale, name, email, phone, company, country, message, ip_address, user_agent, created_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())'
        );
        $stmt->execute([
            $data['product_id'] ?: null,
            $data['locale'],
            $data['name'],
            $data['email'],
            $data['phone'],
            $data['company'],
            $data['country'],
            $data['message'],
            $data['ip_address'],
            $data['user_agent'],
        ]);
        return (int)$this->pdo->lastInsertId();
    }

    public function inquiries(): array
    {
        return $this->pdo->query(
            'SELECT i.*, p.name_ja AS product_name
             FROM inquiries i LEFT JOIN products p ON p.id = i.product_id
             ORDER BY i.created_at DESC LIMIT 200'
        )->fetchAll();
    }
}
