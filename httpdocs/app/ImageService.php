<?php
/**
 * httpdocs/app/ImageService.php
 * 管理画面でアップロードされた画像を保存し、用途別サイズへ自動リサイズします。
 */

declare(strict_types=1);

final class ImageService
{
    private array $sizes = [
        'large' => [1600, 1100],
        'card' => [900, 620],
        'thumb' => [420, 300],
    ];

    public function storeUpload(array $file, string $directory = 'products'): ?string
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

        $datePath = date('Y/m');
        $safeName = pathinfo((string)$file['name'], PATHINFO_FILENAME);
        $safeName = slugify($safeName);
        $baseName = $safeName . '-' . bin2hex(random_bytes(4));
        $relativeDir = 'uploads/' . trim($directory, '/') . '/' . $datePath;
        $absoluteDir = PUBLIC_ROOT . '/' . $relativeDir;
        if (!is_dir($absoluteDir) && !mkdir($absoluteDir, 0775, true) && !is_dir($absoluteDir)) {
            throw new RuntimeException('アップロード先を作成できません。');
        }

        foreach ($this->sizes as $suffix => [$maxWidth, $maxHeight]) {
            $resized = $this->resizeContain($source, imagesx($source), imagesy($source), $maxWidth, $maxHeight);
            $this->saveImage($resized, "{$absoluteDir}/{$baseName}-{$suffix}.jpg");
            imagedestroy($resized);
        }
        imagedestroy($source);

        return "{$relativeDir}/{$baseName}-large.jpg";
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
    }
}
