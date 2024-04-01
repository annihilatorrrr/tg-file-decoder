<?php declare(strict_types=1);
/**
 * Decoded FileId class.
 *
 * This file is part of tg-file-decoder.
 * tg-file-decoder is free software: you can redistribute it and/or modify it under the terms of the GNU Affero General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
 * tg-file-decoder is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 * See the GNU Affero General Public License for more details.
 * You should have received a copy of the GNU General Public License along with tg-file-decoder.
 * If not, see <http://www.gnu.org/licenses/>.
 *
 * @author    Daniil Gentili <daniil@daniil.it>
 * @copyright 2016-2019 Daniil Gentili <daniil@daniil.it>
 * @license   https://opensource.org/licenses/AGPL-3.0 AGPLv3
 *
 * @link https://github.com/tg-file-decoder Documentation
 */

namespace danog\Decoder;

use danog\Decoder\PhotoSizeSource\PhotoSizeSourceDialogPhoto;
use danog\Decoder\PhotoSizeSource\PhotoSizeSourceDialogPhotoBig;
use danog\Decoder\PhotoSizeSource\PhotoSizeSourceDialogPhotoSmall;
use danog\Decoder\PhotoSizeSource\PhotoSizeSourceLegacy;
use danog\Decoder\PhotoSizeSource\PhotoSizeSourceStickersetThumbnail;
use danog\Decoder\PhotoSizeSource\PhotoSizeSourceStickersetThumbnailVersion;
use danog\Decoder\PhotoSizeSource\PhotoSizeSourceThumbnail;

/**
 * Represents decoded bot API file ID.
 *
 * @api
 */
final class FileId
{
    /**
     * Basic constructor function.
     */
    public function __construct(
        /**
         * DC ID.
         *
         */
        public readonly int $dcId,

        /**
         * File type.
         *
         */
        public readonly FileIdType $type,

        /**
         * File id.
         *
         */
        public readonly ?int $id,
        /**
         * File access hash.
         *
         */
        public readonly int $accessHash,

        /**
         * Photo size source.
         *
         */
        public readonly ?PhotoSizeSource $photoSizeSource = null,

        /**
         * Photo volume ID.
         *
         */
        public readonly ?int $volumeId = null,
        /**
         * Photo local ID.
         *
         */
        public readonly ?int $localId = null,

        /**
         * File reference.
         *
         */
        public readonly ?string $fileReference = null,
        /**
         * File URL for weblocation.
         *
         */
        public readonly ?string $url = null,

        /**
         * Bot API file ID version.
         *
         */
        public readonly int $version = 4,

        /**
         * Bot API file ID subversion.
         *
         */
        public readonly int $subVersion = 47,
    ) {
    }

