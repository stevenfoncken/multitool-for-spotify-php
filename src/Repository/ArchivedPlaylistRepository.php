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

namespace StevenFoncken\MultiToolForSpotify\Repository;

use StevenFoncken\MultiToolForSpotify\Entity\ArchivedPlaylist;

/**
 * @since  X.X.X
 * @author Steven Foncken <dev@stevenfoncken.de>
 */
class ArchivedPlaylistRepository
{
    protected const TABLE_NAME = 'archived_playlist';

    /**
     * @param \PDO $pdo
     */
    public function __construct(
        private \PDO $pdo,
    ) {
    }

    /**
     * @return ArchivedPlaylist[]
     * @throws \Exception
     */
    public function findAll(): array
    {
        $stmt = $this->pdo->query('SELECT * FROM ' . self::TABLE_NAME);
        $result = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $archivedPlaylists = [];
        foreach ($result as $row) {
            $archivedPlaylists[] = $this->hydrate($row);
        }


        return $archivedPlaylists;
    }

    /**
     * @param int $id
     *
     * @return ArchivedPlaylist|null
     * @throws \Exception
     */
    public function findOneById(int $id): ?ArchivedPlaylist
    {
        $stmt = $this->pdo->prepare('SELECT * FROM ' . self::TABLE_NAME . ' WHERE id = :id');
        $stmt->bindValue(':id', $id, \PDO::PARAM_INT);
        $stmt->execute();

        $result = $stmt->fetch(\PDO::FETCH_ASSOC);


        return ($result) ? $this->hydrate($result) : null;
    }

    /**
     * @param string $snapshotId
     *
     * @return ArchivedPlaylist|null
     * @throws \Exception
     */
    public function findOneBySnapshotId(string $snapshotId): ?ArchivedPlaylist
    {
        $stmt = $this->pdo->prepare('SELECT * FROM ' . self::TABLE_NAME . ' WHERE orig_playlist_snapshot_id = :snapshot_id');
        $stmt->bindValue(':snapshot_id', $snapshotId, \PDO::PARAM_STR);
        $stmt->execute();

        $result = $stmt->fetch(\PDO::FETCH_ASSOC);


        return ($result) ? $this->hydrate($result) : null;
    }

    /**
     * @param ArchivedPlaylist $archivedPlaylist
     *
     * @return void
     */
    public function create(ArchivedPlaylist $archivedPlaylist): void
    {
        $stmt = $this->pdo->prepare(
            '
                        INSERT INTO ' . self::TABLE_NAME . '
                            (
                                year,
                                week,
                                archived_playlist_id,
                                archived_playlist_name_prefix,
                                archived_playlist_name_suffix,
                                archived_playlist_sortorder,
                                archived_playlist_tracks,
                                orig_playlist_id,
                                orig_playlist_owner,
                                orig_playlist_name,
                                orig_playlist_snapshot_id,
                                orig_playlist_cover
                            )
                        VALUES 
                            (
                                :year,
                                :week,
                                :archived_playlist_id,
                                :archived_playlist_name_prefix,
                                :archived_playlist_name_suffix,
                                :archived_playlist_sortorder,
                                :archived_playlist_tracks,
                                :orig_playlist_id,
                                :orig_playlist_owner,
                                :orig_playlist_name,
                                :orig_playlist_snapshot_id,
                                :orig_playlist_cover
                            )
                    '
        );

        $stmt->bindValue(':year', $archivedPlaylist->getYear(), \PDO::PARAM_INT);
        $stmt->bindValue(':week', $archivedPlaylist->getWeek(), \PDO::PARAM_INT);
        $stmt->bindValue(':archived_playlist_id', $archivedPlaylist->getArchivedPlaylistId(), \PDO::PARAM_STR);
        $stmt->bindValue(':archived_playlist_name_prefix', $archivedPlaylist->getArchivedPlaylistNamePrefix(), \PDO::PARAM_STR);
        $stmt->bindValue(':archived_playlist_name_suffix', $archivedPlaylist->getArchivedPlaylistNameSuffix(), \PDO::PARAM_STR);
        $stmt->bindValue(':archived_playlist_sortorder', $archivedPlaylist->getArchivedPlaylistSortorder(), \PDO::PARAM_STR);
        $stmt->bindValue(':archived_playlist_tracks', $archivedPlaylist->getArchivedPlaylistTracks(), \PDO::PARAM_STR);
        $stmt->bindValue(':orig_playlist_id', $archivedPlaylist->getOrigPlaylistId(), \PDO::PARAM_STR);
        $stmt->bindValue(':orig_playlist_owner', $archivedPlaylist->getOrigPlaylistOwner(), \PDO::PARAM_STR);
        $stmt->bindValue(':orig_playlist_name', $archivedPlaylist->getOrigPlaylistName(), \PDO::PARAM_STR);
        $stmt->bindValue(':orig_playlist_snapshot_id', $archivedPlaylist->getOrigPlaylistSnapshotId(), \PDO::PARAM_STR);
        $stmt->bindValue(':orig_playlist_cover', $archivedPlaylist->getOrigPlaylistCover(), \PDO::PARAM_STR);

        $stmt->execute();


        $archivedPlaylist->setId($this->pdo->lastInsertId());
    }

    /**
     * @param array $dbResult
     *
     * @return ArchivedPlaylist
     * @throws \Exception
     */
    protected function hydrate(array $dbResult): ArchivedPlaylist
    {
        $archivedPlaylist = new ArchivedPlaylist();
        $archivedPlaylist->setId($dbResult['id']);
        $archivedPlaylist->setYear($dbResult['year']);
        $archivedPlaylist->setWeek($dbResult['week']);
        $archivedPlaylist->setArchivedPlaylistId($dbResult['archived_playlist_id']);
        $archivedPlaylist->setArchivedPlaylistNamePrefix($dbResult['archived_playlist_name_prefix']);
        $archivedPlaylist->setArchivedPlaylistNameSuffix($dbResult['archived_playlist_name_suffix']);
        $archivedPlaylist->setArchivedPlaylistSortorder($dbResult['archived_playlist_sortorder']);
        $archivedPlaylist->setArchivedPlaylistTracks($dbResult['archived_playlist_tracks']);
        $archivedPlaylist->setOrigPlaylistId($dbResult['orig_playlist_id']);
        $archivedPlaylist->setOrigPlaylistOwner($dbResult['orig_playlist_owner']);
        $archivedPlaylist->setOrigPlaylistName($dbResult['orig_playlist_name']);
        $archivedPlaylist->setOrigPlaylistSnapshotId($dbResult['orig_playlist_snapshot_id']);
        $archivedPlaylist->setOrigPlaylistCover($dbResult['orig_playlist_cover']);
        $archivedPlaylist->setDatetime(new \DateTime($dbResult['datetime']));


        return $archivedPlaylist;
    }
}
