# Contributing to mod_videoembed

Thanks for your interest in improving the **Video** activity module.

## Ground rules
- The plugin is licensed under the **GNU GPL v3 or later**. By contributing you agree your
  contribution is released under the same license.
- Follow the [Moodle coding style](https://moodledev.io/general/development/policies/codingstyle)
  and the [Moodle development policies](https://moodledev.io/general/development/policies).
- Keep all code comments and identifiers in **English**.
- Preserve existing copyright notices; add your own `@copyright` line rather than replacing one.

## Development
- Plugin source lives in [`workspace/mod_videoembed`](workspace/mod_videoembed).
- After changing `amd/src/*.js`, rebuild the AMD bundle with `grunt amd` (do **not** hand-edit
  `amd/build/*`) so CI's grunt check passes.
- Bump `$plugin->version` in `version.php` for any DB or behavioural change, and update
  [`CHANGELOG.md`](workspace/mod_videoembed/CHANGELOG.md).

## Before opening a pull request
Run the same checks CI runs (via [moodle-plugin-ci](https://github.com/moodlehq/moodle-plugin-ci)):

```bash
moodle-plugin-ci phpcs --max-warnings 0
moodle-plugin-ci phpdoc --max-warnings 0
moodle-plugin-ci mustache
moodle-plugin-ci grunt --max-lint-warnings 0
moodle-plugin-ci phpunit
moodle-plugin-ci behat --profile chrome
```

## Reporting issues
Please use the GitHub issue tracker:
<https://github.com/rurak-ec/moodle-mod_videoembed/issues>.
Include your Moodle version, PHP version, the provider/URL involved, and steps to reproduce.
