<?php
declare(strict_types=1);

namespace Elsnertech\Event\Block;

use Magento\Framework\Registry;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;

class EventView extends Template
{
    public function __construct(
        Context $context,
        private readonly Registry $registry,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    public function getEvent(): ?\Elsnertech\Event\Model\Event
    {
        $event = $this->registry->registry('current_event');
        return $event instanceof \Elsnertech\Event\Model\Event ? $event : null;
    }

    public function getGalleryImages(): array
    {
        $event = $this->getEvent();
        if (!$event) {
            return [];
        }

        $baseMedia = $this->_storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA);
        $images = [];
        foreach ((array)$event->getData('images') as $image) {
            if (!empty($image['disabled'])) {
                continue;
            }
            $file = (string)($image['image'] ?? '');
            if ($file === '') {
                continue;
            }
            $images[] = [
                'url' => $baseMedia . ltrim($file, '/'),
                'label' => (string)($image['label'] ?? ''),
            ];
        }
        return $images;
    }

    public function getFormattedDate(?string $dateTime): string
    {
        if (!$dateTime) {
            return '';
        }
        return (string)$this->formatDate($dateTime, \IntlDateFormatter::MEDIUM, true);
    }

    protected function _prepareLayout()
    {
        $event = $this->getEvent();
        if ($event) {
            $metaTitle = (string)($event->getData('meta_title') ?: $event->getData('title'));
            $metaDescription = (string)($event->getData('meta_description') ?: $event->getData('short_description'));
            $metaKeywords = (string)$event->getData('meta_keywords');
            $pageConfig = $this->pageConfig;
            $pageConfig->getTitle()->set($metaTitle ?: (string)$event->getData('title'));
            if ($metaDescription !== '') {
                $pageConfig->setDescription($metaDescription);
            }
            if ($metaKeywords !== '') {
                $pageConfig->setKeywords($metaKeywords);
            }
        }
        return parent::_prepareLayout();
    }
}

