<?php
declare(strict_types=1);

namespace Elsnertech\Event\Controller\Adminhtml\Event;

use Elsnertech\Event\Controller\Adminhtml\Event;
use Elsnertech\Event\Helper\UrlKey;
use Elsnertech\Event\Model\EventFactory;
use Magento\Framework\Exception\LocalizedException;
use Psr\Log\LoggerInterface;

class Save extends Event
{
    public const ADMIN_RESOURCE = 'Elsnertech_Event::event_save';

    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        private readonly EventFactory $eventFactory,
        private readonly UrlKey $urlKeyHelper,
        private readonly LoggerInterface $logger
    ) {
        parent::__construct($context);
    }

    public function execute()
    {
        $data = $this->getRequest()->getPostValue();
        if (!$data) {
            return $this->resultRedirectFactory->create()->setPath('*/*/');
        }

        $eventId = (int)($data['event_id'] ?? 0);
        $model = $this->eventFactory->create();
        if ($eventId) {
            $model->load($eventId);
            if (!$model->getId()) {
                $this->messageManager->addErrorMessage(__('This event no longer exists.'));
                return $this->resultRedirectFactory->create()->setPath('*/*/');
            }
        } else {
            unset($data['event_id']);
            $model->setId(null);
            $model->isObjectNew(true);
        }

        $urlKey = trim((string)($data['url_key'] ?? ''));
        if ($urlKey === '') {
            $data['url_key'] = $this->urlKeyHelper->generate((string)($data['title'] ?? ''));
        } else {
            $data['url_key'] = $this->urlKeyHelper->generate($urlKey);
        }

        $this->normalizeDateFields($data);
        $this->normalizeStoreIds($data);
        $this->normalizeImages($data);
        $data['content_store_id'] = (int)($data['content_store_id'] ?? 0);

        if (!empty($data['end_datetime']) && !empty($data['start_datetime'])
            && strtotime((string)$data['end_datetime']) < strtotime((string)$data['start_datetime'])) {
            $this->messageManager->addErrorMessage(__('End Date must be greater than or equal to Start Date.'));
            $this->_getSession()->setData('elsnertech_event_form_data', $data);
            return $this->resultRedirectFactory->create()->setPath('*/*/edit', ['event_id' => $eventId]);
        }

        try {
            $model->addData($data);
            $model->save();
            $this->messageManager->addSuccessMessage(__('You saved the event.'));
            $this->_getSession()->setData('elsnertech_event_form_data', null);

            if ($this->getRequest()->getParam('back')) {
                return $this->resultRedirectFactory->create()->setPath('*/*/edit', ['event_id' => $model->getId()]);
            }
            return $this->resultRedirectFactory->create()->setPath('*/*/');
        } catch (LocalizedException $exception) {
            $this->messageManager->addErrorMessage($exception->getMessage());
        } catch (\Throwable $exception) {
            $this->logger->critical($exception);
            $this->messageManager->addErrorMessage(__('Something went wrong while saving the event.'));
        }

        $this->_getSession()->setData('elsnertech_event_form_data', $data);
        return $this->resultRedirectFactory->create()->setPath('*/*/edit', ['event_id' => $eventId]);
    }

    private function normalizeDateFields(array &$data): void
    {
        foreach (['start_datetime', 'end_datetime'] as $field) {
            if (!isset($data[$field]) || $data[$field] === '') {
                $data[$field] = null;
                continue;
            }

            if (is_array($data[$field])) {
                $date = trim((string)($data[$field]['date'] ?? ''));
                $time = trim((string)($data[$field]['time'] ?? ''));
                $value = trim($date . ' ' . $time);
            } else {
                $value = trim((string)$data[$field]);
            }

            if ($value === '') {
                $data[$field] = null;
                continue;
            }

            $timestamp = strtotime($value);
            if ($timestamp === false) {
                throw new LocalizedException(__('%1 has an invalid date/time value.', $field));
            }

            $data[$field] = date('Y-m-d H:i:s', $timestamp);
        }
    }

    private function normalizeStoreIds(array &$data): void
    {
        $storeIds = $data['store_ids'] ?? [0];
        $data['store_ids'] = array_map('intval', (array)$storeIds);
        if (in_array(0, $data['store_ids'], true)) {
            $data['store_ids'] = [0];
        }
    }

    private function normalizeImages(array &$data): void
    {
        $images = $data['images'] ?? [];
        $data['images'] = [];
        foreach ((array)$images as $image) {
            if (!is_array($image)) {
                continue;
            }
            if (!empty($image['delete'])) {
                continue;
            }
            $path = (string)($image['file'] ?? $image['name'] ?? '');
            if ($path !== '') {
                $data['images'][] = [
                    'name' => $path,
                    'label' => (string)($image['label'] ?? ''),
                    'position' => (int)($image['position'] ?? 0),
                    'disabled' => (int)($image['disabled'] ?? 0),
                ];
            }
        }
    }
}
