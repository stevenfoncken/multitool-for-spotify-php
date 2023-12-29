<?php

namespace StevenFoncken\MultiToolForSpotify\Command;

use RuntimeException;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Validator\Constraints\Url;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use StevenFoncken\MultiToolForSpotify\Console\Style\CustomStyle;
use StevenFoncken\MultiToolForSpotify\Service\AuthService;
use StevenFoncken\MultiToolForSpotify\Validator\UrlContainsQueryParameter;

/**
 * Console command that handles the Spotify OAuth process & API token generation.
 *
 * @author Steven Foncken <dev@stevenfoncken.de>
 * @copyright ^
 * @license https://github.com/stevenfoncken/multitool-for-spotify-php/blob/master/LICENSE - MIT License
 */
#[AsCommand(
    name: 'mtfsp:auth',
    description: 'Handles the Spotify OAuth process & API token generation.',
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

        // Switch 'ask' color to magenta
        $outputStyle = new OutputFormatterStyle('magenta');
        $io->getFormatter()->setStyle('info', $outputStyle);

        // ---

        $io->magenta([
            'Please open the URL in your browser and login with your Spotify account:',
            $this->authService->generateOAuthUrl(),
        ]);

        // ---

        $callbackUrlQuestion = $this->generateCallbackUrlQuestion();
        $callbackUrlQuestion->setMaxAttempts(2);

        $callbackURL = $io->askQuestion($callbackUrlQuestion);
        $this->authService->saveApiTokens($callbackURL);

        // ---

        $io->success('Done. You can now use the other commands !');


        return Command::SUCCESS;
    }

    /**
     * @return Question
     */
    private function generateCallbackUrlQuestion(): Question
    {
        $question = new Question('Please paste the callback URL here');
        $validator = Validation::createValidator();

        $question->setValidator(function ($answer) use ($validator): string {
            // Validate against the NotBlank constraint
            $notBlankViolations = $validator->validate($answer, new NotBlank());

            if (count($notBlankViolations) > 0) {
                throw new RuntimeException(
                    $notBlankViolations[0]->getMessage()
                );
            }

            // Validate against the Url constraint
            $urlViolations = $validator->validate($answer, new Url());

            if (count($urlViolations) > 0) {
                throw new RuntimeException(
                    $urlViolations[0]->getMessage()
                );
            }

            // Validate against the UrlContainsQueryParameter constraint
            $urlContainsQueryParameterViolations = $validator->validate(
                $answer,
                new UrlContainsQueryParameter(['code', 'state'])
            );

            if (count($urlContainsQueryParameterViolations) > 0) {
                throw new RuntimeException(
                    $urlContainsQueryParameterViolations[0]->getMessage()
                );
            }

            return $answer;
        });


        return $question;
    }
}
