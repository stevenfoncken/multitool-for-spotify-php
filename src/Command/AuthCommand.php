<?php

namespace StevenFoncken\MultiToolForSpotify\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use StevenFoncken\MultiToolForSpotify\Console\Style\CustomStyle;
use StevenFoncken\MultiToolForSpotify\Service\AuthService;

/**
 * A console command that leverage the AuthService.
 *
 * @author Steven Foncken <dev@stevenfoncken.de>
 * @copyright ^
 * @license https://github.com/stevenfoncken/multitool-for-spotify-php/blob/master/LICENSE - MIT License
 */
#[AsCommand(
    name: 'mtfsp:auth',
    description: 'Handles the Spotify OAuth process & API Token generation.',
)]
class AuthCommand extends Command
{
    /**
     * @param AuthService $authService
     */
    public function __construct(
        private readonly AuthService $authService
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

        // switch 'ask' color to magenta
        $outputStyle = new OutputFormatterStyle('magenta');
        $io->getFormatter()->setStyle('info', $outputStyle);

        // ---

        $io->magenta([
            'Please open the URL in your browser and login with your Spotify account:',
            $this->authService->generateOAuthUrl(),
        ]);

        // ---

        $callbackUrlQuestion = $this->authService->generateCallbackUrlQuestion();
        $callbackUrlQuestion->setMaxAttempts(2);

        $callbackURL = $io->askQuestion($callbackUrlQuestion);
        $this->authService->generateApiTokens($callbackURL);

        // ---

        $io->success('Done. You can now use the other commands !');


        return Command::SUCCESS;
    }
}
