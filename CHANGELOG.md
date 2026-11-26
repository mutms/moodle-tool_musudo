# Change log

Plugin versioning is derived from Moodle releases, it does not comply with the semantic versioning standard.

The format of this change log follows the advice given at [Keep a CHANGELOG](https://keepachangelog.com).

## [Unreleased]

### Added

- Czech translation and AI-produced previews of German, French, Italian, Portuguese and Spanish translations

### Changed

- new privileged user is selected in a users picker panel with identity columns and paging instead of an autocomplete
- migration to new forms library
- privilege contexts are selected by level (system, tenant, category, course) with searchable pickers
  instead of typing numeric context ids, context ids can still be entered for other contexts
- privileged users list uses the Advanced report builder: general search, filter chips, saved filters,
  configurable columns and filters

### Fixed

- MFA factor description in sudo form displayed HTML markup as literal text
