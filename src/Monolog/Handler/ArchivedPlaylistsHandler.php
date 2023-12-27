<?php

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
