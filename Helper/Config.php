<?php
declare(strict_types=1);

namespace Elsnertech\Event\Helper;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

class Config
{
    // General
    private const XML_PATH_PER_PAGE       = 'elsnertech_event/general/per_page';
    private const XML_PATH_SORT_ORDER     = 'elsnertech_event/general/sort_order';
    private const XML_PATH_SHOW_PAST      = 'elsnertech_event/general/show_past';
    private const XML_PATH_LISTING_LABEL  = 'elsnertech_event/general/listing_label';

    // Display
    private const XML_PATH_SHOW_HERO     = 'elsnertech_event/display/show_hero';
    private const XML_PATH_SHOW_GALLERY  = 'elsnertech_event/display/show_gallery';
    private const XML_PATH_CARD_HEIGHT   = 'elsnertech_event/display/card_image_height';
    private const XML_PATH_VENUE_LABEL   = 'elsnertech_event/display/default_venue_label';

    // SEO
    private const XML_PATH_URL_SUFFIX    = 'elsnertech_event/seo/url_suffix';
    private const XML_PATH_CAL_TITLE     = 'elsnertech_event/seo/calendar_page_title';
    private const XML_PATH_CAL_DESC      = 'elsnertech_event/seo/calendar_page_description';

    // Notifications
    private const XML_PATH_ADMIN_NOTIFY     = 'elsnertech_event/notifications/admin_notify';
    private const XML_PATH_NOTIFY_EMAIL     = 'elsnertech_event/notifications/notification_email';

    public function __construct(
        private readonly ScopeConfigInterface $scopeConfig
    ) {
    }

    // ─── General ───────────────────────────────────────────

    public function getPerPage(?int $storeId = null): int
    {
        return (int)$this->scopeConfig->getValue(
            self::XML_PATH_PER_PAGE
        ) ?: 12;
    }

    public function getSortOrder(?int $storeId = null): string
    {
        return (string)$this->scopeConfig->getValue(
            self::XML_PATH_SORT_ORDER
        ) ?: 'start_datetime_asc';
    }

    public function isShowPastEnabled(?int $storeId = null): bool
    {
        return (bool)$this->scopeConfig->isSetFlag(
            self::XML_PATH_SHOW_PAST
        );
    }

    public function getListingLabel(?int $storeId = null): string
    {
        return (string)$this->scopeConfig->getValue(
            self::XML_PATH_LISTING_LABEL
        );
    }

    // ─── Display ───────────────────────────────────────────

    public function isShowHeroEnabled(?int $storeId = null): bool
    {
        return (bool)$this->scopeConfig->isSetFlag(
            self::XML_PATH_SHOW_HERO
        );
    }

    public function isShowGalleryEnabled(?int $storeId = null): bool
    {
        return (bool)$this->scopeConfig->isSetFlag(
            self::XML_PATH_SHOW_GALLERY
        );
    }

    public function getCardImageHeight(?int $storeId = null): int
    {
        return (int)$this->scopeConfig->getValue(
            self::XML_PATH_CARD_HEIGHT
        ) ?: 220;
    }

    public function getDefaultVenueLabel(?int $storeId = null): string
    {
        return (string)$this->scopeConfig->getValue(
            self::XML_PATH_VENUE_LABEL
        );
    }

    // ─── SEO ───────────────────────────────────────────────

    public function isUrlSuffixEnabled(?int $storeId = null): bool
    {
        return (bool)$this->scopeConfig->isSetFlag(
            self::XML_PATH_URL_SUFFIX
        );
    }

    public function getCalendarPageTitle(?int $storeId = null): string
    {
        return (string)$this->scopeConfig->getValue(
            self::XML_PATH_CAL_TITLE
        ) ?: 'Event Calendar';
    }

    public function getCalendarPageDescription(?int $storeId = null): string
    {
        return (string)$this->scopeConfig->getValue(
            self::XML_PATH_CAL_DESC
        ) ?: 'Browse our events by date on the interactive calendar.';
    }

    // ─── Notifications ─────────────────────────────────────

    public function isAdminNotifyEnabled(): bool
    {
        return (bool)$this->scopeConfig->isSetFlag(
            self::XML_PATH_ADMIN_NOTIFY,
            ScopeInterface::SCOPE_WEBSITE
        );
    }

    public function getNotificationEmail(): string
    {
        return (string)$this->scopeConfig->getValue(
            self::XML_PATH_NOTIFY_EMAIL,
            ScopeInterface::SCOPE_WEBSITE
        );
    }
}
