<?php
declare(strict_types=1);

namespace Elsnertech\Event\Model\Source;

use Elsnertech\Event\Model\Event;
use Magento\Framework\Data\OptionSourceInterface;

class Status implements OptionSourceInterface
{
    public function toOptionArray(): array
    {
        return [
            ['value' => Event::STATUS_ENABLED, 'label' => __('Enabled')],
            ['value' => Event::STATUS_DISABLED, 'label' => __('Disabled')],
        ];
    }
}

