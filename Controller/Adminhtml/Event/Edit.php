<?php
declare(strict_types=1);

namespace Elsnertech\Event\Controller\Adminhtml\Event;

use Elsnertech\Event\Controller\Adminhtml\Event;
use Elsnertech\Event\Model\EventFactory;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Registry;

class Edit extends Event
{
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        private readonly EventFactory $eventFactory,
        private readonly Registry $coreRegistry
    ) {
        parent::__construct($context);
    }

    public function execute()
    {
        $eventId = (int)$this->getRequest()->getParam('event_id');
        $model = $this->eventFactory->create();

        if ($eventId) {
            $model->load($eventId);
            if (!$model->getId()) {
                $this->messageManager->addErrorMessage(__('This event no longer exists.'));
                return $this->resultRedirectFactory->create()->setPath('*/*/');
            }

            $contentStoreId = (int)$this->getRequest()->getParam('content_store_id', 0);
            if ($contentStoreId > 0) {
                $model->getResource()->applyStoreContent($model, $contentStoreId);
            }
            $model->setData('content_store_id', $contentStoreId);
        }

        $data = $this->_getSession()->getData('elsnertech_event_form_data', true);
        if (is_array($data) && !empty($data)) {
            $model->addData($data);
        }

        $this->coreRegistry->register('elsnertech_event', $model);

        $resultPage = $this->resultFactory->create(ResultFactory::TYPE_PAGE);
        $resultPage->setActiveMenu('Elsnertech_Event::event');
        $resultPage->getConfig()->getTitle()->prepend(
            $model->getId() ? __('Edit Event') : __('New Event')
        );
        return $resultPage;
    }
}
