<?php

namespace StevenFoncken\MultiToolForSpotify\Service;

use RuntimeException;
use SpotifyWebAPI\Session;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Validator\Constraints\Url;
use Symfony\Component\Validator\Constraints\NotBlank;
use StevenFoncken\MultiToolForSpotify\Validator\UrlContainsQueryParameter;

/**
 * Service for AuthCommand, it handles the Spotify OAuth process & API Token generation.
 *
 * @author Steven Foncken <dev@stevenfoncken.de>
 * @copyright ^
 * @license https://github.com/stevenfoncken/multitool-for-spotify-php/blob/master/LICENSE - MIT License
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
     * @return Question
     */
    public function generateCallbackUrlQuestion(): Question
    {
        $question = new Question('Please paste the callback URL here');
        $validator = Validation::createValidator();

        $question->setValidator(function ($answer) use ($validator): string {
            // Validate against the NotBlank constraint
            $notBlankViolations = $validator->validate($answer, new NotBlank());

            if (count($notBlankViolations) > 0) {
                throw new RuntimeException(
                    $notBlankViolations[0]->getMessage()
                );
            }

            // Validate against the Url constraint
            $urlViolations = $validator->validate($answer, new Url());

            if (count($urlViolations) > 0) {
                throw new RuntimeException(
                    $urlViolations[0]->getMessage()
                );
            }

            // Validate against the UrlContainsQueryParameter constraint
            $urlContainsQueryParameterViolations = $validator->validate(
                $answer,
                new UrlContainsQueryParameter(['code', 'state'])
            );

            if (count($urlContainsQueryParameterViolations) > 0) {
                throw new RuntimeException(
                    $urlContainsQueryParameterViolations[0]->getMessage()
                );
            }

            return $answer;
        });


        return $question;
    }

    /**
     * @param string $callbackURL
     *
     * @return void
     */
    public function generateApiTokens(string $callbackURL): void
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
