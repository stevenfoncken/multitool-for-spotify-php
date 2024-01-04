<?php

namespace StevenFoncken\MultiToolForSpotify\Service;

use Psr\Log\LoggerInterface;
use SpotifyWebAPI\SpotifyWebAPI;
use StevenFoncken\MultiToolForSpotify\Helper\SpotifyApiHelper;

/**
 * Service that handles various tasks related to Spotify tracks.
 *
 * @author Steven Foncken <dev@stevenfoncken.de>
 * @copyright ^
 * @license https://github.com/stevenfoncken/multitool-for-spotify-php/blob/master/LICENSE - MIT License
 */
class TrackService
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
     * @param string $trackId
     *
     * @return object
     */
    public function getTrack(string $trackId): object
    {
        return $this->spotifyApi->getTrack($trackId);
    }

    /**
     * @param object $track
     *
     * @return array
     */
    public function getAllTrackArtists(object $track): array
    {
        $artistNames = [];
        foreach ($track->artists as $artist) {
            $artistNames[] = $artist->name;
        }


        return $artistNames;
    }

    /**
     * @return iterable
     */
    public function getAllUserSavedTracks(): iterable
    {
        foreach (SpotifyApiHelper::universalPagination($this->spotifyApi, 'getMySavedTracks', 50) as $track) {
            yield $track;
        }
    }

    /**
     * @param string|array $trackIds
     *
     * @return array
     */
    public function isTrackInUserSavedTracks(string|array $trackIds): array
    {
        return $this->spotifyApi->myTracksContains($trackIds);
    }
}