    /**
     * Decode file ID from bot API file ID.
     *
     * @param string $fileId File ID
     *
     */
    public static function fromBotAPI(string $fileId): self
    {
        $orig = $fileId;
        $fileId = rleDecode(base64urlDecode($fileId));
        $version = \ord($fileId[\strlen($fileId) - 1]);
        $subVersion = $version === 4 ? \ord($fileId[\strlen($fileId) - 2]) : 0;

        $res = \fopen('php://memory', 'rw+b');
        \assert($res !== false);
        \fwrite($res, $fileId);
        \fseek($res, 0);
        $fileId = $res;
        $read = function (int $length) use (&$fileId): string {
            $res = \stream_get_contents($fileId, $length);
            \assert($res !== false);
            return $res;
        };

        $typeId = unpackInt($read(4));
        $dc_id = unpackInt($read(4));
        $fileReference = $typeId & FILE_REFERENCE_FLAG ? readTLString($fileId) : null;
        $hasWebLocation = (bool) ($typeId & WEB_LOCATION_FLAG);
        $typeId &= ~FILE_REFERENCE_FLAG;
        $typeId &= ~WEB_LOCATION_FLAG;

        if ($hasWebLocation) {
            $url = readTLString($fileId);
            $access_hash = unpackLong($read(8));
            return new self(
                $dc_id,
                FileIdType::from($typeId),
                null,
                $access_hash,
                fileReference: $fileReference,
                url: $url,
                version: $version,
                subVersion: $subVersion
            );
        }
        $id = unpackLong($read(8));
        $access_hash = unpackLong($read(8));

        $volume_id = null;
        $local_id = null;
        $photoSizeSource = null;
        if ($typeId <= FileIdType::PHOTO->value) {
            if ($subVersion < 32) {
                $volume_id = unpackLong($read(8));
                $local_id = unpackInt($read(4));
            }

            /** @psalm-suppress MixedArgument */
            $photosize_source = PhotoSizeSourceType::from($subVersion >= 4 ? \unpack('V', $read(4))[1] : 0);
            switch ($photosize_source) {
                case PhotoSizeSourceType::LEGACY:
                    $photoSizeSource = new PhotoSizeSourceLegacy(unpackLong($read(8)));
                    break;
                case PhotoSizeSourceType::FULL_LEGACY:
                    $volume_id = unpackLong($read(8));
                    $photoSizeSource = new PhotoSizeSourceLegacy(unpackLong($read(8)));
                    $local_id = unpackInt($read(4));
                    break;
                case PhotoSizeSourceType::THUMBNAIL:
                    /** @var array{file_type: int, thumbnail_type: string} */
                    $result = \unpack('Vfile_type/athumbnail_type', $read(8));
                    $photoSizeSource = new PhotoSizeSourceThumbnail(
                        FileIdType::from($result['file_type']),
                        $result['thumbnail_type']
                    );
                    break;
                case PhotoSizeSourceType::DIALOGPHOTO_BIG:
                case PhotoSizeSourceType::DIALOGPHOTO_SMALL:
                    $clazz = $photosize_source === PhotoSizeSourceType::DIALOGPHOTO_SMALL
                        ? PhotoSizeSourceDialogPhotoSmall::class
                        : PhotoSizeSourceDialogPhotoBig::class;
                    $photoSizeSource = new $clazz(
                        unpackLong($read(8)),
                        unpackLong($read(8)),
                    );
                    break;
                case PhotoSizeSourceType::STICKERSET_THUMBNAIL:
                    $photoSizeSource = new PhotoSizeSourceStickersetThumbnail(
                        unpackLong($read(8)),
                        unpackLong($read(8))
                    );
                    break;
                case PhotoSizeSourceType::DIALOGPHOTO_BIG_LEGACY:
                case PhotoSizeSourceType::DIALOGPHOTO_SMALL_LEGACY:
                    $clazz = $photosize_source === PhotoSizeSourceType::DIALOGPHOTO_SMALL_LEGACY
                        ? PhotoSizeSourceDialogPhotoSmall::class
                        : PhotoSizeSourceDialogPhotoBig::class;
                    $photoSizeSource = new $clazz(
                        unpackLong($read(8)),
                        unpackLong($read(8))
                    );

                    $volume_id = unpackLong($read(8));
                    $local_id = unpackInt($read(4));
                    break;
                case PhotoSizeSourceType::STICKERSET_THUMBNAIL_LEGACY:
                    $photoSizeSource = new PhotoSizeSourceStickersetThumbnail(
                        unpackLong($read(8)),
                        unpackLong($read(8)),
                    );

                    $volume_id = unpackLong($read(8));
                    $local_id = unpackInt($read(4));
                    break;
                case PhotoSizeSourceType::STICKERSET_THUMBNAIL_VERSION:
                    $photoSizeSource = new PhotoSizeSourceStickersetThumbnailVersion(
                        unpackLong($read(8)),
                        unpackLong($read(8)),
                        unpackInt($read(4))
                    );
                    break;
            }
        }
        $l = \fstat($fileId)['size'] - \ftell($fileId);
        $l -= $version >= 4 ? 2 : 1;
        if ($l > 0) {
            \trigger_error("File ID $orig has $l bytes of leftover data");
        }

        return new self(
            dcId: $dc_id,
            type: FileIdType::from($typeId),
            id: $id,
            accessHash: $access_hash,
            volumeId: $volume_id,
            localId: $local_id,
            fileReference: $fileReference,
            version: $version,
            subVersion: $subVersion,
            photoSizeSource: $photoSizeSource,
        );
    }

