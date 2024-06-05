<?php

/**
 * This file is part of the multitool-for-spotify-php project.
 * @see https://github.com/stevenfoncken/multitool-for-spotify-php
 *
 * @copyright 2020-present Steven Foncken <dev[at]stevenfoncken[dot]de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * @license https://github.com/stevenfoncken/multitool-for-spotify-php/blob/master/LICENSE MIT License
 */

namespace StevenFoncken\MultiToolForSpotify\Helper;

use SpotifyWebAPI\SpotifyWebAPI;

/**
 * Helpers for the SpotifyWebAPI.
 *
 * @since v1.0.0
 * @author Steven Foncken <dev[at]stevenfoncken[dot]de>
 */
class SpotifyApiHelper
{
    /**
     * @param string $nextUrl
     *
     * @return int
     */
    public static function getOffsetFromNext(string $nextUrl): int
    {
        $query = parse_url($nextUrl, PHP_URL_QUERY);
        parse_str($query, $params);


        return isset($params['offset']) ? (int) $params['offset'] : 0;
    }

    /**
     * Universal pagination method for SpotifyWebAPI endpoints.
     *
     * @param SpotifyWebAPI $spotifyApi
     * @param string        $apiEndpoint
     * @param array         $options
     * @param string|null   $id
     *
     * @return array of "item"s from API objects.
     */
    public static function universalPagination(
        SpotifyWebAPI $spotifyApi,
        string $apiEndpoint,
        array $options,
        string $id = null
    ): array {
        if (method_exists($spotifyApi, $apiEndpoint) === false) {
            throw new \RuntimeException(
                sprintf('Endpoint "%s" does not exist in SpotifyWebAPI', $apiEndpoint),
                1698890806
            );
        }

        $parameters = [];
        $options['offset'] = 0;

        if ($id !== null) {
            $parameters[0] = $id;
        }

        $output = [];
        while (true) {
            $parameters[1] = $options;
            $apiResult = $spotifyApi->{$apiEndpoint}(...array_values($parameters));

            foreach ($apiResult->items as $item) {
                $output[] = $item;
            }

            if (isset($apiResult->next)) {
                $options['offset'] = self::getOffsetFromNext($apiResult->next);
            } else {
                break;
            }
            usleep(10000);
        }


        return $output;
    }
}
