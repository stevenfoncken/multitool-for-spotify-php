<?php

/**
 * This file is part of the multitool-for-spotify-php project.
 * @see https://github.com/stevenfoncken/multitool-for-spotify-php
 *
 * @copyright 2023-present Steven Foncken <dev[at]stevenfoncken[dot]de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * @license https://github.com/stevenfoncken/multitool-for-spotify-php/blob/master/LICENSE MIT License
 */

namespace StevenFoncken\MultiToolForSpotify\Service;

use Psr\Log\LoggerInterface;
use SpotifyWebAPI\SpotifyWebAPI;
use Intervention\Image\ImageManager;
use SpotifyWebAPI\SpotifyWebAPIException;
use StevenFoncken\MultiToolForSpotify\Helper\SpotifyApiHelper;
use StevenFoncken\MultiToolForSpotify\Entity\ArchivedPlaylist;
use StevenFoncken\MultiToolForSpotify\Repository\ArchivedPlaylistRepository;

/**
 * Service that handles various tasks related to Spotify playlists.
 *
 * @since 0.2.0
 * @author Steven Foncken <dev[at]stevenfoncken[dot]de>
 */
class PlaylistService
{
    /**
     * @var object[]
     */
    protected array $copiedPlaylistTracks = [];

    /**
     * @param SpotifyWebAPI   $spotifyApi
     * @param LoggerInterface $logger
     */
    public function __construct(
        private readonly SpotifyWebAPI $spotifyApi,
        private LoggerInterface $logger
    ) {
    }

    /**
     * @param string      $playlistId
     * @param string|null $sortOrder  desc => recent added tracks at top, asc => oldest added tracks at top.
     *
     * @return object[]
     * @throws \Exception
     */
    public function getAllTracksFromPlaylist(string $playlistId, string $sortOrder = null): array
    {
        $apiOptions = [
            'fields' => 'items.added_at,items.track(id,name,preview_url,artists(id,name,type),album(release_date)),next',
            'limit'  => 100,
        ];

        $playlistTracks = SpotifyApiHelper::universalPagination(
            $this->spotifyApi,
            'getPlaylistTracks',
            $apiOptions,
            $playlistId
        );

        if (empty($sortOrder) === false) {
            // Sort the playlist by "added_at" in descending/ascending order
            usort($playlistTracks, static function ($a, $b) use ($sortOrder) {
                $dateA = new \DateTime($a->added_at);
                $dateB = new \DateTime($b->added_at);

                if ($dateA == $dateB) {
                    return 0;
                }

                if ($sortOrder === 'desc') {
                    return (($dateA > $dateB) ? -1 : 1);
                }
                if ($sortOrder === 'asc') {
                    return (($dateA < $dateB) ? -1 : 1);
                }


                return 0;
            });
        }

        $tracks = [];
        foreach ($playlistTracks as $item) {
            $track = $item->track;

            if ($track !== null) {
                $tracks[] = $track;
            }
        }


        return $tracks;
    }

    /**
     * @param bool $selfCreated
     * @param bool $archived
     *
     * @return iterable
     */
    public function getUserPlaylists(
        bool $selfCreated = true,
        bool $archived = true
    ): iterable {
        $archivedPlaylistDescriptionPattern = '/Archive Playlist: (.*?) \| Orig. Playlist Name: (.*?) \| Orig. Playlist Owner: (.*?) \| Orig. Playlist ID: (.*?) \| Orig. Snapshot ID: (.*?)/';
        $userId = $this->spotifyApi->me()->id;
        $apiOptions = ['limit' => 50];

        foreach (SpotifyApiHelper::universalPagination($this->spotifyApi, 'getMyPlaylists', $apiOptions) as $playlist) {
            if ($playlist->owner->id !== $userId) {
                continue;
            }

            $isArchived = (bool) preg_match($archivedPlaylistDescriptionPattern, $playlist->description);
            if (($archived && $isArchived) || ($selfCreated && $isArchived === false)) {
                yield $playlist;
            }
        }
    }

    /**
     * @return object[]
     */
    public function findAllArchivedPlaylists(): array
    {
        $foundArchivedPlaylists = [];
        foreach ($this->getUserPlaylists(selfCreated: false, archived: true) as $playlist) {
            $foundArchivedPlaylists[] = $playlist;
        }


        return $foundArchivedPlaylists;
    }

