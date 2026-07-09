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

        $bounds = $this->detectVisiblePngBounds($source);
        $sourceX = $bounds['x'];
        $sourceY = $bounds['y'];
        $sourceWidth = $bounds['width'];
        $sourceHeight = $bounds['height'];
        $ratio = min($maxWidth / $sourceWidth, $maxHeight / $sourceHeight, 1);
        $width = max(1, (int)round($sourceWidth * $ratio));
        $height = max(1, (int)round($sourceHeight * $ratio));
        $canvas = imagecreatetruecolor($width, $height);
        imagealphablending($canvas, false);
        imagesavealpha($canvas, true);
        imagefilledrectangle($canvas, 0, 0, $width, $height, imagecolorallocatealpha($canvas, 0, 0, 0, 127));
        imagecopyresampled($canvas, $source, 0, 0, $sourceX, $sourceY, $width, $height, $sourceWidth, $sourceHeight);
        if (!imagepng($canvas, $targetPath, 6)) {
            imagedestroy($canvas);
            imagedestroy($source);
            throw new RuntimeException('ロゴPNGを保存できませんでした。');
        }
        chmod($targetPath, 0664);
        imagedestroy($canvas);
        imagedestroy($source);
    }

    private function detectVisiblePngBounds($image): array
    {
        $width = imagesx($image);
        $height = imagesy($image);
        $background = $this->samplePngBackground($image, $width, $height);
        $minX = $width;
        $minY = $height;
        $maxX = -1;
        $maxY = -1;

        for ($y = 0; $y < $height; $y++) {
            for ($x = 0; $x < $width; $x++) {
                if (!$this->isLogoPixel($this->pngPixelAt($image, $x, $y), $background)) {
                    continue;
                }
                $minX = min($minX, $x);
                $minY = min($minY, $y);
                $maxX = max($maxX, $x);
                $maxY = max($maxY, $y);
            }
        }

        if ($maxX < 0 || $maxY < 0) {
            return ['x' => 0, 'y' => 0, 'width' => $width, 'height' => $height];
        }

        $cropWidth = $maxX - $minX + 1;
        $cropHeight = $maxY - $minY + 1;
        $padding = min(24, max(4, (int)round(min($cropWidth, $cropHeight) * 0.03)));
        $minX = max(0, $minX - $padding);
        $minY = max(0, $minY - $padding);
        $maxX = min($width - 1, $maxX + $padding);
        $maxY = min($height - 1, $maxY + $padding);

        return [
            'x' => $minX,
            'y' => $minY,
            'width' => $maxX - $minX + 1,
            'height' => $maxY - $minY + 1,
        ];
    }

    private function samplePngBackground($image, int $width, int $height): array
    {
        $points = [
            [0, 0],
            [$width - 1, 0],
            [0, $height - 1],
            [$width - 1, $height - 1],
            [intdiv($width, 2), 0],
            [intdiv($width, 2), $height - 1],
            [0, intdiv($height, 2)],
            [$width - 1, intdiv($height, 2)],
        ];
        $samples = [];
        $transparentCount = 0;
        foreach ($points as [$x, $y]) {
            $pixel = $this->pngPixelAt($image, $x, $y);
            $samples[] = $pixel;
            if ($pixel['alpha'] >= 120) {
                $transparentCount++;
            }
        }
        if ($transparentCount >= 4) {
            return ['red' => 0, 'green' => 0, 'blue' => 0, 'alpha' => 127, 'transparent' => true];
        }

        $opaqueSamples = array_values(array_filter($samples, static fn(array $sample): bool => $sample['alpha'] < 120));
        $count = max(1, count($opaqueSamples));
        return [
            'red' => (int)round(array_sum(array_column($opaqueSamples, 'red')) / $count),
            'green' => (int)round(array_sum(array_column($opaqueSamples, 'green')) / $count),
            'blue' => (int)round(array_sum(array_column($opaqueSamples, 'blue')) / $count),
            'alpha' => (int)round(array_sum(array_column($opaqueSamples, 'alpha')) / $count),
            'transparent' => false,
        ];
    }

    private function pngPixelAt($image, int $x, int $y): array
    {
        $color = imagecolorat($image, $x, $y);
        return [
            'red' => ($color >> 16) & 0xFF,
            'green' => ($color >> 8) & 0xFF,
            'blue' => $color & 0xFF,
            'alpha' => ($color >> 24) & 0x7F,
        ];
    }

    private function isLogoPixel(array $pixel, array $background): bool
    {
        if ($pixel['alpha'] >= 124) {
            return false;
        }
        if ($background['transparent']) {
            return true;
        }

        return max(
            abs($pixel['red'] - $background['red']),
            abs($pixel['green'] - $background['green']),
            abs($pixel['blue'] - $background['blue'])
        ) > 18 || abs($pixel['alpha'] - $background['alpha']) > 8;
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
