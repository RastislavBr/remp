<?php
declare(strict_types=1);

namespace Remp\MailerModule\Commands;

use DateInterval;
use Mailgun\Model\Event\Event;
use Mailgun\Model\Event\EventResponse;
use Nette\Utils\DateTime;
use Remp\MailerModule\Models\Mailer\MailgunMailer;
use Remp\MailerModule\Repositories\LogsRepository;
use Remp\MailerModule\Models\Sender\MailerFactory;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class MailgunEventsCommand extends Command
{
    const WAIT_SECONDS = 15;

    const INTENTIONAL_DELAY_SECONDS = 60;

    /** @var MailgunMailer  */
    private $mailgun;

    private $logsRepository;

    public function __construct(
        MailerFactory $mailerFactory,
        LogsRepository $logsRepository
    ) {
        parent::__construct();
        $this->mailgun = $mailerFactory->getMailer(MailgunMailer::ALIAS);
        $this->logsRepository = $logsRepository;
    }

    /**
     * Configure command
     */
    protected function configure()
    {
        $this->setName('mailgun:events')
            ->setDescription('Syncs latest mailgun events with local log')
            ->addArgument('now', InputArgument::OPTIONAL, 'Offset from "now" of the first event to be processed in seconds', '30');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $nowOffset = $input->getArgument('now');

        $output->writeln('');
        $output->writeln('<info>***** SYNCING MAILGUN EVENTS *****</info>');
        $output->writeln('');

        $dateTo = (new DateTime())
            ->sub(new DateInterval(sprintf("PT%dS", self::INTENTIONAL_DELAY_SECONDS)));
        $dateFrom = (clone $dateTo)->sub(new DateInterval(sprintf("PT%dS", $nowOffset)));

        $eventResponse = $this->getEvents($dateFrom, $dateTo);
        $latestEventTime = $dateFrom;

        while (true) {
            $events = $eventResponse->getItems();
            if (count($events) == 0) {
                $output->writeln(sprintf("%s: all events processed, waiting for %d seconds before proceeding", new DateTime(), self::WAIT_SECONDS));
                sleep(self::WAIT_SECONDS);

                $dateTo = (new DateTime())
                    ->sub(new DateInterval(sprintf("PT%dS", self::INTENTIONAL_DELAY_SECONDS)));

                $eventResponse = $this->getEvents($latestEventTime, $dateTo);
                continue;
            }

            /** @var Event $event */
            foreach ($events as $event) {
                $userVariables = $event->getUserVariables();
                $date = DateTime::from($event->getEventDate());
                if ($date > $latestEventTime) {
                    $latestEventTime = $date;
                }

                $eventTimestamp = explode('.', (string) $event->getTimestamp())[0];
                $date = DateTime::from($eventTimestamp);

                if (!isset($userVariables['mail_sender_id'])) {
                    // cannot map to logsRepository instance
                    $output->writeln(sprintf("%s: ignoring event: %s (unsupported)", $date, $event->getEvent()));
                    continue;
                }

                $mappedEvent = $this->logsRepository->mapEvent($event->getEvent(), $event->getReason());
                if (!$mappedEvent) {
                    // unsupported event type
                    $output->writeln(sprintf("%s: ignoring event: %s (unsupported)", $date, $event->getEvent()));
                    continue;
                }

                $updated = $this->logsRepository->getTable()->where([
                    'mail_sender_id' => $userVariables['mail_sender_id'],
                ])->update([
                    $mappedEvent => $date,
                    'updated_at' => new DateTime(),
                ]);

                if (!$updated) {
                    $output->writeln(sprintf("%s: event ignored, missing mail_logs record: %s (%s)", $date, $event->getRecipient(), $event->getEvent()));
                } else {
                    $output->writeln(sprintf("%s: event processed: %s (%s)", $date, $event->getRecipient(), $event->getEvent()));
                }
            }

            $eventResponse = $this->mailgun->mailer()->events()->nextPage($eventResponse);
        }

        return 0;
    }

    private function getEvents(DateTime $begin, DateTime $end): EventResponse
    {
        /** @var EventResponse $eventResponse */
        return $this->mailgun->mailer()->events()->get($this->mailgun->option('domain'), [
            'ascending' => true,
            'begin' => $begin->getTimestamp(),
            'end' => $end->getTimestamp(),
            'event' => implode(' OR ', $this->logsRepository->mappedEvents()),
        ]);
    }
}
