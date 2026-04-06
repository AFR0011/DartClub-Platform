# TESTING_CHECKLIST

## Purpose
Use this as the full manual QA path for the website. Run it top to bottom after meaningful changes so the public site, membership flow, tournaments, blog, gallery, and admin console all work together without regressions.

## Test Setup
1. Start Apache and MariaDB from XAMPP.
2. Confirm the database import is current and the app can connect.
3. Confirm the site loads from the intended local URL.
4. Prepare these accounts before you begin:
   - guest session
   - normal signed-in user with no membership
   - approved member
   - manager
   - admin
5. Prepare these data states if possible:
   - at least one open tournament
   - at least one closed/archived tournament
   - at least one tournament of each type: `Round Robin`, `League`, `Group`, `Elimination`
   - at least one published blog post
   - at least one draft blog post
   - at least one gallery image linked to a blog post
   - at least one standalone gallery image

## Logging Rules
For every failure, capture:
1. exact page URL
2. user role/state
3. exact inputs used
4. expected result
5. actual result
6. console error, network error, PHP warning, or DB error text
7. whether the bug reproduces consistently

## 1. Home Page
1. Open `pages/main.php`.
2. Confirm the page loads without PHP warnings, broken JSON errors, or console errors.
3. Confirm the header/nav renders fully on first load.
4. Confirm logo, hero, CTA buttons, and section anchors all render correctly.
5. Click each nav item and confirm it goes to the correct section or page.
6. Scroll through the full page and confirm active-nav highlighting does not throw errors.
7. Resize to tablet and mobile widths and confirm the mobile nav opens, closes, and does not trap the page.
8. Click logout from the shared shell when signed in and confirm it logs out cleanly.

## 2. Signup, Login, Reset
1. Open `pages/sign_up.html`.
2. Submit valid data and confirm account creation succeeds.
3. Try invalid email, weak password, mismatched password, and duplicate account inputs.
4. Confirm errors are clear and the page does not break.
5. Open `pages/login.html`.
6. Log in with a valid account and confirm redirect/session state is correct.
7. Try an invalid password and confirm the error is shown cleanly.
8. Open `pages/reset_password.html`.
9. Test valid and invalid reset attempts.
10. Confirm the auth pages keep their dedicated layout/footer and do not inherit broken public-nav behavior.

## 3. Profile Page
1. Log in as a normal user.
2. Open `pages/profile.html`.
3. Confirm dashboard/profile data loads without console or JSON errors.
4. Update profile fields with valid values and save.
5. Refresh and confirm values persist.
6. Try blank required values, unusually long values, and special characters.
7. Confirm validation is graceful and the page never hard-fails.
8. Confirm tournament history or related profile widgets still load after saving changes.

## 4. Membership Registration
1. Log in as a non-member user.
2. Open `pages/register.html`.
3. Submit a valid membership application.
4. Confirm success messaging is shown and the uploaded file is stored correctly.
5. Try unsupported file types and invalid missing-field submissions.
6. Confirm errors are clear and the page remains usable.
7. Confirm a user with pending or approved membership does not get contradictory messaging.

## 5. Public Tournaments Hub
1. Open `pages/tournaments.html` as a guest.
2. Confirm tournament cards load without errors.
3. Confirm open, closed, archived, and completed tournaments display sensible labels.
4. Open the filter controls and confirm they work.
5. Log in as a user with registrations and test the `My tournaments` filter.
6. Log in as a user without a player profile and test quick registration.
7. Log out and test guest registration for an open tournament.
8. Try duplicate registration and confirm it is blocked cleanly.
9. Try registration for closed, archived, and ended tournaments and confirm it is blocked cleanly.

## 6. Public Tournament Detail
1. Open `pages/tournament_details.php?id=<valid_id>` for each tournament type.
2. Confirm the hero, metadata, fixtures list, and explicit `Tournament Bracket` section render.
3. Confirm the public bracket loads without overlap at desktop, tablet, and mobile widths.
4. Click multiple bracket matchups and confirm the selected matchup detail changes cleanly.
5. Confirm participant names link to the public player profile page.
6. Confirm bracket/player links never expose admin-only editing controls.
7. Try invalid and missing tournament IDs and confirm the page fails gracefully.

## 7. Public Player Profile
1. Open `pages/player_profile.php?id=<valid_player_id>`.
2. Confirm the page loads with public-safe fields only.
3. Confirm tournament-related identity/history data is accurate.
4. Open it from a public tournament participant link.
5. Open it from a public bracket link.
6. Try invalid and missing player IDs and confirm graceful failure.

## 8. Blog Page
1. Open `pages/blog.html` as a guest.
2. Confirm the preview rail shows clearly separated post previews.
3. Click several preview cards and confirm the full article view updates without breaking layout.
4. Use previous and next post navigation in the article view.
5. Confirm draft posts are not visible to guests.
6. Log in as an approved member.
7. Open the `Create post` dialog and confirm it is visually separated from the reading flow.
8. Create a draft with:
   - no image
   - one image
   - multiple images
9. Confirm the full post shows multiple images as a gallery strip, not as one oversized raw image.
10. Log in as manager/admin and publish a draft.
11. Like and unlike a post.
12. Add and delete comments.
13. Delete a draft and confirm the page stays stable.
14. If opening `blog.html?post=<id>`, confirm the correct post opens directly.

## 9. Gallery Page
1. Open `pages/gallery.html`.
2. Confirm the gallery uses a consistent four-up desktop grid with titles underneath.
3. Resize to tablet and mobile widths and confirm the grid collapses cleanly.
4. Open a standalone image and confirm the lightbox works.
5. Open a blog-linked image and confirm the lightbox offers an `Open linked post` action.
6. Click through to the linked blog post and confirm the correct article opens.
7. As manager/admin, upload a standalone gallery image.
8. Delete a standalone image.
9. Remove a blog-linked image from the gallery and confirm the blog post still keeps its image.

