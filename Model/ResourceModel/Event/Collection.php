<?php
declare(strict_types=1);

namespace Elsnertech\Event\Model\ResourceModel\Event;

use Elsnertech\Event\Model\Event as EventModel;
use Elsnertech\Event\Model\ResourceModel\Event as EventResource;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    protected function _construct(): void
    {
        $this->_init(EventModel::class, EventResource::class);
    }

    protected function _afterLoad(): self
    {
        parent::_afterLoad();
        foreach ($this->getItems() as $item) {
            $this->getResource()->afterLoad($item);
        }
        return $this;
    }
}

