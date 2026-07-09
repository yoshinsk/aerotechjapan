<?php
/**
 * httpdocs/app/FileUploadService.php
 * PDFやブランドロゴなど、画像リサイズを伴わない管理画面アップロードを保存します。
 */

declare(strict_types=1);

final class FileUploadService
{
    public function storeBrandLogo(array $file, string $directory = 'brands'): ?string
    {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            return null;
        }

        $extension = strtolower(pathinfo((string)($file['name'] ?? ''), PATHINFO_EXTENSION));
        if ($extension === 'ai') {
            return $this->storeIllustratorLogo($file, $directory);
        }

        return $this->storeImageOriginal($file, $directory);
    }

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

    private function storeIllustratorLogo(array $file, string $directory): string
    {
        $tmp = (string)($file['tmp_name'] ?? '');
        if ($tmp === '' || !is_file($tmp)) {
            throw new RuntimeException('AIファイルを確認できません。');
        }

        $convertedPath = $this->convertIllustratorToPng($tmp);
        try {
            if (!@getimagesize($convertedPath)) {
                throw new RuntimeException('AIファイルをPNGへ変換できませんでした。');
            }

            $datePath = date('Y/m');
            $safeName = slugify(pathinfo((string)$file['name'], PATHINFO_FILENAME));
            $baseName = $safeName . '-' . bin2hex(random_bytes(4));
            $relativeDir = 'uploads/' . trim($directory, '/') . '/' . $datePath;
            $publicDir = PUBLIC_ROOT . '/' . $relativeDir;
            $storageDir = HTTPDOCS_ROOT . '/storage/' . $relativeDir;
            $publicPath = "{$relativeDir}/{$baseName}-logo.png";
            $sourcePath = "{$storageDir}/{$baseName}-original.ai";
            $absolutePublicPath = PUBLIC_ROOT . '/' . $publicPath;

            $this->ensureDirectory($publicDir);
            $this->ensureDirectory($storageDir);
            if (!copy($tmp, $sourcePath)) {
                throw new RuntimeException('AI原本を保存できませんでした。');
            }
            chmod($sourcePath, 0664);

            try {
                $this->resizePngContain($convertedPath, $absolutePublicPath, 1200, 600);
            } catch (Throwable $e) {
                @unlink($sourcePath);
                throw $e;
            }
            return $publicPath;
        } finally {
            @unlink($convertedPath);
            @rmdir(dirname($convertedPath));
        }
    }

    private function convertIllustratorToPng(string $sourcePath): string
    {
        $workDir = sys_get_temp_dir() . '/aerotech-ai-' . bin2hex(random_bytes(6));
        if (!mkdir($workDir, 0700) && !is_dir($workDir)) {
            throw new RuntimeException('AI変換用の一時ディレクトリを作成できません。');
        }

        $outputPath = $workDir . '/logo.png';
        $binary = $this->ghostscriptBinary();
        $command = implode(' ', [
            escapeshellarg($binary),
            '-dSAFER',
            '-dBATCH',
            '-dNOPAUSE',
            '-dQUIET',
            '-dFirstPage=1',
            '-dLastPage=1',
            '-dEPSCrop',
            '-dTextAlphaBits=4',
            '-dGraphicsAlphaBits=4',
            '-sDEVICE=pngalpha',
            '-r300',
            '-sOutputFile=' . escapeshellarg($outputPath),
            escapeshellarg($sourcePath),
            '2>&1',
        ]);

        $output = [];
        $exitCode = 1;
        exec($command, $output, $exitCode);
        if ($exitCode !== 0 || !is_file($outputPath) || filesize($outputPath) < 1) {
            @unlink($outputPath);
            @rmdir($workDir);
            throw new RuntimeException('AIファイルをPNGへ変換できませんでした。Illustratorで「PDF互換ファイルを作成」を有効にして保存し直してください。');
        }
        return $outputPath;
    }

    private function resizePngContain(string $sourcePath, string $targetPath, int $maxWidth, int $maxHeight): void
    {
        $source = @imagecreatefrompng($sourcePath);
        if (!$source) {
            throw new RuntimeException('変換済みPNGを読み込めませんでした。');
        }

        $sourceWidth = imagesx($source);
        $sourceHeight = imagesy($source);
        $ratio = min($maxWidth / $sourceWidth, $maxHeight / $sourceHeight, 1);
        $width = max(1, (int)round($sourceWidth * $ratio));
        $height = max(1, (int)round($sourceHeight * $ratio));
        $canvas = imagecreatetruecolor($width, $height);
        imagealphablending($canvas, false);
        imagesavealpha($canvas, true);
        imagefilledrectangle($canvas, 0, 0, $width, $height, imagecolorallocatealpha($canvas, 0, 0, 0, 127));
        imagecopyresampled($canvas, $source, 0, 0, 0, 0, $width, $height, $sourceWidth, $sourceHeight);
        if (!imagepng($canvas, $targetPath, 6)) {
            imagedestroy($canvas);
            imagedestroy($source);
            throw new RuntimeException('ロゴPNGを保存できませんでした。');
        }
        chmod($targetPath, 0664);
        imagedestroy($canvas);
        imagedestroy($source);
    }

    private function ghostscriptBinary(): string
    {
        foreach (['/usr/bin/gs', '/usr/local/bin/gs'] as $binary) {
            if (is_executable($binary)) {
                return $binary;
            }
        }

        $output = [];
        $exitCode = 1;
        exec('command -v gs 2>/dev/null', $output, $exitCode);
        if ($exitCode === 0 && !empty($output[0])) {
            return trim($output[0]);
        }
        throw new RuntimeException('Ghostscriptが見つからないため、AIファイルを変換できません。');
    }

    private function ensureDirectory(string $directory): void
    {
        if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
            throw new RuntimeException('アップロード先ディレクトリを作成できません。');
        }
    }
}
