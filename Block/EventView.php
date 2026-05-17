<?php
declare(strict_types=1);

namespace Elsnertech\Event\Block;

use Elsnertech\Event\Helper\Config;
use Magento\Framework\Registry;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;

class EventView extends Template
{
    public function __construct(
        Context $context,
        private readonly Registry $registry,
        private readonly Config $moduleConfig,
        private readonly StoreManagerInterface $storeManager,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    public function getEvent(): ?\Elsnertech\Event\Model\Event
    {
        $event = $this->registry->registry('current_event');
        return $event instanceof \Elsnertech\Event\Model\Event ? $event : null;
    }

    public function getFeaturedImage(): string
    {
        $event = $this->getEvent();
        if (!$event) {
            return '';
        }
        $images = (array)$event->getData('images');
        foreach ($images as $image) {
            if (!empty($image['disabled'])) {
                continue;
            }
            $file = (string)($image['image'] ?? '');
            if ($file === '') {
                continue;
            }
            $baseMedia = $this->storeManager->getStore()->getBaseUrl(UrlInterface::URL_TYPE_MEDIA);
            return $baseMedia . (str_contains($file, '/') ? '' : 'elsnertech/event/') . ltrim($file, '/');
        }
        return '';
    }

    public function getGalleryImages(): array
    {
        $event = $this->getEvent();
        if (!$event) {
            return [];
        }

        $baseMedia = $this->storeManager->getStore()->getBaseUrl(UrlInterface::URL_TYPE_MEDIA);
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
                'url' => $baseMedia . (str_contains($file, '/') ? '' : 'elsnertech/event/') . ltrim($file, '/'),
                'label' => (string)($image['label'] ?? ''),
            ];
        }
        return $images;
    }

    public function getDay(string $dateTime): string
    {
        if (!$dateTime) { return ''; }
        return date('d', strtotime($dateTime));
    }

    public function getMonth(string $dateTime): string
    {
        if (!$dateTime) { return ''; }
        return date('M', strtotime($dateTime));
    }

    public function getYear(string $dateTime): string
    {
        if (!$dateTime) { return ''; }
        return date('Y', strtotime($dateTime));
    }

    public function getTimeRange(): string
    {
        $event = $this->getEvent();
        if (!$event) { return ''; }
        $start = $event->getData('start_datetime');
        $end = $event->getData('end_datetime');
        if (!$start) { return ''; }
        $startTime = date('g:i A', strtotime((string)$start));
        if ($end) {
            $endTime = date('g:i A', strtotime((string)$end));
            return $startTime . ' — ' . $endTime;
        }
        return $startTime;
    }

    public function getFormattedDate(?string $dateTime): string
    {
        if (!$dateTime) {
            return '';
        }
        return (string)$this->formatDate($dateTime, \IntlDateFormatter::MEDIUM, true);
    }

    public function isShowGalleryEnabled(): bool
    {
        return $this->moduleConfig->isShowGalleryEnabled();
    }

    public function getDefaultVenueLabel(): string
    {
        $label = $this->moduleConfig->getDefaultVenueLabel();
        return $label;
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

