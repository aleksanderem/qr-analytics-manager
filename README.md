# QR Analytics

WordPress plugin for generating trackable QR codes with comprehensive analytics dashboard.

## Features

- **QR Code Generator** - Create vector SVG QR codes for high-quality print materials
- **Custom Slug Routing** - Define memorable URLs like `/qr/lavazza_promo_stand`
- **Click Tracking** - Track scans with device, browser, OS, and location data
- **Analytics Dashboard** - Visualize scan data with Chart.js graphs
- **LAN Testing** - Configure base URL for testing from mobile devices on localhost

## Installation

1. Download the latest release from [Releases](https://github.com/aleksanderem/qr-analytics-manager/releases)
2. Upload to `/wp-content/plugins/qr-analytics/`
3. Activate the plugin through the 'Plugins' menu in WordPress
4. Go to QR Analytics menu to create your first QR code

## Auto-Updates

This plugin supports automatic updates from GitHub. When a new release is published, WordPress will notify you about the update in the Plugins page.

### Creating a Release

1. Update `Version:` in `qr-analytics.php`
2. Update `QR_ANALYTICS_VERSION` constant
3. Commit and push changes
4. Create a new release on GitHub with a tag like `v1.0.1`

## Usage

### Creating a QR Code

1. Go to QR Analytics > Add New QR
2. Enter a name and unique slug (e.g., `summer_campaign`)
3. Set the destination URL
4. Save and download the SVG QR code

### Tracking

QR codes redirect through `/qr/{slug}/` and track:
- IP address (anonymized)
- Device type (mobile, tablet, desktop)
- Browser and OS
- Referrer
- Timestamp

### Analytics

View analytics in the Dashboard or Reports page:
- Clicks over time
- Device breakdown
- Top performing QR codes

## Requirements

- WordPress 5.0+
- PHP 7.4+

## Author

**Alex M.**

## License

GPL v2 or later
