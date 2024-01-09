<?php

/**
 * This file is part of the multitool-for-spotify-php project.
 * @see https://github.com/stevenfoncken/multitool-for-spotify-php
 *
 * @copyright 2023-present Steven Foncken <dev@stevenfoncken.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * @license https://github.com/stevenfoncken/multitool-for-spotify-php/blob/master/LICENSE MIT License
 */

namespace StevenFoncken\MultiToolForSpotify\Monolog\Handler;

use Monolog\LogRecord;
use Monolog\Handler\HandlerWrapper;

/**
 * HandlerWrapper that adds custom filtering so that only archive playlist creation is logged.
 * It can be applied to other handlers e.g. StreamHandler.
 *
 * @author Steven Foncken <dev@stevenfoncken.de>
 * @copyright ^
 * @license https://github.com/stevenfoncken/multitool-for-spotify-php/blob/master/LICENSE - MIT License
 */
class ArchivedPlaylistsHandler extends HandlerWrapper
{
    /**
     * @param LogRecord $record
     *
     * @return bool
     */
    public function handle(LogRecord $record): bool
    {
        if ($record->message !== 'archivePlaylist: Done') {
            return false;
        }


        return $this->handler->handle($record);
    }
}
