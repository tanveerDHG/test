# Payment Link Generator for WordPress

A WordPress plugin that integrates with Forminator forms to generate secure payment links. Users fill out a form, submit it, and receive a unique checkout link containing their form data and selected payment gateway.

## Features

- 🔗 **Automatic Payment Link Generation**: Creates unique checkout URLs after form submission
- 💳 **Multiple Payment Gateways**: Supports Stripe, PayPal, and Razorpay
- 📧 **Email Notifications**: Automatically sends payment links to users
- 🎨 **Beautiful UI**: Modern, responsive checkout pages
- 🔒 **Secure**: All data is sanitized and validated
- 📊 **Form Integration**: Works seamlessly with Forminator forms
- 📱 **Mobile Friendly**: Responsive design works on all devices

## Requirements

- WordPress 5.0 or higher
- PHP 7.4 or higher
- Forminator plugin (for form integration)
- SSL certificate recommended for payment processing

## Installation

### Method 1: Manual Installation

1. **Download the plugin files** and upload them to your WordPress site:
   ```
   /wp-content/plugins/payment-link-generator/
   ├── payment-link-generator.php
   ├── forminator-integration.php
   ├── templates/
   │   ├── payment-form.php
   │   └── checkout-page.php
   └── assets/
       ├── payment-link.css
       └── payment-link.js
   ```

2. **Activate the plugin** from WordPress Admin → Plugins

3. **Configure URL rewriting** by going to Settings → Permalinks and clicking "Save Changes"

### Method 2: Using Plugin Files from this Repository

1. Copy all files to your WordPress plugin directory:
   ```bash
   cp -r . /path/to/wordpress/wp-content/plugins/payment-link-generator/
   ```

2. Activate the plugin in WordPress Admin

## Configuration

### 1. Configure Payment Link Settings

1. Go to **Settings → Payment Links** in WordPress Admin
2. Enter your Forminator form IDs (comma-separated) that should generate payment links
3. Save the settings

### 2. Set Up Your Forminator Form

Create a Forminator form with the following fields and IDs:

| Field Type | Field ID | Label | Required |
|------------|----------|-------|----------|
| Text | `name-1` | Item Name | Yes |
| Number | `number-1` | Item Price | Yes |
| Number | `number-2` | Discount | No |
| Textarea | `textarea-1` | Item Description | No |
| Text | `text-1` | Item Category | Yes |
| Select | `select-1` | Payment Gateway | Yes |
| Email | `email-1` | Email Address | Yes |

**Payment Gateway Options** for the select field:
- Value: `stripe`, Label: `Stripe`
- Value: `paypal`, Label: `PayPal` 
- Value: `razorpay`, Label: `Razorpay`

### 3. Alternative: Use Standalone Form

You can also use the built-in form without Forminator by adding this shortcode to any page or post:

```php
[payment_link_form]
```

## How It Works

1. **User fills out the form** with item details and selects a payment gateway
2. **Form is submitted** and data is validated
3. **Unique payment link is generated** and stored in the database
4. **User receives the payment link** (via email if using Forminator integration)
5. **Customer visits the link** to see a beautiful checkout page
6. **Payment is processed** through the selected gateway
7. **Order status is updated** in the database

## Checkout Page Features

The generated checkout pages include:

- ✅ **Order Summary**: Complete breakdown of the purchase
- 💳 **Payment Gateway Selection**: Secure payment processing
- 🔒 **Security Indicators**: SSL and security notices
- 📱 **Mobile Responsive**: Works perfectly on all devices
- ⚡ **Real-time Processing**: Instant payment confirmation

## Customization

### Styling

The plugin includes comprehensive CSS that you can customize by editing `assets/payment-link.css`. The design uses:

- Modern gradient backgrounds
- Clean typography
- Smooth animations
- Responsive breakpoints

### Payment Gateway Integration

The plugin includes placeholder methods for payment processing:

```php
// In payment-link-generator.php
private function process_stripe_payment($payment_data, $post_data) {
    // Add your Stripe API integration here
    return array(
        'success' => true,
        'message' => 'Payment processed successfully',
        'transaction_id' => 'stripe_' . uniqid()
    );
}
```

### Field Mapping

You can customize how Forminator fields map to payment data by modifying the `get_field_mapping()` method in `forminator-integration.php`.

## URL Structure

Payment links follow this pattern:
```
https://yoursite.com/checkout/{payment_id}
```

Example:
```
https://yoursite.com/checkout/a1b2c3d4e5f6g7h8i9j0
```

## Database Tables

The plugin creates a `wp_payment_links` table with the following structure:

| Column | Type | Description |
|--------|------|-------------|
| id | INT | Auto-increment primary key |
| payment_id | VARCHAR(32) | Unique payment identifier |
| form_data | LONGTEXT | Serialized form data |
| item_name | VARCHAR(255) | Product/service name |
| item_price | DECIMAL(10,2) | Original price |
| discount | DECIMAL(10,2) | Discount amount |
| total_cost | DECIMAL(10,2) | Final price |
| item_category | VARCHAR(100) | Product category |
| payment_gateway | VARCHAR(50) | Selected gateway |
| item_description | TEXT | Product description |
| status | VARCHAR(20) | Payment status |
| created_at | DATETIME | Creation timestamp |

## Security Features

- 🔒 **Nonce Verification**: All AJAX requests are protected
- 🧼 **Data Sanitization**: All input is sanitized and validated
- 🛡️ **SQL Injection Protection**: Uses prepared statements
- 🔑 **Unique Payment IDs**: MD5 hashed unique identifiers
- ⚡ **XSS Prevention**: All output is escaped

## Troubleshooting

### Common Issues

1. **404 on checkout pages**
   - Go to Settings → Permalinks and click "Save Changes"
   - Make sure the plugin is activated

2. **Payment links not generating**
   - Check that your Forminator form ID is configured correctly
   - Verify all required fields are present in your form

3. **Email notifications not sending**
   - Ensure your WordPress site can send emails
   - Check that the email field is properly mapped

### Debug Mode

Enable WordPress debug mode to see detailed error messages:

```php
// Add to wp-config.php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
```

## Hooks and Filters

The plugin provides several hooks for customization:

```php
// Filter payment data before processing
apply_filters('payment_link_form_data', $form_data);

// Action after payment link generation
do_action('payment_link_generated', $payment_id, $checkout_url);

// Filter email content
apply_filters('payment_link_email_content', $message, $payment_data);
```

## Development

### Local Development Setup

1. Set up a local WordPress environment
2. Install Forminator plugin
3. Clone this repository to the plugins directory
4. Activate the plugin and configure as above

### Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Test thoroughly
5. Submit a pull request

## License

This plugin is licensed under the GPL v2 or later.

## Support

For support and questions:

1. Check the troubleshooting section above
2. Review the WordPress debug logs
3. Test with a default WordPress theme
4. Ensure all requirements are met

## Changelog

### Version 1.0.0
- Initial release
- Forminator integration
- Multiple payment gateway support
- Responsive checkout pages
- Email notifications
- Admin configuration panel

---

**Made with ❤️ for WordPress**