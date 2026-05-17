<?php
declare(strict_types=1);

namespace Elsnertech\Event\Controller;

use Magento\Framework\App\ActionFactory;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\RouterInterface;

class Router implements RouterInterface
{
    public function __construct(
        private readonly ActionFactory $actionFactory
    ) {
    }

    public function match(RequestInterface $request)
    {
        $identifier = trim((string)$request->getPathInfo(), '/');
        if ($identifier === 'events') {
            return null;
        }
        if (!str_starts_with($identifier, 'events/')) {
            return null;
        }

        $urlKey = trim(substr($identifier, strlen('events/')), '/');
        if ($urlKey === '') {
            return null;
        }

        $request->setModuleName('events')
            ->setControllerName('view')
            ->setActionName('index')
            ->setParam('url_key', $urlKey);

        return $this->actionFactory->create(\Magento\Framework\App\Action\Forward::class);
    }
}

