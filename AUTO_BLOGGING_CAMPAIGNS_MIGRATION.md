# Auto Blogging Campaign System - Migration Guide

## Overview

The auto blogging feature has been upgraded to support **multiple campaigns** instead of a single configuration. This allows users to create and manage different auto-blogging campaigns with unique settings, schedules, and topics.

## Key Features

### Multiple Campaigns
- Create unlimited auto-blogging campaigns
- Each campaign has its own settings, schedule, and topics
- Independent cron jobs for each campaign
- Enable/disable campaigns individually

### Campaign Management UI
- Visual campaign list with status indicators
- Easy campaign creation and editing
- Quick toggle to enable/disable campaigns
- Delete campaigns when no longer needed

### Backward Compatibility
- **Old settings are automatically migrated** to a "Default Campaign"
- Existing single auto-blogging setup continues to work
- No data loss during migration
- Old `rapidtextai_auto_blogging` option is preserved

## Database Structure Changes

### New Options

#### `rapidtextai_auto_blogging_campaigns`
Stores all campaigns as an associative array:

```php
array(
    'campaign_xxxxxxxxxx' => array(
        'id' => 'campaign_xxxxxxxxxx',  // Unique campaign ID
        'name' => 'Campaign Name',       // User-defined name
        'enabled' => 1,                  // 1 or 0
        'schedule' => 'daily',           // hourly, twicedaily, daily, weekly
        'post_status' => 'draft',        // publish, draft, pending
        'post_author' => 1,              // User ID
        'topics' => 'Topic 1\nTopic 2',  // Line-separated topics
        'model' => 'gpt-3.5-turbo',      // AI model
        'tone' => 'informative',         // Content tone
        'post_category' => array(1, 2),  // Array of category IDs
        'generate_tags' => 1,            // 1 or 0
        'tags_count' => 5,               // Number of tags
        'excerpt_length' => 55,          // Words in excerpt
        'taxonomy_limit' => 5,           // Max taxonomies
        'include_images' => 1,           // 1 or 0
        'include_featured_image' => 0,   // 1 or 0
        'max_images' => 5,               // Maximum images
        'enable_logging' => 0,           // 1 or 0
        'created' => '2025-01-01 12:00:00',  // Creation timestamp
        'updated' => '2025-01-01 12:30:00',  // Last update timestamp
    ),
    // ... more campaigns
)
```

#### `rapidtextai_campaigns_migrated`
Boolean flag indicating whether migration has been completed:
- `true` - Migration completed
- `false` or not set - Migration needed

### Preserved Options

#### `rapidtextai_auto_blogging`
The original option is **preserved for backward compatibility**. It is updated when:
- Only one campaign exists (treated as the main campaign)
- The "Default Campaign" is modified

### Post Meta Fields

New post meta added to track campaign association:

```php
'_rapidtextai_campaign_id'  // The ID of the campaign that generated this post
```

Existing post meta fields:
```php
'_rapidtextai_topic'        // The topic used
'_rapidtextai_settings'     // Campaign settings snapshot
'_rapidtextai_raw_content'  // Original markdown content
'_rapidtextai_status'       // Processing status
'_rapidtextai_started'      // When generation started
```

## Cron Job Changes

### Old System
Single cron hook for all auto-blogging:
```php
'rapidtextai_auto_blogging_cron'
```

### New System
**Campaign-specific cron hooks** (one per campaign):
```php
'rapidtextai_auto_blogging_cron_campaign_xxxxxxxxxx'
```

The old cron hook is **maintained** for backward compatibility.

### Cron Scheduling

Each enabled campaign gets its own scheduled cron job:

```php
// Example for a daily campaign
wp_schedule_event(time(), 'daily', 'rapidtextai_auto_blogging_cron_campaign_12345');
```

When a campaign is:
- **Enabled**: Cron job is scheduled
- **Disabled**: Cron job is cleared
- **Deleted**: Cron job is cleared

## Function Signature Changes

### `rapidtextai_generate_auto_blog_post()`

