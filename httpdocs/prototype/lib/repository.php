<?php
/**
 * 商品データのリポジトリ。
 *
 * USE_DB の値に応じて、MariaDB（PDO）か JSON ファイルのどちらかを
 * データソースとして扱う。呼び出し側（product.php / admin/edit.php）は
 * データの保存先を意識せずに find()/save()/all() を使える。
 */

require_once __DIR__ . '/../config.php';

class ProductRepository
{
    /** @var PDO|null */
    private $pdo = null;

    /** @var string */
    private $jsonPath;

    public function __construct()
    {
        $this->jsonPath = __DIR__ . '/../data/products.json';
        if (USE_DB) {
            $dsn = sprintf('mysql:host=%s;dbname=%s;charset=%s', DB_HOST, DB_NAME, DB_CHARSET);
            $this->pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        }
    }

    /** 全商品を取得（一覧用） */
    public function all(): array
    {
        if ($this->pdo) {
            $rows = $this->pdo->query('SELECT * FROM products ORDER BY sort_order, id')->fetchAll();
            return array_map([$this, 'hydrate'], $rows);
        }
        return array_values($this->readJson());
    }

    /** slug を指定して1商品を取得。見つからなければ null */
    public function find(string $slug): ?array
    {
        if ($this->pdo) {
            $stmt = $this->pdo->prepare('SELECT * FROM products WHERE slug = ? LIMIT 1');
            $stmt->execute([$slug]);
            $row = $stmt->fetch();
            return $row ? $this->hydrate($row) : null;
        }
        $data = $this->readJson();
        return $data[$slug] ?? null;
    }

    /** 1商品を保存（slug が既存なら更新、なければ追加） */
    public function save(array $product): void
    {
        if ($this->pdo) {
            $sql = 'INSERT INTO products (slug, name, model_year, category, image_dir, images, specs, notes)
                    VALUES (:slug, :name, :model_year, :category, :image_dir, :images, :specs, :notes)
                    ON DUPLICATE KEY UPDATE
                        name = VALUES(name), model_year = VALUES(model_year),
                        category = VALUES(category), image_dir = VALUES(image_dir),
                        images = VALUES(images), specs = VALUES(specs), notes = VALUES(notes)';
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                ':slug'       => $product['slug'],
                ':name'       => $product['name'],
                ':model_year' => $product['model_year'] ?? '',
                ':category'   => $product['category'] ?? '',
                ':image_dir'  => $product['image_dir'] ?? '',
                ':images'     => json_encode($product['images'] ?? [], JSON_UNESCAPED_UNICODE),
                ':specs'      => json_encode($product['specs'] ?? [], JSON_UNESCAPED_UNICODE),
                ':notes'      => $product['notes'] ?? '',
            ]);
            return;
        }
        $data = $this->readJson();
        $data[$product['slug']] = $product;
        $this->writeJson($data);
    }

    /** DB の行（images/specs が JSON文字列）を配列に整形 */
    private function hydrate(array $row): array
    {
        $row['images'] = json_decode($row['images'] ?? '[]', true) ?: [];
        $row['specs']  = json_decode($row['specs'] ?? '[]', true) ?: [];
        return $row;
    }

    /** @return array<string,array> slug をキーにした連想配列 */
    private function readJson(): array
    {
        if (!is_file($this->jsonPath)) {
            return [];
        }
        $json = file_get_contents($this->jsonPath);
        $data = json_decode($json, true);
        return is_array($data) ? $data : [];
    }

    private function writeJson(array $data): void
    {
        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        file_put_contents($this->jsonPath, $json, LOCK_EX);
    }
}
