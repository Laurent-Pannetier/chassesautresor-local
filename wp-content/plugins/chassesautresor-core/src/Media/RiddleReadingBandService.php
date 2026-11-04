<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Media;

/**
 * Cut a tall riddle strip into static horizontal bands.
 *
 * The reader fetches only the bands on screen, then the ones below.
 * Each band is an ordinary file next to the original, so a later move of
 * uploads/_enigmes to Cloudflare (CDN or R2) caches them as-is. No view-time
 * crop, and no cdn-cgi window: Cloudflare image resizing cannot extract an
 * arbitrary vertical slice.
 */
class RiddleReadingBandService
{
    public const BAND_HEIGHT = 1000;

    public static function register(callable $addFilter): void
    {
        $addFilter('wp_generate_attachment_metadata', [self::class, 'afterMetadata'], 100, 2);
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
     * Bands ready to serve. A short image stays the original file.
     *
     * @return list<array{path:string,width:int,height:int}>
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
        if ($height <= self::BAND_HEIGHT || $this->isBandFile($absoluteFile)) {
            return $single;
        }

        $expected = $this->expected($absoluteFile, $width, $height);
        if ($this->ready($expected)) {
            return $expected;
        }

        if (!$this->write($absoluteFile, $width, $expected)) {
            return $single;
        }

        return $this->ready($expected) ? $expected : $single;
    }

    public function isBandFile(string $absoluteFile): bool
    {
        $stem = (string) pathinfo($absoluteFile, PATHINFO_FILENAME);

        return (bool) preg_match('/-band\d+$/', $stem);
    }

    /**
     * @return list<array{path:string,width:int,height:int}>
     */
    private function expected(string $absoluteFile, int $width, int $height): array
    {
        $directory = dirname($absoluteFile);
        $stem = (string) pathinfo($absoluteFile, PATHINFO_FILENAME);
        $bands = [];
        $index = 0;
        for ($top = 0; $top < $height; $top += self::BAND_HEIGHT) {
            $bandHeight = min(self::BAND_HEIGHT, $height - $top);
            $bands[] = [
                'path' => $directory . '/' . $stem . '-band' . $index . '.jpg',
                'width' => $width,
                'height' => $bandHeight,
            ];
            $index++;
        }

        return $bands;
    }

    /**
     * @param list<array{path:string,width:int,height:int}> $bands
     */
    private function ready(array $bands): bool
    {
        foreach ($bands as $band) {
            if (!is_file($band['path'])) {
                return false;
            }
            $info = @getimagesize($band['path']);
            $width = is_array($info) ? (int) ($info[0] ?? 0) : 0;
            $height = is_array($info) ? (int) ($info[1] ?? 0) : 0;
            if ($width !== $band['width'] || $height !== $band['height']) {
                return false;
            }
        }

        return $bands !== [];
    }

    /**
     * @param list<array{path:string,width:int,height:int}> $bands
     */
    private function write(string $absoluteFile, int $width, array $bands): bool
    {
        if (!function_exists('imagecreatetruecolor') || !function_exists('imagejpeg')) {
            return false;
        }

        $source = $this->load($absoluteFile);
        if (!$source instanceof \GdImage) {
            return false;
        }

        $top = 0;
        foreach ($bands as $band) {
            $bandHeight = $band['height'];
            $slice = imagecreatetruecolor($width, $bandHeight);
            if (!$slice instanceof \GdImage) {
                imagedestroy($source);

                return false;
            }
            imagecopy($slice, $source, 0, 0, 0, $top, $width, $bandHeight);
            $written = imagejpeg($slice, $band['path'], 85);
            imagedestroy($slice);
            if ($written !== true) {
                imagedestroy($source);

                return false;
            }
            $top += $bandHeight;
        }
        imagedestroy($source);

        return true;
    }

    private function load(string $absoluteFile): ?\GdImage
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

        return $image instanceof \GdImage ? $image : null;
    }
}
