<?php

namespace StevenFoncken\MultiToolForSpotify\Validator;

use Symfony\Component\Validator\Constraint;

/**
 * Constraint (rule) for URL query parameters.
 *
 * @author Steven Foncken <dev@stevenfoncken.de>
 * @copyright ^
 * @license https://github.com/stevenfoncken/multitool-for-spotify-php/blob/master/LICENSE - MIT License
 */
class UrlContainsQueryParameter extends Constraint
{
    public string $messageMissingQueryParameters = 'The URL must contain following query parameter: "{{ mandatory_query_paras_comma_sep }}".';
    public string $messageNoParameters = 'The URL does not contain any query parameters.';
    public array $queryParametersToCheck = [];

    /**
     * @param array|null $queryParametersToCheck
     * @param array|null $groups
     * @param mixed|null $payload
     */
    public function __construct(
        array $queryParametersToCheck = null,
        array $groups = null,
        mixed $payload = null
    ) {
        parent::__construct([], $groups, $payload);

        $this->queryParametersToCheck = ($queryParametersToCheck ?? $this->queryParametersToCheck);
    }
}
