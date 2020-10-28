<?php

namespace Remp\MailerModule\Tracker;

use Nette\Utils\DateTime;

interface ITracker
{
    /**
     * trackEvent tracks event with given metadata.
     *
     * @param DateTime $dateTime
     * @param string $category
     * @param string $action
     * @param EventOptions $options
     * @return mixed
     */
    public function trackEvent(DateTime $dateTime, string $category, string $action, EventOptions $options);
}
