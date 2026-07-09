<?php
/**
 * httpdocs/app/ImageService.php
 * 管理画面でアップロードされた画像を保存し、用途別サイズへ自動リサイズします。
 */

declare(strict_types=1);

final class ImageService
{
    private array $sizes = [
        'large' => [1600, 1200],
        'thumb' => [480, 360],
    ];

    private array $legacyImagePrefixes = ['garage-img/', 'img/', 'order/', 'parts/', 'dash-boad-table/', 'side-table/', 'event/'];

    public function storeUpload(array $file, string $directory = 'products'): ?string
    {
        $stored = $this->storeUploadSet($file, $directory);
        return $stored['large_path'] ?? null;
    }

    public function storeUploadSet(array $file, string $directory = 'products'): ?array
    {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            return null;
        }

        $tmp = (string)$file['tmp_name'];
        $info = @getimagesize($tmp);
        if (!$info) {
            throw new RuntimeException('画像ファイルを判別できません。');
        }

        $source = $this->createImageResource($tmp, (int)$info[2]);
        if (!$source) {
            throw new RuntimeException('対応していない画像形式です。');
        }

        $extension = $this->extensionForType((int)$info[2]);
        $datePath = date('Y/m');
        $safeName = pathinfo((string)$file['name'], PATHINFO_FILENAME);
        $safeName = slugify($safeName);
        $baseName = $safeName . '-' . bin2hex(random_bytes(4));
        $relativeDir = 'uploads/' . trim($directory, '/') . '/' . $datePath;
        $absoluteDir = PUBLIC_ROOT . '/' . $relativeDir;
        if (!is_dir($absoluteDir) && !mkdir($absoluteDir, 0775, true) && !is_dir($absoluteDir)) {
            throw new RuntimeException('アップロード先を作成できません。');
        }

        $originalPath = "{$relativeDir}/{$baseName}-original.{$extension}";
        $this->storeOriginal($tmp, PUBLIC_ROOT . '/' . $originalPath);

        $paths = [
            'original_path' => $originalPath,
        ];
        foreach ($this->sizes as $suffix => [$maxWidth, $maxHeight]) {
            $resized = $this->resizeContain($source, imagesx($source), imagesy($source), $maxWidth, $maxHeight);
            $paths[$suffix . '_path'] = "{$relativeDir}/{$baseName}-{$suffix}.jpg";
            $this->saveImage($resized, PUBLIC_ROOT . '/' . $paths[$suffix . '_path']);
            imagedestroy($resized);
        }
        imagedestroy($source);

        return [
            'path' => $paths['large_path'],
            'original_path' => $paths['original_path'],
            'large_path' => $paths['large_path'],
            'thumb_path' => $paths['thumb_path'],
        ];
    }

    private function createImageResource(string $path, int $type): GdImage|false
    {
        return match ($type) {
            IMAGETYPE_JPEG => imagecreatefromjpeg($path),
            IMAGETYPE_PNG => imagecreatefrompng($path),
            IMAGETYPE_WEBP => function_exists('imagecreatefromwebp') ? imagecreatefromwebp($path) : false,
            default => false,
        };
    }

    private function extensionForType(int $type): string
    {
        return match ($type) {
            IMAGETYPE_JPEG => 'jpg',
            IMAGETYPE_PNG => 'png',
            IMAGETYPE_WEBP => 'webp',
            default => throw new RuntimeException('対応していない画像形式です。'),
        };
    }

    private function storeOriginal(string $tmp, string $path): void
    {
        $stored = is_uploaded_file($tmp) ? move_uploaded_file($tmp, $path) : copy($tmp, $path);
        if (!$stored) {
            throw new RuntimeException('オリジナル画像を保存できません。');
        }
        chmod($path, 0664);
    }

    private function resizeContain(GdImage $source, int $sourceWidth, int $sourceHeight, int $maxWidth, int $maxHeight): GdImage
    {
        $ratio = min($maxWidth / $sourceWidth, $maxHeight / $sourceHeight, 1);
        $width = max(1, (int)round($sourceWidth * $ratio));
        $height = max(1, (int)round($sourceHeight * $ratio));
        $canvas = imagecreatetruecolor($width, $height);
        imagecopyresampled($canvas, $source, 0, 0, 0, 0, $width, $height, $sourceWidth, $sourceHeight);
        return $canvas;
    }

    private function saveImage(GdImage $image, string $path): void
    {
        imageinterlace($image, true);
        imagejpeg($image, $path, 84);
        chmod($path, 0664);
    }

    public function deleteProductImageFiles(array $image): void
    {
        $paths = array_unique(array_filter([
            $image['path'] ?? '',
            $image['original_path'] ?? '',
            $image['large_path'] ?? '',
            $image['thumb_path'] ?? '',
        ]));
        foreach ($paths as $path) {
            $this->deleteManagedProductImage((string)$path);
        }
    }

    public function deletePublicUpload(string $path): void
    {
        $path = trim($path);
        if ($path === '' || !str_starts_with($path, 'uploads/')) {
            return;
        }
        $absolute = PUBLIC_ROOT . '/' . $path;
        $realUploads = realpath(PUBLIC_ROOT . '/uploads');
        $realFile = is_file($absolute) ? realpath($absolute) : false;
        if (!$realUploads || !$realFile || !str_starts_with($realFile, $realUploads . DIRECTORY_SEPARATOR)) {
            return;
        }
        @unlink($realFile);
    }

    public function deleteBrandLogoFiles(string $path): void
    {
        $path = trim(ltrim(str_replace('\\', '/', $path), '/'));
        $this->deletePublicUpload($path);
        if (!preg_match('#^uploads/brands/\d{4}/\d{2}/.+-logo\.png$#', $path)) {
            return;
        }

        $sourcePath = HTTPDOCS_ROOT . '/storage/' . preg_replace('/-logo\.png$/', '-original.ai', $path);
        $storageRoot = realpath(HTTPDOCS_ROOT . '/storage/uploads');
        $sourceRealPath = is_file($sourcePath) ? realpath($sourcePath) : false;
        if (!$storageRoot || !$sourceRealPath || !str_starts_with($sourceRealPath, $storageRoot . DIRECTORY_SEPARATOR)) {
            return;
        }
        @unlink($sourceRealPath);
    }

    private function deleteManagedProductImage(string $path): void
    {
        $path = trim(ltrim(str_replace('\\', '/', $path), '/'));
        if ($path === '' || str_contains($path, '..')) {
            return;
        }
        if (str_starts_with($path, 'uploads/')) {
            $this->deletePublicUpload($path);
            return;
        }
        if (!$this->isLegacyProductImagePath($path)) {
            return;
        }

        $root = rtrim((string)config_value('app.legacy_root'), '/\\');
        $absolute = $root . '/' . $path;
        $realRoot = realpath($root);
        $realFile = is_file($absolute) ? realpath($absolute) : false;
        if (!$realRoot || !$realFile || !str_starts_with($realFile, $realRoot . DIRECTORY_SEPARATOR)) {
            return;
        }
        @unlink($realFile);
    }

    private function isLegacyProductImagePath(string $path): bool
    {
        if (!is_image_path($path)) {
            return false;
        }
        foreach ($this->legacyImagePrefixes as $prefix) {
            if (str_starts_with($path, $prefix)) {
                return true;
            }
        }
        return false;
    }
}
