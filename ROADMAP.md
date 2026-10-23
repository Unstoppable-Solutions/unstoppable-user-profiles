# Unstoppable User Profiles — Development Roadmap

Maintained per the Unstoppable Solutions development standards. Items marked
**Completed** are verified in the current codebase; everything else is a
candidate for a future release. **Proposed** items have not been approved for a
milestone yet — treat them as suggestions open to revision or removal.

**Current released version:** 1.1.3 (live on WordPress.org)

Source legend: *Previous Discussion* (raised in prior planning), *Code Review*
(found by reviewing the current code), *New Suggestion* (proposed proactively).

---

## Phase 1 — Immediate Priorities

### Repository migration and verification
- **Description:** Full plugin source maintained in `Unstoppable-Solutions/unstoppable-user-profiles` as the source of truth.
- **Benefit:** Single canonical home for development and automated publishing.
- **Priority:** High · **Complexity:** Small · **Milestone:** 1.1.x
- **Status:** Completed · **Source:** Code Review

### WordPress compatibility metadata (7.1)
- **Description:** `Tested up to: 7.1` set in `readme.txt` (branch format covers 7.1.3).
- **Benefit:** Listing reflects current compatibility; avoids "outdated" warnings.
- **Priority:** High · **Complexity:** Small · **Milestone:** 1.1.x
- **Status:** Completed · **Source:** Previous Discussion

### SVN publishing workflow verification
- **Description:** Confirm `SVN_USERNAME` / `SVN_PASSWORD` repo secrets are set so `deploy-to-wporg.yml` can push on the next published release. (Secrets are not readable by tooling — owner to confirm in repo Settings → Secrets.)
- **Benefit:** First automated release succeeds instead of failing on missing credentials.
- **Priority:** High · **Complexity:** Small · **Milestone:** before next release
- **Status:** In Progress · **Source:** Code Review

### WordPress.org listing assets
- **Description:** Produce and commit `assets/icon-128x128.png`, `icon-256x256.png`, `banner-772x250.png`, `banner-1544x500.png`, and `screenshot-1/2/3.png` matching the three screenshots the readme already describes.
- **Benefit:** A complete, professional directory listing; higher install rate.
- **Priority:** High · **Complexity:** Small · **Milestone:** 1.1.x
- **Status:** Proposed · **Source:** Code Review

### Clean up avatar on user deletion
- **Description:** On `delete_user`, remove the user's stored avatar file and meta. Uninstall already purges everything, but deleting a single user currently leaves an orphaned file.
- **Benefit:** No orphaned uploads accumulate; tidier, more correct data handling.
- **Priority:** Medium · **Complexity:** Small · **Milestone:** 1.2.0
- **Status:** Proposed · **Source:** Code Review

---

## Phase 2 — Short-Term Improvements

### Email-change re-confirmation
- **Description:** When a member changes their email, require confirmation via the new address before it takes effect (WordPress core pending-email pattern).
- **Benefit:** Prevents typos locking members out and reduces account-takeover risk.
- **Priority:** Medium · **Complexity:** Medium · **Milestone:** 1.2.0
- **Status:** Proposed · **Source:** Previous Discussion

### Additional profile fields (phone, social links)
- **Description:** Optional, toggleable fields beyond the current set, with the same sanitize/escape discipline.
- **Benefit:** Richer member directories without a second plugin.
- **Priority:** Medium · **Complexity:** Medium · **Milestone:** 1.2.0
- **Status:** Proposed · **Source:** Previous Discussion

### ClubSuite native photo-slot integration
- **Description:** Populate ClubSuite's own profile-photo field (separate from core avatars) so uploads appear in ClubSuite UI that doesn't use `get_avatar()`.
- **Benefit:** Seamless photos for ClubSuite-based directories.
- **Priority:** Medium · **Complexity:** Medium · **Milestone:** 1.3.0
- **Status:** Proposed · **Source:** Previous Discussion

### Translation template (.pot) and i18n audit
- **Description:** Ship a `languages/unstoppable-user-profiles.pot` and verify all strings are translatable.
- **Benefit:** Opens the plugin to non-English sites and translators.
- **Priority:** Low · **Complexity:** Small · **Milestone:** 1.2.0
- **Status:** Proposed · **Source:** Code Review

### Default / fallback avatar setting
- **Description:** Let admins pick the image shown when a member hasn't uploaded one, instead of the Gravatar mystery-person.
- **Benefit:** On-brand directories even before members add photos.
- **Priority:** Low · **Complexity:** Small · **Milestone:** 1.3.0
- **Status:** Proposed · **Source:** New Suggestion

---

## Phase 3 — Mid-Term Development

### Client-side crop / reposition before upload
- **Description:** A simple cropper so members frame their photo before it saves.
- **Benefit:** Consistent, well-framed avatars; fewer re-uploads.
- **Priority:** Medium · **Complexity:** Large · **Milestone:** 1.4.0
- **Status:** Proposed · **Source:** New Suggestion

### Gutenberg block
- **Description:** A block equivalent of the `[uupr_profile_form]` shortcode.
- **Benefit:** Native editing experience for block-editor users.
- **Priority:** Low · **Complexity:** Medium · **Milestone:** 1.4.0
- **Status:** Proposed · **Source:** New Suggestion

### Explicit BuddyPress / bbPress support
- **Description:** Verified integration and tests for community plugins beyond the core-avatar override.
- **Benefit:** Confident compatibility claims for community sites.
- **Priority:** Low · **Complexity:** Medium · **Milestone:** 1.4.0
- **Status:** Proposed · **Source:** New Suggestion

### Upload rate limiting
- **Description:** Per-user throttle on uploads to deter abuse.
- **Benefit:** Protects storage and server resources on public sites.
- **Priority:** Low · **Complexity:** Small · **Milestone:** 1.4.0
- **Status:** Proposed · **Source:** New Suggestion

---

## Phase 4 — Long-Term Development

### Member directory display
- **Description:** A first-party shortcode/block rendering a grid of member cards (photo + chosen fields).
- **Benefit:** Delivers the end goal — the directory — without a separate plugin.
- **Priority:** Medium · **Complexity:** Large · **Milestone:** 2.0.0
- **Status:** Proposed · **Source:** New Suggestion

### Pro tier
- **Description:** Premium add-on (advanced fields, richer moderation, multiple photos/galleries) distributed outside WordPress.org.
- **Benefit:** Revenue stream; funds continued free-plugin maintenance.
- **Priority:** Low · **Complexity:** Large · **Milestone:** 2.0.0+
- **Status:** Proposed · **Source:** New Suggestion

### WebP conversion and responsive sizes
- **Description:** Store an optimized WebP plus sized variants; serve the right size per context.
- **Benefit:** Faster pages, less bandwidth on photo-heavy directories.
- **Priority:** Low · **Complexity:** Medium · **Milestone:** 2.0.0
- **Status:** Proposed · **Source:** New Suggestion

---

## Completed (released)

- **1.1.3** — Resolved Plugin Check input-sanitization warnings.
- **1.1.2** — Hid the redundant native "Profile Picture" row and Gravatar link.
- **1.1.1** — Hardened file handling to WordPress upload/filesystem APIs; removed silenced operators.
- **1.1.0** — Added WordPress profile-screen uploader and MemberPress "Profile Photo" account tab.
- **1.0.0** — Initial release: front-end profile editor, secure photo upload, core-avatar override, moderation.
