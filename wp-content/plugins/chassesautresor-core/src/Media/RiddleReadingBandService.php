<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Media;

/**
 * Cut a tall riddle strip into static horizontal bands.
 *
 * The reader fetches the bands on screen, plus about one viewport ahead.
 * Each band is an ordinary file next to the original, so a later move of
 * uploads/_enigmes to Cloudflare (CDN or R2) caches them as-is. No view-time
 * crop, and no cdn-cgi window: Cloudflare image resizing cannot extract an
 * arbitrary vertical slice.
 *
 * Bands are WebP when the server can write it, otherwise JPEG. Widths of
 * 720 and 1000 sit beside the full strip so the theme srcset can match the
 * column. The crop is done with Imagick when it is present: GD would hold
 * the whole strip inside the PHP memory limit.
 */
class RiddleReadingBandService
{
    public const BAND_HEIGHT = 1008;

    public const OVERLAP = 1;

    /** @var list<int> */
    public const VARIANT_WIDTHS = [720, 1000];

    private const WEBP_QUALITY = 82;

    private const JPEG_QUALITY = 85;

    private ?string $extension = null;

    public function __construct(private string $engine = '')
    {
    }

    public static function register(callable $addFilter): void
    {
        $addFilter('wp_generate_attachment_metadata', [self::class, 'afterMetadata'], 100, 2);
        $addFilter('intermediate_image_sizes_advanced', [self::class, 'afterSizes'], 10, 3);
    }

    public static function splits(int $height): bool
    {
        return $height > self::BAND_HEIGHT;
    }

    /**
     * @param mixed $metadata
     * @param mixed $attachmentId
     * @return mixed
     */
    public static function afterMetadata($metadata, $attachmentId = 0)
    {
        if (!is_array($metadata) || !function_exists('get_attached_file')) {
            return $metadata;
        }

        $file = get_attached_file((int) $attachmentId);
        $normalized = str_replace('\\', '/', (string) $file);
        if ($file === '' || !is_file($file) || !str_contains($normalized, '/_enigmes/')) {
            return $metadata;
        }

        (new self())->ensure($file);

        return $metadata;
    }

    /**
     * Drop intermediates whose height would stay taller than one band.
     * Thumbnail and medium stay: their box is small on both axes.
     *
     * @param mixed $sizes
     * @param mixed $metadata
     * @param mixed $attachmentId
     * @return mixed
     */
    public static function afterSizes($sizes, $metadata = null, $attachmentId = 0)
    {
        if (!is_array($sizes)) {
            return $sizes;
        }

        $width = is_array($metadata) ? (int) ($metadata['width'] ?? 0) : 0;
        $height = is_array($metadata) ? (int) ($metadata['height'] ?? 0) : 0;
        $file = is_array($metadata) ? (string) ($metadata['file'] ?? '') : '';
        if ($file === '' && function_exists('get_attached_file')) {
            $file = (string) get_attached_file((int) $attachmentId);
        }

        return (new self())->filterSubsizes($sizes, $width, $height, $file);
    }

    /**
     * Bands ready to serve. A short image stays the original file.
     *
     * @return list<array{
     *     path:string,
     *     width:int,
     *     height:int,
     *     sources?:list<array{path:string,width:int,height:int}>
     * }>
     */
    public function ensure(string $absoluteFile): array
    {
        if (!is_file($absoluteFile)) {
            return [];
        }

        $info = @getimagesize($absoluteFile);
        $width = is_array($info) ? (int) ($info[0] ?? 0) : 0;
        $height = is_array($info) ? (int) ($info[1] ?? 0) : 0;
        if ($width <= 0 || $height <= 0) {
            return [];
        }

        $single = [['path' => $absoluteFile, 'width' => $width, 'height' => $height]];
        if (!self::splits($height) || $this->isBandFile($absoluteFile)) {
            return $single;
        }

        $expected = $this->expected($absoluteFile, $width, $height);
        if ($this->ready($expected)) {
            $this->forgetStale($absoluteFile, $expected);

            return $this->publish($expected);
        }

        if (!$this->write($absoluteFile, $width, $expected) || !$this->ready($expected)) {
            return $single;
        }

        $this->forgetStale($absoluteFile, $expected);

        return $this->publish($expected);
    }

    public function isBandFile(string $absoluteFile): bool
    {
        $stem = (string) pathinfo($absoluteFile, PATHINFO_FILENAME);

        return (bool) preg_match('/-band\d+(?:-w\d+)?$/', $stem);
    }

