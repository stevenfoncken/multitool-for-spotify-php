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
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use StevenFoncken\MultiToolForSpotify\Service\ArtistService;
use StevenFoncken\MultiToolForSpotify\Service\PlaylistService;
use StevenFoncken\MultiToolForSpotify\Console\Style\CustomStyle;

/**
 * Console command that copies a given artist's catalog into a new or given playlist.
 *
 * @since v2.0.0
 * @author Steven Foncken <dev[at]stevenfoncken[dot]de>
 */
#[AsCommand(
    name: 'mtfsp:artist:catalog-to-playlist',
    description: 'Copy artist catalog to playlist',
)]
class CreateArtistCatalogPlaylistCommand extends Command
{
    /**
     * @param LoggerInterface $logger
     * @param PlaylistService $playlistService
     * @param ArtistService   $artistService
     */
    public function __construct(
        private LoggerInterface $logger,
        private readonly PlaylistService $playlistService,
        private readonly ArtistService $artistService
    ) {
        parent::__construct();
    }

    /**
     * @return void
     */
    protected function configure(): void
    {
        $this
            ->addArgument(
                'artistId',
                InputArgument::REQUIRED,
                'Id of the artist to get the catalog from'
            )
            ->addArgument(
                'playlistId',
                InputArgument::OPTIONAL,
                'Id of the playlist to add the tracks to'
            );
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return int
     * @throws \Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->logger->info('"Create artist catalog playlist" process: Start');

        $artistId = (string) $input->getArgument('artistId');
        $playlistId = (string) $input->getArgument('playlistId');
        $artist = $this->artistService->getArtist($artistId);
        $io = new CustomStyle($input, $output);

        // ---

        $io->magenta('Artist: ' . $artist->name);

        // ---

        if (empty($playlistId)) {
            $playlistName = 'Catalog - ' . $artist->name;

            $playlist = $this->playlistService->createNewUserPlaylist(
                [
                    'name'        => $playlistName,
                    'description' => 'Creation: ' . date('Y-m-d H:i:s'),
                    'public'      => false,
                ]
            );

            $playlistId = $playlist->id;
        } else {
            $playlist = $this->playlistService->getPlaylistMetadata($playlistId);
            $playlistName = $playlist->name;
        }

        $playlistURL = $playlist->external_urls->spotify;

        // ---

        $section = $output->section();
        $section->writeln('<fg=yellow>Fetching all artist tracks...</>');
        $artistTracks = $this->artistService->getAllArtistTracks($artistId);
        $section->clear();

        $artistTrackObjects = [];
        foreach ($artistTracks as $artistTrack) {
            $artistTrackObjects[] = $artistTrack['SpotifySimplifiedTrackObject'];
        }

        $section->writeln('<fg=yellow>Add tracks to playlist...</>');
        $this->playlistService->addTracksToPlaylist($playlistId, $artistTrackObjects);
        $section->clear();

        // ---

        $io->green('Tracks added to playlist: ' . $playlistName);
        $io->text($playlistURL);
        $io->newLine();

        self::displayTracksTable(
            $output,
            $artistTracks
        );

        $this->logger->info('"Create artist catalog playlist" process: Done');


        return Command::SUCCESS;
    }

    /**
     * @param OutputInterface $output
     * @param array           $tracks
     *
     * @return void
     * @throws \Exception
     */
    private static function displayTracksTable(OutputInterface $output, array $tracks): void
    {
        $table = new Table($output);
        $table->setHeaders(['Track name', 'Release date']);

        foreach ($tracks as $track) {
            $releaseDate = $track['release_date'];
            $trackObject = $track['SpotifySimplifiedTrackObject'];
            $name = $trackObject->name;

            $dateTime = new \DateTime($releaseDate);
            $releaseDateFormatted = $dateTime->format('Y-m-d');

            $table->addRow([$name, $releaseDateFormatted]);
        }

        $table->render();
    }
}
