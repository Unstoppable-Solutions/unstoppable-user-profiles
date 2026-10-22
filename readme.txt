=== Unstoppable User Profiles ===
Contributors: paramedic192
Tags: profile, avatar, user profile, profile photo, front-end
Requires at least: 6.0
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 1.1.3
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Let logged-in members edit their own profile and upload a profile photo from the front end. No media library access. Works with any membership plugin.

== Description ==

Unstoppable User Profiles gives your members a simple front-end form to edit their own profile details and upload their own profile photo — without ever touching the WordPress media library and without you having to add anyone's picture by hand.

Drop the `[uupr_profile_form]` shortcode on any page (built with the block editor, Elementor, or any page builder) and logged-in users get a self-service profile editor.

**Key features**

* Front-end profile editing for the logged-in user only (name, display name, email, website, bio — all toggleable).
* Secure profile photo upload that is stored in its own folder, kept out of the media library so it never clutters it and members never see other people's files.
* Uploaded photos appear everywhere automatically. Because the plugin overrides WordPress core avatars, the member's photo shows in any plugin that uses standard avatars — membership directories, community profiles, comment lists, author boxes, and more — with no extra setup.
* Optional admin moderation: require approval before a new photo goes live.
* Configurable maximum upload size and allowed fields.
* Uploaded images are re-encoded server-side to strip metadata and normalise dimensions.

**Privacy and external services**

This plugin does not connect to any external services, does not load any third-party scripts or fonts, and does not collect, send, or track any data. All uploads stay on your own server.

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/unstoppable-user-profiles` directory, or install the plugin through the WordPress Plugins screen.
2. Activate the plugin through the 'Plugins' screen in WordPress.
3. Go to User Profiles > Settings to choose which fields members can edit and set your photo size limit.
4. Add the `[uupr_profile_form]` shortcode to any page where logged-in members should edit their profile.

== Frequently Asked Questions ==

= Do members need a WordPress account? =

They already have one. Most membership plugins create a WordPress user behind the scenes when someone registers, so members never see wp-admin — this plugin simply lets them edit that account from the front end.

= Will the uploaded photo show in my membership directory? =

If your directory or community plugin uses standard WordPress avatars, yes — automatically. The plugin overrides core avatar output, so no per-plugin configuration is needed.

= Do uploaded photos clutter my media library? =

No. Photos are stored in a dedicated uploads folder and are never added as media library attachments.

== Screenshots ==

1. The front-end profile editor shown by the shortcode.
2. The admin settings screen.
3. The photo approval queue when moderation is enabled.

== Changelog ==

= 1.1.3 =
* Fixed: resolved Plugin Check input-sanitization warnings — profile fields are now sanitized on access, and file-upload arrays carry correct, documented handling notes.

= 1.1.2 =
* Improved: hide the redundant native "Profile Picture" row (and its Gravatar link) on the profile screens, since the plugin now manages photos.

= 1.1.1 =
* Hardened file handling to use the WordPress upload and filesystem APIs (wp_handle_upload, WP_Filesystem) ahead of WordPress.org review.
* Removed silenced PHP operators and cleaned up account-tab navigation handling.

= 1.1.0 =
* Added: photo uploader on the WordPress profile screen (Users > Profile / Edit User), for admins and staff.
* Added: "Profile Photo" tab on the MemberPress Account page so members can upload from the front end.

= 1.0.0 =
* Initial release.

== Upgrade Notice ==

= 1.1.3 =
Clears all Plugin Check warnings. Recommended before submitting or distributing.

= 1.1.2 =
Hides the duplicate native profile picture row so only the plugin's photo uploader shows.

= 1.1.1 =
Internal hardening to align file handling with WordPress.org standards. No changes to features or behavior.

= 1.1.0 =
Adds the photo uploader directly to the WordPress profile screen and the MemberPress Account page.

= 1.0.0 =
Initial release.
