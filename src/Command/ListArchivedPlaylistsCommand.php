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

namespace StevenFoncken\MultiToolForSpotify\Command;

use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use StevenFoncken\MultiToolForSpotify\Service\PlaylistService;
use StevenFoncken\MultiToolForSpotify\Console\Style\CustomStyle;

/**
 * Console command that lists archived playlists.
 *
 * @since v2.0.0
 * @author Steven Foncken <dev[at]stevenfoncken[dot]de>
 */
#[AsCommand(
    name: 'mtfsp:archive:list-playlists',
    description: 'List archived playlists',
)]
class ListArchivedPlaylistsCommand extends Command
{
    /**
     * @param PlaylistService $playlistService
     */
    public function __construct(
        private readonly PlaylistService $playlistService
    ) {
        parent::__construct();
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new CustomStyle($input, $output);

        $table = new Table($output);
        $table->setHeaders(['Name', 'URL']);

        $rows = [];
        foreach ($this->playlistService->findAllArchivedPlaylists() as $playlist) {
            $rows = $table->addRow([$playlist->name, $playlist->external_urls->spotify]);
        }

        if (empty($rows)) {
            $io->magenta('No archived playlists found.');
        } else {
            $table->render();
        }


        return Command::SUCCESS;
    }
}
