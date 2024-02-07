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

namespace StevenFoncken\MultiToolForSpotify\Console\Style;

use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Custom style for console commands.
 *
 * @since 0.2.0
 * @author Steven Foncken <dev[at]stevenfoncken[dot]de>
 */
class CustomStyle extends SymfonyStyle
{
    /**
     * @param string|array $message
     *
     * @return void
     */
    public function green(string|array $message): void
    {
        $this->block($message, null, 'fg=green;bg=', ' ', false);
    }

    /**
     * @param string|array $message
     *
     * @return void
     */
    public function red(string|array $message): void
    {
        $this->block($message, null, 'fg=red;bg=', ' ', false);
    }

    /**
     * @param string|array $message
     *
     * @return void
     */
    public function yellow(string|array $message): void
    {
        $this->block($message, null, 'fg=yellow;bg=', ' ', false);
    }

    /**
     * @param string|array $message
     *
     * @return void
     */
    public function magenta(string|array $message): void
    {
        $this->block($message, null, 'fg=magenta;bg=', ' ', false);
    }
}
