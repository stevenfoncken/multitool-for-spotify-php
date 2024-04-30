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
use StevenFoncken\MultiToolForSpotify\Helper\SpotifyApiHelper;

/**
 * Service that handles various tasks related to Spotify tracks.
 *
 * @since v0.2.0
 * @author Steven Foncken <dev[at]stevenfoncken[dot]de>
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
     * @return string[]
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
        $apiOptions = ['limit' => 50];

        foreach (SpotifyApiHelper::universalPagination($this->spotifyApi, 'getMySavedTracks', $apiOptions) as $track) {
            yield $track;
        }
    }

    /**
     * @param string|array $trackIds
     *
     * @return string[]
     */
    public function isTrackInUserSavedTracks(string|array $trackIds): array
    {
        return $this->spotifyApi->myTracksContains($trackIds);
    }
}
