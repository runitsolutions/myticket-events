# MyTicket Events

> **Note:** This is a fork of the original [MyTicket Events](https://github.com/kenzap/myticket-events-gutenberg-blocks) plugin by Kenzap. This fork is now maintained by **RunIT Solutions**.

A beautiful and easy customizable set of Gutenberg blocks to list events, create calendars and generate QR-code PDF tickets. The plugin extends WooCommerce plugin functionality by creating additional fields under products section, provides seamless checkout experience and support of more than 100+ payment gateways.

## Repository

**Source:** https://github.com/runitsolutions/myticket-events

## Features

- 📅 Event listing with keyword, category, price, location and date filtering
- 🗓️ Event calendar with carousel
- 🎫 Large call to action ticket add buttons
- 🛒 Custom WooCommerce checkout page with QR-code PDF ticket download
- 🎭 Extra WooCommerce product fields like date, venue, location to transform products into events
- 📱 Secure MyTicket Scanner android application for QR-code ticket validation
- ✉️ Customizable email and PDF templates
- 🎪 Concert hall/stadium seat chart layout with reservations
- 🔒 Security improvements and WordPress best practices

## Requirements

- WordPress 5.6 or higher
- PHP 7.1 or higher
- WooCommerce plugin (required)

## Installation

### From WordPress Admin

1. Log in and navigate to **Plugins → Add New**
2. Type "MyTicket Events" into the Search and hit Enter
3. Locate the MyTicket Events plugin in the list and click **Install Now**
4. Once installed, click the **Activate** link
5. Go to **Pages → Add New** → Find **MyTicket Listing** block
6. Adjust **Container → Max width** setting if elements are not displayed properly

### Manual Installation

1. Download the plugin from the [releases page](https://github.com/runitsolutions/myticket-events/releases)
2. Unzip the package and upload to your `wp-content/plugins/` directory
3. Log into WordPress and navigate to the **Plugins** screen
4. Locate MyTicket Events in the list and click the **Activate** link

## Gutenberg Blocks

The plugin includes the following Gutenberg blocks:

- **MyTicket Listing 1** - Event listing with filters
- **MyTicket Listing 2** - Event calendar carousel
- **MyTicket Listing 3** - Call to action buttons
- **MyTicket Listing 4** - Schedule listing
- **MyTicket Listing 5** - Concert hall/stadium seat chart layout
- **MyTicket Listing 6** - Additional listing options

## Changelog

### Version 2.0.0

- ✨ Fork maintained by RunIT Solutions
- 🔒 Fixed CVE-2025-27299 Path Traversal vulnerability
- 🛠️ Removed CMB2 dependency - now uses native WordPress metaboxes
- 🔐 Added nonce verification to all AJAX endpoints
- 🛡️ Fixed directory traversal vulnerability in template loading
- ✅ Added proper input validation and sanitization for file paths
- 🧹 Fixed XSS vulnerabilities in user input handling
- 🍪 Improved cookie handling with proper sanitization
- ✅ Added whitelist validation for template type values
- 📝 Updated author to RunIT Solutions

See [readme.txt](readme.txt) for complete changelog.

## Security

This version includes multiple security improvements:

- Path traversal vulnerability fixes
- Nonce verification for all AJAX endpoints
- Input validation and sanitization
- XSS prevention
- Secure file path handling following WordPress best practices

## Development

### Building the Plugin

```bash
npm install
npm run build
```

### Development Mode

```bash
npm start
```

## Support

For support, please visit:
- **Repository Issues:** https://github.com/runitsolutions/myticket-events/issues
- **RunIT Solutions:** https://runitcr.com/

## License

This plugin is licensed under GPL2+.

## Credits

- **Original Plugin:** Kenzap
- **Current Maintainer:** RunIT Solutions
- **Version:** 2.0.0
