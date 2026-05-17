<?php
declare(strict_types=1);

namespace Elsnertech\Event\Model\Source;

use Magento\Framework\Data\OptionSourceInterface;

class SortOrder implements OptionSourceInterface
{
    public function toOptionArray(): array
    {
        return [
            ['value' => 'start_datetime_asc', 'label' => __('Start Date — Earliest First')],
            ['value' => 'start_datetime_desc', 'label' => __('Start Date — Latest First')],
            ['value' => 'title_asc', 'label' => __('Title — A to Z')],
            ['value' => 'title_desc', 'label' => __('Title — Z to A')],
            ['value' => 'sort_order_asc', 'label' => __('Sort Order — Ascending')],
        ];
    }
}
