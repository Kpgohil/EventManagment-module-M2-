<?php
declare(strict_types=1);

namespace Elsnertech\Event\Controller\Adminhtml\Event;

use Elsnertech\Event\Controller\Adminhtml\Event;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Controller\ResultFactory;
use Magento\MediaStorage\Model\File\UploaderFactory;
use Magento\Framework\Filesystem;

class Upload extends Event
{
    public const ADMIN_RESOURCE = 'Elsnertech_Event::event_save';

    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        private readonly UploaderFactory $uploaderFactory,
        private readonly Filesystem $filesystem
    ) {
        parent::__construct($context);
    }

    public function execute()
    {
        $result = $this->resultFactory->create(ResultFactory::TYPE_JSON);
        try {
            $uploader = $this->uploaderFactory->create(['fileId' => 'images']);
            $uploader->setAllowedExtensions(['jpg', 'jpeg', 'gif', 'png', 'webp']);
            $uploader->setAllowRenameFiles(true);
            $uploader->setFilesDispersion(false);

            $media = $this->filesystem->getDirectoryWrite(DirectoryList::MEDIA);
            $target = 'elsnertech/event';
            $media->create($target);
            $saveResult = $uploader->save($media->getAbsolutePath($target));
            $saveResult['url'] = $this->_url->getBaseUrl(['_type' => \Magento\Framework\UrlInterface::URL_TYPE_MEDIA])
                . 'elsnertech/event/' . ltrim((string)($saveResult['file'] ?? ''), '/');
            $result->setData($saveResult);
        } catch (\Throwable $exception) {
            $result->setData([
                'error' => $exception->getMessage(),
                'errorcode' => $exception->getCode()
            ]);
        }

        return $result;
    }
}
