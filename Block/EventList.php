<?php
declare(strict_types=1);

namespace Elsnertech\Event\Block;

use Elsnertech\Event\Helper\Config;
use Elsnertech\Event\Model\ResourceModel\Event\Collection;
use Elsnertech\Event\Model\ResourceModel\Event\CollectionFactory;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Store\Model\StoreManagerInterface;

class EventList extends Template
{
    private ?Collection $collection = null;

    public function __construct(
        Context $context,
        private readonly CollectionFactory $collectionFactory,
        private readonly StoreManagerInterface $storeManager,
        private readonly Config $moduleConfig,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    public function getEventCollection(): Collection
    {
        if ($this->collection !== null) {
            return $this->collection;
        }

        $storeId = (int)$this->storeManager->getStore()->getId();
        $collection = $this->collectionFactory->create();
        $collection->addFieldToFilter('main_table.status', 1);

        // Show past events toggle
        if (!$this->moduleConfig->isShowPastEnabled()) {
            $now = (new \DateTimeImmutable('now', new \DateTimeZone('UTC')))->format('Y-m-d H:i:s');
            $collection->addFieldToFilter('main_table.start_datetime', ['gteq' => $now]);
        }

        $collection->getSelect()->join(
            ['event_store' => $collection->getTable('elsnertech_event_store')],
            'main_table.event_id = event_store.event_id',
            []
        )->joinLeft(
            ['store_content' => $collection->getTable('elsnertech_event_store_content')],
            'main_table.event_id = store_content.event_id AND store_content.store_id = ' . $storeId,
            []
        )->columns([
            'title' => new \Zend_Db_Expr('COALESCE(store_content.title, main_table.title)'),
            'short_description' => new \Zend_Db_Expr('COALESCE(store_content.short_description, main_table.short_description)'),
            'description' => new \Zend_Db_Expr('COALESCE(store_content.description, main_table.description)'),
            'venue' => new \Zend_Db_Expr('COALESCE(store_content.venue, main_table.venue)'),
            'meta_title' => new \Zend_Db_Expr('COALESCE(store_content.meta_title, main_table.meta_title)'),
            'meta_keywords' => new \Zend_Db_Expr('COALESCE(store_content.meta_keywords, main_table.meta_keywords)'),
            'meta_description' => new \Zend_Db_Expr('COALESCE(store_content.meta_description, main_table.meta_description)'),
        ])->where('event_store.store_id IN (?)', [0, $storeId])
            ->group('main_table.event_id');

        // Apply sort order from config
        $this->applySortOrder($collection);

        // Apply per-page from config
        $perPage = $this->moduleConfig->getPerPage();
        $collection->setPageSize($perPage);
        $collection->setCurPage((int)max(1, (int)$this->getRequest()->getParam('p', 1)));
        $this->collection = $collection;

        return $this->collection;
    }

    private function applySortOrder(Collection $collection): void
    {
        $sortOrder = $this->moduleConfig->getSortOrder();

        match ($sortOrder) {
            'start_datetime_desc' => $collection->setOrder('start_datetime', 'DESC'),
            'title_asc'           => $collection->setOrder('title', 'ASC'),
            'title_desc'          => $collection->setOrder('title', 'DESC'),
            'sort_order_asc'      => $collection->setOrder('sort_order', 'ASC'),
            default               => $collection->setOrder('start_datetime', 'ASC'),
        };
    }

    public function getListingLabel(): string
    {
        $label = $this->moduleConfig->getListingLabel();
        if ($label === '') {
            $label = (string)__('Discover & Experience');
        }
        return $label;
    }

    public function getCardImageHeight(): int
    {
        return $this->moduleConfig->getCardImageHeight();
    }

    public function getDefaultVenueLabel(): string
    {
        return $this->moduleConfig->getDefaultVenueLabel();
    }

    public function isShowHeroEnabled(): bool
    {
        return $this->moduleConfig->isShowHeroEnabled();
    }

    protected function _prepareLayout()
    {
        parent::_prepareLayout();
        if ($this->getEventCollection()) {
            $perPage = $this->moduleConfig->getPerPage();
            $pager = $this->getLayout()->createBlock(
                \Magento\Theme\Block\Html\Pager::class,
                'elsnertech.event.list.pager'
            );
            $pager->setAvailableLimit([$perPage => $perPage, $perPage * 2 => $perPage * 2, $perPage * 3 => $perPage * 3]);
            $pager->setLimit($perPage);
            $pager->setCollection($this->getEventCollection());
            $this->setChild('pager', $pager);
        }
        return $this;
    }

    public function getPagerHtml(): string
    {
        return $this->getChildHtml('pager');
    }

    public function getEventUrl(string $urlKey): string
    {
        $suffix = $this->moduleConfig->isUrlSuffixEnabled() ? '.html' : '';
        return $this->getUrl('events') . ltrim($urlKey, '/') . $suffix;
    }

    public function getFormattedDate(?string $dateTime): string
    {
        if (!$dateTime) {
            return '';
        }
        return (string)$this->formatDate($dateTime, \IntlDateFormatter::MEDIUM, true);
    }

    public function getFeaturedImage(object $event): string
    {
        $images = (array)$event->getData('images');
        if (empty($images)) {
            return '';
        }
        $first = reset($images);
        if (!is_array($first)) {
            return '';
        }
        $file = (string)($first['image'] ?? $first['url'] ?? '');
        if ($file === '') {
            return '';
        }
        // If it's already a full URL, return as-is
        if (str_starts_with($file, 'http://') || str_starts_with($file, 'https://')) {
            return $file;
        }
        $baseMedia = $this->storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA);
        return $baseMedia . (str_contains($file, '/') ? '' : 'elsnertech/event/') . ltrim($file, '/');
    }

    public function getDay(string $dateTime): string
    {
        if (!$dateTime) {
            return '';
        }
        $ts = strtotime($dateTime);
        return date('d', $ts);
    }

    public function getMonth(string $dateTime): string
    {
        if (!$dateTime) {
            return '';
        }
        $ts = strtotime($dateTime);
        return date('M', $ts);
    }

    public function truncate(string $text, int $length = 120): string
    {
        if (mb_strlen($text) <= $length) {
            return $text;
        }
        return mb_substr($text, 0, $length) . '…';
    }
}
