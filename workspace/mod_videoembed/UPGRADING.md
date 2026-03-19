# mod_videoembed Upgrade notes

## 5.1

### Improved

- Bunny Stream support now accepts canonical player input URLs in the form:
  `https://player.mediadelivery.net/play/{library}/{video}`.
- Bunny URLs are normalised to canonical embed URLs:
  `https://player.mediadelivery.net/embed/{library}/{video}`.
- Default Bunny embed flags are now applied for consistent behaviour:
  `autoplay=false`, `loop=false`, `muted=false`, `preload=true`, `responsive=true`.
- Bunny iframe rendering now uses lazy loading and provider-specific allow attributes.

### Deprecated

- The \mod_videoembed\event\course_module_instance_list_viewed event is now deprecated. Use \core\event\course_resources_list_viewed instead.

  For more information see [MDL-84632](https://tracker.moodle.org/browse/MDL-84632)