    /**
     * @param array<int|string, mixed> $sizes
     * @return array<int|string, mixed>
     */
    public function filterSubsizes(array $sizes, int $width, int $height, string $file): array
    {
        if ($width <= 0 || !self::splits($height) || !$this->isStripFile($file)) {
            return $sizes;
        }

        $kept = [];
        foreach ($sizes as $name => $size) {
            if (!is_array($size)) {
                $kept[$name] = $size;
                continue;
            }
            if ($this->outputHeight($width, $height, $size) <= self::BAND_HEIGHT) {
                $kept[$name] = $size;
            }
        }

        return $kept;
    }

    /**
     * @param array<string, mixed> $size
     */
    private function outputHeight(int $sourceWidth, int $sourceHeight, array $size): int
    {
        $maxWidth = (int) ($size['width'] ?? 0);
        $maxHeight = (int) ($size['height'] ?? 0);
        if (!empty($size['crop']) && $maxWidth > 0 && $maxHeight > 0) {
            return min($sourceHeight, $maxHeight);
        }

        $scale = 1.0;
        if ($maxWidth > 0 && $sourceWidth > $maxWidth) {
            $scale = min($scale, $maxWidth / $sourceWidth);
        }
        if ($maxHeight > 0 && $sourceHeight > $maxHeight) {
            $scale = min($scale, $maxHeight / $sourceHeight);
        }

        return (int) round($sourceHeight * $scale);
    }

    private function isStripFile(string $file): bool
    {
        $normalized = str_replace('\\', '/', $file);

        return str_contains($normalized, '/_enigmes/')
            || str_starts_with($normalized, '_enigmes/');
    }

    /**
     * @return list<array{top:int,height:int}>
     */
    private function slices(int $height): array
    {
        $slices = [];
        $top = 0;
        while ($top < $height) {
            $bandHeight = min(self::BAND_HEIGHT, $height - $top);
            if ($slices !== [] && $bandHeight <= self::OVERLAP) {
                break;
            }
            $slices[] = ['top' => $top, 'height' => $bandHeight];
            if ($top + $bandHeight >= $height) {
                break;
            }
            $top += $bandHeight - self::OVERLAP;
        }

        return $slices;
    }

    /**
     * @return list<array{
     *     path:string,
     *     width:int,
     *     height:int,
     *     top:int,
     *     sources:list<array{path:string,width:int,height:int}>
     * }>
     */
    private function expected(string $absoluteFile, int $width, int $height): array
    {
        $directory = dirname($absoluteFile);
        $stem = (string) pathinfo($absoluteFile, PATHINFO_FILENAME);
        $extension = $this->extension();
        $bands = [];
        foreach ($this->slices($height) as $index => $slice) {
            $bandHeight = $slice['height'];
            $fullPath = $directory . '/' . $stem . '-band' . $index . '.' . $extension;
            $sources = [];
            foreach (self::VARIANT_WIDTHS as $variantWidth) {
                if ($variantWidth >= $width) {
                    continue;
                }
                $sources[] = [
                    'path' => $directory . '/' . $stem . '-band' . $index
                        . '-w' . $variantWidth . '.' . $extension,
                    'width' => $variantWidth,
                    'height' => max(1, (int) round($bandHeight * $variantWidth / $width)),
                ];
            }
            $sources[] = [
                'path' => $fullPath,
                'width' => $width,
                'height' => $bandHeight,
            ];
            $bands[] = [
                'path' => $fullPath,
                'width' => $width,
                'height' => $bandHeight,
                'top' => $slice['top'],
                'sources' => $sources,
            ];
        }

        return $bands;
    }

    /**
     * @param list<array{sources:list<array{path:string,width:int,height:int}>}> $bands
     */
    private function ready(array $bands): bool
    {
        if ($bands === []) {
            return false;
        }
        foreach ($bands as $band) {
            foreach ($band['sources'] as $source) {
                if (!$this->fileMatches($source['path'], $source['width'], $source['height'])) {
                    return false;
                }
            }
        }

        return true;
    }

    private function fileMatches(string $path, int $width, int $height): bool
    {
        if (!is_file($path)) {
            return false;
        }
        $info = @getimagesize($path);
        $actualWidth = is_array($info) ? (int) ($info[0] ?? 0) : 0;
        $actualHeight = is_array($info) ? (int) ($info[1] ?? 0) : 0;

        return $actualWidth === $width && $actualHeight === $height;
    }

