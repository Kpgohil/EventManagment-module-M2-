<?php
declare(strict_types=1);

namespace Elsnertech\Event\Block;

use Elsnertech\Event\Helper\Config;
use Elsnertech\Event\Model\ResourceModel\Event\Collection;
use Elsnertech\Event\Model\ResourceModel\Event\CollectionFactory;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Store\Model\StoreManagerInterface;

class Calendar extends Template
{
    private ?array $calendarData = null;

    /** @var array<int, array<int, Collection>> */
    private ?array $eventsByDay = null;

    public function __construct(
        Context $context,
        private readonly CollectionFactory $collectionFactory,
        private readonly StoreManagerInterface $storeManager,
        private readonly Config $moduleConfig,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    public function getCurrentYear(): int
    {
        return (int)max(2020, $this->getRequest()->getParam('year', (int)date('Y')));
    }

    public function getCurrentMonth(): int
    {
        $month = (int)$this->getRequest()->getParam('month', (int)date('n'));
        return max(1, min(12, $month));
    }

    public function getMonthName(int $month): string
    {
        return date('F', mktime(0, 0, 0, $month, 1));
    }

    public function getCalendarTitle(): string
    {
        $title = $this->moduleConfig->getCalendarPageTitle();
        if ($title === '') {
            $title = (string)__('Event Calendar');
        }
        return $title;
    }

    public function getCalendarDescription(): string
    {
        $desc = $this->moduleConfig->getCalendarPageDescription();
        if ($desc === '') {
            $desc = (string)__('Browse our events by date on the interactive calendar.');
        }
        return $desc;
    }

    public function getFirstDayOfWeek(): int
    {
        return (int)date('w', mktime(0, 0, 0, $this->getCurrentMonth(), 1, $this->getCurrentYear()));
    }

    public function getTotalDays(): int
    {
        return (int)date('t', mktime(0, 0, 0, $this->getCurrentMonth(), 1, $this->getCurrentYear()));
    }

    /**
     * Get events for the current month, grouped by day number.
     *
     * @return array<int, Collection> keyed by day number (1-31)
     */
    public function getEventsByDay(): array
    {
        if ($this->eventsByDay !== null) {
            return $this->eventsByDay;
        }

        $year = $this->getCurrentYear();
        $month = $this->getCurrentMonth();
        $storeId = (int)$this->storeManager->getStore()->getId();

        $monthStart = sprintf('%04d-%02d-01 00:00:00', $year, $month);
        $totalDays = $this->getTotalDays();
        $monthEnd = sprintf('%04d-%02d-%02d 23:59:59', $year, $month, $totalDays);

        $collection = $this->collectionFactory->create();
        $collection->addFieldToFilter('main_table.status', 1);

        // Filter by date range
        $collection->addFieldToFilter('main_table.start_datetime', [
            'from' => $monthStart,
            'to' => $monthEnd,
            'datetime' => true,
        ]);

        // Show past events toggle
        if (!$this->moduleConfig->isShowPastEnabled()) {
            $now = (new \DateTimeImmutable('now', new \DateTimeZone('UTC')))->format('Y-m-d H:i:s');
            // Only include events that start within this month AND are not past
            // Actually, if show_past is off, we only show events from today onwards within this month
            $collection->addFieldToFilter('main_table.start_datetime', ['gteq' => $now]);
        }

        $collection->getSelect()->join(
            ['event_store' => $collection->getTable('elsnertech_event_store')],
            'main_table.event_id = event_store.event_id',
            []
        )->joinLeft(
            ['store_content' => $collection->getTable('elsnertech_event_store_content')],
            'main_table.event_id = store_content.event_id AND store_content.store_id = ' . $storeId,
            []
        )->columns([
            'title' => new \Zend_Db_Expr('COALESCE(store_content.title, main_table.title)'),
            'short_description' => new \Zend_Db_Expr('COALESCE(store_content.short_description, main_table.short_description)'),
            'venue' => new \Zend_Db_Expr('COALESCE(store_content.venue, main_table.venue)'),
        ])->where('event_store.store_id IN (?)', [0, $storeId])
            ->group('main_table.event_id')
            ->order('main_table.start_datetime ASC');

        $this->eventsByDay = [];

        foreach ($collection as $event) {
            $startDate = (string)$event->getData('start_datetime');
            $day = (int)date('j', strtotime($startDate));
            if (!isset($this->eventsByDay[$day])) {
                $this->eventsByDay[$day] = [];
            }
            $this->eventsByDay[$day][] = $event;
        }

        return $this->eventsByDay;
    }

    /**
     * Get number of events on a specific day.
     */
    public function hasEvents(int $day): bool
    {
        $events = $this->getEventsByDay();
        return isset($events[$day]) && count($events[$day]) > 0;
    }

    /**
     * Get events for a specific day.
     */
    public function getDayEvents(int $day): array
    {
        return $this->getEventsByDay()[$day] ?? [];
    }

    /**
     * Get the number of rows needed for this month (6 max, 5 typical).
     */
    public function getWeeksInMonth(): int
    {
        $firstDay = $this->getFirstDayOfWeek();
        $totalDays = $this->getTotalDays();
        return (int)ceil(($firstDay + $totalDays) / 7);
    }

    public function getPrevMonthParams(): array
    {
        $month = $this->getCurrentMonth();
        $year = $this->getCurrentYear();

        if ($month === 1) {
            return ['month' => 12, 'year' => $year - 1];
        }
        return ['month' => $month - 1, 'year' => $year];
    }

    public function getNextMonthParams(): array
    {
        $month = $this->getCurrentMonth();
        $year = $this->getCurrentYear();

        if ($month === 12) {
            return ['month' => 1, 'year' => $year + 1];
        }
        return ['month' => $month + 1, 'year' => $year];
    }

    public function getEventUrl(object $event): string
    {
        $suffix = $this->moduleConfig->isUrlSuffixEnabled() ? '.html' : '';
        return $this->getUrl('events') . ltrim((string)$event->getData('url_key'), '/') . $suffix;
    }

    public function getFeaturedImage(object $event): string
    {
        $images = (array)$event->getData('images');
        if (empty($images)) {
            return '';
        }
        $first = reset($images);
        if (!is_array($first)) {
            return '';
        }
        $file = (string)($first['image'] ?? $first['url'] ?? '');
        if ($file === '') {
            return '';
        }
        if (str_starts_with($file, 'http://') || str_starts_with($file, 'https://')) {
            return $file;
        }
        $baseMedia = $this->storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA);
        return $baseMedia . (str_contains($file, '/') ? '' : 'elsnertech/event/') . ltrim($file, '/');
    }

    public function getFormattedDate(?string $dateTime): string
    {
        if (!$dateTime) {
            return '';
        }
        $ts = strtotime($dateTime);
        return date('g:i A', $ts);
    }

    /**
     * Check if current month is the actual current month.
     */
    public function isCurrentMonth(): bool
    {
        return $this->getCurrentMonth() === (int)date('n')
            && $this->getCurrentYear() === (int)date('Y');
    }

    /**
     * Check if a given day is today.
     */
    public function isToday(int $day): bool
    {
        return $this->isCurrentMonth() && $day === (int)date('j');
    }

    public function getDefaultVenueLabel(): string
    {
        return $this->moduleConfig->getDefaultVenueLabel();
    }

    /**
     * Get total event count for the displayed month.
     */
    public function getTotalEventCount(): int
    {
        $count = 0;
        foreach ($this->getEventsByDay() as $dayEvents) {
            $count += count($dayEvents);
        }
        return $count;
    }
}
