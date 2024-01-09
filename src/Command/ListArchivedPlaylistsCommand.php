<?php

namespace StevenFoncken\MultiToolForSpotify\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\Table;
use StevenFoncken\MultiToolForSpotify\Service\PlaylistService;
use StevenFoncken\MultiToolForSpotify\Console\Style\CustomStyle;

/**
 * Console command that lists archived playlists.
 *
 * @author    Steven Foncken <dev@stevenfoncken.de>
 * @copyright ^
 * @license   https://github.com/stevenfoncken/multitool-for-spotify-php/blob/master/LICENSE - MIT License
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

        if (empty($rows) === false) {
            $table->render();
        } else {
            $io->magenta('No archived playlists found.');
        }


        return Command::SUCCESS;
    }
}
