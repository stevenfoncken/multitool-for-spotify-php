<?php

namespace StevenFoncken\MultiToolForSpotify\Service;

use Psr\Log\LoggerInterface;
use SpotifyWebAPI\SpotifyWebAPI;
use SpotifyWebAPI\SpotifyWebAPIException;
use StevenFoncken\MultiToolForSpotify\Helper\SpotifyApiHelper;
use Intervention\Image\ImageManagerStatic as Image;

/**
 * Service that handles various tasks related to Spotify playlists.
 *
 * @author Steven Foncken <dev@stevenfoncken.de>
 * @copyright ^
 * @license https://github.com/stevenfoncken/multitool-for-spotify-php/blob/master/LICENSE - MIT License
 */
class PlaylistService
{
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
     * @param string|null $sortOrder  "desc" or "asc"
     *
     * @return array
     * @throws \Exception
     */
    public function getAllTracksIdsFromPlaylist(string $playlistId, string $sortOrder = null): array
    {
        $fields = 'items.added_at,items.track.id,next';
        $playlistTracks = SpotifyApiHelper::universalPagination(
            $this->spotifyApi,
            'getPlaylistTracks',
            100,
            $playlistId,
            $fields
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
                $tracks[] = $track->id;
            }
        }


        return $tracks;
    }

    /**
     * @return array
     */
    public function findAllArchivedPlaylists(): array
    {
        $pattern = '/Archive Playlist: (.*?) \| Orig. Playlist Name: (.*?) \| Orig. Playlist Owner: (.*?) \| Orig. Playlist ID: (.*?) \| Orig. Snapshot ID: (.*?)/';

        $foundArchivedPlaylists = [];
        foreach (SpotifyApiHelper::universalPagination($this->spotifyApi, 'getMyPlaylists', 50) as $playlist) {
            if (preg_match($pattern, $playlist->description)) {
                $foundArchivedPlaylists[] = $playlist;
            }
        }


        return $foundArchivedPlaylists;
    }

    /**
     * @return void
     */
    public function deleteAllArchivedPlaylists(): void
    {
        foreach ($this->findAllArchivedPlaylists() as $archivedPlaylist) {
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
     * @return string
     * @throws \Exception
     */
    public function copyPlaylist(
        string $origPlaylistId,
        object $origPlaylist = null,
        bool $public = false,
        string $newName = null,
        string $newDescription = null,
        string $tracksSortOrder = null
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

        // ---

        // Create new playlist
        $newPlaylist = $this->spotifyApi->createPlaylist(
            $this->spotifyApi->me()->id,
            [
                'name'        => $newName,
                'description' => $newDescription,
                'public'      => $public,
            ]
        );

        // ---

        $this->checkIfPlaylistDescriptionSetCorrectly($newPlaylist, $newDescription);

        // ---

        // Copy tracks to newPlaylist
        $tracksFromOrigPlaylistChunked = array_chunk($this->getAllTracksIdsFromPlaylist($origPlaylist->id, $tracksSortOrder), 99);
        foreach ($tracksFromOrigPlaylistChunked as $trackChunk) {
            $this->spotifyApi->addPlaylistTracks($newPlaylist->id, $trackChunk);
        }

        // ---

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
     * @param string      $newNamePrefix
     * @param string      $newNameSuffix
     * @param string|null $tracksSortOrder
     * @param bool        $alsoArchiveExtern
     *
     * @return bool
     * @throws \Exception
     */
    public function archivePlaylist(
        string $playlistId,
        array $archivedPlaylists,
        string $newNamePrefix = 'ARCHIVE',
        string $newNameSuffix = '',
        string $tracksSortOrder = null,
        bool $alsoArchiveExtern = false,// TODO
    ): bool {
        $this->logger->debug('archivePlaylist: Start', ['playlist_id_orig' => $playlistId]);

        // Get original playlist (metadata)
        $origPlaylist = $this->getPlaylistMetadata($playlistId);
        if ($origPlaylist === null) {
            return false;
        }

        if ($this->checkIfArchivedPlaylistChanged($origPlaylist->snapshot_id, $archivedPlaylists) === true) {
            $dateTime = new \DateTime();
            $currentYear = $dateTime->format('o');
            $currentWeek = $dateTime->format('W');
            $currentDate = $dateTime->format('d.m.Y H:i:s');

            $nameSuffix = (($newNameSuffix !== '') ? $newNameSuffix : $origPlaylist->name);
            // PREFIX-YYYY-WW-SUFFIX or PLAYLIST_NAME
            $newPlaylistName = sprintf(
                '%s-%d-%d-%s',
                $newNamePrefix,
                $currentYear,
                $currentWeek,
                $nameSuffix
            );

            $newPlaylistDescription = sprintf(
                'Archive Playlist: %s | Orig. Playlist Name: %s | Orig. Playlist Owner: %s | Orig. Playlist ID: %s | Orig. Snapshot ID: %s',
                $currentDate,
                $origPlaylist->name,
                ($origPlaylist->owner->display_name ?? 'n/a'),
                $origPlaylist->id,
                $origPlaylist->snapshot_id
            );

            $newPlaylistId = $this->copyPlaylist(
                $playlistId,
                $origPlaylist,
                false,
                $newPlaylistName,
                $newPlaylistDescription,
                $tracksSortOrder
            );

            $this->logger->info(
                'archivePlaylist: Done',
                [
                    'playlist_id_new'      => $newPlaylistId,
                    'playlist_name_prefix' => $newNamePrefix,
                    'playlist_name_year'   => $currentYear,
                    'playlist_name_week'   => $currentWeek,
                    'playlist_name_suffix' => $nameSuffix,
                ]
            );


            return true;
        }


        return false;
    }

    /**
     * Checks the snapshot_id in the archived playlist description.
     *
     * @param string $origPlaylistSnapshotId
     * @param array  $archivedPlaylists
     *
     * @return bool
     */
    public function checkIfArchivedPlaylistChanged(string $origPlaylistSnapshotId, array $archivedPlaylists): bool
    {
        $pattern = '/Orig. Snapshot ID: (.*)/';

        foreach ($archivedPlaylists as $playlist) {
            if (
                preg_match($pattern, $playlist->description, $matches) &&
                /* Snapshot ID*/ $matches[1] === $origPlaylistSnapshotId
            ) {
                $this->logger->debug(
                    'Archived playlist not changed to last archived version',
                    [
                        'snapshot_id_orig'              => $origPlaylistSnapshotId,
                        'playlist_description_archived' => $playlist->description,
                    ]
                );

                return false;
            }
        }

        $this->logger->debug(
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
    public function updatePlaylistCoverImage(string $playlistId, string $imagePath): void
    {
        $this->logger->debug('updatePlaylistCoverImage: Start', ['playlist_id' => $playlistId]);

        for ($retryCount = 0; $retryCount < 3; $retryCount++) {
            try {
                $origCoverImageData = file_get_contents($imagePath);
                $fileInfo = new \finfo(FILEINFO_MIME_TYPE);
                $mimeType = $fileInfo->buffer($origCoverImageData);

                switch ($mimeType) {
                    case 'image/jpeg':
                        $compressedImage = Image::make($origCoverImageData)->encode('image/jpeg', 60);
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
                $this->logger->error(
                    'updatePlaylistCoverImage: Caught SpotifyWebAPIException: ' . $e->getMessage(),
                    ['exception' => $e]
                );
                $this->logger->error('updatePlaylistCoverImage: Retrying', ['attempt' => ($retryCount + 1)]);
                sleep(1);
            }
        }
    }

    /**
     * Check if newPlaylist description is set correctly, else retry to set it.
     *
     * @param object $newPlaylist
     * @param string $shouldDescription
     *
     * @return void
     */
    public function checkIfPlaylistDescriptionSetCorrectly(object $newPlaylist, string $shouldDescription): void
    {
        $idOfNewPlaylist = $newPlaylist->id;
        $descriptionOfNewPlaylist = $newPlaylist->description;

        $this->logger->debug(
            'checkIfPlaylistDescriptionSetCorrectly: Start',
            [
                'should_playlist_desc' => $shouldDescription,
                'is_playlist_desc'     => $newPlaylist->description,
            ]
        );

        while ($descriptionOfNewPlaylist === true || empty($descriptionOfNewPlaylist)) {
            $this->spotifyApi->updatePlaylist($idOfNewPlaylist, ['description' => $shouldDescription]);
            $descriptionOfNewPlaylist = $this->getPlaylistMetadata($idOfNewPlaylist)->description;
            $this->logger->debug(
                'checkIfPlaylistDescriptionSetCorrectly: Updated',
                ['new_playlist_desc' => $descriptionOfNewPlaylist]
            );
            usleep(20000);
        }

        $this->logger->debug('checkIfPlaylistDescriptionSetCorrectly: Done');
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
                ['fields' => 'id,name,description,owner.display_name,snapshot_id,images']
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
}