**Old Signature:**
```php
function rapidtextai_generate_auto_blog_post()
```

**New Signature:**
```php
function rapidtextai_generate_auto_blog_post($campaign_id = '')
```

**Parameters:**
- `$campaign_id` (string, optional): The campaign ID to use
  - If empty, uses old settings for backward compatibility
  - If provided, loads campaign settings from campaigns array

**Example Usage:**
```php
// Old way (still works)
rapidtextai_generate_auto_blog_post();

// New way with campaign
rapidtextai_generate_auto_blog_post('campaign_1234567890');
```

### New Helper Functions

#### `rapidtextai_schedule_campaign_cron()`
Schedules a cron job for a specific campaign:

```php
rapidtextai_schedule_campaign_cron($campaign_id, $campaign);
```

#### `rapidtextai_migrate_to_campaigns()`
Converts old single settings to campaign-based structure:

```php
rapidtextai_migrate_to_campaigns();
```

Automatically called on `plugins_loaded` hook.

## Migration Process

### Automatic Migration

Migration happens automatically when:
1. Plugin is activated
2. `plugins_loaded` hook fires
3. Option `rapidtextai_campaigns_migrated` is not set

### Migration Steps

1. **Check if migration needed**
   ```php
   if (get_option('rapidtextai_campaigns_migrated')) {
       return; // Already migrated
   }
   ```

2. **Load old settings**
   ```php
   $old_settings = get_option('rapidtextai_auto_blogging', array());
   ```

3. **Create default campaign**
   ```php
   $default_campaign = array(
       'id' => 'campaign_' . time(),
       'name' => 'Default Campaign',
       // ... copy all old settings
   );
   ```

4. **Save to new structure**
   ```php
   $campaigns[$default_campaign['id']] = $default_campaign;
   update_option('rapidtextai_auto_blogging_campaigns', $campaigns);
   ```

5. **Mark as migrated**
   ```php
   update_option('rapidtextai_campaigns_migrated', true);
   ```

6. **Schedule cron if enabled**
   ```php
   if ($default_campaign['enabled']) {
       rapidtextai_schedule_campaign_cron($default_campaign['id'], $default_campaign);
   }
   ```

### Manual Migration

If needed, users can trigger migration by:
- Deactivating and reactivating the plugin
- Or via custom admin action (if implemented)

## User Interface Changes

### Campaign List View

New main view showing all campaigns:

**Features:**
- Table showing all campaigns
- Campaign status (Enabled/Disabled)
- Quick actions: Edit, Toggle, Delete
- "Add New Campaign" button
- Next run time for enabled campaigns
- Empty state when no campaigns exist

**URL:** `admin.php?page=rapidtextai-auto-blogging`

### Campaign Editor

Edit/create individual campaigns:

**Features:**
- Campaign name field (new)
- All existing auto-blogging settings
- Back to campaigns button
- Campaign-specific quick actions

**URL:** `admin.php?page=rapidtextai-auto-blogging&edit_campaign=campaign_id`

### Campaign Actions

**Available Actions:**
- **Edit** - Modify campaign settings
- **Toggle** - Enable/disable campaign
- **Delete** - Remove campaign permanently
- **Generate Now** - Manually trigger post generation

## Logging Enhancements

All log messages now include campaign ID for better tracking:

**Old Format:**
```
RapidTextAI: Stage 1: Starting content streaming...
```

**New Format:**
```
RapidTextAI: [Campaign: campaign_12345] Stage 1: Starting content streaming...
```

This allows filtering logs by campaign and better debugging.

## API Compatibility

### WordPress Hooks

**New Hooks:**
```php
// Campaign-specific cron hooks (dynamic)
'rapidtextai_auto_blogging_cron_campaign_{id}'
```

**Preserved Hooks:**
```php
// Original hooks still work
'rapidtextai_auto_blogging_cron'
'rapidtextai_generate_title'
'rapidtextai_create_post'
'rapidtextai_finalize_post'
```

## Testing Checklist

