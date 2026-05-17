<?php
declare(strict_types=1);

namespace Elsnertech\Event\Controller\Calendar;

use Elsnertech\Event\Helper\Config;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;

class Index extends Action
{
    public function __construct(
        Context $context,
        private readonly PageFactory $resultPageFactory,
        private readonly Config $moduleConfig
    ) {
        parent::__construct($context);
    }

    public function execute()
    {
        $resultPage = $this->resultPageFactory->create();
        $config = $resultPage->getConfig();

        $config->getTitle()->set($this->moduleConfig->getCalendarPageTitle());
        $config->setDescription($this->moduleConfig->getCalendarPageDescription());

        return $resultPage;
    }
}