    /**
     * @return object[]
     */
    public function findAllSelfCreatedPlaylists(): array
    {
        $foundSelfCreatedPlaylists = [];
        foreach ($this->getUserPlaylists(selfCreated: true, archived: false) as $playlist) {
            $foundSelfCreatedPlaylists[] = $playlist;
        }


        return $foundSelfCreatedPlaylists;
    }

    /**
     * @return iterable
     */
    public function deleteAllArchivedPlaylists(): iterable
    {
        foreach ($this->findAllArchivedPlaylists() as $archivedPlaylist) {
            yield $archivedPlaylist;

            $this->spotifyApi->unfollowPlaylist($archivedPlaylist->id);

            $this->logger->debug(
                'Deleted archived playlist',
                [
                    'playlist_id'          => $archivedPlaylist->id,
                    'playlist_description' => $archivedPlaylist->description,
                ]
            );
        }
    }

    /**
     * @param string      $origPlaylistId
     * @param object|null $origPlaylist
     * @param bool        $public
     * @param string|null $newName
     * @param string|null $newDescription
     * @param string|null $tracksSortOrder
     *
     * @return string new playlist id.
     * @throws \Exception
     */
    public function copyPlaylist(
        string $origPlaylistId,
        ?object $origPlaylist = null,
        bool $public = false,
        ?string $newName = null,
        ?string $newDescription = null,
        ?string $tracksSortOrder = null
    ): string {
        $this->logger->debug('copyPlaylist: Start', ['playlist_id_orig' => $origPlaylistId]);

        // Get original playlist (metadata), if origPlaylist object is not passed to copyPlaylist()
        if ($origPlaylist === null) {
            $origPlaylist = $this->getPlaylistMetadata($origPlaylistId);
            if ($origPlaylist === null) {
                return 'Playlist not found';
            }
        }

        // Defaults to origPlaylist values when no custom name or description is set
        $newName = ($newName ?? $origPlaylist->name);
        $newDescription = ($newDescription ?? $origPlaylist->description);

        $newPlaylist = $this->createNewUserPlaylist(
            [
                'name'        => $newName,
                'description' => $newDescription,
                'public'      => $public,
            ]
        );

        $this->checkIfPlaylistDescriptionSetCorrectly($newPlaylist, $newDescription);

        // Copy tracks to new playlist
        $this->copiedPlaylistTracks = $this->getAllTracksFromPlaylist($origPlaylist->id, $tracksSortOrder);
        $this->addTracksToPlaylist($newPlaylist->id, $this->copiedPlaylistTracks);

        $this->updatePlaylistCoverImage($newPlaylist->id, $origPlaylist->images[0]->url);

        $this->logger->debug(
            'copyPlaylist: Done',
            [
                'playlist_id_new'          => $newPlaylist->id,
                'playlist_id_orig'         => $origPlaylistId,
                'playlist_name_new'        => $newName,
                'playlist_description_new' => $newDescription,
            ]
        );


        return $newPlaylist->id;
    }

