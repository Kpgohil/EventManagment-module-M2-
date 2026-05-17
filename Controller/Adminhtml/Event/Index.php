<?php
declare(strict_types=1);

namespace Elsnertech\Event\Controller\Adminhtml\Event;

use Elsnertech\Event\Controller\Adminhtml\Event;
use Magento\Framework\Controller\ResultFactory;

class Index extends Event
{
    public function execute()
    {
        $resultPage = $this->resultFactory->create(ResultFactory::TYPE_PAGE);
        $resultPage->setActiveMenu('Elsnertech_Event::event');
        $resultPage->getConfig()->getTitle()->prepend(__('Events'));
        return $resultPage;
    }
}

