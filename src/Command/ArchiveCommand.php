<?php

namespace StevenFoncken\MultiToolForSpotify\Command;

use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Command\LockableTrait;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use StevenFoncken\MultiToolForSpotify\Helper\CsvHelper;
use StevenFoncken\MultiToolForSpotify\Service\PlaylistService;
use StevenFoncken\MultiToolForSpotify\Console\Style\CustomStyle;

/**
 * Console command that archive playlists based on passed playlist Ids.
 *
 * @author Steven Foncken <dev@stevenfoncken.de>
 * @copyright ^
 * @license https://github.com/stevenfoncken/multitool-for-spotify-php/blob/master/LICENSE - MIT License
 */
#[AsCommand(
    name: 'mtfsp:archive',
    description: 'Archive playlists from CSV or argument input.',
)]
class ArchiveCommand extends Command
{
    use LockableTrait;

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
     * @return void
     */
    protected function configure(): void
    {
        $this->addArgument(
            'playlistIDsOrCsv',
            InputArgument::REQUIRED,
            'Comma-separated playlist Ids or CSV (Playlist_Name_Prefix;Playlist_Name_Suffix;Playlist_Sort_Order;Playlist_Id;Tags)'
        );
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if ($this->lock() === false) {
            $output->writeln('<fg=yellow>The command is already running in another process..</>');

            return Command::SUCCESS;
        }

        $this->logger->info('Archive process: Start');

        // ---

        $playlistIDsOrCsvArg = $input->getArgument('playlistIDsOrCsv');
        $io = new CustomStyle($input, $output);

        // ---

        // Validate argument
        if (
            strpos($playlistIDsOrCsvArg, ',') === false &&
            (
                file_exists($playlistIDsOrCsvArg) === false ||
                pathinfo($playlistIDsOrCsvArg, PATHINFO_EXTENSION) !== 'csv'
            )
        ) {
            throw new InvalidArgumentException(
                'Invalid input data. Please provide a CSV file or a comma-separated string.',
                1699522791
            );
        }

        // ---

        $section = $output->section();
        $section->writeln('<fg=yellow>Fetching all archived playlists...</>');
        $archivedPlaylists = $this->playlistService->findAllArchivedPlaylists();
        $section->clear();

        // ---

        ProgressBar::setFormatDefinition('custom', ' %current%/%max% - Archiving playlists... (%playlist_id%)');
        $progressBar = new ProgressBar($output);
        $progressBar->setFormat('custom');
        $progressBar->setMessage('', 'playlist_id');

        $newArchivedPlaylistsCount = 0;
        if (strpos($playlistIDsOrCsvArg, ',') !== false) {
            // Process comma-separated string
            $inputPlaylistIds = \str_replace(' ', '', \str_getcsv($playlistIDsOrCsvArg));

            foreach ($progressBar->iterate($inputPlaylistIds) as $playlistId) {
                $progressBar->setMessage($playlistId, 'playlist_id');

                if (
                    $this->playlistService->archivePlaylist(
                        playlistId: $playlistId,
                        archivedPlaylists: $archivedPlaylists
                    )
                ) {
                    $newArchivedPlaylistsCount++;
                }
                usleep(20000);
            }
        } elseif (file_exists($playlistIDsOrCsvArg) && pathinfo($playlistIDsOrCsvArg, PATHINFO_EXTENSION) === 'csv') {
            // Process CSV file
            foreach ($progressBar->iterate(CsvHelper::getCsvData($playlistIDsOrCsvArg, ';')) as $csvRow) {
                $progressBar->setMessage($csvRow['Playlist_Id'], 'playlist_id');

                if (
                    $this->playlistService->archivePlaylist(
                        playlistId: $csvRow['Playlist_Id'],
                        archivedPlaylists: $archivedPlaylists,
                        newNamePrefix: $csvRow['Playlist_Name_Prefix'],
                        newNameSuffix: $csvRow['Playlist_Name_Suffix'],
                        tracksSortOrder: $csvRow['Playlist_Sort_Order']
                    )
                ) {
                    $newArchivedPlaylistsCount++;
                }
                usleep(20000);
            }
        }

        // ---

        $io->newLine();
        $io->magenta('New archived playlists: ' . $newArchivedPlaylistsCount);
        $io->success('Done.');
        $this->logger->info('Archive process: Done');


        return Command::SUCCESS;
    }
}
