<?php
declare(strict_types=1);

namespace Remp\MailerModule\Api\v1\Handlers\Users;

use Remp\MailerModule\Api\JsonValidationTrait;
use Remp\MailerModule\Repository\ListsRepository;
use Remp\MailerModule\Repository\ListVariantsRepository;
use Remp\MailerModule\Repository\UserSubscriptionsRepository;
use Tomaj\NetteApi\Handlers\BaseHandler;
use Tomaj\NetteApi\Params\InputParam;
use Tomaj\NetteApi\Params\RawInputParam;
use Tomaj\NetteApi\Response\JsonApiResponse;
use Tomaj\NetteApi\Response\ResponseInterface;

class UnSubscribeHandler extends BaseHandler
{
    private $userSubscriptionsRepository;

    private $listsRepository;

    private $listVariantsRepository;

    use JsonValidationTrait;

    public function __construct(
        UserSubscriptionsRepository $userSubscriptionsRepository,
        ListsRepository $listsRepository,
        ListVariantsRepository $listVariantsRepository
    ) {
        parent::__construct();
        $this->userSubscriptionsRepository = $userSubscriptionsRepository;
        $this->listsRepository = $listsRepository;
        $this->listVariantsRepository = $listVariantsRepository;
    }

    public function params(): array
    {
        return [
            new RawInputParam('raw'),
        ];
    }

    public function handle(array $params): ResponseInterface
    {
        $payload = $this->validateInput($params['raw'], __DIR__ . '/unsubscribe.schema.json');

        if ($this->hasErrorResponse()) {
            return $this->getErrorResponse();
        }

        if (isset($payload['list_code'])) {
            $list = $this->listsRepository->findByCode($payload['list_code'])->fetch();
        } else {
            $list = $this->listsRepository->find($payload['list_id']);
        }

        if ($list === false) {
            return new JsonApiResponse(404, ['status' => 'error', 'message' => 'list not found']);
        }

        if (isset($payload['variant_id'])) {
            $variant = $this->listVariantsRepository->findByIdAndMailTypeId($payload['variant_id'], $list->id);
            if (!$variant) {
                return new JsonApiResponse(404, ['status' => 'error', 'message' => 'variant not found']);
            }

            $userSubscription = $this->userSubscriptionsRepository->getUserSubscription($list, $payload['user_id'], $payload['email']);
            if (!$userSubscription) {
                return new JsonApiResponse(200, ['status' => 'ok']);
            }

            $this->userSubscriptionsRepository->unsubscribeUserVariant($userSubscription, $variant, $payload['utm_params'] ?? []);
        } else {
            $this->userSubscriptionsRepository->unsubscribeUser($list, $payload['user_id'], $payload['email'], $payload['utm_params'] ?? []);
        }

        return new JsonApiResponse(200, ['status' => 'ok']);
    }
}
