<?php
declare(strict_types=1);

namespace Elsnertech\Event\Model\Source;

use Magento\Framework\Data\OptionSourceInterface;
use Magento\Store\Model\StoreManagerInterface;

class StoreView implements OptionSourceInterface
{
    public function __construct(
        private readonly StoreManagerInterface $storeManager
    ) {
    }

    public function toOptionArray(): array
    {
        $options = [
            ['value' => 0, 'label' => __('All Store Views')]
        ];

        foreach ($this->storeManager->getStores() as $store) {
            $options[] = [
                'value' => (int)$store->getId(),
                'label' => $store->getName(),
            ];
        }

        return $options;
    }
}
