<?php
declare(strict_types=1);

namespace Elsnertech\Event\Controller;

use Elsnertech\Event\Helper\Config;
use Magento\Framework\App\ActionFactory;
use Magento\Framework\App\Action\Forward;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\RouterInterface;

class Router implements RouterInterface
{
    public function __construct(
        private readonly ActionFactory $actionFactory,
        private readonly Config $moduleConfig
    ) {
    }

    public function match(RequestInterface $request)
    {
        $identifier = trim((string)$request->getPathInfo(), '/');

        // Listing page — always works without .html
        if ($identifier === 'events') {
            return null;
        }

        // Only match URLs starting with events/
        if (!str_starts_with($identifier, 'events/')) {
            return null;
        }

        $urlKey = trim(substr($identifier, strlen('events/')), '/');
        if ($urlKey === '') {
            return null;
        }

        // Calendar route — let standard Magento routing handle it
        if ($urlKey === 'calendar' || $urlKey === 'calendar.html') {
            return null;
        }

        $hasHtmlSuffix = str_ends_with($urlKey, '.html');

        // Enforce URL format based on config — return null to let others handle (→ 404)
        $suffixEnabled = $this->moduleConfig->isUrlSuffixEnabled();
        if (($suffixEnabled && !$hasHtmlSuffix) || (!$suffixEnabled && $hasHtmlSuffix)) {
            return null;
        }

        // Strip .html for the controller
        if ($hasHtmlSuffix) {
            $urlKey = substr($urlKey, 0, -5);
        }

        $request->setModuleName('events')
            ->setControllerName('view')
            ->setActionName('index')
            ->setParam('url_key', $urlKey);

        return $this->actionFactory->create(Forward::class);
    }
}

