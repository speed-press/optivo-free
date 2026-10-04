# Optivo – Product Options for WooCommerce

A WooCommerce plugin for extra product fields and optional prices.

## Install

1. Download `optivo.zip`
2. WordPress → Plugins → Add New → Upload Plugin
3. Activate (WooCommerce must already be active)

## Configure

**Per product**
Products → Edit → **Product Options** tab → Add option group → Add fields.

**Store-wide**
The plugin menu → **Product Options** → Add Group → set assignment rules (all products, IDs, categories, tags, types) and exclusions.

**Display**
The plugin menu → Settings: cart/checkout/email visibility, live price, summary panel, upload defaults.

## Field types

Text, textarea, number, email, dropdown, radio, checkbox, checkbox group, multi-select, image radio, image checkbox, color picker, color swatches, file upload, date, time, quantity, customer price, heading, HTML, hidden.

## Pricing types

- Fixed
- Percentage of product price
- Quantity × price (optional tiers on the field)
- Character × price (optional “first N characters = base, each extra = rate”)
- Length / area / weight × price
- Negative / discount
- Per-option prices on choice fields
- Role-price overrides (stored on the field)

## Conditional logic

On any field → Settings → Enable conditions.

- Show or hide the field
- Match ALL rules (AND) or ANY rule (OR)
- Compare against another field’s **ID** (the hidden `fld_…` value) and an option ID or label

Example: wrapping paper field shows when Gift Wrap field value is the “Yes” option ID.

## Frontend extras

- Live price next to the product price (AJAX, server-validated again at add-to-cart)
- Selection summary above Add to cart
- Image cards and color swatches
- Drag-and-drop file preview (filenames)
- Works with variable products — add-ons sit on top of Size/Color variations

## Developer

| Item | Value |
| --- | --- |
| Product meta | `_sppa_addon_groups` |
| Exclude globals | `_sppa_exclude_global_addons` |
| Global CPT | `sppa_addon_group` |
| REST | `GET/POST /wp-json/sppa/v1/products/{id}` |
| REST | `GET /wp-json/sppa/v1/groups` |
| Shortcode | `[sppa_product_addons product_id=""]` |
| Gutenberg | `sppa/product-addons` |
| Elementor | Product Options widget |
| Filters | `sppa_field_types`, `sppa_resolved_groups` |

Prices are always recalculated in `woocommerce_before_calculate_totals`. Never trust the browser amount.

## Roadmap leftovers (hooks are ready)

Quantity-tier editor UI in admin (data model already supports `price_tiers`), richer role-price UI, stock decrement on order, time-slot lists, cart/checkout block inner field editing (options are still captured on the product page).
