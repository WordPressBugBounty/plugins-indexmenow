=== IndexMeNow ===
Company: ASF Collector
Contributors: fluddeni, indexmenow
Tags: indexing, seo, google, indexation, serp
Requires at least: 5.8
Tested up to: 6.9
Requires PHP: 7.4
Stable tag: 1.2.4
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Push your URLs to IndexMeNow for fast Google indexation. Supports manual push, bulk push, auto-push on publish/update, sitemap push, and more.

== Description ==

**IndexMeNow** connects your WordPress site to the [IndexMeNow](https://indexmenow.com) service to accelerate Google indexation of your pages and posts.

When you publish or update content, getting it indexed by Google can take days or even weeks. IndexMeNow solves this problem by submitting your URLs to Google through multiple channels, significantly reducing indexation time.

= Key Features =

* **6 push methods** - Manual push, bulk push from posts list, auto-push on publish, auto-push on update, admin bar quick push, and sitemap push
* **Dashboard widget** - View your credit balance and recent push activity directly on the WordPress dashboard
* **Smart duplicate detection** - URLs already being indexed are automatically skipped to save credits
* **Push history tracking** - Complete log of all push attempts with status, trigger, and timestamp
* **Project management** - Create projects automatically or use existing ones from your IndexMeNow account
* **Post type filtering** - Choose which content types (posts, pages, custom post types) can be pushed
* **Category filtering** - Only auto-push posts from specific categories
* **Low credits alert** - Get notified when your credit balance falls below a configurable threshold
* **Secure storage** - API key is encoded before being stored in the database
* **Multi-language** - English and French translations included

= Push Methods =

**1. Manual Push (Editor Metabox)**
A dedicated metabox appears in the post/page editor sidebar. Click "Push to IndexMeNow" to send the current URL. Recent push history is displayed directly in the metabox.

**2. Posts List Actions**
Push URLs directly from the Posts/Pages list screen. Use the "Push to IndexMeNow" row action for individual posts, or select multiple posts and use the bulk action to push them all at once.

**3. Admin Bar Quick Push**
When viewing a published post on the frontend while logged in, an "IndexMeNow" button appears in the WordPress admin bar. One click sends the URL without needing to open the editor.

**4. Auto-Push on First Publish**
Enable this option to automatically push URLs when content is first published. Draft-to-publish transitions trigger the push. Re-publishing or scheduling does not trigger duplicate pushes.

**5. Auto-Push on Update**
Enable this option to automatically push URLs when a published post's title or content is modified. Minor changes (categories, tags, featured image) do not trigger a push.

**6. Sitemap Push**
Push all URLs from your XML sitemap at once. Ideal for bulk indexation of existing content. The plugin automatically detects your sitemap (WordPress core, Yoast SEO, etc.) and lets you select which URLs to push.

= How Credits Work =

IndexMeNow uses a credit-based system. Each URL push consumes 1 credit. The plugin checks your credit balance before each push and displays an error if insufficient. You can view your current balance on the settings page, dashboard widget, and refresh it anytime.

To avoid wasting credits, the plugin checks if a URL is already being processed before pushing. If the URL is found and not yet completed, the push is skipped automatically.

== Installation ==

= Automatic Installation =

1. Go to **Plugins > Add New** in your WordPress admin
2. Search for "IndexMeNow"
3. Click **Install Now**, then **Activate**

= Manual Installation =

1. Download the plugin ZIP file
2. Go to **Plugins > Add New > Upload Plugin**
3. Select the ZIP file and click **Install Now**
4. Activate the plugin

= FTP Installation =

1. Download and extract the plugin ZIP file
2. Upload the `indexmenow` folder to `/wp-content/plugins/`
3. Activate the plugin through the **Plugins** menu

== Configuration ==

After activation, go to **Settings > IndexMeNow** to configure the plugin.

= Step 1: API Key =

1. If you don't have an account, sign up at [indexmenow.com](https://indexmenow.com)
2. Find your API key at [tool.indexmenow.com/docapi](https://tool.indexmenow.com/docapi)
3. Enter your API key in the settings page
4. Click **Verify & load projects** to validate your key

The API key is encoded before being stored in the database.

= Step 2: Project Selection =

Choose how URLs should be organized in your IndexMeNow account:

* **Automatic mode** (recommended): A project named `yourdomain.com (wp-w2)` is created automatically on first push
* **Existing project**: Select an existing project from your account to use for all pushes

= Step 3: Auto-Push Options =

* **Auto-push on new publish**: Automatically push URLs when content is first published (OFF by default)
* **Auto-push on update**: Automatically push URLs when published content is modified (OFF by default)

= Step 4: Post Types =

Select which content types can be pushed to IndexMeNow:

* Posts (enabled by default)
* Pages (enabled by default)
* Any registered custom post type

Only selected post types will show the manual push metabox and be eligible for auto-push.

= Step 5: Category Filter (Optional) =

Select specific categories to limit auto-push. When enabled, only posts belonging to selected categories will be auto-pushed. Posts in other categories can still be pushed manually. Leave empty to push all categories.

= Step 6: Low Credits Alert =

Set a threshold to receive admin notifications when your credit balance is low. Default is 10 credits. Set to 0 to disable the alert.

== Settings Reference ==

| Option | Description | Default |
|--------|-------------|---------|
| API Key | Your IndexMeNow API key (encoded storage) | Empty |
| Project Mode | Automatic or existing project | Automatic |
| Project | Existing project to use (if mode is "existing") | None |
| Auto-push on publish | Push when content is first published | Off |
| Auto-push on update | Push when published content is modified | Off |
| Post Types | Content types eligible for pushing | Posts, Pages |
| Category Filter | Only auto-push posts from selected categories | All categories |
| Low Credits Alert | Show warning when credits fall below threshold | 10 |

== Push History ==

All push attempts are logged in a dedicated database table. Each entry includes:

* **Date/time** of the push attempt
* **URL** that was pushed
* **Post ID** (linked for easy navigation)
* **Status**: success, error, or skipped
* **Trigger**: manual, bulk, auto_publish, auto_update, or sitemap
* **Message**: Success confirmation or error details

View the complete history on the settings page. Use the **Purge** feature to delete old entries (30/60/90 days or all) and keep your database clean.

== Dashboard Widget ==

The IndexMeNow dashboard widget displays:

* **Credit balance** with visual indication when low or empty
* **Quick link** to buy more credits when running low
* **5 most recent push attempts** with status and relative time
* **Quick links** to settings and full history

The widget appears automatically for users with edit_posts capability when an API key is configured.

== Sitemap Push ==

The sitemap push feature allows bulk indexation of existing content:

1. Go to **Settings > IndexMeNow**
2. Scroll to the **Sitemap Push** section
3. Click **Load sitemap URLs** to fetch all URLs from your sitemap
4. Review and select/deselect URLs as needed
5. Click **Push selected URLs** to submit them to IndexMeNow

The plugin automatically detects sitemaps from:
* WordPress core (wp-sitemap.xml)
* Yoast SEO (sitemap_index.xml)
* All in One SEO
* Jetpack
* Standard sitemap.xml

Maximum 2000 URLs can be loaded and pushed at once. Sitemap parsing is limited to 3 levels deep for performance and security.

== Third-Party Service ==

This plugin connects to the **IndexMeNow API** to submit URLs for indexation.

* **Service:** [IndexMeNow](https://indexmenow.com)
* **API endpoint:** `https://tool.indexmenow.com/api/v1`
* **Terms of Service:** [https://indexmenow.com/en/mentions-legales/](https://indexmenow.com/en/mentions-legales/)
* **Privacy Policy:** [https://indexmenow.com/en/politique-de-confidentialite/](https://indexmenow.com/en/politique-de-confidentialite/)

**Data transmitted:**

* Your API key (for authentication)
* Post/page URLs (the URLs you want indexed)
* Your site domain name (used as default project name)

**When data is transmitted:**

* When validating the API key (settings save or verify button)
* When clicking the manual push button (editor, admin bar, or posts list)
* When using bulk push or sitemap push
* When content is published/updated with auto-push enabled
* When refreshing credit balance

No tracking, analytics, or other data is collected.

== Frequently Asked Questions ==

= Where do I get an API key? =

Sign up at [indexmenow.com](https://indexmenow.com) and find your API key in your account settings at [tool.indexmenow.com/docapi](https://tool.indexmenow.com/docapi).

= How much does it cost? =

IndexMeNow uses a credit-based system. Check [indexmenow.com](https://indexmenow.com) for current pricing. The WordPress plugin itself is free.

= Does this plugin work with custom post types? =

Yes. Any public custom post type registered on your site will appear in the settings. Simply check the ones you want to enable.

= What happens if I run out of credits? =

The plugin checks your credit balance before each push. If you have insufficient credits, the push will fail with a clear error message. Your content is not affected. You'll also see an admin notice reminding you to buy more credits.

= Why was my push "skipped"? =

A push is skipped when the URL is already being processed by IndexMeNow. This prevents duplicate credit usage. Wait for the current indexation to complete before pushing again.

= Can I push the same URL multiple times? =

Yes, but only after the previous indexation is complete. The plugin checks the URL status before each push to prevent duplicates.

= Does auto-push work with scheduled posts? =

Yes. When a scheduled post transitions to "published" status, it triggers the auto-push if enabled.

= Can I limit auto-push to specific categories? =

Yes. Use the Category Filter option in settings to select which categories should trigger auto-push. Posts in other categories can still be pushed manually.

= How do I push all my existing content? =

Use the Sitemap Push feature. Go to Settings > IndexMeNow, click "Load sitemap URLs", select the URLs you want, and click "Push selected URLs". This is ideal for initial bulk indexation.

= Is my API key secure? =

The API key is encoded before being stored in the database. It's not stored in plain text.

= What happens when I uninstall the plugin? =

All plugin data is removed: options, push history table, post meta, and transient caches. Your IndexMeNow account and projects are not affected.

= Does this plugin slow down my site? =

No. Push operations are triggered only on specific actions (publish, update, manual click) and run via AJAX. There is no impact on frontend performance.

= Can I use a custom sitemap URL? =

Yes. Use the `imn_w2_sitemap_url` filter in your theme's functions.php to specify a custom sitemap location:
`add_filter( 'imn_w2_sitemap_url', function() { return 'https://example.com/custom-sitemap.xml'; } );`

= Is the API key secure? =

Yes. The API key is encrypted using AES-256-CBC encryption before storage (if OpenSSL is available). On servers without OpenSSL, base64 encoding is used as a fallback.

== Screenshots ==

1. Settings page with connection status and configuration options
2. Manual push metabox in the post editor
3. Admin bar button for quick push from frontend
4. Push history table with pagination
5. Dashboard widget showing credits and recent pushes
6. Bulk push from posts list
7. Sitemap push interface

== Changelog ==

= v1.2.4 =
* Fix French translations (accents)

= 1.2.3 =
* **Security enhancement**: Re-enabled AES-256-CBC encryption for API keys with openssl. Fallback to base64 encoding if openssl is unavailable. Backward compatible with existing keys.
* **Security fix**: Enabled SSL verification for sitemap fetching to prevent MITM attacks. Added filter `imn_w2_sitemap_sslverify` for testing environments.
* **Security improvement**: Enhanced POST array sanitization for sitemap URLs with strict type checking.
* **Performance improvement**: Added recursion depth limit (max 3 levels) for sitemap parsing to prevent infinite loops and memory exhaustion.
* **Performance improvement**: Created helpers class with cached getters for frequently accessed options (post types, categories) to reduce database queries.
* **Performance improvement**: Defined constants for all magic numbers (cache duration, rate limits, sitemap limits) for better maintainability.
* **Code quality**: Improved URL truncation in dashboard widget (character-based instead of word-based for better display).
* **Code quality**: Enhanced date display safety in dashboard widget with timestamp validation.
* **Developer feature**: Added filter `imn_w2_sitemap_url` to allow custom sitemap URL specification.
* **Bug fix**: Fixed duplicate row action filter registration for post types.
* **Bug fix**: Corrected Jetpack sitemap detection (fixed typo: JEPACK__VERSION → JETPACK__VERSION).

= 1.2.2 =
* **Bulk push optimization**: Sitemap push and bulk push from posts list now send all URLs in a single API call instead of one by one, significantly improving performance.

= 1.2.1 =
* **Simplified API key storage**: Changed from AES-256-CBC encryption to base64 encoding for better compatibility across different hosting environments.
* **Bug fix**: Fixed issue where API key validation could fail on some servers.

= 1.2.0 =
* **Posts list integration**: New "Push to IndexMeNow" action in posts/pages list. Push individual posts via row action or multiple posts via bulk action.
* **Dashboard widget**: New widget on the WordPress dashboard showing your credit balance and 5 most recent push attempts with quick links to settings and history.
* **Category filtering**: New option to limit auto-push to specific categories. Posts in other categories can still be pushed manually.
* **Low credits notification**: Admin notice when credits fall below configurable threshold (default: 10). Dismissible for 24 hours.
* **Sitemap push**: New feature to push all URLs from your XML sitemap at once. Automatically detects WordPress core, Yoast, AIOSEO, and standard sitemaps.
* **Bulk trigger**: Push history now tracks "bulk" and "sitemap" triggers in addition to manual and auto triggers.
* **Updated translations**: French and English translations updated with all new strings.

= 1.1.0 =
* **Admin bar button**: New "IndexMeNow" button in the WordPress admin bar when viewing a published post on the frontend. Push URLs with one click without going to the editor.
* **Improved URL comparison**: URLs are now normalized (lowercase host, no trailing slash) before comparison to avoid false negatives and duplicate credits usage.
* **API key encoding**: The API key is now encoded before being stored in the database.
* **API key security**: The API key field is now a password field with a show/hide toggle button.
* **Refresh credits button**: Added a dedicated button to refresh your credit balance without re-entering the API key.
* **Push rate limiting**: Manual push button is disabled for 5 seconds after a successful push to prevent accidental double-pushes.
* **History purge**: New option to delete old push history entries (30/60/90 days or all) to keep your database clean.
* **Uninstall cleanup**: URL status transients are now properly cleaned up when the plugin is uninstalled.
* **Updated translations**: French and English translations updated with all new strings.

= 1.0.1 =
* **Push history**: Each push (manual, auto-publish, auto-update) is now logged in a dedicated database table with timestamp, status, trigger, and message.
* **Metabox history**: The single "Last pushed" timestamp is replaced by a list of the 5 most recent pushes with status indicators.
* **Settings page history**: New "Push History" section with a paginated AJAX table showing all push entries across the site.
* **URL status check**: Before pushing, the plugin verifies via the API whether the URL is already being indexed. If so, the push is skipped to avoid duplicate credits usage.
* **Transient cache**: Status check uses a 5-minute transient cache per URL to limit API calls.
* **First publish optimization**: Auto-push on first publish skips the status check (URL cannot already exist).
* **Fail-open behavior**: If the status check API call fails, the push proceeds normally.
* **Settings link**: Quick access link added to the Plugins page.
* **Database versioning**: Schema updates are applied automatically on plugin update.
* **Uninstall cleanup**: The push history table is dropped when the plugin is uninstalled.
* **Updated translations**: French and English translations with all new strings.

= 1.0.0 =
* Initial release.
* Manual push from post editor.
* Auto-push on new publish.
* Auto-push on content/title update.
* Project selection (auto-create or choose existing).
* French and English translations.

== Upgrade Notice ==

= 1.2.3 =
Security update: AES-256 encryption for API keys, SSL verification for sitemaps, and Jetpack detection fix. Recommended update.

= 1.2.2 =
Bulk push performance improvement - URLs are now sent in a single API call.

= 1.2.1 =
Bug fix for API key validation on some hosting environments. Recommended update.

= 1.2.0 =
New posts list bulk push, dashboard widget, category filtering, low credits notification, and sitemap push features. Major update recommended.

= 1.1.0 =
New admin bar button for quick push, API key encoding, history purge, and various improvements.

= 1.0.1 =
Adds push history tracking, URL status check to prevent duplicate pushes, and improved metabox display.
