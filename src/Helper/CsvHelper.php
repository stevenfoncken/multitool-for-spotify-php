<?php

namespace StevenFoncken\MultiToolForSpotify\Helper;

use ParseCsv\Csv;

/**
 * Helpers for handling CSV files.
 *
 * @author Steven Foncken <dev@stevenfoncken.de>
 * @copyright ^
 * @license https://github.com/stevenfoncken/multitool-for-spotify-php/blob/master/LICENSE - MIT License
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
