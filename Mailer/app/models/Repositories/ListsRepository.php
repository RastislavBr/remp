<?php

namespace Remp\MailerModule\Repository;

use Remp\MailerModule\ActiveRow;
use Nette\Utils\DateTime;
use Remp\MailerModule\Repository;
use Remp\MailerModule\Selection;

class ListsRepository extends Repository
{
    protected $tableName = 'mail_types';

    protected $dataTableSearchable = ['code', 'title', 'description'];

    public function all()
    {
        return $this->getTable()->order('sorting ASC');
    }

    public function add(
        int $categoryId,
        int $priority,
        string $code,
        string $name,
        int $sorting,
        bool $isAutoSubscribe,
        bool $isLocked,
        bool $isPublic,
        string $description,
        ?string $previewUrl = null,
        ?string $imageUrl = null,
        bool $publicListing = true
    ) {
        $result = $this->insert([
            'mail_type_category_id' => $categoryId,
            'priority' => $priority,
            'code' => $code,
            'title' => $name,
            'description' => $description,
            'sorting' => $sorting,
            'auto_subscribe' => (bool)$isAutoSubscribe,
            'locked' => (bool)$isLocked,
            'is_public' => (bool)$isPublic,
            'public_listing' => (bool)$publicListing,
            'image_url' => $imageUrl,
            'preview_url' => $previewUrl,
            'created_at' => new DateTime(),
            'updated_at' => new DateTime()
        ]);

        if (is_numeric($result)) {
            return $this->getTable()->where('id', $result)->fetch();
        }

        return $result;
    }

    public function update(ActiveRow &$row, array $data): bool
    {
        unset($data['id']);
        $data['updated_at'] = new DateTime();
        return parent::update($row, $data);
    }

    public function updateSorting(int $newCategoryId, int $newSorting, ?int $oldCategoryId = null, ?int $oldSorting = null): void
    {
        if ($newSorting === $oldSorting) {
            return;
        }

        if ($oldSorting !== null) {
            $this->getTable()
                ->where(
                    'sorting > ? AND mail_type_category_id = ?',
                    $oldSorting,
                    $oldCategoryId
                )->update(['sorting-=' => 1]);
        }

        $this->getTable()->where(
            'sorting >= ? AND mail_type_category_id = ?',
            $newSorting,
            $newCategoryId
        )->update(['sorting+=' => 1]);
    }

    public function findByCode(string $code)
    {
        return $this->getTable()->where(['code' => $code]);
    }

    public function findByCategory(int $categoryId)
    {
        return $this->getTable()->where(['mail_type_category_id' => $categoryId]);
    }

    /**
     * @return Selection
     */
    public function tableFilter()
    {
        return $this->getTable()->order('mail_type_category.sorting, mail_types.sorting');
    }

    public function search(string $term, int $limit): array
    {
        foreach ($this->dataTableSearchable as $column) {
            $where[$column . ' LIKE ?'] = '%' . $term . '%';
        }

        $results = $this->all()
            ->select(implode(',', array_merge(['id'], $this->dataTableSearchable)))
            ->whereOr($where ?? [])
            ->limit($limit)
            ->fetchAssoc('id');

        return $results ?? [];
    }
}