    /**
     * Get bot API file ID.
     *
     */
    public function getBotAPI(): string
    {
        $type = $this->type->value;
        if ($this->fileReference !== null) {
            $type |= FILE_REFERENCE_FLAG;
        }
        if ($this->url !== null) {
            $type |= WEB_LOCATION_FLAG;
        }

        $fileId = \pack('VV', $type, $this->dcId);
        if ($this->fileReference !== null) {
            $fileId .= packTLString($this->fileReference);
        }
        if ($this->url !== null) {
            $fileId .= packTLString($this->url);
            $fileId .= packLong($this->accessHash);
            return base64urlEncode(rleEncode($fileId));
        }

        \assert($this->id !== null);
        $fileId .= packLong($this->id);
        $fileId .= packLong($this->accessHash);

        if ($this->photoSizeSource !== null) {
            $photoSize = $this->photoSizeSource;
            $writeExtra = false;
            switch (true) {
                case $photoSize instanceof PhotoSizeSourceLegacy:
                    if ($this->volumeId === null) {
                        $writeExtra = true;
                        $fileId .= \pack('V', PhotoSizeSourceType::LEGACY->value);
                        $fileId .= packLong($photoSize->secret);
                    } else {
                        $fileId .= \pack('V', PhotoSizeSourceType::FULL_LEGACY->value);
                        $fileId .= packLong($this->volumeId);
                        $fileId .= packLong($photoSize->secret);
                        $fileId .= \pack('l', $this->localId);
                    }
                    break;
                case $photoSize instanceof PhotoSizeSourceThumbnail:
                    $fileId .= \pack('V', PhotoSizeSourceType::THUMBNAIL->value);
                    $fileId .= \pack('Va4', $photoSize->thumbFileType->value, $photoSize->thumbType);
                    break;
                case $photoSize instanceof PhotoSizeSourceDialogPhoto:
                    $fileId .= \pack(
                        'V',
                        ($writeExtra = $this->volumeId !== null) ?
                        (
                            $photoSize->isSmallDialogPhoto()
                            ? PhotoSizeSourceType::DIALOGPHOTO_SMALL_LEGACY->value
                            : PhotoSizeSourceType::DIALOGPHOTO_BIG_LEGACY->value
                        ) : (
                            $photoSize->isSmallDialogPhoto()
                            ? PhotoSizeSourceType::DIALOGPHOTO_SMALL->value
                            : PhotoSizeSourceType::DIALOGPHOTO_BIG->value
                        )
                    );
                    $fileId .= packLong($photoSize->dialogId);
                    $fileId .= packLong($photoSize->dialogAccessHash);
                    break;
                case $photoSize instanceof PhotoSizeSourceStickersetThumbnail:
                    $writeExtra = $this->volumeId !== null;
                    $fileId .= packLong($photoSize->stickerSetId);
                    $fileId .= packLong($photoSize->stickerSetAccessHash);
                    break;
                case $photoSize instanceof PhotoSizeSourceStickersetThumbnailVersion:
                    $fileId .= packLong($photoSize->stickerSetId);
                    $fileId .= packLong($photoSize->stickerSetAccessHash);
                    $fileId .= \pack('l', $photoSize->stickerSetVersion);
                    break;
            }
            if ($writeExtra && $this->volumeId !== null && $this->localId !== null) {
                $fileId .= packLong($this->volumeId);
                $fileId .= \pack('l', $this->localId);
            }
        }

        if ($this->version >= 4) {
            $fileId .= \chr($this->subVersion);
        }
        $fileId .= \chr($this->version);

        return base64urlEncode(rleEncode($fileId));
    }

    /**
     * Get unique file ID from file ID.
     *
     */
    public function getUnique(): UniqueFileId
    {
        return UniqueFileId::fromFileId($this);
    }
    /**
     * Get unique bot API file ID from file ID.
     *
     */
    public function getUniqueBotAPI(): string
    {
        return UniqueFileId::fromFileId($this)->getUniqueBotAPI();
    }
    /**
     * Get bot API file ID.
     *
     */
    public function __toString(): string
    {
        return $this->getBotAPI();
    }
}
