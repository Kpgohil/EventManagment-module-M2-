<?php
declare(strict_types=1);

namespace Elsnertech\Event\Model;

use Magento\Framework\Model\AbstractModel;

class Event extends AbstractModel
{
    public const STATUS_ENABLED = 1;
    public const STATUS_DISABLED = 0;

    protected function _construct(): void
    {
        $this->_init(\Elsnertech\Event\Model\ResourceModel\Event::class);
    }
}

