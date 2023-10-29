<?php

namespace StevenFoncken\MultiToolForSpotify\Console\Style;

use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Custom style for console commands.
 *
 * @author Steven Foncken <dev@stevenfoncken.de>
 * @copyright ^
 * @license https://github.com/stevenfoncken/multitool-for-spotify-php/blob/master/LICENSE - MIT License
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
