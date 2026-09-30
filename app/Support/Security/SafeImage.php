<?php

namespace App\Support\Security;

use Illuminate\Http\UploadedFile;
use InvalidArgumentException;
use Spatie\Image\Enums\Fit;
use Spatie\Image\Image;

/**
 * Re-encodes user photos before storage. Decoding and re-encoding with GD:
 * - removes EXIF metadata (GPS position of the author's phone, device serial),
 * - drops anything appended to or hidden in the file (polyglot payloads),
 * - caps the resolution.
 */
final class SafeImage
{
    public const MAX_DIMENSION = 2560;

    /**
     * @return string Path of a new, clean JPEG in the temp directory.
     */
    public static function reencode(UploadedFile $file): string
    {
        $info = @getimagesize($file->getRealPath());

        if ($info === false || ! in_array($info[2], [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_WEBP], true)) {
            throw new InvalidArgumentException('Unsupported image.');
        }

        if ($info[0] > 12000 || $info[1] > 12000) {
            throw new InvalidArgumentException('Image too large.');
        }

        $target = tempnam(sys_get_temp_dir(), 'img').'.jpg';

        Image::load($file->getRealPath())
            ->fit(Fit::Max, self::MAX_DIMENSION, self::MAX_DIMENSION)
            ->quality(82)
            ->format('jpg')
            ->save($target);

        return $target;
    }
}
