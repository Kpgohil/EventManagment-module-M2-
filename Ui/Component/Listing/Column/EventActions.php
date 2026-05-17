<?php
declare(strict_types=1);

namespace Elsnertech\Event\Ui\Component\Listing\Column;

use Magento\Framework\UrlInterface;
use Magento\Ui\Component\Listing\Columns\Column;

class EventActions extends Column
{
    private const URL_PATH_EDIT = 'elsnertech_event/event/edit';
    private const URL_PATH_DELETE = 'elsnertech_event/event/delete';

    public function __construct(
        \Magento\Framework\View\Element\UiComponent\ContextInterface $context,
        \Magento\Framework\View\Element\UiComponentFactory $uiComponentFactory,
        private readonly UrlInterface $urlBuilder,
        array $components = [],
        array $data = []
    ) {
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    public function prepareDataSource(array $dataSource): array
    {
        if (!isset($dataSource['data']['items'])) {
            return $dataSource;
        }

        $name = $this->getData('name');
        foreach ($dataSource['data']['items'] as &$item) {
            if (!isset($item['event_id'])) {
                continue;
            }
            $item[$name] = [
                'edit' => [
                    'href' => $this->urlBuilder->getUrl(self::URL_PATH_EDIT, ['event_id' => $item['event_id']]),
                    'label' => __('Edit'),
                ],
                'delete' => [
                    'href' => $this->urlBuilder->getUrl(self::URL_PATH_DELETE, ['event_id' => $item['event_id']]),
                    'label' => __('Delete'),
                    'confirm' => [
                        'title' => __('Delete "%1"', $item['title']),
                        'message' => __('Are you sure you want to delete "%1"?', $item['title']),
                    ],
                ],
            ];
        }

        return $dataSource;
    }
}

