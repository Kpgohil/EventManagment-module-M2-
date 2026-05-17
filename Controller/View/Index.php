<?php
declare(strict_types=1);

namespace Elsnertech\Event\Controller\View;

use Elsnertech\Event\Model\ResourceModel\Event\CollectionFactory;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Exception\NotFoundException;
use Magento\Framework\Registry;
use Magento\Store\Model\StoreManagerInterface;

class Index extends Action
{
    public function __construct(
        Context $context,
        private readonly CollectionFactory $collectionFactory,
        private readonly StoreManagerInterface $storeManager,
        private readonly Registry $registry
    ) {
        parent::__construct($context);
    }

    public function execute()
    {
        $urlKey = (string)$this->getRequest()->getParam('url_key');
        if ($urlKey === '') {
            throw new NotFoundException(__('Page not found.'));
        }

        $storeId = (int)$this->storeManager->getStore()->getId();
        $collection = $this->collectionFactory->create();
        $collection->addFieldToFilter('main_table.url_key', $urlKey);
        $collection->addFieldToFilter('main_table.status', 1);
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
        ])->where('event_store.store_id IN (?)', [0, $storeId])->limit(1);

        $event = $collection->getFirstItem();
        if (!$event->getId()) {
            throw new NotFoundException(__('Page not found.'));
        }

        $this->registry->register('current_event', $event);
        return $this->resultFactory->create(ResultFactory::TYPE_PAGE);
    }
}
