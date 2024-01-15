<?php

/**
 * This file is part of the multitool-for-spotify-php project.
 * @see https://github.com/stevenfoncken/multitool-for-spotify-php
 *
 * @copyright 2023-present Steven Foncken <dev@stevenfoncken.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * @license https://github.com/stevenfoncken/multitool-for-spotify-php/blob/master/LICENSE MIT License
 */

namespace StevenFoncken\MultiToolForSpotify\Entity;

/**
 * @since  X.X.X
 * @author Steven Foncken <dev@stevenfoncken.de>
 */
class ArchivedPlaylist
{
    /**
     * @var int
     */
    private int $id = 0;

    /**
     * @var int
     */
    private int $year = 0;

    /**
     * @var int
     */
    private int $week = 0;

    /**
     * @var string
     */
    private string $archivedPlaylistId = '';

    /**
     * @var string
     */
    private string $archivedPlaylistNamePrefix = '';

    /**
     * @var string
     */
    private string $archivedPlaylistNameSuffix = '';

    /**
     * @var string
     */
    private string $archivedPlaylistSortorder = '';

    /**
     * @var string
     */
    private string $archivedPlaylistTracks = '';

    /**
     * @var string
     */
    private string $origPlaylistId = '';

    /**
     * @var string
     */
    private string $origPlaylistOwner = '';

    /**
     * @var string
     */
    private string $origPlaylistSnapshotId = '';

    /**
     * @var string
     */
    private string $origPlaylistCover = '';

    /**
     * @var \DateTime
     */
    private \DateTime $datetime;



    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @param int $id
     *
     * @return $this
     */
    public function setId(int $id): ArchivedPlaylist
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return int
     */
    public function getYear(): int
    {
        return $this->year;
    }

    /**
     * @param int $year
     *
     * @return $this
     */
    public function setYear(int $year): ArchivedPlaylist
    {
        $this->year = $year;

        return $this;
    }

    /**
     * @return int
     */
    public function getWeek(): int
    {
        return $this->week;
    }

    /**
     * @param int $week
     *
     * @return $this
     */
    public function setWeek(int $week): ArchivedPlaylist
    {
        $this->week = $week;

        return $this;
    }

    /**
     * @return string
     */
    public function getArchivedPlaylistId(): string
    {
        return $this->archivedPlaylistId;
    }

    /**
     * @param string $archivedPlaylistId
     *
     * @return $this
     */
    public function setArchivedPlaylistId(string $archivedPlaylistId): ArchivedPlaylist
    {
        $this->archivedPlaylistId = $archivedPlaylistId;

        return $this;
    }

    /**
     * @return string
     */
    public function getArchivedPlaylistNamePrefix(): string
    {
        return $this->archivedPlaylistNamePrefix;
    }

    /**
     * @param string $archivedPlaylistNamePrefix
     *
     * @return $this
     */
    public function setArchivedPlaylistNamePrefix(string $archivedPlaylistNamePrefix): ArchivedPlaylist
    {
        $this->archivedPlaylistNamePrefix = $archivedPlaylistNamePrefix;

        return $this;
    }

    /**
     * @return string
     */
    public function getArchivedPlaylistNameSuffix(): string
    {
        return $this->archivedPlaylistNameSuffix;
    }

    /**
     * @param string $archivedPlaylistNameSuffix
     *
     * @return $this
     */
    public function setArchivedPlaylistNameSuffix(string $archivedPlaylistNameSuffix): ArchivedPlaylist
    {
        $this->archivedPlaylistNameSuffix = $archivedPlaylistNameSuffix;

        return $this;
    }

    /**
     * @return string
     */
    public function getArchivedPlaylistSortorder(): string
    {
        return $this->archivedPlaylistSortorder;
    }

    /**
     * @param string $archivedPlaylistSortorder
     *
     * @return $this
     */
    public function setArchivedPlaylistSortorder(string $archivedPlaylistSortorder): ArchivedPlaylist
    {
        $this->archivedPlaylistSortorder = $archivedPlaylistSortorder;

        return $this;
    }

    /**
     * @return string
     */
    public function getArchivedPlaylistTracks(): string
    {
        return $this->archivedPlaylistTracks;
    }

    /**
     * @param string $archivedPlaylistTracks
     *
     * @return $this
     */
    public function setArchivedPlaylistTracks(string $archivedPlaylistTracks): ArchivedPlaylist
    {
        $this->archivedPlaylistTracks = $archivedPlaylistTracks;

        return $this;
    }

    /**
     * @return string
     */
    public function getOrigPlaylistId(): string
    {
        return $this->origPlaylistId;
    }

    /**
     * @param string $origPlaylistId
     *
     * @return $this
     */
    public function setOrigPlaylistId(string $origPlaylistId): ArchivedPlaylist
    {
        $this->origPlaylistId = $origPlaylistId;

        return $this;
    }

    /**
     * @return string
     */
    public function getOrigPlaylistOwner(): string
    {
        return $this->origPlaylistOwner;
    }

    /**
     * @param string $origPlaylistOwner
     *
     * @return $this
     */
    public function setOrigPlaylistOwner(string $origPlaylistOwner): ArchivedPlaylist
    {
        $this->origPlaylistOwner = $origPlaylistOwner;

        return $this;
    }

    /**
     * @return string
     */
    public function getOrigPlaylistSnapshotId(): string
    {
        return $this->origPlaylistSnapshotId;
    }

    /**
     * @param string $origPlaylistSnapshotId
     *
     * @return $this
     */
    public function setOrigPlaylistSnapshotId(string $origPlaylistSnapshotId): ArchivedPlaylist
    {
        $this->origPlaylistSnapshotId = $origPlaylistSnapshotId;

        return $this;
    }

    /**
     * @return string
     */
    public function getOrigPlaylistCover(): string
    {
        return $this->origPlaylistCover;
    }

    /**
     * @param string $origPlaylistCover
     *
     * @return $this
     */
    public function setOrigPlaylistCover(string $origPlaylistCover): ArchivedPlaylist
    {
        $this->origPlaylistCover = $origPlaylistCover;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getDatetime(): \DateTime
    {
        return $this->datetime;
    }

    /**
     * @param \DateTime $datetime
     *
     * @return $this
     */
    public function setDatetime(\DateTime $datetime): ArchivedPlaylist
    {
        $this->datetime = $datetime;

        return $this;
    }
}
