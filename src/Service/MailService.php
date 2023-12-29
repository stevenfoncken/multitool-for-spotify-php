<?php

namespace StevenFoncken\MultiToolForSpotify\Service;

use Psr\Log\LoggerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mailer\Transport;

/**
 * Service that handles mailing.
 *
 * @author Steven Foncken <dev@stevenfoncken.de>
 * @copyright ^
 * @license https://github.com/stevenfoncken/multitool-for-spotify-php/blob/master/LICENSE - MIT License
 */
class MailService
{
    /**
     * @param string          $mailerDsn
     * @param string          $fromMail
     * @param string          $toMail
     * @param string          $lastRunLogPath
     * @param LoggerInterface $logger
     */
    public function __construct(
        private readonly string $mailerDsn,
        private readonly string $fromMail,
        private readonly string $toMail,
        private readonly string $lastRunLogPath,
        private LoggerInterface $logger
    ) {
    }

    /**
     * @param string $subject
     *
     * @return void
     * @throws \Symfony\Component\Mailer\Exception\TransportExceptionInterface
     */
    public function sendLastRunLogsMail(string $subject): void
    {
        $transport = Transport::fromDsn($this->mailerDsn, null, null, $this->logger);
        $mailer = new Mailer($transport);
        $email = (new Email())
            ->from($this->fromMail)
            ->to($this->toMail)
            ->subject($subject)
            ->text(file_get_contents($this->lastRunLogPath));

        $mailer->send($email);
    }
}
