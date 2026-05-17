<?php
declare(strict_types=1);

namespace Elsnertech\Event\Block;

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
        $now = (new \DateTimeImmutable('now', new \DateTimeZone('UTC')))->format('Y-m-d H:i:s');
        $collection = $this->collectionFactory->create();
        $collection->addFieldToFilter('main_table.status', 1);
        $collection->addFieldToFilter('main_table.start_datetime', ['gteq' => $now]);
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
        $collection->setOrder('start_datetime', 'ASC');
        $collection->setOrder('sort_order', 'ASC');
        $collection->setPageSize(12);
        $collection->setCurPage((int)max(1, (int)$this->getRequest()->getParam('p', 1)));
        $this->collection = $collection;

        return $this->collection;
    }

    protected function _prepareLayout()
    {
        parent::_prepareLayout();
        if ($this->getEventCollection()) {
            $pager = $this->getLayout()->createBlock(
                \Magento\Theme\Block\Html\Pager::class,
                'elsnertech.event.list.pager'
            )->setCollection($this->getEventCollection());
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
        return $this->getUrl('events') . ltrim($urlKey, '/');
    }

    public function getFormattedDate(?string $dateTime): string
    {
        if (!$dateTime) {
            return '';
        }
        return (string)$this->formatDate($dateTime, \IntlDateFormatter::MEDIUM, true);
    }
}
