<?php declare(strict_types=1);
/**
 * Photosize source class.
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

namespace danog\Decoder\PhotoSizeSource;

use danog\Decoder\PhotoSizeSource;

/**
 * Represents source of photosize.
 *
 * @extends PhotoSizeSource<PhotoSizeSourceStickersetThumbnailVersion>
 */
class PhotoSizeSourceStickersetThumbnailVersion extends PhotoSizeSource
{
    /**
     * Stickerset ID.
     *
     */
    private int $stickerSetId;
    /**
     * Stickerset access hash.
     *
     */
    private int $stickerSetAccessHash;
    /**
     * Stickerset version.
     *
     */
    private int $stickerSetVersion;

    /**
     * Get stickerset ID.
     *
     * @return int
     */
    public function getStickerSetId()
    {
        return $this->stickerSetId;
    }

    /**
     * Set stickerset ID.
     *
     * @param int $stickerSetId Stickerset ID
     *
     */
    public function setStickerSetId(int $stickerSetId): self
    {
        $this->stickerSetId = $stickerSetId;

        return $this;
    }

    /**
     * Get stickerset access hash.
     *
     * @return int
     */
    public function getStickerSetAccessHash()
    {
        return $this->stickerSetAccessHash;
    }

    /**
     * Set stickerset access hash.
     *
     * @param int $stickerSetAccessHash Stickerset access hash
     *
     */
    public function setStickerSetAccessHash(int $stickerSetAccessHash): self
    {
        $this->stickerSetAccessHash = $stickerSetAccessHash;

        return $this;
    }

    /**
     * Get stickerset version.
     *
     */
    public function getStickerSetVersion(): int
    {
        return $this->stickerSetVersion;
    }

    /**
     * Set stickerset version.
     *
     * @param int $stickerSetVersion Stickerset version.
     *
     */
    public function setStickerSetVersion(int $stickerSetVersion): self
    {
        $this->stickerSetVersion = $stickerSetVersion;

        return $this;
    }
}
