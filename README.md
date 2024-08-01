# BE RSS Builder

**Contributors:** billerickson  
**Requires at least:** 4.6  
**Tested up to:** 6.6  
**Stable tag:** 1.4.0  
**License:** GPLv2 or later  
**License URI:** http://www.gnu.org/licenses/gpl-2.0.html

Build custom RSS feeds for email marketing. Go to Settings > RSS Builder to create a new feed.

After making your selections, click "Get Feed URL" and copy the URL at the top of the page.

![screenshot](https://p198.p4.n0.cdn.getcloudapp.com/items/yAuvpjO2/Image%202020-04-07%20at%2010.34.39%20AM.png?v=edef383fd16d250b02eee65681a09f01)

### Customization Options

- Limit to a Category
- Change the sort order ( most recent, random, or [most shares](https://sharedcountsplugin.com))
- Change how many are returned
- Control the offset (first posts excluded from feed) - useful when using multiple feeds in a single email campaign
- Change image size for featured image
- Exclude posts older than a date, or a `strtotime()` compatible string (ex: "-1 year ago")
- Exclude posts in a specific category or tag

### Custom RSS Post Title

This plugin also adds a "RSS Post Title" metabox for specifying a separate title for use in the RSS feed. You can disable this using:

`add_filter( 'be_rss_builder_post_types', '__return_empty_array' );`

### No Newsletter tag

When building your feed, there's a checkbox labeled "Exclude posts tagged 'no-newsletter'". This allows content editors to easily mark certain content as excluded, like sponsored posts.

You should use the `ea_no_newsletter_term_id` filter to specify the term_id used for this tag. Ex:

`add_filter( 'ea_no_newsletter_term_id', function() { return 123; });`