### Before Updating

- [ ] Backup database (options table)
- [ ] Export current auto-blogging settings
- [ ] Note any active cron jobs
- [ ] Document current configuration

### After Updating

- [ ] Verify "Default Campaign" exists
- [ ] Check campaign has correct settings
- [ ] Confirm cron job is scheduled (if enabled)
- [ ] Test creating new campaign
- [ ] Test editing campaign
- [ ] Test enabling/disabling campaign
- [ ] Test deleting campaign
- [ ] Test manual post generation
- [ ] Check logs for campaign ID
- [ ] Verify backward compatibility

### Regression Testing

- [ ] Old posts still accessible
- [ ] Post metadata intact
- [ ] Images and tags working
- [ ] Scheduled posts continue
- [ ] Logs still accessible

## Troubleshooting

### Migration Issues

**Problem:** Old settings not migrated

**Solution:**
```php
// Manually reset migration flag
delete_option('rapidtextai_campaigns_migrated');
// Then reload the page
```

**Problem:** Cron not running

**Solution:**
```php
// Check scheduled events
wp_next_scheduled('rapidtextai_auto_blogging_cron_campaign_xxxxx');

// Reschedule if needed
$campaign = /* get campaign */;
rapidtextai_schedule_campaign_cron($campaign_id, $campaign);
```

**Problem:** Duplicate campaigns

**Solution:**
```php
// Get all campaigns
$campaigns = get_option('rapidtextai_auto_blogging_campaigns', array());
// Remove duplicates manually
// Update option
update_option('rapidtextai_auto_blogging_campaigns', $campaigns);
```

### Common Issues

1. **Campaign not generating posts**
   - Check if campaign is enabled
   - Verify topics are set
   - Check API key is valid
   - Review error logs

2. **Multiple posts generated**
   - Check for duplicate cron jobs
   - Verify only one campaign per schedule
   - Clear duplicate crons

3. **Settings not saving**
   - Check user permissions
   - Verify nonce validation
   - Review PHP error logs

## Rollback Plan

If issues arise, rollback by:

1. **Restore old behavior:**
   ```php
   delete_option('rapidtextai_campaigns_migrated');
   delete_option('rapidtextai_auto_blogging_campaigns');
   ```

2. **Revert code changes** using version control

3. **Reschedule original cron:**
   ```php
   $settings = get_option('rapidtextai_auto_blogging');
   if ($settings['enabled']) {
       wp_schedule_event(time(), $settings['schedule'], 'rapidtextai_auto_blogging_cron');
   }
   ```

## Best Practices

### Creating Campaigns

1. **Use descriptive names**: "Tech Blog - Weekly", "Product Updates - Daily"
2. **One topic set per campaign**: Group related topics together
3. **Separate schedules**: Avoid multiple campaigns on same schedule
4. **Test first**: Start with draft status and logging enabled

### Managing Multiple Campaigns

1. **Organize by topic**: Create campaigns for different content categories
2. **Vary schedules**: Distribute load across different times
3. **Monitor logs**: Keep logging enabled initially
4. **Review regularly**: Check generated posts quality

### Performance Optimization

1. **Limit active campaigns**: Too many can overwhelm the server
2. **Stagger schedules**: Don't run all at same time
3. **Use appropriate intervals**: Consider server resources
4. **Clean up unused**: Delete inactive campaigns

## Support and Updates

### Version Information
- **Feature Added**: Version 3.8.0
- **Migration Required**: Automatic
- **Backward Compatible**: Yes

### Getting Help

If you encounter issues:
1. Enable logging in campaign settings
2. Check error logs for detailed messages
3. Review this documentation
4. Contact RapidTextAI support with campaign ID and error logs

## Future Enhancements

Planned features:
- Campaign templates
- Bulk operations
- Campaign statistics dashboard
- Advanced scheduling options
- Campaign categories/folders
- Import/export campaigns

---

**Last Updated:** January 1, 2026  
**Version:** 3.8.0  
**Author:** RapidTextAI Team
