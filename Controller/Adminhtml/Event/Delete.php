<?php
declare(strict_types=1);

namespace Elsnertech\Event\Controller\Adminhtml\Event;

use Elsnertech\Event\Controller\Adminhtml\Event;
use Elsnertech\Event\Model\EventFactory;

class Delete extends Event
{
    public const ADMIN_RESOURCE = 'Elsnertech_Event::event_delete';

    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        private readonly EventFactory $eventFactory
    ) {
        parent::__construct($context);
    }

    public function execute()
    {
        $eventId = (int)$this->getRequest()->getParam('event_id');
        if (!$eventId) {
            return $this->resultRedirectFactory->create()->setPath('*/*/');
        }

        try {
            $model = $this->eventFactory->create()->load($eventId);
            if (!$model->getId()) {
                throw new \Magento\Framework\Exception\NoSuchEntityException(__('Event not found.'));
            }
            $model->delete();
            $this->messageManager->addSuccessMessage(__('The event has been deleted.'));
        } catch (\Throwable $exception) {
            $this->messageManager->addErrorMessage($exception->getMessage());
        }

        return $this->resultRedirectFactory->create()->setPath('*/*/');
    }
}

