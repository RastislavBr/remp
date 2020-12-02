<?php

namespace Remp\MailerModule\Repository;

use Nette\Utils\DateTime;
use Remp\MailerModule\Repository;

class AutoLoginTokensRepository extends Repository
{
    protected $tableName = 'autologin_tokens';

    public function getInsertData(string $token, string $email, DateTime $validFrom, DateTime $validTo, int $maxCount = 1)
    {
        return [
            'token' => $token,
            'email' => $email,
            'valid_from' => $validFrom,
            'valid_to' => $validTo,
            'max_count' => $maxCount,
            'used_count' => 0,
            'created_at' => new DateTime(),
        ];
    }
}
