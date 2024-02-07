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

namespace StevenFoncken\MultiToolForSpotify\Helper;

use ParseCsv\Csv;

/**
 * Helpers for handling CSV files.
 *
 * @since 0.2.0
 * @author Steven Foncken <dev[at]stevenfoncken[dot]de>
 */
class CsvHelper
{
    /**
     * @param string $csvPath
     * @param string $delimiter
     *
     * @return array
     */
    public static function getCsvData(string $csvPath, string $delimiter): array
    {
        $csv = new Csv();
        $csv->encoding('UTF-8', 'UTF-8');
        $csv->delimiter = $delimiter;
        $csv->parseFile($csvPath);


        return $csv->data;
    }
}
