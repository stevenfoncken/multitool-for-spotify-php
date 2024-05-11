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

namespace StevenFoncken\MultiToolForSpotify\Validator;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

/**
 * Validator for the UrlContainsQueryParameter constraint.
 *
 * @since v2.0.0
 * @author Steven Foncken <dev[at]stevenfoncken[dot]de>
 */
class UrlContainsQueryParameterValidator extends ConstraintValidator
{
    /**
     * @param mixed      $value
     * @param Constraint $constraint
     *
     * @return void
     */
    public function validate(mixed $value, Constraint $constraint): void
    {
        if ($constraint instanceof UrlContainsQueryParameter === false) {
            throw new UnexpectedTypeException($constraint, UrlContainsQueryParameter::class);
        }

        // Skip validation if the URL is empty. NotBlank, NotNull, etc. should take care of that
        if ($value === null || $value === '') {
            return;
        }

        if (\is_scalar($value) === false && $value instanceof \Stringable === false) {
            throw new UnexpectedValueException($value, 'string');
        }


        // Parse the query string from the given URL
        $urlParts = parse_url($value);
        parse_str(($urlParts['query'] ?? ''), $queryParasOfGivenUrl);

        if (empty($constraint->queryParametersToCheck)) {
            // $constraint->queryParametersToCheck is empty
            // check if there are any query parameters in given URL
            if (empty($queryParasOfGivenUrl) === true) {
                $this->context->buildViolation($constraint->messageNoParameters)
                    ->addViolation();
            }
        } else {
            // $constraint->queryParametersToCheck contains query parameters
            // check if they are part of the given URL,
            // if not, create comma separated list and pass it to the violation
            $mandatoryQueryParasCommaSep = '';
            foreach ($constraint->queryParametersToCheck as $mandatoryQueryPara) {
                if (isset($queryParasOfGivenUrl[$mandatoryQueryPara]) === false) {
                    $mandatoryQueryParasCommaSep .= ($mandatoryQueryParasCommaSep === '') ? $mandatoryQueryPara : ', ' . $mandatoryQueryPara;
                }
            }

            if (empty($mandatoryQueryParasCommaSep) === false) {
                $this->context->buildViolation($constraint->messageMissingQueryParameters)
                    ->setParameter('{{ mandatory_query_paras_comma_sep }}', $mandatoryQueryParasCommaSep)
                    ->addViolation();
            }
        }
    }
}
