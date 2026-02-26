# Returns

This guide explains how returns work in the [Channable module](https://www.magmodules.eu/magento2-channable.html). It covers the full return flow — from a marketplace customer requesting a return, to the return appearing in your Magento admin, to creating a credit memo. Whether you're processing returns manually or want to automate the whole thing, this page has you covered.

## How Returns Work

When a customer initiates a return on a marketplace (e.g., bol, Amazon), Channable picks it up and pushes it to your Magento store via a webhook. The return lands in the **Channable → Returns** grid in the admin, linked to the original order.

From there you can:
- Review the return details (item, reason, customer)
- Update the status (accept, reject, repair, exchange, etc.)
- Create a credit memo — manually or automatically

The status you set is sent back to Channable, which updates the marketplace accordingly.

## Setting Up Returns

**Location:** Stores → Configuration → Channable → Returns

### Enable

Turns the returns feature on or off per store view.

### Webhooks

After enabling, a webhook URL is generated for each store view. Copy this URL and paste it into your Channable account under the marketplace connection settings. Make sure you copy the full URL — it's long and may be partially hidden.

**Format:** `{base_url}/channable/returns/hook/store/{store_id}/code/{token}`

### Show Return Block on Credit Memo Page

When enabled, a return block appears on the credit memo creation page for Channable orders that have a pending return. This lets you manually select which return(s) to link when creating the credit memo.

### Automatically Match Returns

When enabled, any pending return is automatically marked as "accepted" when you create a credit memo for that order. This overrides the manual return block selection.

**When to use:** You have established processes to handle credit memos and don't need to manually review each return.

### Credit Memo Completed Returns

When enabled, a credit memo is created automatically for returns that arrive with status "complete". These are returns already handled and fulfilled by the marketplace itself — the module just mirrors that in Magento.

## Return Statuses

| Status | Meaning |
|---|---|
| New | Just imported, awaiting action |
| Accepted | Return approved, refund will be processed |
| Rejected | Return declined |
| Repaired | Item will be repaired and sent back |
| Exchanged | Item will be replaced with a new one |
| Keeps | Customer keeps the item (no return shipment) |
| Cancelled | Return process was cancelled |
| Complete | Fully processed on marketplace side |

## Returns Grid

**Location:** Channable → Returns

The grid shows all imported returns with the following information:

**Columns:**
- Store, Magento Order, Credit Memo
- Channel Return ID, Channel Order ID
- Customer Name
- Item (formatted as "Qty x Title (GTIN)")
- Reason (including customer comments)
- Order Credit Memos (count)
- Order Status
- Imported Date
- Status

### Row Actions

When a return has status "new", you can set it to any other status directly from the grid: Accept, Reject, Repair, Exchange, Keep, or Cancel.

### Mass Actions

- **Re-process** — Re-links selected returns to their Magento orders (useful if order wasn't found during initial import)
- **Create Credit Memo** — Creates a credit memo for the linked order
- **Create Credit Memo + Accept** — Creates a credit memo and sets return status to "accepted"
- **Delete** — Removes the return from Magento (does not update Channable)

## Credit Memo Integration

The module can create credit memos from returns in three ways:

### 1. Manual via Mass Action
Select returns in the grid → "Create Credit Memo" mass action. The module finds the order item by matching the GTIN from the return to a product SKU (or configured GTIN attribute).

### 2. Return Block on Credit Memo Page
When "Show Return Block on Credit Memo Page" is enabled, you'll see a block with checkboxes on the credit memo creation page. Select which returns to link, and saving the credit memo updates those returns to "accepted".

### 3. Fully Automatic
Enable both "Automatically Match Returns" and "Credit Memo Completed Returns". Returns arriving as "complete" get a credit memo created automatically. Returns in "new" status get marked "accepted" whenever you create a credit memo for their order.

## GTIN Matching

When creating a credit memo from a return, the module needs to match the returned item to an order line. It does this using the GTIN (barcode) from the return data.

The GTIN attribute is configured under: Stores → Configuration → Channable → Feed → GTIN Attribute

Options:
- **SKU** (default) — matches directly on SKU
- **EAN/barcode attribute** — matches on a custom product attribute
- **Product ID** — uses the numeric product ID

### Product ID Fallback

Some marketplaces don't include a GTIN in their return data. When the configured GTIN attribute doesn't produce a match and the value is numeric, the module automatically tries to load the product by entity ID as a fallback.

If neither the attribute match nor the ID fallback finds a product, the credit memo creation will fail and an error is logged.

## Test Returns

You can create test returns from the admin grid via the "Simulate" button. This creates a return with random product data (or a specific order if configured) without needing an actual Channable webhook call. Useful for testing your configuration before going live.

---

## Need More Help?

**Documentation:**
- [All Help Articles](https://www.magmodules.eu/help/channable/) - Complete documentation overview

**Support:**
- [Contact Support](https://www.magmodules.eu/support/) - Get help from our team
