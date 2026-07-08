<?php
/**
 * httpdocs/app/FileUploadService.php
 * PDFやブランドロゴなど、画像リサイズを伴わない管理画面アップロードを保存します。
 */

declare(strict_types=1);

final class FileUploadService
{
    public function storePdf(array $file, string $directory = 'documents'): ?string
    {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            return null;
        }

        $name = (string)($file['name'] ?? '');
        $tmp = (string)($file['tmp_name'] ?? '');
        if ($tmp === '' || !is_file($tmp)) {
            throw new RuntimeException('PDFファイルを確認できません。');
        }

        $extension = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        $mime = (string)(@mime_content_type($tmp) ?: '');
        if ($extension !== 'pdf' || !in_array($mime, ['application/pdf', 'application/x-pdf', 'application/octet-stream'], true)) {
            throw new RuntimeException('PDFファイルのみアップロードできます。');
        }

        return $this->storeFile($file, $directory, 'pdf');
    }

    public function storeImageOriginal(array $file, string $directory = 'brands'): ?string
    {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            return null;
        }

        $tmp = (string)($file['tmp_name'] ?? '');
        $info = @getimagesize($tmp);
        if (!$info) {
            throw new RuntimeException('画像ファイルを判別できません。');
        }

        $extension = match ((int)$info[2]) {
            IMAGETYPE_JPEG => 'jpg',
            IMAGETYPE_PNG => 'png',
            IMAGETYPE_GIF => 'gif',
            IMAGETYPE_WEBP => 'webp',
            default => throw new RuntimeException('対応していない画像形式です。'),
        };

        return $this->storeFile($file, $directory, $extension);
    }

    private function storeFile(array $file, string $directory, string $extension): string
    {
        $datePath = date('Y/m');
        $safeName = slugify(pathinfo((string)$file['name'], PATHINFO_FILENAME));
        $baseName = $safeName . '-' . bin2hex(random_bytes(4));
        $relativeDir = 'uploads/' . trim($directory, '/') . '/' . $datePath;
        $absoluteDir = PUBLIC_ROOT . '/' . $relativeDir;
        if (!is_dir($absoluteDir) && !mkdir($absoluteDir, 0775, true) && !is_dir($absoluteDir)) {
            throw new RuntimeException('アップロード先を作成できません。');
        }

        $relativePath = "{$relativeDir}/{$baseName}.{$extension}";
        $absolutePath = PUBLIC_ROOT . '/' . $relativePath;
        $tmp = (string)$file['tmp_name'];
        $stored = is_uploaded_file($tmp)
            ? move_uploaded_file($tmp, $absolutePath)
            : copy($tmp, $absolutePath);
        if (!$stored) {
            throw new RuntimeException('アップロードファイルを保存できません。');
        }
        chmod($absolutePath, 0664);

        return $relativePath;
    }
}
