# Brand & Supplier Carousel — design decisions

## Scope

- Technical name `producercarousel`, display name “Brand & Supplier Carousel”.
- Supported versions: PrestaShop 8.x and 9.x with the PHP versions they support.
- The module renders two independent carousels: manufacturers and suppliers.
- Default hook: `displayHome`.
- The module implements `PrestaShop\PrestaShop\Core\Module\WidgetInterface`, so it can be called from any hook or template.
- Example widget calls are shown on the module configuration page.

## Data and front-office behaviour

- Name, logo and URL come from PrestaShop's native manufacturer/supplier data.
- Clicking a logo opens the native manufacturer or supplier page.
- The image `alt` attribute holds the entity name.
- An entity without a logo stays in the list and takes an empty slot. The administrator can exclude it in the configuration.
- Inactive entities are never shown on the front office, even when not excluded in the configuration.
- The widget `type` parameter accepts `all`, `manufacturers` or `suppliers` and selects which carousel (or both) is rendered — this is how the two carousels are used as separate widgets, e.g. `{widget name='producercarousel' type='manufacturers'}`.
- `displayHome` without an explicit `type` uses the “What to display in the main hook” setting (`PC_DISPLAY_MODE`, see below).

## Back-office configuration

- The administrator chooses what `displayHome` renders: both carousels, manufacturers only or suppliers only (`PC_DISPLAY_MODE`, default `all`).
- Manufacturers and suppliers each have a full, separate set of carousel settings (the form has three sections: general, manufacturers, suppliers). Keys are `PC_MFR_<KEY>` / `PC_SUP_<KEY>`; definitions and allowed values live in a single `CAROUSEL_SETTINGS` constant.
- Visible items is the number of logos shown at once on a large screen. Smaller breakpoints reduce it.
- Speed is the autoplay delay between slides in milliseconds.
- Scroll mode (`MODE`): `slide` (stops at the end; autoplay rewinds via `rewind`), `loop` (infinite loop, default — keeps the 1.0 behaviour) and `marquee` (continuous linear “infinite ticker”). In `marquee` mode speed means the time one logo takes to pass, and drag/swipe is disabled.
- Arrow style (`ARROWS`): `none`, `minimal`, `circle`, `square`. Pagination style (`DOTS`): `none` (default), `dots`, `lines`, `dynamic`. Colours are set in the theme through the CSS custom properties `--producer-carousel-accent` and `--producer-carousel-accent-contrast`.
- Dimensions are entered in px: item width (`WIDTH`, 40–600, 0 = derived from visible items; a fixed width switches Swiper to `slidesPerView: 'auto'` and overrides visible items), item height (`HEIGHT`, 30–400, 0 = default) and gap (`GAP`, 0–100, default 16).
- Select fields accept only the listed values; numeric fields are range-validated.
- New configuration keys are created by `installDefaults()`, called on install and from `upgrade/upgrade-1.1.0.php`; existing values are never overwritten. Missing or invalid values fall back to defaults on the front office.
- All entities are selected by default. The database stores an exclusion list, so newly created entities appear automatically.
- Configuration respects the multistore shop context through `Configuration`.

## Translations

- Source strings are English, following PrestaShop convention. The module uses the new translation system (`isUsingNewTranslationSystem()`) with domains `Modules.Producercarousel.Admin` and `Modules.Producercarousel.Shop`.
- Polish ships twice, generated from the same word list:
  - `translations/pl.php` — the legacy module format. PrestaShop 8 and 9 fall back to it whenever the translator catalogue has no entry for a `Modules.*` string (`PrestaShopTranslatorTrait::shouldFallbackToLegacyModuleTranslation`). Keys are `<{producercarousel}prestashop>admin_<md5>` / `…>shop_<md5>`, where `admin`/`shop` is the last part of the domain.
  - `translations/pl-PL/*.xlf` — the new-system catalogue, loaded by `TranslatorLanguageLoader` for active modules. On the PrestaShop 8.2.8 test shop it was not picked up (cause not found), which is why the legacy file is the one that must stay complete.
- When adding or changing a string, update both files. Other languages can be added the same way or through International → Translations.
- A few generic labels (“Save”, “Disabled”, “The settings have been updated.”) use core domains so PrestaShop's own translations apply.

## Slider and assets

- Classic does not expose a stable multi-item carousel API intended for modules.
- Swiper 12.2.0 (MIT) is used: it includes the prototype-pollution security fix and keeps wider browser support than the 14.x line.
- Swiper files ship locally with the module, no CDN. The module does not depend on an external server and sends no visitor data to one.
- The init script is scoped to the module container and supports multiple widget instances on one page.
- `loop` and `marquee` always run, even when all logos fit on screen (the administrator chose them explicitly). Only `slide` uses `watchOverflow` and hides arrows/dots when there is nothing to scroll.
- Swiper's loop needs noticeably more slides than are visible at once. With too few logos the script duplicates the whole set; copies get `aria-hidden` and `tabindex="-1"`, so screen readers and keyboard users meet each logo once. When slides are duplicated, pagination is rendered by the module (`type: 'custom'`) — one dot per real logo; the `dynamic` style then looks like plain dots.
- With `prefers-reduced-motion: reduce`, autoplay and continuous scroll are disabled.
- Swiper 12 injects the arrow as an SVG (`.swiper-navigation-icon`), so arrow styles target the button and the SVG instead of an icon font.
