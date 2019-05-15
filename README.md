# BE RSS Builder

**Contributors:** billerickson  
**Requires at least:** 4.6  
**Tested up to:** 5.2  
**Stable tag:** 1.2.0  
**License:** GPLv2 or later  
**License URI:** http://www.gnu.org/licenses/gpl-2.0.html

Build custom RSS feeds for email marketing. Go to Settings > RSS Builder to create a new feed.

After making your selections, click "Get Feed URL" and copy the URL at the top of the page.

![screenshot](https://d16rm1n165bd05.cloudfront.net/items/0P063m3z0y3m1n010F12/Screen%20Shot%202019-05-15%20at%2011.19.19%20AM.png?X-CloudApp-Visitor-Id=78955b2d79e4b4c9650076a91b4db727&v=bc51a953)

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