    /**
     * @param string      $playlistId
     * @param array       $archivedPlaylists
     * @param string|null $namePrefix
     * @param string|null $nameSuffix
     * @param string|null $tracksSortOrder
     *
     * @return bool
     * @throws \Exception
     */
    public function archivePlaylist(
        string $playlistId,
        array $archivedPlaylists,
        ?string $namePrefix = null,
        ?string $nameSuffix = null,
        ?string $tracksSortOrder = null
    ): bool {
        $this->logger->debug('archivePlaylist: Start', ['playlist_id_orig' => $playlistId]);

        // Get original playlist (metadata)
        $origPlaylist = $this->getPlaylistMetadata($playlistId);
        if ($origPlaylist === null) {
            return false;
        }

        if ($this->checkIfArchivedPlaylistChanged($origPlaylist->id, $origPlaylist->snapshot_id, $archivedPlaylists)) {
            $dateTime = new \DateTime();
            $currentYear = $dateTime->format('o');
            $currentWeek = $dateTime->format('W');
            $currentDate = $dateTime->format('d.m.Y H:i:s');

            $namePrefix = (empty($namePrefix) ? 'ARCHIVE' : $namePrefix);
            $nameSuffix = (empty($nameSuffix) ? $origPlaylist->name : $nameSuffix);

            // PREFIX-YYYY-WW-SUFFIX or PLAYLIST_NAME
            $archivedPlaylistName = sprintf(
                '%s-%s-%s-%s',
                $namePrefix,
                $currentYear,
                $currentWeek,
                $nameSuffix
            );

            $archivedPlaylistDescription = sprintf(
                'Archive Playlist: %s | Orig. Playlist Name: %s | Orig. Playlist Owner: %s | Orig. Playlist ID: %s | Orig. Snapshot ID: %s',
                $currentDate,
                $origPlaylist->name,
                ($origPlaylist->owner->display_name ?? 'n/a'),
                $origPlaylist->id,
                $origPlaylist->snapshot_id
            );

            $newArchivedPlaylistId = $this->copyPlaylist(
                $playlistId,
                $origPlaylist,
                false,
                $archivedPlaylistName,
                $archivedPlaylistDescription,
                $tracksSortOrder
            );

            // ---------------------------------------------------------
            try {
                $pdo = new \PDO($_ENV['PDO_DSN']);//TODO env
                $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

                //$stmt = $pdo->prepare(file_get_contents(__DIR__ . '/../../fixtures/database_schema.sql'));
                //$stmt->execute();

                $archivedPlaylistRepository = new ArchivedPlaylistRepository($pdo);

                $archivedPlaylist = new ArchivedPlaylist();
                $archivedPlaylist->setYear($currentYear);
                $archivedPlaylist->setWeek($currentWeek);
                $archivedPlaylist->setArchivedPlaylistId($newArchivedPlaylistId);
                $archivedPlaylist->setArchivedPlaylistNamePrefix($namePrefix);
                $archivedPlaylist->setArchivedPlaylistNameSuffix($nameSuffix);
                $archivedPlaylist->setArchivedPlaylistSortorder($tracksSortOrder);
                $archivedPlaylist->setArchivedPlaylistTracks(
                    json_encode($this->copiedPlaylistTracks, JSON_THROW_ON_ERROR)
                );
                $archivedPlaylist->setOrigPlaylistId($origPlaylist->id);
                $archivedPlaylist->setOrigPlaylistOwner(($origPlaylist->owner->display_name ?? 'n/a'));
                $archivedPlaylist->setOrigPlaylistName($origPlaylist->name);
                $archivedPlaylist->setOrigPlaylistSnapshotId($origPlaylist->snapshot_id);
                $archivedPlaylist->setOrigPlaylistCover(
                    ImageManager::gd()
                        ->read(file_get_contents($origPlaylist->images[0]->url))->toJpeg(quality: 55)->toString()
                );

                $archivedPlaylistRepository->create($archivedPlaylist);
            } catch (\PDOException $e) {
                echo 'PDO error: ' . $e->getMessage();
            }
            // ---------------------------------------------------------

            $this->logger->info(
                'archivePlaylist: Done',
                [
                    'archived_playlist_id_new'      => $newArchivedPlaylistId,
                    'archived_playlist_name_prefix' => $namePrefix,
                    'archived_playlist_name_year'   => $currentYear,
                    'archived_playlist_name_week'   => $currentWeek,
                    'archived_playlist_name_suffix' => $nameSuffix,
                ]
            );


            return true;
        }


        return false;
    }

    /**
     * @param string $playlistId
     *
     * @return object|null
     */
    public function getPlaylistMetadata(string $playlistId): object|null
    {
        try {
            return $this->spotifyApi->getPlaylist(
                $playlistId,
                ['fields' => 'id,name,description,snapshot_id,images,owner(display_name),external_urls(spotify)']
            );
        } catch (SpotifyWebAPIException $e) {
            $this->logger->error(
                'Caught SpotifyWebAPIException: ' . $e->getMessage(),
                [
                    'playlist_id' => $playlistId,
                    'exception'   => $e,
                ]
            );
        }


        return null;
    }

    /**
     * @param array $options
     *
     * @return object PlaylistObject.
     */
    public function createNewUserPlaylist(array $options = []): object
    {
        return $this->spotifyApi->createPlaylist(
            $this->spotifyApi->me()->id,
            $options
        );
    }

    /**
     * @param string $playlistIdToBeAddedTo
     * @param array  $tracks
     *
     * @return void
     */
    public function addTracksToPlaylist(string $playlistIdToBeAddedTo, array $tracks): void
    {
        $trackIds = [];
        foreach ($tracks as $track) {
            $trackIds[] = $track->id;
        }

        $trackChunks = array_chunk(
            $trackIds,
            99
        );

        foreach ($trackChunks as $trackChunk) {
            $this->spotifyApi->addPlaylistTracks($playlistIdToBeAddedTo, $trackChunk);
            sleep(3);
        }
    }

