# SPX - Webp Converter

**Contributors:** René Otto
**Tags:** webp, image, converter, uploads, optimization
**Requires at least:** 5.0
**Tested up to:** 6.5
**Stable tag:** 1.1.3
**License:** GPLv2 or later
**License URI:** [https://www.gnu.org/licenses/gpl-2.0.html](https://www.gnu.org/licenses/gpl-2.0.html)

## Description
This plugin automatically converts uploaded JPEG and PNG images to WebP format and allows WebP uploads in the WordPress media library. It helps optimize your website images for better performance and smaller file sizes.

## Installation
1. Upload the plugin files to the `/wp-content/plugins/spx-webp-converter` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress.

## Frequently Asked Questions
**What does this plugin do?**
It converts JPEG and PNG uploads to WebP and allows WebP uploads in the media library.

**Does it delete the original image?**
Yes, after successful conversion, the original JPEG/PNG file is removed.

**What are the requirements?**
Your server must support the GD library with WebP support (PHP >= 7.0 recommended).

## Changelog
### Unreleased
* (none)

### 1.1.3 – 2025-08-19
* style: code style / formatting (PSR/WP standards) in helpers, admin, converter

### 1.1.2 – 2025-08-19
* refactor: split code into modular includes (helpers/admin/converter)

### 1.1.1 – 2025-08-19
* fix: proportional single-pass resize & apply settings properly
* refactor: internal conversion flow for clarity

### 1.1.0 – 2025-08-19
* feat: add admin settings for quality and max dimensions (f23c9ae)

### 1.0.0 – 2025-08-19
* Converts uploads to WebP and allows WebP uploads (4dabede)
* docs(changelog): add commit history entries (c4617cd)
* docs: convert readme to Markdown and remove legacy readme.txt (1ab7438)
* Initializes WebP conversion plugin (2bdf55d)
* Initial commit (d91eb93)

## Upgrade Notice
### 1.0.0
First release.