## 10. Admin Panel And Navigation
1. Log in as admin.
2. Open `pages/admin/admin_panel.php`.
3. Click every admin nav item.
4. Confirm no admin page loads with PHP warnings or broken assets.
5. Confirm unauthorized direct access is blocked for non-admin/non-manager users.

## 11. Admin Players And Membership
1. Open `pages/admin/manage_players.php`.
2. Confirm membership applications load.
3. Filter applications by text and status.
4. Shrink the page width until action buttons no longer fit on one row.
5. Confirm application actions collapse into the dropdown cleanly.
6. Preview a PDF application.
7. Preview a DOC/DOCX application and confirm the fallback/open-in-new-tab behavior is sane.
8. Approve an application and confirm membership changes without changing auth role.
9. Reject an application and confirm notes flow works.
10. Use the player registry filter and sort controls.

## 12. Admin Users
1. Open `pages/admin/manage_users.php`.
2. Confirm role and membership are visually distinct.
3. Confirm the role selector itself acts as the role pill.
4. Filter users by username/email.
5. Sort by name, role, membership, oldest, newest.
6. Change a user role and confirm it persists.
7. Try deleting another user and confirm it succeeds only when allowed.
8. Try deleting the currently signed-in admin and confirm the self-delete guard still works.

## 13. Admin Tournament Creation
1. Open `pages/admin/manage_tournaments.php`.
2. Confirm the page loads with the improved player picker.
3. Filter and sort the available player list.
4. Select players by clicking the row as well as the checkbox.
5. Create a tournament with no initial players.
6. Create a tournament with a seeded player list.
7. Confirm invalid date combinations are rejected.
8. Confirm type-specific fields show and hide correctly for each tournament type.

## 14. Admin Tournament Detail - Shared Workflow
1. Open `pages/admin/show_tournament_details.php?id=<valid_id>`.
2. Change to a non-default section, such as `Tournament Bracket` or `Bracket Board`.
3. Save a match score.
4. Confirm the page does not hard-reset back to the default section.
5. Change a match date/time from the modal.
6. Confirm the page updates without kicking you back to the top/default view.
7. Switch between sections and confirm the section-toggle buttons stay visually consistent.
8. Confirm the current-player and available-player lists support filter/sort correctly.

## 15. Admin Tournament Detail - Match Modal
1. Open a match from the matches table.
2. Open a match from the connected bracket.
3. Confirm both paths open the same focused dialog.
4. Confirm the dialog only shows:
   - score controls
   - date/time controls
   - concise match context
5. Confirm low-level bracket wiring fields are not exposed in the day-to-day modal.
6. Try saving schedule only.
7. Try saving result only.
8. Try saving result when one slot is not seeded and confirm it is blocked clearly.
9. Confirm the modal closes and the page state remains stable after save.

## 16. Admin Tournament Detail - Bracket
1. Open an elimination or league-knockout tournament.
2. Confirm connected bracket matchups do not overlap visually.
3. Confirm matchups have clearer color meaning:
   - scheduled
   - ready/waiting
   - in progress
   - completed
4. Confirm winners and losers are visually distinguishable once a result is recorded.
5. Drag and drop seeded players between valid slots.
6. Confirm invalid drag/drop targets are blocked.
7. Confirm completed matches cannot be drag-reseeded.
8. Confirm the bracket board still reflects the latest state after changes.

## 17. Tournament Type Regression Matrix
Run these end to end:
1. `Round Robin`
   - create
   - record multiple results
   - confirm standings update correctly
2. `League`
   - create with groups
   - finish group stage
   - confirm knockout creation
   - confirm advancers are correct
3. `Group`
   - create teams
   - save team results
   - confirm team standings update correctly
4. `Elimination`
   - create even-player and odd-player brackets
   - confirm bye handling
   - confirm winner propagation through the full bracket

## 18. Blog And Gallery Cross-Integration
1. Create a blog post with multiple images.
2. Confirm each image appears correctly in the blog post gallery.
3. Confirm those images also appear in the gallery with the gallery page's normal card layout.
4. Open a blog-linked gallery image and jump back to the linked post.
5. Remove the image from the gallery only and confirm the blog post still keeps it.

## 19. Responsive And Abuse Pass
For `main`, `tournaments`, `tournament_details`, `profile`, `register`, `blog`, `gallery`, `manage_players`, `manage_users`, `manage_tournaments`, and `show_tournament_details`:
1. Check desktop width.
2. Check tablet width.
3. Check mobile width.
4. Try empty inputs.
5. Try very long text.
6. Try special characters and quotes.
7. Try invalid IDs in query strings where applicable.
8. Confirm no screen throws raw PHP warnings, JSON parse errors, or layout-breaking overflow.

## 20. Legacy And Fallback Pages
1. Open `pages/admin/manage_blogs.php`.
2. Open `pages/admin/image_upload.php`.
3. Open `pages/admin/record_match_result.php`.
4. Open `pages/admin/view_match_details.php`.
5. Confirm these still load without fatal errors, even if they are no longer the primary workflow.

## Exit Criteria
The run passes only if all of these are true:
1. public pages load without fatal PHP output or broken JSON contracts
2. auth and profile flows work cleanly
3. membership submission and admin review work cleanly
4. tournaments work across public and admin flows without hard resets or broken progression
5. blog and gallery work independently and together
6. admin tables, filters, sorts, and dialogs remain stable across viewport sizes
7. no confirmed blocker remains unlogged
