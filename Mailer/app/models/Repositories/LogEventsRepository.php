<?php

namespace Remp\MailerModule\Repository;

use Remp\MailerModule\ActiveRow;
use Nette\Utils\DateTime;
use Remp\MailerModule\Repository;

class LogEventsRepository extends Repository
{
    protected $tableName = 'mail_logs_events';

    public function addLog(ActiveRow $log, DateTime $eventTime, $type)
    {
        return $this->insert([
            'mail_log_id' => $log->id,
            'created_at' => new DateTime(),
            'event_at' => $eventTime,
            'type' => $type,
        ]);
    }

    public function findByLogType(int $logId, string $type)
    {
        return $this->getTable()->where([
            'mail_log_id' => $logId,
            'type' => $type,
        ])->fetch();
    }

    public function latestEventTime(): DateTime
    {
        return $this->getTable()->max('event_at');
    }
}