    /**
     * @param list<array{
     *     path:string,
     *     width:int,
     *     height:int,
     *     top:int,
     *     sources:list<array{path:string,width:int,height:int}>
     * }> $bands
     * @return list<array{
     *     path:string,
     *     width:int,
     *     height:int,
     *     sources:list<array{path:string,width:int,height:int}>
     * }>
     */
    private function publish(array $bands): array
    {
        $public = [];
        foreach ($bands as $band) {
            unset($band['top']);
            $public[] = $band;
        }

        return $public;
    }

    /**
     * @param list<array{sources:list<array{path:string}>}> $bands
     */
    private function forgetStale(string $absoluteFile, array $bands): void
    {
        $directory = dirname($absoluteFile);
        $stem = (string) pathinfo($absoluteFile, PATHINFO_FILENAME);
        $keep = [];
        foreach ($bands as $band) {
            foreach ($band['sources'] as $source) {
                $keep[$source['path']] = true;
            }
        }
        $entries = scandir($directory);
        if ($entries === false) {
            return;
        }
        $pattern = '/^' . preg_quote($stem, '/') . '-band\d+(?:-w\d+)?\.(?:jpe?g|webp)$/i';
        foreach ($entries as $entry) {
            if (!preg_match($pattern, $entry)) {
                continue;
            }
            $path = $directory . '/' . $entry;
            if (!isset($keep[$path]) && is_file($path)) {
                unlink($path);
            }
        }
    }

    /**
     * @param list<array{
     *     path:string,
     *     width:int,
     *     height:int,
     *     top:int,
     *     sources:list<array{path:string,width:int,height:int}>
     * }> $bands
     */
    private function write(string $absoluteFile, int $width, array $bands): bool
    {
        if ($this->engine === 'imagick') {
            return $this->writeWithImagick($absoluteFile, $width, $bands);
        }
        if ($this->engine === 'gd') {
            return $this->writeWithGd($absoluteFile, $width, $bands);
        }
        if ($this->writeWithImagick($absoluteFile, $width, $bands)) {
            return true;
        }

        return $this->writeWithGd($absoluteFile, $width, $bands);
    }

    /**
     * @param list<array{
     *     path:string,
     *     width:int,
     *     height:int,
     *     top:int,
     *     sources:list<array{path:string,width:int,height:int}>
     * }> $bands
     */
    private function writeWithImagick(string $absoluteFile, int $width, array $bands): bool
    {
        if (!class_exists(\Imagick::class)) {
            return false;
        }

        $source = null;
        try {
            $source = new \Imagick();
            $source->readImage($absoluteFile);
            $source->setFirstIterator();
            $this->flattenImagick($source);
            foreach ($bands as $band) {
                $slice = clone $source;
                $slice->cropImage($width, (int) $band['height'], 0, (int) $band['top']);
                $slice->setImagePage(0, 0, 0, 0);
                $written = $this->writeImagickSlice($slice, $band);
                $slice->clear();
                if (!$written) {
                    return false;
                }
            }

            return true;
        } catch (\Throwable $exception) {
            return false;
        } finally {
            if ($source instanceof \Imagick) {
                $source->clear();
            }
        }
    }

    private function flattenImagick(\Imagick $image): void
    {
        $image->setImageBackgroundColor('white');
        if (!defined('Imagick::ALPHACHANNEL_REMOVE')) {
            return;
        }
        try {
            $image->setImageAlphaChannel(\Imagick::ALPHACHANNEL_REMOVE);
        } catch (\Throwable $exception) {
            return;
        }
    }

    /**
     * @param array{
     *     sources:list<array{path:string,width:int,height:int}>
     * } $band
     */
    private function writeImagickSlice(\Imagick $slice, array $band): bool
    {
        foreach ($band['sources'] as $source) {
            $frame = $slice->getImageWidth() === $source['width']
                && $slice->getImageHeight() === $source['height']
                ? $slice
                : clone $slice;
            if ($frame !== $slice) {
                $frame->resizeImage(
                    $source['width'],
                    $source['height'],
                    \Imagick::FILTER_LANCZOS,
                    1
                );
            }
            $written = $this->storeImagick($frame, $source['path'], $source['width'], $source['height']);
            if ($frame !== $slice) {
                $frame->clear();
            }
            if (!$written) {
                return false;
            }
        }

        return true;
    }

