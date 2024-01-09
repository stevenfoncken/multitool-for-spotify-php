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

namespace StevenFoncken\MultiToolForSpotify\Command;

use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use StevenFoncken\MultiToolForSpotify\Service\PlaylistService;
use StevenFoncken\MultiToolForSpotify\Console\Style\CustomStyle;

/**
 * Console command that deletes archived playlists.
 *
 * @author    Steven Foncken <dev@stevenfoncken.de>
 * @copyright ^
 * @license   https://github.com/stevenfoncken/multitool-for-spotify-php/blob/master/LICENSE - MIT License
 */
#[AsCommand(
    name: 'mtfsp:archive:delete-playlists',
    description: 'Delete archived playlists',
)]
class DeleteArchivedPlaylistsCommand extends Command
{
    /**
     * @param LoggerInterface $logger
     * @param PlaylistService $playlistService
     */
    public function __construct(
        private LoggerInterface $logger,
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
        $this->logger->info('"Archived playlist deletion" process: Start');

        $section = $output->section();
        $io = new CustomStyle($input, $section);

        // Switch 'ask' color to magenta
        $oldOutputFormatterStyle = $io->getFormatter()->getStyle('info');
        $outputStyleRed = new OutputFormatterStyle('red');
        $io->getFormatter()->setStyle('info', $outputStyleRed);

        // ---

        $confirmation = $io->confirm('DO YOU WANT TO DELETE ALL ARCHIVED PLAYLISTS?', false);
        if ($confirmation === false) {
            return Command::SUCCESS;
        }
        $section->clear();

        $confirmation = $io->confirm('Are you really sure?');
        if ($confirmation === false) {
            return Command::SUCCESS;
        }
        $section->clear();

        // ---

        // Set OutputFormatterStyle to initial
        $io->getFormatter()->setStyle('info', $oldOutputFormatterStyle);

        $table = new Table($section);
        $table->setHeaders(['Deleted playlists']);

        $rows = [];
        foreach ($this->playlistService->deleteAllArchivedPlaylists() as $deletedPlaylist) {
            $rows = $table->appendRow([$deletedPlaylist->name]);
        }

        if (empty($rows)) {
            $io->magenta('No archived playlists found.');
        }

        $this->logger->info('"Archived playlist deletion" process: Done');


        return Command::SUCCESS;
    }
}
