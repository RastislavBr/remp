<?php

namespace Remp\MailerModule\User;

class Dummy implements IUser
{
    public function list(array $userIds, int $page): array
    {
        if ($page > 1) {
            return [];
        }

        return [
            1 => ['id' => 1, 'email' => 'foo@example.com'],
            2 => ['id' => 2, 'email' => 'bar@example.com'],
        ];
    }
}
