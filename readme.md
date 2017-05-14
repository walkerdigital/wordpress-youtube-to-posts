# wordpress-youtube-to-posts

Create WordPress posts based on videos you have uploaded to your YouTube channel.

I was needing a plugin to accomplish the simple task of mirroring a YouTube channel by frequently "polling" a channel via the YouTube Data API and fetching any new content and converting to posts.

This plugin does the following and could probably easily be customized to accomplish your intended feature set.

* Scrapes a given YouTube channel every hour looking for new content.
* Creates a single post within WordPress including the video title, description, an embedded video player and even a feature image / thumbnail if available on your video.
* Videos will only be scraped once.
* Scrape history doesn't go back too far.  This could easily be adjusted but I only grab the first 50 results returned by the YouTube API
* Via a "Settings" page you can configure the following:
    * The YouTube channel URL
    * Your Data API Key
    * The single Post Category you wish to have videos assigned to.

# Installation
To install this plugin follow these instructions:

1. Download a ZIP of this repo (or clone it) into your wordpress plugins directory
2. Activate the plugin via the Plugins page
3. Go to your "Settings" menu and provide the necessary information including YouTube Channel, API Key and selected category.
4. The plugin will run hourly on it's own or you can run it immediately from the settings page.

# Troubleshooting
This plugin was quick & dirty and I don't have any particular expertise in WordPress plugin development.  Should you encounter any issues I would suggest the following.

**Plugin doesn't seem to be running at regular intervals**

This plugin uses WordPress' built-in cron system to run hourly.  This system isn't exactly precise but should be "good enough".  If you suspect it is not running I can suggest getting a plugin to let you peek into all of the scheduled processes to confirm that the `yttp_scrape_channel` process is in fact scheduled. [WP-Crontrol](https://wordpress.org/plugins/wp-crontrol/) is one that worked great for me.

**When I manually run the plugin it shows "Scraped 0 videos..."**

The plugin will only retrieve videos which aren't already posted on your site.  This plugin uses a custom tag called `video_id` which if already in your system will not be duplicated.  If you're certain that new / unique videos aren't beeing retrieved check out the results of the AJAX call via your browser console and log an issue.

**How can I contact you?**

You can email me at jon `@` walkerdigital.co

## Example Images ##

![Example Post](https://github.com/walkerdigital/wordpress-youtube-to-posts/raw/master/_img_smpl/post.jpg)  

![Settings Page](https://github.com/walkerdigital/wordpress-youtube-to-posts/raw/master/_img_smpl/settings.jpg)