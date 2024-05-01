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

use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use StevenFoncken\MultiToolForSpotify\Service\TrackService;
use StevenFoncken\MultiToolForSpotify\Service\ArtistService;
use StevenFoncken\MultiToolForSpotify\Service\PlaylistService;

/**
 * Playground console command.
 *
 * @since v1.0.0
 * @author Steven Foncken <dev[at]stevenfoncken[dot]de>
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
     * @param ArtistService   $artistService
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
