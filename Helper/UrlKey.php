<?php
declare(strict_types=1);

namespace Elsnertech\Event\Helper;

use Magento\Framework\Filter\TranslitUrl;

class UrlKey
{
    public function __construct(
        private readonly TranslitUrl $translitUrl
    ) {
    }

    public function generate(string $value): string
    {
        $urlKey = trim($this->translitUrl->filter($value));
        if ($urlKey === '') {
            return 'event';
        }
        return strtolower($urlKey);
    }
}

