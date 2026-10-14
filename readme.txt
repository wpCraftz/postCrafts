=== PostCrafts - Advanced Post Blocks to Highlight, Summarize and Beautifully Organize Your Posts ===
Contributors: faisalahmed29, wpcraftz
Tags: post grid, gutenberg blocks, post blocks, recent posts, news
Requires at least: 5.6
Tested up to: 7.1
Requires PHP: 7.4
Tested up to PHP: 8.3
Stable tag: 1.1.0
License: GPLv3
License URI: http://www.gnu.org/licenses/gpl-3.0.html

PostCrafts is the best post grid, blog designer, news, magazine, and WordPress blog plugin that comes with various Gutenberg blocks.

== Description ==

PostCrafts is the best news, magazine, and WordPress blog plugin that comes with blog designer packs, post grid blocks, post list blocks, post slider blocks, and numerous Gutenberg blocks.

== 🏆 FEATURES ==

- 2 Gutenberg Blocks
- Advanced Query Builder
- Ajax Pagination
- Excerpt Customization

## Advanced Query Builder

Discover the power of PostCrafts' advanced query builder, where effortlessly showcasing and sorting your posts, pages, or a blend of both becomes seamless. Tailor your display by highlighting specific posts and custom post types with ease, all within any PostCrafts post block.

- Showcase Posts Based on Category
- Showcase Posts Based on Tags
- Showcase Posts Based on Category and Tags
- Showcase Specific Posts
- Reorder Posts
- Exclude Posts
- Exclude Sticky Post
- Exclude Current Post

## Ajax Pagination

Unlock a Trio of Ajax Pagination Options with PostCrafts:

- Load More
- Arrow Navigation
- Numeric Pagination.

= Author =
Developed by [wpCraftz](https://wpcraftz.com/).

= Liked PostCrafts? =
- Join our [Facebook Group](https://www.facebook.com/groups/postcrafts/).
- Learn from our tutorials on [YouTube Channel](https://www.youtube.com/@wpcraftz).

### 📗 Translations

PostCrafts plugin is compatible with WPML Plugin and also it works perfectly with loco translate plugin. 
You can Translate PostCrafts on [translate.wordpress.org](https://translate.wordpress.org/projects/wp-plugins/post-crafts/).


== Installation ==

= Minimum Requirements =

* WordPress 5.0.0 or greater
* PHP version 5.6 or greater
* MySQL version 5.6 or greater

= Automatic installation =

Automatic installation is the easiest option as WordPress handles the file transfers itself and you don’t even need to leave your web browser. 

1. Go to your WordPress Plugin installation menu (Dashboard > Plugins > Add New)
2. In the search field type PostCrafts and press enter.
3. "Install Now" and then click "Active"


= Manual installation =

For Manual installation, you download our product from WordPress directory uploading it to your web-server via your FTP or CPanel application.

1. Download the plugin and unzip it
2. Using an FTP program or CPanel upload the unzipped plugin folder to your WordPress installation’s wp-content/plugins/ directory.
3. Activate the plugin from the Plugins menu (Dashboard > Plugins > Installed Plugins) within the WordPress admin.

= Upgrading =

Automatic update working great for this plugin. If you customize the plugin make sure to store a backup before update.

Sometimes because of the permalink issue during the plugin update, just go to WordPress > Settings > Permalinks and hit the ‘Save’ button.


== Frequently Asked Questions ==

= Is this a Gutenberg Blocks plugin? =

Yes, but it is a Gutenberg Post Block plugin.  It displays the dynamic post on your page. 

= Where can I request new features? =

We are available on the WordPress support forum.

= Where can I report bugs and contribute to the project? =

Bugs can be reported on the WordPress support forum.

= Is it working with older version of WordPress? =

It is a Gutenberg plugin it works great with WordPress version 5.x.x or more. And WordPress older version with Gutenberg plugin.


== Screenshots ==

1. Post List Block: arrange yours posts in list view.
2. Post Grid block: display posts in grid view.
3. Advanced post selection tool: enables you to select specific posts.


== Changelog ==

= 1.1.0 - 14 October 2026 =

- New: Styles tab with category, title, title hover, meta and excerpt colors and a responsive title font size
- New: Keyboard-accessible pagination with screen reader labels
- New: Post picker search matches titles; selection can be cancelled
- Fix: Security hardening for AJAX pagination, saved block CSS and the style REST endpoint
- Fix: Load More no longer loads posts twice on a double click and disables after the last page
- Fix: Numbered pagination with four pages
- Fix: Excerpt words no longer run together; manual excerpts are used
- Fix: "Exclude current post" now applies in the editor, the first page and AJAX pages
- Fix: Blocks inside Group/Columns get their styles and settings on AJAX pages
- Fix: Placeholder shown for posts with a missing featured image
- Fix: Activation and translations load from the plugin root
- Update: Editor preview matches the front end
- Update: Removed the empty PostCrafts admin page
- Update: Removed site-wide excerpt length/"more" filters

= 0.2.0 - 29 April 2024 =

- Update: Range component UI/UX
- Update: Excerpt default length

= 0.1.2 - 09 April 2024 =

- Fix: Pagination issue when max page is less than 3

= 0.1.1 - 09 April 2024 =

- New: Responsive Settings
- New: Unit Settings
- New: Range Field
- Fix: Excerpt length issue in editor

= 0.1.0 - 03 April 2024 =

- New: Ajax Pagination
- New: Excerpt Settings


= 0.0.2 - 06 Mar 2024 =

- Update: Block Icons
- Update: Editor CSS


= 0.0.1 - 21 Feb 2024 =

* Alpha release

== Upgrade Notice ==

= 1.1.0 =
Security and pagination fixes. Note: with "Exclude current post" off (the default), a block on a single post now includes that post; theme excerpts outside PostCrafts blocks use WordPress's default "more" text.
