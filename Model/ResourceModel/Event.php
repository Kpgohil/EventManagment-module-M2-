<?php
declare(strict_types=1);

namespace Elsnertech\Event\Model\ResourceModel;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class Event extends AbstractDb
{
    protected function _construct(): void
    {
        $this->_init('elsnertech_event', 'event_id');
    }

    protected function _afterLoad(AbstractModel $object): self
    {
        $object->setData('store_ids', $this->lookupStoreIds((int)$object->getId()));
        $object->setData('images', $this->lookupImages((int)$object->getId()));
        $object->setData('content_store_id', $this->lookupContentStoreId((int)$object->getId()));

        return parent::_afterLoad($object);
    }

    protected function _beforeSave(AbstractModel $object): self
    {
        $urlKey = (string)$object->getData('url_key');
        if ($urlKey === '') {
            throw new LocalizedException(__('URL key is required.'));
        }

        $connection = $this->getConnection();
        $bind = ['url_key' => $urlKey];
        $select = $connection->select()
            ->from($this->getMainTable(), ['event_id'])
            ->where('url_key = :url_key');
        $existingId = (int)$connection->fetchOne($select, $bind);
        if ($existingId && $existingId !== (int)$object->getId()) {
            throw new LocalizedException(__('URL key already exists.'));
        }

        return parent::_beforeSave($object);
    }

    protected function _afterSave(AbstractModel $object): self
    {
        $this->saveStores($object);
        $this->saveImages($object);
        $this->saveStoreContent($object);
        return parent::_afterSave($object);
    }

    public function applyStoreContent(AbstractModel $object, int $storeId): void
    {
        if ((int)$object->getId() <= 0 || $storeId <= 0) {
            return;
        }

        $connection = $this->getConnection();
        $select = $connection->select()
            ->from($this->getTable('elsnertech_event_store_content'))
            ->where('event_id = ?', (int)$object->getId())
            ->where('store_id = ?', $storeId)
            ->limit(1);
        $row = $connection->fetchRow($select);
        if (!$row) {
            return;
        }

        foreach (['title', 'short_description', 'description', 'venue', 'meta_title', 'meta_keywords', 'meta_description'] as $field) {
            if (array_key_exists($field, $row) && $row[$field] !== null && $row[$field] !== '') {
                $object->setData($field, $row[$field]);
            }
        }
        $object->setData('content_store_id', $storeId);
    }

    public function lookupStoreIds(int $eventId): array
    {
        if ($eventId <= 0) {
            return [];
        }

        $connection = $this->getConnection();
        $select = $connection->select()
            ->from($this->getTable('elsnertech_event_store'), 'store_id')
            ->where('event_id = ?', $eventId);

        return array_map('intval', $connection->fetchCol($select));
    }

    public function lookupImages(int $eventId): array
    {
        if ($eventId <= 0) {
            return [];
        }

        $connection = $this->getConnection();
        $select = $connection->select()
            ->from($this->getTable('elsnertech_event_image'), ['image_id', 'image', 'label', 'position', 'disabled'])
            ->where('event_id = ?', $eventId)
            ->order('position ASC');

        return $connection->fetchAll($select);
    }

    public function lookupContentStoreId(int $eventId): int
    {
        if ($eventId <= 0) {
            return 0;
        }

        $connection = $this->getConnection();
        $select = $connection->select()
            ->from($this->getTable('elsnertech_event_store_content'), 'store_id')
            ->where('event_id = ?', $eventId)
            ->order('store_id ASC')
            ->limit(1);

        return (int)$connection->fetchOne($select);
    }

    private function saveStores(AbstractModel $object): void
    {
        $connection = $this->getConnection();
        $table = $this->getTable('elsnertech_event_store');
        $eventId = (int)$object->getId();
        if ($eventId <= 0) {
            return;
        }
        $storeIds = $object->getData('store_ids') ?: [0];
        $storeIds = array_unique(array_map('intval', (array)$storeIds));

        $connection->delete($table, ['event_id = ?' => $eventId]);
        $rows = [];
        foreach ($storeIds as $storeId) {
            $rows[] = ['event_id' => $eventId, 'store_id' => $storeId];
        }
        if ($rows) {
            $connection->insertMultiple($table, $rows);
        }
    }

    private function saveImages(AbstractModel $object): void
    {
        $connection = $this->getConnection();
        $table = $this->getTable('elsnertech_event_image');
        $eventId = (int)$object->getId();
        if ($eventId <= 0) {
            return;
        }
        $images = $object->getData('images') ?: [];
        $normalized = [];

        foreach ((array)$images as $position => $image) {
            if (!is_array($image)) {
                continue;
            }
            $path = (string)($image['name'] ?? $image['image'] ?? '');
            if ($path === '') {
                continue;
            }
            if (!empty($image['tmp_name']) && empty($image['name'])) {
                continue;
            }

            $normalized[] = [
                'event_id' => $eventId,
                'image' => ltrim($path, '/'),
                'label' => (string)($image['label'] ?? ''),
                'position' => isset($image['position']) ? (int)$image['position'] : ($position + 1),
                'disabled' => !empty($image['disabled']) ? 1 : 0,
            ];
        }

        $connection->delete($table, ['event_id = ?' => $eventId]);
        if ($normalized) {
            $connection->insertMultiple($table, $normalized);
        }
    }

    private function saveStoreContent(AbstractModel $object): void
    {
        $eventId = (int)$object->getId();
        if ($eventId <= 0) {
            return;
        }

        $storeId = (int)$object->getData('content_store_id');
        if ($storeId <= 0) {
            return;
        }

        $connection = $this->getConnection();
        $table = $this->getTable('elsnertech_event_store_content');
        $data = [
            'event_id' => $eventId,
            'store_id' => $storeId,
            'title' => (string)$object->getData('title'),
            'short_description' => (string)$object->getData('short_description'),
            'description' => (string)$object->getData('description'),
            'venue' => (string)$object->getData('venue'),
            'meta_title' => (string)$object->getData('meta_title'),
            'meta_keywords' => (string)$object->getData('meta_keywords'),
            'meta_description' => (string)$object->getData('meta_description'),
        ];

        $connection->insertOnDuplicate(
            $table,
            $data,
            ['title', 'short_description', 'description', 'venue', 'meta_title', 'meta_keywords', 'meta_description']
        );
    }
}
