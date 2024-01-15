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
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Command\LockableTrait;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use StevenFoncken\MultiToolForSpotify\Helper\CsvHelper;
use StevenFoncken\MultiToolForSpotify\Service\MailService;
use StevenFoncken\MultiToolForSpotify\Service\PlaylistService;
use StevenFoncken\MultiToolForSpotify\Console\Style\CustomStyle;

/**
 * Console command that archive playlists based on passed playlist Ids.
 *
 * @since 0.2.0
 * @author Steven Foncken <dev@stevenfoncken.de>
 */
#[AsCommand(
    name: 'mtfsp:archive',
    description: 'Archive playlists from CSV or argument input',
)]
class ArchiveCommand extends Command
{
    use LockableTrait;

    /**
     * @param LoggerInterface $logger
     * @param PlaylistService $playlistService
     * @param MailService     $mailService
     */
    public function __construct(
        private LoggerInterface $logger,
        private readonly PlaylistService $playlistService,
        private readonly MailService $mailService
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
                'playlistIDsOrCsv',
                InputArgument::REQUIRED,
                'Comma-separated playlist Ids or CSV (Playlist_Name_Prefix;Playlist_Name_Suffix;Playlist_Sort_Order;Playlist_Id;Tags)'
            )
            ->addOption(
                'mail',
                null,
                InputOption::VALUE_NONE,
                'Mail the logs generated from the run (config in .env)',
            );
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return int
     * @throws \Symfony\Component\Mailer\Exception\TransportExceptionInterface
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if ($this->lock() === false) {
            $output->writeln('<fg=yellow>The command is already running in another process..</>');

            return Command::SUCCESS;
        }

        $this->logger->info('Archive process: Start');

        // ---

        $playlistIDsOrCsvArg = (string) $input->getArgument('playlistIDsOrCsv');
        $mailLastRunLogs = (bool) $input->getOption('mail');
        $io = new CustomStyle($input, $output);

        // ---

        // Validate argument
        if (
            file_exists($playlistIDsOrCsvArg) === false &&
            pathinfo($playlistIDsOrCsvArg, PATHINFO_EXTENSION) === 'csv'
        ) {
            throw new InvalidArgumentException(
                'Invalid input data. Please provide a CSV file or a comma-separated string.',
                1699522791
            );
        }

        // ---

        $section = $output->section();
        $section->writeln('<fg=yellow>Fetching all archived playlists...</>');
        $archivedPlaylists = []/*$this->playlistService->findAllArchivedPlaylists()*/;//TODO
        $section->clear();

        // ---

        ProgressBar::setFormatDefinition('custom', ' %current%/%max% - Archiving playlist... (%playlist_id%)');
        $progressBar = new ProgressBar($output);
        $progressBar->setFormat('custom');
        $progressBar->setMessage('', 'playlist_id');

        $newArchivedPlaylistsCount = 0;

        // Process CSV file
        if (file_exists($playlistIDsOrCsvArg) && pathinfo($playlistIDsOrCsvArg, PATHINFO_EXTENSION) === 'csv') {
            $csvData = CsvHelper::getCsvData($playlistIDsOrCsvArg, ';');
            $progressBar->start(count($csvData));

            foreach ($csvData as $csvRow) {
                $progressBar->setMessage($csvRow['Playlist_Id'], 'playlist_id');
                $progressBar->advance();

                if (
                    $this->playlistService->archivePlaylist(
                        playlistId: $csvRow['Playlist_Id'],
                        archivedPlaylists: $archivedPlaylists,
                        namePrefix: $csvRow['Playlist_Name_Prefix'],
                        nameSuffix: $csvRow['Playlist_Name_Suffix'],
                        tracksSortOrder: $csvRow['Playlist_Sort_Order']
                    )
                ) {
                    $newArchivedPlaylistsCount++;
                }
                usleep(20000);
            }
        }

        if (pathinfo($playlistIDsOrCsvArg, PATHINFO_EXTENSION) !== 'csv') {
            $inputPlaylistIds[] = $playlistIDsOrCsvArg;

            // Process comma-separated string
            if (str_contains($playlistIDsOrCsvArg, ',')) {
                $inputPlaylistIds = \str_replace(' ', '', \str_getcsv($playlistIDsOrCsvArg));
            }
            $progressBar->start(count($inputPlaylistIds));

            foreach ($inputPlaylistIds as $playlistId) {
                $progressBar->setMessage($playlistId, 'playlist_id');
                $progressBar->advance();

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
        }
        $progressBar->finish();

        // ---

        $io->newLine();
        $io->magenta('New archived playlists: ' . $newArchivedPlaylistsCount);
        $io->success('Done.');
        $this->logger->info('Archive process: Done', ['archived_playlists' => $newArchivedPlaylistsCount]);

        if ($mailLastRunLogs) {
            $mailSubject = 'MTFSP - Playlists archived ' . date('W/o');

            $this->mailService->sendLastRunLogsMail($mailSubject);
        }


        return Command::SUCCESS;
    }
}
