<?php
declare(strict_types=1);

namespace Elsnertech\Event\Model\Event;

use Elsnertech\Event\Model\ResourceModel\Event\CollectionFactory;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Ui\DataProvider\AbstractDataProvider;

class DataProvider extends AbstractDataProvider
{
    private array $loadedData = [];

    public function __construct(
        string $name,
        string $primaryFieldName,
        string $requestFieldName,
        CollectionFactory $collectionFactory,
        private readonly StoreManagerInterface $storeManager,
        array $meta = [],
        array $data = []
    ) {
        $this->collection = $collectionFactory->create();
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
    }

    public function getData(): array
    {
        if (!empty($this->loadedData)) {
            return $this->loadedData;
        }

        $mediaUrl = $this->storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA);
        foreach ($this->collection->getItems() as $event) {
            $data = $event->getData();
            $images = [];
            foreach ((array)$event->getData('images') as $image) {
                $name = (string)($image['image'] ?? '');
                if ($name === '') {
                    continue;
                }
                $images[] = [
                    'name' => $name,
                    'url' => $mediaUrl . ltrim($name, '/'),
                    'file' => $name,
                    'label' => (string)($image['label'] ?? ''),
                    'position' => (int)($image['position'] ?? 0),
                    'disabled' => (int)($image['disabled'] ?? 0),
                ];
            }
            $data['images'] = $images;
            $this->loadedData[(int)$event->getId()] = $data;
        }

        return $this->loadedData;
    }
}
