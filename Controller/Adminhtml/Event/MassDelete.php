<?php
declare(strict_types=1);

namespace Elsnertech\Event\Controller\Adminhtml\Event;

use Elsnertech\Event\Controller\Adminhtml\Event;
use Elsnertech\Event\Model\ResourceModel\Event\CollectionFactory;
use Magento\Ui\Component\MassAction\Filter;

class MassDelete extends Event
{
    public const ADMIN_RESOURCE = 'Elsnertech_Event::event_delete';

    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        private readonly Filter $filter,
        private readonly CollectionFactory $collectionFactory
    ) {
        parent::__construct($context);
    }

    public function execute(): \Magento\Framework\Controller\ResultInterface
    {
        $collection = $this->filter->getCollection($this->collectionFactory->create());
        $count = 0;
        foreach ($collection as $item) {
            $item->delete();
            $count++;
        }
        $this->messageManager->addSuccessMessage(__('%1 event(s) have been deleted.', $count));
        return $this->resultRedirectFactory->create()->setPath('*/*/');
    }
}