    private function storeImagick(\Imagick $image, string $path, int $width, int $height): bool
    {
        $image->setImageFormat($this->extension());
        $image->setImageCompressionQuality($this->quality());
        try {
            $image->writeImage($path);
        } catch (\Throwable $exception) {
            return false;
        }

        return $this->fileMatches($path, $width, $height);
    }

    /**
     * @param list<array{
     *     height:int,
     *     top:int,
     *     sources:list<array{path:string,width:int,height:int}>
     * }> $bands
     */
    private function writeWithGd(string $absoluteFile, int $width, array $bands): bool
    {
        if (!function_exists('imagecreatetruecolor')) {
            return false;
        }
        $source = $this->loadGd($absoluteFile);
        if (!$source instanceof \GdImage) {
            return false;
        }

        foreach ($bands as $band) {
            $bandHeight = (int) $band['height'];
            $slice = imagecreatetruecolor($width, $bandHeight);
            if (!$slice instanceof \GdImage) {
                imagedestroy($source);

                return false;
            }
            $white = imagecolorallocate($slice, 255, 255, 255);
            imagefilledrectangle($slice, 0, 0, $width, $bandHeight, $white);
            imagecopy($slice, $source, 0, 0, 0, (int) $band['top'], $width, $bandHeight);
            $written = $this->writeGdSlice($slice, $band);
            imagedestroy($slice);
            if (!$written) {
                imagedestroy($source);

                return false;
            }
        }
        imagedestroy($source);

        return true;
    }

    /**
     * @param array{sources:list<array{path:string,width:int,height:int}>} $band
     */
    private function writeGdSlice(\GdImage $slice, array $band): bool
    {
        foreach ($band['sources'] as $source) {
            $frame = $slice;
            $owned = false;
            if (imagesx($slice) !== $source['width'] || imagesy($slice) !== $source['height']) {
                if (!function_exists('imagescale')) {
                    return false;
                }
                $scaled = imagescale($slice, $source['width'], $source['height']);
                if (!$scaled instanceof \GdImage) {
                    return false;
                }
                $frame = $scaled;
                $owned = true;
            }
            $written = $this->storeGd($frame, $source['path'], $source['width'], $source['height']);
            if ($owned) {
                imagedestroy($frame);
            }
            if (!$written) {
                return false;
            }
        }

        return true;
    }

    private function storeGd(\GdImage $image, string $path, int $width, int $height): bool
    {
        $written = $this->extension() === 'webp'
            ? imagewebp($image, $path, self::WEBP_QUALITY)
            : imagejpeg($image, $path, self::JPEG_QUALITY);

        return $written === true && $this->fileMatches($path, $width, $height);
    }

    private function loadGd(string $absoluteFile): ?\GdImage
    {
        $info = @getimagesize($absoluteFile);
        $type = is_array($info) ? (int) ($info[2] ?? 0) : 0;
        $image = match ($type) {
            IMAGETYPE_JPEG => function_exists('imagecreatefromjpeg')
                ? @imagecreatefromjpeg($absoluteFile)
                : false,
            IMAGETYPE_PNG => function_exists('imagecreatefrompng')
                ? @imagecreatefrompng($absoluteFile)
                : false,
            IMAGETYPE_GIF => function_exists('imagecreatefromgif')
                ? @imagecreatefromgif($absoluteFile)
                : false,
            IMAGETYPE_WEBP => function_exists('imagecreatefromwebp')
                ? @imagecreatefromwebp($absoluteFile)
                : false,
            default => false,
        };
        if (!$image instanceof \GdImage) {
            return null;
        }
        if (function_exists('imagepalettetotruecolor')) {
            imagepalettetotruecolor($image);
        }

        return $image;
    }

    private function extension(): string
    {
        if ($this->extension === null) {
            $this->extension = $this->canWriteWebp() ? 'webp' : 'jpg';
        }

        return $this->extension;
    }

    private function quality(): int
    {
        return $this->extension() === 'webp' ? self::WEBP_QUALITY : self::JPEG_QUALITY;
    }

    private function canWriteWebp(): bool
    {
        if ($this->engine !== 'gd' && class_exists(\Imagick::class)) {
            try {
                $formats = \Imagick::queryFormats('WEBP');
                if (is_array($formats) && $formats !== []) {
                    return true;
                }
            } catch (\Throwable $exception) {
                // GD may still write WebP.
            }
        }

        return function_exists('imagewebp');
    }
}
