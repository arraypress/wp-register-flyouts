# Component Reference Summary

## Display Components (read-only)

| Type          | Description                            | Data Fields                                                                                                                                                                 |
|---------------|----------------------------------------|-----------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| `header`      | Entity header with image, badges, meta | `title`, `subtitle`, `image`, `icon`, `badges`, `meta`, `description`, `attachment_id`, `editable`, `image_size`, `image_shape`, `fallback_image`, `fallback_attachment_id` |
| `alert`       | Alert messages with styles             | `type`, `message`, `title`                                                                                                                                                  |
| `empty_state` | Empty state with icon and action       | `icon`, `title`, `description`, `action_text`                                                                                                                               |
| `articles`    | Article card list                      | `items`                                                                                                                                                                     |
| `timeline`    | Chronological event list               | `items`                                                                                                                                                                     |
| `stats`       | Metric cards with trends               | `items`                                                                                                                                                                     |

## Data Components (read-only, structured data)

| Type             | Description           | Data Fields                                                                                  |
|------------------|-----------------------|----------------------------------------------------------------------------------------------|
| `data_table`     | Tabular data display  | `columns`, `data`                                                                            |
| `info_grid`      | Label/value grid      | `items`                                                                                      |
| `payment_method` | Payment card display  | `payment_method`, `payment_brand`, `payment_last4`, `stripe_risk_score`, `stripe_risk_level` |
| `price_summary`  | Price breakdown table | `items`, `subtotal`, `tax`, `discount`, `total`, `currency`                                  |

## Interactive Components (user interaction, submit data)

| Type             | Description                      | Data Fields                                  |
|------------------|----------------------------------|----------------------------------------------|
| `gallery`        | Image gallery with media library | `items` (array of attachment IDs)            |
| `files`          | File manager with media library  | `items` (array of file objects)              |
| `line_items`     | Order line items with search     | `items` (array of item objects)              |
| `notes`          | Threaded notes with AJAX         | `items` (array of note objects)              |
| `feature_list`   | Text item list                   | `items` (array of strings)                   |
| `key_value_list` | Key-value pair manager           | `items` (array of `{key, value}`)            |
| `refund_form`    | Inline refund panel              | `amount_paid`, `amount_refunded`, `currency` |
| `action_buttons` | AJAX action buttons              | `buttons`                                    |
| `action_menu`    | Dropdown action menu             | `items`                                      |

## Form Components (input fields)

| Type                                              | Description                                            |
|---------------------------------------------------|--------------------------------------------------------|
| `text`, `email`, `url`, `tel`, `password`, `date` | Standard input fields                                  |
| `number`                                          | Numeric input with min/max/step                        |
| `textarea`                                        | Multi-line text                                        |
| `select`                                          | Dropdown (single or multi)                             |
| `ajax_select`                                     | Select2 searchable dropdown (single, multi, or tags)   |
| `post`                                            | Shortcut for ajax_select with post search callback     |
| `taxonomy`                                        | Shortcut for ajax_select with taxonomy search callback |
| `user`                                            | Shortcut for ajax_select with user search callback     |
| `image`                                           | Single image picker with media library                 |
| `toggle`                                          | Switch/checkbox                                        |
| `radio`                                           | Radio button group                                     |
| `color`                                           | Color picker                                           |
| `tags`                                            | Tag input                                              |
| `card_choice`                                     | Visual card selection                                  |
| `price_config`                                    | Stripe pricing (one-time or recurring)                 |
| `discount_config`                                 | Stripe discount/coupon configuration                   |
| `unit_input`                                      | Numeric input with unit prefix or suffix               |
| `code_generator`                                  | Text input with code generation button                 |
| `hidden`                                          | Hidden input                                           |
| `group`                                           | Nested field group with layout                         |

## Layout Components

| Type        | Description                        |
|-------------|------------------------------------|
| `separator` | Visual divider with optional label |
| `accordion` | Collapsible sections               |
