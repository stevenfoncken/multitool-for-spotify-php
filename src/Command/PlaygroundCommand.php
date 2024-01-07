<?php

namespace StevenFoncken\MultiToolForSpotify\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use StevenFoncken\MultiToolForSpotify\Service\TrackService;
use StevenFoncken\MultiToolForSpotify\Service\PlaylistService;
use StevenFoncken\MultiToolForSpotify\Service\ArtistService;
use Psr\Log\LoggerInterface;

/**
 * Playground console command.
 *
 * @author Steven Foncken <dev@stevenfoncken.de>
 * @copyright ^
 * @license https://github.com/stevenfoncken/multitool-for-spotify-php/blob/master/LICENSE - MIT License
 */
#[AsCommand(
    name: 'mtfsp:playground',
    description: 'Playground for SpotifyWebAPI',
    hidden: true,
)]
class PlaygroundCommand extends Command
{
    /**
     * @param LoggerInterface $logger
     * @param PlaylistService $playlistService
     * @param TrackService    $trackService
     */
    public function __construct(
        private LoggerInterface $logger,
        private readonly PlaylistService $playlistService,
        private readonly TrackService $trackService,
        private readonly ArtistService $artistService
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
        $io = new SymfonyStyle($input, $output);
        $io->text(
            $this->playlistService->getPlaylistMetadata('5aHawERps0AMmMLU1KHvv6')->name
        );
        $this->logger->info('PlaygroundCommand log');


        return Command::SUCCESS;
    }
}
