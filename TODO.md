# Elsnertech_Event — Code Review TODO

> Generated from code review on 2026-05-17
> Module: `app/code/Elsnertech/Event`

---

## 🔴 High Priority

### H1. Service Locator in `Save` Controller

**File:** `Controller/Adminhtml/Event/Save.php` (line 76)

```php
// ❌ Current: Uses ObjectManager (anti-pattern)
$this->_objectManager->get(\Psr\Log\LoggerInterface::class)->critical($exception);
```

Inject `LoggerInterface` through the constructor instead.

**Fix:**
```php
public function __construct(
    \Magento\Backend\App\Action\Context $context,
    private readonly EventFactory $eventFactory,
    private readonly UrlKey $urlKeyHelper,
    private readonly \Psr\Log\LoggerInterface $logger   // ← ADD THIS
) {
    parent::__construct($context);
}

// In execute():
$this->logger->critical($exception);
```

---

## 🟡 Medium Priority

### M1. Missing Return Type Declarations on `execute()` Methods

Add `: \Magento\Framework\Controller\ResultInterface` (or specific type) return type hint to all `execute()` methods.

| File | Line | Expected Return |
|------|------|-----------------|
| `Controller/Adminhtml/Event/Index.php` | 11 | `\Magento\Framework\Controller\ResultInterface` |
| `Controller/Adminhtml/Event/Edit.php` | 21 | `\Magento\Framework\Controller\ResultInterface` |
| `Controller/Adminhtml/Event/Save.php` | 23 | `\Magento\Framework\Controller\ResultInterface` |
| `Controller/Adminhtml/Event/Delete.php` | 20 | `\Magento\Framework\Controller\ResultInterface` |
| `Controller/Adminhtml/Event/MassDelete.php` | 22 | `\Magento\Framework\Controller\ResultInterface` |
| `Controller/Adminhtml/Event/MassStatus.php` | 22 | `\Magento\Framework\Controller\ResultInterface` |
| `Controller/Adminhtml/Event/Upload.php` | 24 | `\Magento\Framework\Controller\ResultInterface` |
| `Controller/Index/Index.php` | 17 | `\Magento\Framework\Controller\ResultInterface` |
| `Controller/View/Index.php` | 25 | `\Magento\Framework\Controller\ResultInterface` |

### M2. `_storeManager` Accessed via Parent Property in `EventView`

**File:** `Block/EventView.php` (line 33)

```php
// ❌ Uses inherited $_storeManager from Template parent
$baseMedia = $this->_storeManager->getStore()->getBaseUrl(...);
```

`EventList.php` already injects `StoreManagerInterface` via DI — be consistent and inject it in `EventView` too.

**Fix:**
```php
public function __construct(
    Context $context,
    private readonly Registry $registry,
    private readonly StoreManagerInterface $storeManager,  // ← ADD THIS
    array $data = []
) {
    parent::__construct($context, $data);
}
```

### M3. `Zend_Db_Expr` Usage (Deprecated)

**Files:**
- `Block/EventList.php` (lines 45–51)
- `Controller/View/Index.php` (lines 45–51)

```php
// ❌ Zend_Db_Expr is deprecated in favor of Laminas
'title' => new \Zend_Db_Expr('COALESCE(store_content.title, main_table.title)'),
```

**Fix:** Replace with `\Laminas\Db\Sql\Expression`:
```php
'title' => new \Laminas\Db\Sql\Expression('COALESCE(store_content.title, main_table.title)'),
```

---

## 🔵 Low Priority

### L1. `ContentStore` Shows Inactive Stores

**File:** `Model/Source/ContentStore.php` (line 22)

```php
// Shows ALL stores including disabled/inactive
foreach ($this->storeManager->getStores() as $store) {
```

**Fix:** Filter to active stores only:
```php
foreach ($this->storeManager->getStores(true) as $store) {
```

### L2. `saveStores()` Empty Array Fallback — Document Behaviour

**File:** `Model/ResourceModel/Event.php` (line 132)

```php
$storeIds = $object->getData('store_ids') ?: [0];
```

An empty array `[]` silently falls back to `[0]` (All Store Views). This is intentional but unobvious. Consider adding a comment explaining the fallback logic.

### L3. Image Upload: Unexplained `tmp_name` Guard

**File:** `Model/ResourceModel/Event.php` (line 164)

```php
if (!empty($image['tmp_name']) && empty($image['name'])) {
    continue;
}
```

This edge-case guard skips images that have a temp file but no filename. The intent is not self-documenting — add a comment explaining when this scenario occurs.

### L4. `DeleteButton` JS String Concatenation

**File:** `Block/Adminhtml/Event/Edit/DeleteButton.php` (lines 17–19)

```php
'on_click' => 'deleteConfirm(\'' . __(...) . '\', \'' . $this->getDeleteUrl() . '\')',
```

String concatenation for JavaScript is fragile if translation strings contain quotes. Consider using `json_encode()` for the arguments.

**Fix:**
```php
'on_click' => sprintf(
    "deleteConfirm(%s, '%s')",
    json_encode(__('Are you sure you want to do this?')),
    $this->getDeleteUrl()
),
```

---

## ✅ Done Well (Keep)

| Pattern | Location |
|---------|----------|
| Constructor property promotion + `readonly` | Most classes |
| Fine-grained ACL per admin action | Save, Delete, MassDelete, MassStatus |
| `declare(strict_types=1)` everywhere | All PHP files |
| Custom router for clean `/events/slug` URLs | `Controller/Router.php` |
| Store-scoped content via DB-level COALESCE | `EventList`, `View/Index` |
| UI Filter for mass actions | `MassDelete`, `MassStatus` |
| File upload with extension whitelist | `Upload.php` |
| Foreign keys with CASCADE delete in DB schema | `db_schema.xml` |
| Compound index on `(status, start_datetime)` | `db_schema.xml` |
| Unique constraint + app-level validation on `url_key` | `db_schema.xml` + `_beforeSave` |
| Pagination on frontend list | `EventList::_prepareLayout` |
| Graceful fallback for empty URL keys | `Helper/UrlKey.php` |

---

*Priority classification: 🔴 High = bug/anti-pattern risk, 🟡 Medium = standards/maintainability, 🔵 Low = polish*
