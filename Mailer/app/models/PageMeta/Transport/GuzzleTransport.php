<?php

namespace Remp\MailerModule\PageMeta;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\ServerException;

class GuzzleTransport implements TransportInterface
{
    public function getContent(string $url): ?string
    {
        $client = new Client();
        try {
            $res = $client->get($url);
            return (string) $res->getBody();
        } catch (ConnectException $e) {
            return null;
        } catch (ServerException $e) {
            return null;
        }
    }
}
