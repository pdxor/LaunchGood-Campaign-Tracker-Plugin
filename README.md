# LaunchGood Campaign Tracker Plugin

A WordPress plugin that allows you to track and display LaunchGood campaign progress on your WordPress website. This plugin scrapes campaign data directly from LaunchGood campaign pages and displays it in a beautiful, responsive widget.

## Features

- Display campaign progress including amount raised, goal, supporters count, and days remaining
- Auto-refresh functionality to keep data current
- Responsive design that matches LaunchGood's aesthetic
- Available as both a shortcode and widget
- Customizable refresh intervals
- Progress bar visualization
- Clean, modern styling

## Installation

1. Download the plugin files
2. Create a new directory called `launchgood-tracker` in your WordPress plugins directory (`wp-content/plugins/`)
3. Upload the plugin files to the directory
4. Activate the plugin through the 'Plugins' menu in WordPress

## Directory Structure

```
launchgood-tracker/
├── css/
│   └── style.css
├── js/
│   └── script.js
├── launchgood-tracker.php
└── README.md
```

## Usage

### As a Shortcode

Add the campaign tracker to any post or page using the shortcode:

```php
[launchgood_campaign url="YOUR_CAMPAIGN_URL" refresh="3600"]
```

Parameters:
- `url` (required): The full URL to your LaunchGood campaign
- `refresh` (optional): Refresh interval in seconds (default: 3600)

Example:
```php
[launchgood_campaign url="https://www.launchgood.com/v4/campaign/your-campaign" refresh="1800"]
```

### As a Widget

1. Go to Appearance > Widgets in your WordPress admin panel
2. Find the "LaunchGood Campaign Tracker" widget
3. Drag it to your desired widget area
4. Configure the widget settings:
   - Title (optional)
   - Campaign URL (required)
   - Refresh Interval (optional, in seconds)

## Technical Details

The plugin works by:
1. Fetching the campaign page HTML using WordPress's HTTP API
2. Parsing the HTML using DOMDocument to extract campaign data
3. Cleaning and formatting the extracted data
4. Displaying the data in a styled container
5. Automatically refreshing the data at specified intervals using AJAX

## Requirements

- WordPress 5.0 or higher
- PHP 7.0 or higher
- PHP DOM extension enabled
- WordPress REST API enabled

## Security Features

- Data sanitization for all inputs
- XSS protection
- AJAX nonce verification
- Direct file access prevention
- Proper escaping of output

## Customization

### Styling

The plugin's appearance can be customized by overriding the CSS classes in your theme's stylesheet. Main CSS classes:

```css
.launchgood-campaign {}
.campaign-progress {}
.amount-raised {}
.goal {}
.progress-bar {}
.progress {}
.stats {}
```

### Refresh Interval

The default refresh interval is 1 hour (3600 seconds). This can be modified:
- Per instance using the shortcode `refresh` parameter
- Globally by filtering `launchgood_default_refresh`

## Known Limitations

- Relies on LaunchGood's HTML structure (may need updates if their markup changes)
- Requires JavaScript for auto-refresh functionality
- Campaign URLs must be from LaunchGood's main website

## Troubleshooting

### Common Issues

1. **No Data Displayed**
   - Verify the campaign URL is correct and accessible
   - Check if your server can make external HTTP requests
   - Ensure PHP DOM extension is enabled

2. **Auto-refresh Not Working**
   - Check if JavaScript is enabled
   - Verify WordPress AJAX URL is accessible
   - Check browser console for errors

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

## License

This project is licensed under the GPL v2 or later.

## Credits

Created by [Your Name]
Inspired by LaunchGood's fundraising platform

## Support

For support, please open an issue on the GitHub repository or contact [your contact information].
