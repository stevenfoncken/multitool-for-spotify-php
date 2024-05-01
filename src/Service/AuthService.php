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

use SpotifyWebAPI\Session;

/**
 * Service for AuthCommand that contains the logic for the Spotify OAuth process & API token generation.
 *
 * @since v1.0.0
 * @author Steven Foncken <dev[at]stevenfoncken[dot]de>
 */
class AuthService
{
    private const ACCESS_TOKEN_PATH = __DIR__ . '/../../config/.access_token';

    private const REFRESH_TOKEN_PATH = __DIR__ . '/../../config/.refresh_token';

    /**
     * @param Session $spotifySession
     */
    public function __construct(
        private readonly Session $spotifySession
    ) {
    }

    /**
     * @return string
     */
    public function generateOAuthUrl(): string
    {
        $state = $this->spotifySession->generateState();
        $options = [
            'scope' => [
                'user-library-read',
                'user-library-modify',
                'playlist-read-private',
                'playlist-modify-private',
                'playlist-modify-public',
                'ugc-image-upload',
                'user-follow-read',
            ],
            'state' => $state,
        ];


        return $this->spotifySession->getAuthorizeUrl($options);
    }

    /**
     * @param string $callbackURL
     *
     * @return void
     */
    public function saveApiTokens(string $callbackURL): void
    {
        parse_str(parse_url($callbackURL)['query'], $queryParameters);
        $callbackAuthorizationCode = $queryParameters['code'];

        $this->spotifySession->requestAccessToken($callbackAuthorizationCode);

        $accessToken = $this->spotifySession->getAccessToken();
        $refreshToken = $this->spotifySession->getRefreshToken();

        file_put_contents(self::ACCESS_TOKEN_PATH, $accessToken);
        file_put_contents(self::REFRESH_TOKEN_PATH, $refreshToken);
    }
}
