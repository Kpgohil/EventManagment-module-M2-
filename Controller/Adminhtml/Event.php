<?php
declare(strict_types=1);

namespace Elsnertech\Event\Controller\Adminhtml;

use Magento\Backend\App\Action;

abstract class Event extends Action
{
    public const ADMIN_RESOURCE = 'Elsnertech_Event::event';
}

