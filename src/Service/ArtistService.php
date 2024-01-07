<?php

namespace StevenFoncken\MultiToolForSpotify\Service;

use Psr\Log\LoggerInterface;
use SpotifyWebAPI\SpotifyWebAPI;
use StevenFoncken\MultiToolForSpotify\Helper\SpotifyApiHelper;

/**
 * Service that handles various tasks related to Spotify artists.
 *
 * @author Steven Foncken <dev@stevenfoncken.de>
 * @copyright ^
 * @license https://github.com/stevenfoncken/multitool-for-spotify-php/blob/master/LICENSE - MIT License
 */
class ArtistService
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
     * @param string $artistId
     *
     * @return array|object
     */
    public function getArtist(string $artistId): array|object
    {
        return $this->spotifyApi->getArtist($artistId);
    }

    /**
     * @param string $artistId
     *
     * @return array
     */
    public function getArtistCatalog(string $artistId): array
    {
        $albums = [];
        $apiOptions = ['limit' => 50];

        $apiOptions['include_groups'] = ['album'];
        foreach (SpotifyApiHelper::universalPagination($this->spotifyApi, 'getArtistAlbums', $apiOptions, $artistId) as $item) {
            $albums[] = $item;
        }

        $apiOptions['include_groups'] = ['single'];
        foreach (SpotifyApiHelper::universalPagination($this->spotifyApi, 'getArtistAlbums', $apiOptions, $artistId) as $item) {
            $albums[] = $item;
        }

        $apiOptions['include_groups'] = ['appears_on'];
        foreach (SpotifyApiHelper::universalPagination($this->spotifyApi, 'getArtistAlbums', $apiOptions, $artistId) as $item) {
            $albums[] = $item;
        }


        return $albums;
    }

    /**
     * @param string $artistId
     *
     * @return array
     * @throws \Exception
     */
    public function getAllArtistTracks(string $artistId): array
    {
        $apiOptions = ['limit' => 50];

        $artistTracks = [];
        foreach ($this->getArtistCatalog($artistId) as $album) {
            $albumId = $album->id;
            $albumTracks = SpotifyApiHelper::universalPagination($this->spotifyApi, 'getAlbumTracks', $apiOptions, $albumId);

            if (
                ($album->album_group === 'album' && $album->album_type === 'album') ||
                ($album->album_group === 'single' && $album->album_type === 'single') ||
                (
                    $album->album_group === 'appears_on' && (
                        $album->album_type === 'compilation' ||
                        $album->album_type === 'album' ||
                        $album->album_type === 'single'
                    )
                )
            ) {
                foreach ($albumTracks as $albumTrack) {
                    $trackArtists = $albumTrack->artists;
                    foreach ($trackArtists as $trackArtist) {
                        if ($trackArtist->id === $artistId) {
                            $artistTrack = [];

                            $artistTrack['release_date'] = $album->release_date;
                            if ($album->release_date_precision !== 'day') {
                                // Set first of year or month, so it can be handled by DateTime
                                // e.g.: year: 2023[-01] month: 2023-12[-01]
                                $artistTrack['release_date'] .= '-01';
                            }
                            $artistTrack['SpotifySimplifiedTrackObject'] = $albumTrack;
                            $artistTrack['album_type'] = $album->album_type;

                            $artistTracks[] = $artistTrack;
                            break;
                        }
                    }
                }
            }
        }

        $this->sortArtistTracksAscending($artistTracks);

        $uniqueTracks = [];
        foreach ($artistTracks as $artistTrack) {
            $artistTrackObject = $artistTrack['SpotifySimplifiedTrackObject'];
            $trackName = $artistTrackObject->name;
            $trackDuration = $artistTrackObject->duration_ms;

            // Unique key based on 'name' & 'duration_ms'
            $uniqueKey = $trackName . $trackDuration;

            // Check if the key already exists in $uniqueTracks
            if (array_key_exists($uniqueKey, $uniqueTracks)) {
                // Overwrite it when 'album_type' of save unique track is 'compilation',
                // 'single' & 'album' is winning over 'compilation'
                if (
                    $uniqueTracks[$uniqueKey]['album_type'] === 'compilation' &&
                    $artistTrack['album_type'] !== 'compilation'
                ) {
                    $uniqueTracks[$uniqueKey] = $artistTrack;
                }
            } else {
                // If not, add the current track to the uniqueTracks array
                $uniqueTracks[$uniqueKey] = $artistTrack;
            }
        }
        // Now, $uniqueTracks contains only the unique tracks based on 'name' & 'duration_ms'

        // Re-sort
        $this->sortArtistTracksAscending($uniqueTracks);


        return $uniqueTracks;
    }

    /**
     * @param array $tracks
     *
     * @return void
     * @throws \Exception
     */
    private function sortArtistTracksAscending(array &$tracks): void
    {
        usort($tracks, static function ($a, $b) {
            $dateA = new \DateTime($a['release_date']);
            $dateB = new \DateTime($b['release_date']);
            return (($dateA < $dateB) ? -1 : 1);
        });
    }
}
