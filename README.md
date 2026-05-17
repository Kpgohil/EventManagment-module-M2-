# Elsnertech Event Module

A premium Magento 2 event management module with admin CRUD, frontend listing/detail pages, monthly calendar view, and configurable options.

## Features

- **Admin CRUD** — Create, edit, delete, and manage events from the admin panel
- **Frontend Listing** — `/events/` with card grid, pagination, sort order, hero banner
- **Event Detail** — `/events/{url-key}` with full description, gallery, sidebar details
- **Calendar View** — `/events/calendar/` with monthly grid, day cells, event pills, month navigation
- **Store-Scoped Content** — Per-store-view titles, descriptions, venue, and meta fields
- **Image Gallery** — Upload multiple images per event via admin form
- **SEO** — Per-event meta title, keywords, description; configurable URL suffix (`.html`)
- **Configurable** — Per-page limit, sort order, hero/gallery toggle, card height, venue fallback, calendar page SEO

## Requirements

- Magento 2.4.x (tested on 2.4.8-p4)
- PHP 8.1+

## Installation

### Via Composer (recommended)

```bash
composer require elsnertech/event
php bin/magento setup:upgrade
php bin/magento setup:di:compile
php bin/magento setup:static-content:deploy -f
php bin/magento cache:flush
```

### Manual

1. Copy the module to `app/code/Elsnertech/Event/`
2. Run:
   ```bash
   php bin/magento setup:upgrade
   php bin/magento setup:di:compile
   php bin/magento setup:static-content:deploy -f
   php bin/magento cache:flush
   ```

## Configuration

Navigate to **Stores → Configuration → Elsnertech → Event Management** in the admin panel.

### General
| Setting | Description | Default |
|---------|-------------|---------|
| Events Per Page | Number of events on listing | `12` |
| Default Sort Order | Sort order for listing | Start Date ASC |
| Show Past Events | Include past events | No |
| Upcoming Events Label | Hero heading text | `Discover & Experience` |

### Display
| Setting | Description | Default |
|---------|-------------|---------|
| Show Hero Banner | Toggle listing hero | Yes |
| Show Gallery | Toggle detail gallery | Yes |
| Card Image Height | Card image height (px) | `220` |
| Default Venue Label | Fallback venue text | — |

### SEO & URLs
| Setting | Description | Default |
|---------|-------------|---------|
| URL Suffix | `.html` on detail URLs | No |
| Calendar Page Title | Meta title for calendar | `Event Calendar` |
| Calendar Page Meta Description | Meta description for calendar | Browse our events by date... |

## Routes

| URL | Page |
|-----|------|
| `/events/` | Listing page |
| `/events/{url-key}.html` | Event detail (suffix configurable) |
| `/events/calendar/` | Monthly calendar view |

## Frontend

The frontend uses the **Inter** font with a premium dark gradient hero, card grid layout, and responsive design. Styles are compiled via `_module.less` at `view/frontend/web/css/source/_module.less`.

### Responsive Breakpoints
- **< 640px**: Mobile list view for calendar, single column cards
- **641–768px**: Tablet card grid
- **769–1024px**: 2-col card grid, narrower sidebar
- **> 1024px**: Full desktop layout

## Admin

Manage events under **Elsnertech → Manage Events** in the admin sidebar.

- Grid with filters for title, status, store view
- Form with fields for title, URL key, status, store views, schedule, venue, description (WYSIWYG), images, SEO
- Mass actions: delete, status change
- Image upload with extension whitelist (jpg, jpeg, gif, png, webp)

## Development

### Compile Styles

```bash
php bin/magento setup:static-content:deploy -f
```

### Clear Cache

```bash
php bin/magento cache:flush
```

## License

Proprietary — All rights reserved.
