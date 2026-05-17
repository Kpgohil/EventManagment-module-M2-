<?php
declare(strict_types=1);

namespace Elsnertech\Event\Block\Adminhtml\Event\Edit;

use Magento\Backend\Block\Widget\Context;
use Magento\Framework\Registry;

class GenericButton
{
    public function __construct(
        protected Context $context,
        protected Registry $registry
    ) {
    }

    public function getEventId(): ?int
    {
        $event = $this->registry->registry('elsnertech_event');
        return $event && $event->getId() ? (int)$event->getId() : null;
    }

    public function getUrl(string $route = '', array $params = []): string
    {
        return $this->context->getUrlBuilder()->getUrl($route, $params);
    }
}

