<?php

namespace StevenFoncken\MultiToolForSpotify\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\Table;
use StevenFoncken\MultiToolForSpotify\Service\PlaylistService;
use StevenFoncken\MultiToolForSpotify\Service\TrackService;
use StevenFoncken\MultiToolForSpotify\Console\Style\CustomStyle;

/**
 * Console command that searches for a given track (id) in all user-generated playlists (library).
 *
 * @author    Steven Foncken <dev@stevenfoncken.de>
 * @copyright ^
 * @license   https://github.com/stevenfoncken/multitool-for-spotify-php/blob/master/LICENSE - MIT License
 */
#[AsCommand(
    name: 'mtfsp:search:track-in-library',
    description: 'Search track in user library',
)]
class SearchTrackInLibraryCommand extends Command
{
    /**
     * @param PlaylistService $playlistService
     * @param TrackService    $trackService
     */
    public function __construct(
        private readonly PlaylistService $playlistService,
        private readonly TrackService $trackService
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
                'trackIdNeedle',
                InputArgument::REQUIRED,
                'Id of the track to search for'
            )
            ->addOption(
                'skipArchived',
                null,
                InputOption::VALUE_NONE,
                'Skip archived playlists',
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
        $trackIdNeedle = $input->getArgument('trackIdNeedle');
        $skipArchived = $input->getOption('skipArchived');
        $io = new CustomStyle($input, $output);

        // ---

        $track = $this->trackService->getTrack($trackIdNeedle);
        $trackName = $track->name;
        $trackArtists = implode(', ', $this->trackService->getAllTrackArtists($track));

        $io->magenta(
            sprintf(
                'Search for track: %s by %s',
                $trackName,
                $trackArtists,
            )
        );

        // ---

        $foundInPlaylists = [];
        if ($this->trackService->isTrackInUserSavedTracks($trackIdNeedle)[0]) {
            $foundInPlaylists[] = 'Liked Songs';
        }

        foreach ($this->playlistService->getAllUserPlaylists(true, !$skipArchived) as $playlist) {
            $trackIds = $this->playlistService->getAllTracksIdsFromPlaylist($playlist->id);

            foreach ($trackIds as $trackId) {
                if ($trackId === $trackIdNeedle) {
                    $foundInPlaylists[] = $playlist->name;
                    break;
                }
            }
        }

        if (empty($foundInPlaylists)) {
            $io->yellow('Track not found in any user playlist.');
            return Command::SUCCESS;
        }

        // ---

        $io->magenta('Found in:');

        $table = new Table($output);
        $table->setHeaders(['Playlist']);

        foreach ($foundInPlaylists as $playlistName) {
            $table->addRow([$playlistName]);
        }
        $table->render();


        return Command::SUCCESS;
    }
}
