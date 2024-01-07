<?php

namespace StevenFoncken\MultiToolForSpotify\Command;

use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use StevenFoncken\MultiToolForSpotify\Console\Style\CustomStyle;
use StevenFoncken\MultiToolForSpotify\Service\ArtistService;
use StevenFoncken\MultiToolForSpotify\Service\PlaylistService;
use Psr\Log\LoggerInterface;

/**
 * Console command that copies a given artist's catalog into a new or given playlist.
 *
 * @author    Steven Foncken <dev@stevenfoncken.de>
 * @copyright ^
 * @license   https://github.com/stevenfoncken/multitool-for-spotify-php/blob/master/LICENSE - MIT License
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
        $this->logger->info('Create artist catalog playlist process: Start');

        $artistId = $input->getArgument('artistId');
        $playlistId = $input->getArgument('playlistId');
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

        $artistTrackIds = [];
        foreach ($artistTracks as $artistTrack) {
            $artistTrackIds[] = $artistTrack['SpotifySimplifiedTrackObject']->id;
        }

        $section->writeln('<fg=yellow>Add tracks to playlist...</>');
        $this->playlistService->addTracksToPlaylist($playlistId, $artistTrackIds);
        $section->clear();

        // ---

        $io->green('Tracks added to playlist: ' . $playlistName);
        $io->text($playlistURL);
        $io->newLine();

        self::displayTracksTable(
            $output,
            $artistTracks
        );

        $this->logger->info('Create artist catalog playlist process: Done');


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
