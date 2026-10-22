# Unstoppable User Profiles

[![WordPress Plugin Version](https://img.shields.io/wordpress/plugin/v/unstoppable-user-profiles.svg)](https://wordpress.org/plugins/unstoppable-user-profiles/)
[![WordPress Plugin Downloads](https://img.shields.io/wordpress/plugin/dt/unstoppable-user-profiles.svg)](https://wordpress.org/plugins/unstoppable-user-profiles/)
[![License: GPL v2+](https://img.shields.io/badge/License-GPLv2+-blue.svg)](https://www.gnu.org/licenses/gpl-2.0)

Front-end profile editing and avatar uploads for logged-in members. No WordPress Media Library access required, no manual photo wrangling on your side.

> **End users:** install from the WordPress Plugin Directory — [wordpress.org/plugins/unstoppable-user-profiles](https://wordpress.org/plugins/unstoppable-user-profiles/)

## Features

- **Front-end edit form** — members update name, bio, email, custom fields from the site, not the dashboard
- **Front-end photo upload** — avatars uploaded via a scoped front-end form, no Media Library access
- **Works with any membership plugin** — Paid Memberships Pro, MemberPress, WooCommerce Memberships, etc.
- **Shortcode and block** — drop the profile editor anywhere
- **Role-aware** — admins see the admin profile; members see the member view

## Who it's for

Member sites, directories, team pages, and any build where the owner doesn't want members poking around the dashboard or getting access to the full WordPress media library just to change their picture.

## Development

### Release workflow

Releases are deployed to WordPress.org automatically by GitHub Actions:

1. Finish the feature work on `main`
2. Bump the version in `unstoppable-user-profiles.php` (header + any constant), `readme.txt` (Stable tag), and add a Changelog entry
3. Commit and push `main`
4. On GitHub, create a new **Release** tagged `vX.Y.Z` — the deploy workflow fires and pushes to WordPress.org SVN

### CI

Every push and pull request runs the official WordPress Plugin Check against the codebase.

## License

GPL-2.0-or-later. See the `License` header in `unstoppable-user-profiles.php`.

## Author

Developed and maintained by [Unstoppable Solutions](https://unstoppablesolutions.app).