    /**
     * Searches for the given snapshot_id in the description of all archived playlists to determine if the already
     * archived playlist changed in its current version.
     *
     * @param string $origPlaylistId
     * @param string $origPlaylistSnapshotId
     * @param array  $archivedPlaylists
     *
     * @return bool
     * @throws \Exception
     */
    private function checkIfArchivedPlaylistChanged(string $origPlaylistId, string $origPlaylistSnapshotId, array $archivedPlaylists): bool
    {
        try {
            $pdo = new \PDO($_ENV['PDO_DSN']);//TODO env
            $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

            $archivedPlaylistRepository = new ArchivedPlaylistRepository($pdo);
        } catch (\PDOException $e) {
            echo 'PDO error: ' . $e->getMessage();
        }

        if ($archivedPlaylistRepository->findOneBySnapshotId($origPlaylistSnapshotId) !== null) {
            $this->logger->info(
                'Archived playlist not changed to last archived version',
                ['snapshot_id_orig' => $origPlaylistSnapshotId]
            );

            return false;
        }

        $this->logger->info(
            'Archived playlist changed to last archived version',
            ['snapshot_id_orig' => $origPlaylistSnapshotId]
        );


        return true;
    }

    /**
     * @param string $playlistId
     * @param string $imagePath
     *
     * @return void
     */
    private function updatePlaylistCoverImage(string $playlistId, string $imagePath): void
    {
        $this->logger->debug('updatePlaylistCoverImage: Start', ['playlist_id' => $playlistId]);

        $imageManager = ImageManager::gd();
        $fileInfo = new \finfo(FILEINFO_MIME_TYPE);

        for ($retryCount = 0; $retryCount < 6; $retryCount++) {
            try {
                $origCoverImageData = file_get_contents($imagePath);
                $mimeType = $fileInfo->buffer($origCoverImageData);

                switch ($mimeType) {
                    case 'image/jpeg':
                        $compressedImage = $imageManager->read($origCoverImageData)->toJpeg(quality: 55)->toString();
                        $this->spotifyApi->updatePlaylistImage($playlistId, base64_encode($compressedImage));
                        break;
                    case 'application/x-gzip':
                        $this->spotifyApi->updatePlaylistImage($playlistId, base64_encode(\gzdecode($origCoverImageData)));
                        break;
                }
                $this->logger->debug('updatePlaylistCoverImage: Done', ['playlist_id' => $playlistId]);

                // If no exception thrown
                break;
            } catch (SpotifyWebAPIException $e) {
                $this->logger->debug(
                    'updatePlaylistCoverImage: Caught SpotifyWebAPIException: ' . $e->getMessage(),
                    ['exception' => $e]
                );
                $this->logger->debug('updatePlaylistCoverImage: Retrying', ['attempt' => ($retryCount + 1)]);
                sleep(5);
            }
        }
    }

    /**
     * Check if playlist description is set correctly, else retry to set it.
     *
     * @param object $playlist
     * @param string $shouldDescription
     *
     * @return void
     */
    private function checkIfPlaylistDescriptionSetCorrectly(object $playlist, string $shouldDescription): void
    {
        $playlistId = $playlist->id;
        $playlistDescription = $playlist->description;

        $this->logger->debug(
            'checkIfPlaylistDescriptionSetCorrectly: Start',
            [
                'should_playlist_desc' => $shouldDescription,
                'is_playlist_desc'     => $playlistDescription,
            ]
        );

        while (
            $playlistDescription === true ||
            empty($playlistDescription) ||
            html_entity_decode((string) $playlistDescription) !== $shouldDescription
        ) {
            $this->spotifyApi->updatePlaylist($playlistId, ['description' => $shouldDescription]);
            sleep(31);
            $playlistDescription = $this->getPlaylistMetadata($playlistId)->description;
            $this->logger->debug(
                'checkIfPlaylistDescriptionSetCorrectly: Updated',
                ['new_playlist_desc' => $playlistDescription]
            );
        }

        $this->logger->debug('checkIfPlaylistDescriptionSetCorrectly: Done');
    }
}
