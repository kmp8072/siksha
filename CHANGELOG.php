<?php
/*

shezar LMS Changelog

Release 9.0 (19th October 2016):
================================


New features:

    TL-6874        Introduction of Font Icons

                   Font Icons have been made available through a new "flex icon" API.
                   Additionally the majority of icons used within shezar have been converted to use font icons.

                   Font icons bring exciting new possibilities when styling an icon.
                   With them you can use any standard CSS font styling to customise the icon, including changing its colour and size.
                   Additionally the font icons are fully scalable, and are not designed at a specific size.
                   shezar's font icon implementation is based upon the Font Awesome toolkit.

                   Information on using font icons, as well as how to work with them can be found in the developer documentation.

    TL-9031        Introduction of Roots, a new base theme for shezar

                   A new base theme has been created for shezar.
                   Called Roots it utilises Bootstrap 3 to produce a clean new look.

                   We strongly recommend that all new themes utilise the roots theme as a parent.

                   This theme is based on the Bootstrap theme (https://github.com/bmbrands/theme_bootstrap) produced and maintained by Bas
                   Brands, David Scotson, and other contributors.

                   For more information on the shezar 9 approach to theme management please refer to our developer documentation
                   https://help.shezarlearning.com/display/DEV/shezar+LMS+9+approach+to+theme+management

    TL-7468        Introduction of Basis, the new default theme for shezar

                   Basis is a new theme added to shezar, and now set as the default theme for all new installations.

                   Designed in house by our UX experts it brings a brand new look to shezar out of the box.

                   For more information on the shezar 9 approach to theme management please refer to our developer documentation
                   https://help.shezarlearning.com/display/DEV/shezar+LMS+9+approach+to+theme+management

    TL-9493        Repurpose the base theme

                   The base theme in shezar is no longer a stand alone theme.
                   It now contains only essential styles for the Font Icons.

                   It is strongly recommended that all themes use the base theme as a parent.
                   This will ensure that the new font icons will work out of the box for your theme.

                   Making your theme use the base theme is as simple adding "base" to the end of the parents array in your theme config.
                   For instance you should have something like the following:

                   $THEME->parents = array('roots', 'base');

    TL-7494        New shezar forms library

                   There is a new modern forms library available in shezar that can be used as an alternative to the current forms library.
                   The new library provides additional functionality not available in the Quick Forms based mform library.

                   This new forms library will be our library of choice for all future work.
                   It is designed to overcome the limitations of the current forms library and lend itself more towards use within the
                   current technologies embraced within shezar, including fully template driven, deliverable and translatable via
                   JavaScript and AJAX, easy introduction of new custom elements, full testable, more intuitive PHP API, and modular
                   extendable JavaScript API.

                   See the developer documentation for more details https://help.shezarlearning.com/display/DEV/shezar+forms+library

    TL-8208        Added support for hooks

                   Hooks were designed to improve interactions of plugins and simplify customisation. This release contains the hook
                   infrastructure and conversion of one sample area - the course editing form. We are planning to add hook support to more
                   areas in the future and welcome code contributions inserting new hooks where required.

                   See the developer documentation for more information
                   https://help.shezarlearning.com/display/DEV/Hooks+developer+documentation

    TL-8513        Added Current Learning block

                   The Current learning block is a new block which displays all learning that is active for the viewing user. This includes
                   courses, programs and certifications in a unified view, but excludes completed learning.

                   A new user_learning API has been introduced to provide a standard way to access learning data, currently four components
                   are supported:

                   *  Course
                   *  Program
                   *  Certification
                   *  Learning Plan

                   Other components and plugins can extend the user_learning classes if they wish to integrate with the current learning
                   block. Look in code for examples.

    TL-8514        Added Last Course Accessed block

                   The Last Course accessed block can be added to any page that supports blocks. It provides quick access to the course the
                   user last accessed.

    TL-7456        Added a new Location custom field

                   A new Location custom field has been added to shezar.
                   This can now be used anywhere custom fields can be used.
                   It allows a address to be entered and optionally displays a map with a pinned location that can be either the same as
                   the address or a secondary (dropped) location.

                   Please note that this location field uses the Google Map API in order to present a visual interactive map.
                   In order to use the visual map you will need to generate and provide shezar with a Google Maps API key.

    TL-7466        Added an optional regular expression validation for the "Text Input" custom field type.

                   Text Input custom fields can now be restricted to match particular format using PHP PCRE regular expressions.

    TL-7983        Moodle 3.0

                   This release of shezar contains new features and improvements introduced upstream in Moodle 3.0.0
                   Additionally fixes made in Moodle 3.0.0 through to and including 3.0.6 are included in this release.
                   For details please refer to the Moodle release notes.



New features - Seminar:

    TL-7461        The Face-to-face activity has been renamed to Seminar

                   All strings used in the interface that contained 'Face-to-face' have been updated.

                   Previously, a 'Face-to-face' activity contained a number of 'Sessions', which each contained some amount of 'Session
                   dates'.
                   'Sessions' have been renamed as 'Events' and 'Session dates' are now called 'Sessions'.
                   Strings containing these terms have been updated.

                   Face-to-face report sources and embedded reports have also had their names updated to reflect these changes, this is
                   only a display change and should not effect any existing reports.

                   There will be some language strings which still contain the old terms. These should not be used anywhere on the
                   interface. They are used to identify old notification messages and placeholders in Seminar notifications.

    TL-7453        New interface for bulk add/remove of users in Seminar events

                   Users in Seminar events are now added or removed in two steps: preparing a list of users and confirmation.

                   Custom fields have been added to the Sign-ups report source. Also, sign-up custom fields can be populated in a CSV file
                   upload for each user that will be added to the session.

                   The Attendees tab on the attendees page now also uses an editable embedded report for displaying data.

    TL-7455        Added sign-in sheet to download for Seminar events

                   A sign-in sheet can now be downloaded from the attendees tab in a Seminar event, which contains a list of learners
                   booked for that sessions along with spaces for signatures. Details such as start/end times and location are included in
                   the header of the sign-in sheet.

    TL-7458        Sign-up periods added to Seminar events

                   Sign-up start and end times can be added to a Seminar event. These restrict when a user can book into an event and when
                   managers can approve sign-up requests. If the user views the event outside of the sign-up period, dates will be shown
                   for when they will be able to sign up.

                   Setting these dates is optional. If just the end is set, then users will be able to sign up any time prior to that time.
                   If just the start is set, then users will be able to sign up any time from then until the start of the first session.

                   These settings will not prevent a Seminar admin from adding or removing users via the attendees page.

    TL-7463        Added session assets and room improvements

                   Seminar sessions can have assets associated with them. These may represent equipment or other items that are used during
                   the course of the session, such as a laptop or projector. Assets can be available globally or created and used for one
                   event. Multiple assets can be assigned to each session (formerly 'session date', of which there may be one or more in an
                   event).

                   Rooms may now be assigned per session rather than per event. During upgrade, any rooms assigned to an event will be
                   assigned to all sessions within that event.

                   The 'datetimeknown' field has been dropped as part of this process. Whether or not the date/times are known for an event
                   is determined in code by whether there are session records (in the 'facetoface_session_dates' table) associated with
                   that event. If an event had the 'datetimeknown' field set to unknown (value of 0), then any data associated with that
                   event in the 'facetoface_session_dates' table will be dropped during upgrade.

                   Custom fields have been added for rooms and assets. The fields for a room's 'building' and 'address' have also been
                   converted to default custom fields. The fields for these values have been dropped from the 'facetoface_room' table. Any
                   data in these fields will be transferred to the newly generated room custom fields during upgrade.

                   Report sources have been added for rooms, room assignments, assets and asset assignments.

                   As a result of the change from rooms being per-event to per-session, a system has been added for looping through
                   sessions within an event when substituting placeholders in a notification email.
                   Within a notification the placeholders [#sessions] and [/sessions] respectively indicate the start and end of a repeated
                   section.
                   That section of the email will be repeated for each session. Within each repeat, placeholders that are associated with
                   per-session data will be substituted with data for a given session. See our help documentation for further explanation.

                   The placeholders [alldates], [session:room], [session:venue] and [session:location] have been deprecated. These will
                   only be removed from the message when an email is generated, but no data will be substituted in. Placeholders such as
                   [session:room] can be replaced using the system above and new placeholders added for this purpose, for this case, it
                   would be [session:room:name]. In place of [alldates], a loop will need to be created which defines session start/end
                   times and other information specifically. The full list of placeholders relevant to these loops are available in the
                   help documentation.

                   Existing placeholders in notifications: If a template or specific notification exactly matches the default template for
                   that message in 2.9, it will be replaced with the new default. Otherwise, existing templates will have any [alldates]
                   placeholders replaced with an equivalent loop during upgrade, meaning the same message details should appear in
                   notifications. However, placeholders for room, location and venue cannot be replaced automatically as their relation to
                   each Seminar event is changing. If you have any notifications using room placeholders (and they are not exactly the same
                   as the 2.9 default template), you will need to update the notification to use the new tags for looping through sessions.

    TL-7465        Added new approval options to Seminars

                   Previously the approval options were limited to "No approval required", "Approval required", or in a separate setting
                   "Self approval" with terms and conditions. These have all been grouped into one setting, "Approval required" has been
                   renamed to "Manager Approval", and two new approval options "Role Approval" and "Manager and Administrative Approval"
                   have been added.

                   Role Approval:
                   To enable role approval you first need to make the desired role assignable at the event level by selecting it in the
                   "Event roles" setting on the site administration > Seminars > Global settings page. When the role is assignable a
                   corresponding option will be displayed under the available approval options setting on the same page, you may have to
                   save the page before it shows up. When a Seminar is using a role approval option, approval requests will be sent to
                   everyone assigned with the specified role in the seminar event. Any of those same users can then go to the seminars
                   pending requests tab and approve or deny the requests.

                   Manager and Administrative Approval:
                   This is a 2 step approval option. First the request must be approved by their manager, the same as "manager approval".
                   Then they must be approved by a "Seminar Administrator". Seminar administrators are a combination of site level
                   administrators and seminar (activity) level administrators. Site level administrators are users with the
                   "mod/facetoface:approveanyrequest" capability at site level, who have been selected in the "Site level administrative
                   approvers" setting on the site administration > Seminars > Global settings page. Seminar level administrators are any
                   user who has been selected with the "add approver" dialogue on the edit seminars page underneath the "admin approval"
                   option. When a user requests signup with this setting their manager and all of the administrators are sent the request,
                   the manager must first approve the request, then any one of the admins must approve it.

                   Another new setting on the site administration > Seminars > Global settings page, is the "Users select manager" setting.
                   This setting is for when manager approval is required but manager assignment data is not available. It prompts the user
                   to select their manager from a dialogue when they attempt to sign up, the selected user is then treated as their manager
                   for the rest of the approval process.

    TL-7944        Added seminar minimum bookings status and notification

                   This utilises a minimum booking setting that allows the user managing a Seminar activity to specify the minimum number
                   of attendees required for the event.
                   The value of this setting is then used when reporting on the status of the event, and can be used to trigger
                   notifications.

    TL-8187        Seminar events can now be cancelled

                   Seminar events can be cancelled by a Seminar admin. The event's information will remain, allowing it to be viewed in
                   reports. However, users will not be able to book into the event and the event's details can not be updated.

    TL-9675        Any one of a user's managers can approve their Seminar signup

                   The setting 'Select job assignment on sign-up' replaces 'Select position on sign-up'. With this setting on, a learner
                   with multiple job assignments will need to choose which job assignment they are signing up under.

                   Where learners have multiple managers (by having multiple job assignments), there are several possible scenarios for
                   sending out Seminar notifications to managers  (such as approval requests):

                   1: If the setting 'Select job assignment on sign-up' is on, notifications will go to the manager related to job
                      assignment chosen by the user.
                   2: If the setting 'Select job assignment on sign-up' is off, notifications will go to all of the user's managers.
                   3: If the 'Users select manager' site setting is turned on, then regardless of whether a job assignment was selected,
                      manager notifications will go to the manager chosen during sign-up. For more information on the 'User selects manager'
                      setting, see change log entry TL-7465.



New features - Multiple jobs:

    TL-8945        Multiple job assignments

                   Multiple job assignments can be defined for each user. Existing data in primary and secondary positions was converted to
                   job assignments. See the related changelog entries for specific uses of this feature.

    TL-9513        Added "Allow multiple job assignments" setting

                   By default, multiple job assignments are enabled.
                   By disabling this setting, only one job assignment can be created for each user.
                   HR Import will also prevent uploading multiple job assignments for a single user when disabled.
                   Any existing multiple jobs within the system when the setting is disabled will still remain, they will not be
                   automatically removed. They will continue to function until they are manually removed.

    TL-8946        HR Import now supports Job Assignments

                   The HR Import User source has been changed to import into job assignments, rather than the deprecated position
                   assignments:

                   *  The option "Link job assignments" has been added to the User source. The effects of this option are detailed below.
                   *  The column "Job assignment ID Number" has been added. This field relates to the ID Number field in the user's job
                      assignments. If "Link job assignments" is set "to the user's first job assignment" when you import user records, the
                      imported record will be linked to the user's "first" job assignment, and if the "Job assignment ID Number" field is
                      included then it will be updated with the specified value. If "Link job assignments" is "using the user's job
                      assignment ID number" when you import user records, the imported records will be linked to the user's existing job
                      assignment records using the ID Number.
                   *  If job assignment data is specified in the import and the matching job assignment record does not already exist, it
                      will be created.
                   *  The column "Manager's job assignment ID Number" has been added. This is required when "using the user's job
                      assignment ID number". If specified, the specific job belonging to the manager will be linked to the user's job
                      assignment. If "Link job assignments" is set "to the user's first job assignment", the manager's "first" job
                      assignment will be linked to the user's job assignment.
                   *  The column "Position title" has been renamed "Job assignment full name".
                   *  The columns "Position start date" and "Position end date" were renamed "Job assignment start date" and "Job
                      assignment end date".

                   Special care should be taken by sites upgrading to shezar 9.0 who used HR Import to populate the old "primary position",
                   and who wish to import multiple job assignments. During upgrade, all existing position assignments were made into job
                   assignments. Their ID Numbers were set to their location in the users' job assignment list. It is important to note that
                   after upgrading, the location in the list of job assignments is not connected to the ID Number - if a user's first and
                   second just assignments are swapped, it would result in the first job assignment having ID Number "2" and the second
                   would have "1". Therefore, after upgrading, before the site goes live, if your HR management system already has a system
                   of ID Numbers used to identify job assignments, and you want to use this value rather than the default "1" and "2", then
                   you should perform the following steps:

                   1. Upgrade to shezar 9.0 or later.
                   2. With "Link job assignments" set "to the user's first job assignment" and column "Job assignment ID Number" enabled,
                      import the ID Numbers from your HR management system into the "first" job assignments (previously primary position
                      assignments). Note that your import should only contain the "primary" job assignment for each user at this stage.
                   3. At this stage, your "first" job assignments have the ID Number from your HR management system.
                   4. If you had previously set up old "secondary position assignments", they will now exist as "second" job assignments
                      with ID number "2". You now need to manually update the ID Number in each of them to the ID number from your HR
                      management system.
                   5. Finally, set "Link job assignments" to "using the user's job assignment ID number". After the first import using this
                      setting, it will not be possible to change it back (the setting will be removed from the interface). The "Job
                      assignment ID Number" column will be required when importing job assignment data. Any number of job assignments, each
                      identified by the "Job assignment ID Number" field, can now be imported in a single upload.

    TL-8947        Updated the embedded "My team" report to work with multiple job assignments

                   The embedded "My Team" report has been updated to show all of the manager's staff members across all of their job
                   assignments.

    TL-8948        Updated the dynamic audience rules to work with multiple job assignments

                   Dynamic audience rules based on position assignments previously only applied to users primary positions, these have been
                   replaced with new job assignment rules that apply across all of a user's job assignments. This means that for sites
                   currently only using primary and not secondary position assignments, existing audience membership will not change on
                   upgrade. However sites currently using secondary position assignments might see audience memberships change on upgrade.
                   If you do not want secondary position data to be considered when reviewing dynamic audience memberships then you will
                   need to remove secondary position data from your users prior to upgrade.

                   The position and organisation audience rules have been combined under a new "All Job Assignments" header, the affected
                   rules are:

                   *  Titles (New - The job assignment full name)
                   *  Start dates (Previously: Position Start Date)
                   *  End dates (Previously: Position End Date)
                   *  Positions
                   *  Position Names
                   *  Position ID Numbers
                   *  Position Assignment Dates
                   *  Position Types
                   *  Position Custom Fields
                   *  Organisations
                   *  Organisation Names
                   *  Organisation ID Numbers
                   *  Organisation Types
                   *  Organisation Custom Fields
                   *  Managers
                   *  Has Direct Reports

    TL-8949        Updated the report builder columns, filters, and content options to work with multiple job assignments

                   Report builder columns, filters and content options based on a users position assignment previously only displayed or
                   checked the users primary positions.
                   These have been replaced with new job assignment columns, filters and content options that apply across all of a users
                   job assignments. The new columns are concatenated and display one job assignment field per line, with a spacer "-"
                   displayed for empty fields. The new filters now check whether any of the users job assignments match the specified
                   constraints, and if so will display all the data. These columns have been moved from the "User" header and are all now
                   located under a new "All User's Job Assignments" header to make them easier to find. The User, Organisation, and
                   Position content options are now applied across all of the users job assignments. This means if a report has a content
                   option set to "Show user's direct reports" and a user has three job assignments, all three managers would see the user
                   when viewing the report.

                   Note: Concatenated columns can not be sorted, and there are no concatenated text area columns or filters since there is
                   no way to display them inline. Any existing columns or filters for position and organisation text area custom fields, or
                   framework descriptions, will be lost on upgrade.

    TL-9524        Managers are selected via their job assignment

                   When assigning a manager to a staff member, both the manager and the job related to that manager-staff relationship can
                   be selected.

                   If the user has the 'shezar/hierarchy:assignuserposition' capability in the manager's context or in the system context,
                   they will be able to create an empty job assignment for the manager within the selection dialog and assign that as the
                   job that the manager has this staff member under.

                   As part of this change, the selector dialog for choosing a manager or temporary manager will now return a job assignment
                   id as well as a manager id. The job assignment id can only be empty when the user has the capability described above, in
                   which case the new job assignment will be created.

                   If a user selecting a manager does not have permission to create the empty job assignment, and the manager currently has
                   no job assignments, a new job assignment must be created by someone with permission to do so before that manager can be
                   assigned a staff member.

    TL-9531        Updated program relative due dates to work with multiple job assignments

                   The relative to 'position start date' rule has been renamed to 'job assignment start date' and, along with the relative
                   to 'position assigned date' rule, has been changed to work across all of a users job assignments. When a user has the
                   same position in several job assignments, the maximum due date will be selected.

    TL-9677        Appraisals have been modified to work with the new job assignments feature.

                   Take note that the workflow for appraisals has changed:
                   1. Create appraisal; assign appraisees; fill in the content as per normal
                   2. The following has changed when an appraisal is activated:
                       *  Previously the system warned against missing roles and disallowed activation if it was a static appraisal. In 9.0,
                          there is no longer a check here for missing roles since that is tied in with the job assignment an appraisee links
                          to his appraisal.
                       *  The job assignment is linked only when the appraisee sees the appraisal for the very first time
                       *  Now, as long as the appraisal has assigned appraisees and questions, it can be activated, even if the appraisal is
                          static.
                   3. The following has changed when an appraisee opens their appraisal:
                       *  If the appraisee has multiple existing job assignments then they must now select a job assignment to link to the
                          appraisal. They cannot proceed with the appraisal until a job assignment is selected.
                       *  If the appraisee has only one job assignment then it is automatically linked to the appraisal.
                       *  If the appraisee doesn't have an existing job assignment then a "default" one is created and automatically linked to
                          the appraisal.
                       *  Only now does the system register the managers and appraisers based on the job assignment the appraisee selects.
                   4. The rest of the appraisal workflow is the same as in previous versions.

    TL-9468        Converted the user aspirational position into a user profile field

                   On upgrade to 9, aspirational position data will be migrated into the new profile field. This is because it behaves
                   differently to existing job assignments and serves a different purpose (a target position, rather than current one).

                   At present, aspirational position data is informational only, however in the future we may add gap analysis
                   functionality that makes use of this value.



Improvements:

    TL-5143        Added job assignment title as a filter for dynamic audiences

                   This was originally contributed by Aldo Paradiso to allow dynamic audiences based on primary position titles.  However,
                   with the new job assignments feature in 9.0, this has been reworked to use job assignment titles instead - specifically
                   job assignment full names.

    TL-5946        Certification block now uses its own duedate string

                   Previously the certification block was using strings belonging to programs.
                   It now has its own versions of these strings, allowing them to be translated with specific reference to the block.

    TL-6243        Added the ability to automatically create learning plans when new members are added to an audience

                   This improvement extends the manual learning plan creation within an audience by saving the creation configuration
                   settings and dynamically creating learning plans when new members are added to the audience, based on the saved
                   configuration.

    TL-6578        Fixed dynamic audiences with user profile custom fields so default field values work

                   It is possible to use checkbox, menu and text custom fields within dynamic audience rules for user profiles. However,
                   the default values for these fields have never been considered when computing the membership of the dynamic audience.
                   This fix allows the system to do so.
                   Take note: this fix may cause audience changes upon upgrading.

    TL-6643        SCORM serving scripts now have explicit HTTP headers to consistently prevent caching problems
    TL-6879        Changed HR import scheduling to use scheduled tasks scheduling

                   Scheduling now uses the core scheduled task scheduling, allowing for more fine grained control of scheduling. The UI on
                   within HR Import hasn't changed but will be disabled if the schedule is too complex to be displayed. A complex schedule
                   can be created using the scheduled task scheduler.

    TL-7060        Made appraisals CSS classes more specific
    TL-7332        Removed appraisal question options for disabled functionality

                   Previously you could add "course from plan" review questions to your appraisal even if learning plans had been disabled
                   for your site, these types of question should now only be available if the associated feature(s) are enabled.

    TL-7386        Improved the run time of report builder column unit tests
    TL-7452        Improved Seminar navigation

                   The Seminar administration menu has been moved from the plugins menu (Site administration > Plugins > Activity modules >
                   Seminar) to the Site administration root (Site administration > Seminar).
                   Additionally the settings have been regrouped depending upon the scope of their influence, for example the "Available
                   approval options" setting is located under "Global settings" as it determines the available options for all Seminars.

    TL-7459        Added links to further information on each room when signing up
    TL-7460        Improved Seminar summary report source

                   Seminar summary report has a number of new columns and filters that allows the user to get more detailed information
                   about Seminar events.

    TL-7621        Seminar add/remove attendees selectors now use the user identity setting
    TL-7795        Improved the handling of signup requests when switching approval types

                   Fixed user's status code from REQUESTED to BOOKED/WAITLISTED depending from session capacity when Seminar is changed
                   from approval required to not

    TL-7914        Removed report builder filter HTML when no filters are displayed
    TL-7963        Added the ability to use custom fields within evidence

                   On upgrade the description, evidencelink, institution and datecompleted database dp_plan_evidence fields will be deleted
                   with their data being transferred into new evidence custom fields.

    TL-8023        Added a new option "All courses are optional" to program coursesets

                   You can now define program course sets that don't require any of the courses to be complete. Note that the course set
                   will automatically be marked as complete when it is reached and will potentially contribute towards the progress in the
                   program. If you use this option with OR or THEN operators, you might find that portions of the program are automatically
                   completed before the user is required to do anything.

    TL-8043        Improved capability handling on the Tag Management pages
    TL-8116        Extended the file input custom field to support multiple files
    TL-8118        Added the ability to import evidence custom field data in the completion Import tool

                   Evidence Custom field data can be included in the import by adding columns to the CSV file named with the prefix
                   'customfield_' followed by the custom field shortname.

                   Custom fields of type 'file' and 'multiselect' can not be uploaded.

                   On install or upgrade, an evidence 'Date Completed' custom field is created. This field will be used to store the
                   completiondate value from the CSV upload file.

                   Also, on install or upgrade,  an evidence 'Description' field is created. If this field is specified in the CSV upload
                   file, the content from the CSV upload file will be used. If the content is empty, the default value set in the custom
                   'Description' field will be used. If the 'Description'  field is not specified in the CSV upload file, an auto-generated
                   description will be created, based on the course completion data and stored in the 'Description' field.

    TL-8168        Improved method of sending Seminar calendar events

                   Previously the events were sent as ics file attachments, this practice is not recommended any more for security reasons.
                   The calendar event is now embedded in the email.

    TL-8181        Improved alignment in the add/edit scheduled report form
    TL-8182        Re-implemented manual course completion archiving

                   You can manually archive all completions for a course by going to Course administration > completions archive page, this
                   has been restricted to courses that are not part of programs or certifications to avoid flow on effects.

    TL-8186        Added user first_name string as placeholder for new user welcome email
    TL-8272        Dashboards can now be cloned

                   The ability to clone dashboards has been added to shezar 9.0
                   Cloning a dashboard creates a copy of the original dashboard, the blocks that have been added to it and any audience
                   assignments that have been made.
                   Cloning does not copy any user customisations to the dashboard.

    TL-8383        Activity completion reaggregated during scheduled task after unlock and delete

                   Previously, when activity completion was unlocked and deleted, completions were recalculated immediately (based on
                   grades already attained for example). However this led to performance problems given it was being recalculated for each
                   user.

                   Activity completions will now be set to incomplete (rather than deleted) immediately and will then be flagged for
                   reaggregation. The reaggregation will happen on the following run of the \core\task\completion_regular_task which, by
                   default, is run every minute.

                   With completions being set to incomplete rather than deleted, this means the record remains (where it was previously
                   just deleted). This has the advantage of retaining whether an activity was viewed by a user (if that was recorded).  If
                   an activity was manually completed, this information is still not retained.

                   When the state is set to incomplete, the timecompleted field is also set to null (if it wasn't already).

                   A new field, named 'reaggregate', has been added to the course_modules_completion table. This is an integer field for
                   storing the UTC timestamp of when it is set.

    TL-8413        Enabled URL custom fields to display as working links on view Hierarchy details page
    TL-8459        Added landing page for Session Attendees to allow user to select Seminar event attendees and then view a report.
    TL-8465        Added 'Sign-up Period' column to Seminar Events and Sessions reports
    TL-8469        Added Seminar Event registration expired notification
    TL-8497        The language menu is now displayed on the login page if multiple languages are installed
    TL-8515        Reorganised the main menu navigation defaults and dashboard default blocks

                   The main menu has been reorganised with new naming and simplified content. On upgrade the existing content will be left
                   but the new menu can be selected by clicking on "reset menu to default configuration" in "Appearance > Main menu".

                   The My Learning page has been removed. The contents has been moved into a "Legacy My Learning" shezar dashboard which is
                   hidden by default. There is a new My Learning dashboard shown to all logged in users which has new default content. You
                   can switch back to the old My learning by changing the visibility settings on "Appearance > Dashboards".

                   The defaulthomepage options have been simplified.

    TL-8516        Added "Visible to all users" option to Dashboards which makes it accessible to every logged in user
    TL-8569        Applied correct style to "Join Waitlist" link for a Seminar event.
    TL-8578        Changed Seminar signup page to show overlapping signup warning
    TL-8636        Added pagination when reviewing attendees to be added or removed from a Seminar session
    TL-8637        Removed "Face-to-face" block

                   This block's functionality is superseded by current Seminar Reportbuilder sources.

    TL-8647        Added option to specify behaviour of empty strings in HR Import

                   When importing data from CSV files, there is a new option to determine the behaviour of empty strings. Option "Empty
                   strings are ignored" means that any empty field in the import will be ignored and existing data will be unchanged, while
                   option "Empty strings erase existing data" means that any empty field in the import will cause the existing data in that
                   field to be erased.

                   External database sources are unaffected by this change. Fields set to null will leave the existing data unchanged while
                   empty strings will cause the existing data to be erased.

    TL-8652        Improved URL references in the template library

                   Previously URL references in the template library were largely dependant on the developers setup. With this change, URLs
                   are adjusted to suit whatever the setup is of the person viewing the template library (assuming the mustache template
                   uses __WWWROOT__ and __THEME__ placeholders, as created when using
                   \tool_templatelibrary\exampledata_formatter::to_json())

    TL-8713        Improved diagnostic messages in scheduled report task execution
    TL-8724        Improved the user workflow when completing actions within a Seminar activity
    TL-8767        Rephrased help text for room scheduling conflict option
    TL-8779        Removed non-standard 'back to' navigation links from Learning Plans and Record of Learning
    TL-8786        Refactored Seminar asset and room "allow conflicts" data storage
    TL-8814        Made Seminar Room name a required field
    TL-8853        Improved a number of JavaScript modules to meet current coding standards

                   The following JS modules have been improved to meet current coding standards:

                   *  Category manager
                   *  Acceptance testing JS
                   *  Reportbuilder graphical reporting

    TL-8863        Introduced a new API for adding custom role access restrictions in Reportbuilder

                   Previously the role access restrictions were implemented in Report builder lib.php file. This meant that plugin
                   developers could not add custom restrictions without modifying the report builder.

                   All access restriction code was refactored to to use new rb\access class namespace and a new discovery mechanism was
                   added to allow any plugin to define new access restriction classes for all other reports. See
                   shezar/reportbuilder/classes/rb/access/base.php file for more information.

                   All pre-existing role access code customisations need to be updated to use this new API.

    TL-8871        Improved category expander JavaScript to meet current coding standards
    TL-8886        Moved Seminar Default minimum bookings setting to Event defaults
    TL-8892        Fixed sign-up time column option in Seminar events report builder
    TL-8895        Added Events report source and embedded report
    TL-8900        Added "Description of regular expression validation format" option to text input custom field
    TL-8915        Created a "booking options" section when adding Seminar event attendees
    TL-8940        Improved display of capacity for waitlisted users of waitlisted events
    TL-8950        Learning plan approval requests are now sent to all managers

                   With the ability to have several managers by having multiple job assignments, approval requests and other learning plan
                   notifications will now go to all of the learner's managers. All of the learner's managers will have the ability to
                   approve, decline or delete a user's learning plan or its components.

    TL-8968        Converted course edit form customisations to proper hooks

                   This change introduced three new hooks:

                   1. \core_course\hook\edit_form_definition_complete
                      Gets called at the end of the course_edit_form definition.
                      Through this watcher we can make any adjustments to the form definition we want, including adding shezar specific
                      elements.

                   2. \core_course\hook\edit_form_save_changes
                      Gets called after the form has been submitted and the initial saving has been done, before the user is redirected.
                      Through this watcher we can save any custom element data we need to.

                   3. \core_course\hook\edit_form_display
                      Gets called immediately before the form is displayed and is used to initialise any required JS.

    TL-9123        Added rel="noreferrer" to links being displayed by URL custom fields
    TL-9127        Added Grunt tasks for working with Less

                   Less may be used to author plugin styles by providing a less directory containing a styles.less file e.g.
                   local/myplugin/less/styles.less. Theme less files if found are compiled and a RTL equivalent generated.

    TL-9141        Added back-end validation methods to date/time custom fields.
    TL-9156        Removed the setting "Users can enter requests when signing up" from the Seminar module

                   The setting "Users can enter requests when signing up" previously governed whether the ability to add text (a 'sign-up
                   note') was available to the user when they signed up. Options like this are now based on whether there are event custom
                   fields enabled.

    TL-9237        Added support for unique index on nullable column
    TL-9256        Improved directory removal to prevent race conditions from file and directory removal
    TL-9259        Theme RTL stylesheets are now served separately

                   This update allows developers to provide a RTL equivalent for any theme stylesheet by providing a file with an -rtl
                   suffix e.g. shezar.css and shezar-rtl.css. Where found the RTL theme sheet will be cached and served instead for RTL
                   languages.

    TL-9267        Improved display of SCORM in popup windows
    TL-9274        Review and clean up of all old shezar update code

                   All shezar upgrade code has been reviewed and tidied up to greatly reduce the likelihood of upgrade conflicts being
                   encountered.

                   The following changes have been made:

                   *  The shezar_core upgrade is now executed immediately after core upgrade and install before any other plugins
                   *  There are only two shezar pre upgrade scripts - one executed once before migration from Moodle and other gets
                      executed before every shezar upgrade
                   *  shezar blocks are using standard install.php scripts
                   *  All language packs are updated only once
                   *  The upgrade sections were renamed so that the first heading is "shezar", then "System", then  "shezar_core" and then
                      the rest of plugins
                   *  Custom field capabilities were cleaned up
                   *  Migration of badge capabilities from old installations was improved
                   *  Added detection and automatic fixing of capabilities migrated from plugins to core
                   *  Removed unnecessary upgrade flags from shezar_core and shezar_cohort
                   *  Fixed shezar_cohort upgrade.php to use $oldversion instead of previous release hack
                   *  Removed code duplication from shezar_upgrade_mod_savepoint()
                   *  Moved shezar_completionimport upgrade code to shezar_completionimport
                   *  Fixed incorrect include in shezar menu upgrade

    TL-9322        Provided Less compilation --themedir option

                   Allows a developer compiling Less via Grunt to pass an optional parameter 'themedir' to add a custom theme directory (as
                   per $CFG->themedir) to the Less import search path.

    TL-9353        Increased the Seminars CALENDAR_MAX_NAME_LENGTH constant

                   Previously seminar names were being truncated to 32 characters while creating calendar events, this has been changed to
                   256 characters. And can now be overridden in config.php using "define('CALENDAR_MAX_NAME_LENGTH', 32);" if you want to
                   continue shortening the name.
                   Note: this will not change any existing calendar events, but can be updated by editing the associated seminar.

    TL-9430        Added the ability to include partials dynamically into a Mustache template

                   This allows a list of items to be displayed differently depending on the type of an item using partials within a
                   Mustache template.

    TL-9514        Report builder scheduled report email message string 'scheduledreportmessage' now supports markdown format including html tags
    TL-9539        Embedded reports and sources may override exported report headers

                   Developers may now customise headers in exported reports by adding new method get_custom_export_header() to the embedded
                   report or source class. Non-null value overrides the standard report header in exported reports.

    TL-9676        Updated the programs extension requests to work with multiple job assignments

                   When a user requests an extension, messages will now be sent to all of the user's managers, across all of their job
                   assignments. Any of the managers can then go and approve or deny the extension request.

    TL-9727        Manager's job assignment is selected when assigning program via management hierarchy

                   When assigning management hierarchies to a program, one or more of the manager's job assignments must be selected. Only
                   staff managed under the selected job assignments will be assigned to the program via this method.

                   Any custom code using assignment via management hierarchy must use the ASSIGNTYPE_MANAGERJA constant (which has a value
                   of 6) rather than the ASSIGNTYPE_MANAGER constant (which as a value of 4). You will need to ensure that any calls to
                   shezar functions using this assignment type use the manager's job assignment id in place of where the manager's user id
                   was being used. Data in the prog_assignment table will updated during upgrade.

    TL-9765        Improved template handling in situations where the active theme no longer exists
    TL-9774        Use system fonts

                   This change updates the Roots theme to use the appropriate sans-serif system font for the user's operating system. This
                   ensures that the font is clear, readable and available, and that languages such as Hebrew and Arabic will be displayed
                   correctly.

    TL-9791        Converted the main menu to use use renderers

                   The main menu can now be overridden using Mustache templates improving the ease of customisation

    TL-9792        Visual improvements to the admin notification page to bring it inline with the new themes
    TL-9805        Changed the default theme to Basis
    TL-9809        Updated PHP doc for has_config() method
    TL-9817        Improved displaying of custom field dates
    TL-9839        Deprecated broad CSS in Appraisals
    TL-9866        Standard shezar Responsive now aligns form labels to the left
    TL-9944        URL type custom fields can now be imported through HR Import
    TL-9979        Fixed the constructor of the QuickForm custom renderer class for PHP7
    TL-9995        Reportbuilder titles are now filtered for multilingual content
    TL-10019       Fixed visual display of SCORM reports
    TL-10032       Assignments to programs and certifications take into account all of a user's job assignments

                   With the addition of the multiple job assignments functionality, assignments to a program or certification via position,
                   organisation or management hierarchy will include users with the relevant settings (such as organisation) in any of
                   their job assignments.

                   When upgrading, be aware that any positions, organisations and manager assignments in a users second job assignment
                   (formerly called secondary position assignment) may lead to users being assigned to additional programs and
                   certifications.

    TL-10115       Improved the display of the miniature calendar used in the calendar block and calendar interface

                   Calendar plugins can now specify an abbreviated calendar name within the structure::get_weekdays method. This should be
                   the shortest possible abbreviation of the day, and is used predominantly within the calendar when displayed as a block
                   in a small space.

    TL-10130       Re-approval for a previously declined Seminar event no longer results in a debugging message
    TL-10137       A warning about deleted fields has been added to user source configuration in HR Import

                   HR Import will allow you to add job assignments via a User source. The deleted field contained in this source only
                   applies to deleting users and should not be used for attempting to delete job assignment data.

                   HR Import currently only allows job assignment records to be added or updated. It does not allow deletion of job
                   assignment records. However that can be achieved via a user's profile.

                   A warning regarding this has been added to the user source configuration in HR Import. However, anyone who may edit HR
                   Import data sources should also be made aware of this information.

    TL-10182       Fixed a number of layout and appearance issues on the course management page.
    TL-10402       Removed calendar icons from frozen form elements
    TL-10429       'Process messages and alerts' task was split in to 'Dismiss alerts and tasks after 30 days' and 'Cleanup messaging related data'
    TL-10452       Minimum required browser version were increased

                   It is recommended to use only browser that are supported by their manufacturer, minimum versions for shezar are:

                   *  recent Chrome
                   *  recent Firefox
                   *  Safari 9
                   *  Internet Explorer 9

    TL-10597       Improved program and certification progress calculation to exclude 'Optional' courses
    TL-10655       Hyphen and full-stop characters are now valid within the Reportbuilder scheduled report export path setting
    TL-10704       Ensured hierarchy AJAX is disabled when hierarchies have been disabled in Advanced features
    TL-10908       All current Bootstrap 2 themes have been deprecated

                   With the arrival of the new Bootstrap 3 themes we have deprecated the current Bootstrap 2 themes.
                   This includes the following themes:

                   *  Bootstrap Base
                   *  Standard shezar Responsive
                   *  Custom shezar Responsive
                   *  Kiwifruit Responsive

                   Deprecation is the first step towards eventual removal. These themes will remain in core for one more major release and
                   may then be moved out of shezar core.
                   They will still be made available for those who want to use them, but will not longer receive regular updates.


Accessibility improvements:

    TL-6292        Improved the HTML markup used when viewing Reportbuilder reports

                   Previously Reportbuilder reports had all the controls inside an HTML table, and when there were no results, it was
                   displayed as a table with one cell. This improvement moves the controls outside of the report table, and replaces the
                   table when there are no results. Themes may have to have their CSS changed to accommodate this.

    TL-7740        The add/remove site administrators user interface no longer uses HTML tables for layout
    TL-7742        The lanugage pack importer user interface no longer uses HTML tables for layout
    TL-7837        The add/remove role user interface no longer uses HTML tables for layout
    TL-7840        The user interface for assigning user's to audiences no longer uses HTML tables for layout
    TL-7843        The user interface for managing group membership no longer uses HTML tables for layout
    TL-7861        The Seminar event allocation user interface no longer uses HTML tables for layout
    TL-7863        The Seminar add/remove attendees user interface no longer uses HTML tables for layout
    TL-7875        The user interface for managing groupings within a course no longer uses HTML tables for layout
    TL-7877        The Feedback activity add/remove user's user interface no longer uses HTML tables for layout
    TL-7880        The user selector used for enrolling users into a course no longer uses HTML tables for layout
    TL-7882        The user interface for managing webservice user's no longer uses HTML tables for layout
    TL-7885        The user interface for awarding badges no longer uses an HTML table for layout
    TL-7890        The user interface for managing Forum subscribers no longer uses HTML tables for layout
    TL-7924        The audience rules user interface no longer uses HTML tables for layout
    TL-7996        Removed duplicate HTML id's when adding linked competencies
    TL-8530        Removed tables for layout when viewing items from a learning plan
    TL-8598        Improved Aria accessibility experience within Seminar
    TL-8913        Improved accessibility when removing users from a feedback360 request
    TL-9164        Linked custom URL field text to their appropriate input elements
    TL-10241       Added alternative text to badge images
    TL-10259       Improved accessibility when using multi check filters in report builder reports
    TL-10262       Improved the HTML markup on the My Badges page
    TL-10263       Added a legend when searching your badges
    TL-10264       Linked reason input field in a learning plan to a label
    TL-10265       Added a legend to the report builder export functionality
    TL-10269       The completion status interface within the course completion status block no longer uses an HTML table for layout
    TL-10271       Improved accessibility around creating a calendar event
    TL-10272       Added a label to the move forum post dropdown
    TL-10277       Removed labels that were not required when displaying date fields
    TL-10279       Improved accessibility when adding groups of users to a goal
    TL-10283       Replaced unnecessary headings in badges.
    TL-10309       Added accessible label when viewing custom checkboxes on a users profile


Technology conversions:

    TL-7947        Converted shezar_program_completion/block.js to an AMD module

                   The M.block_shezar_program_completion JavaScript code has been converted from a statically loaded JS file to an AMD
                   module.
                   This allows the JS to be loaded dynamically with much greater ease and unlocks the benefits AMD brings such as
                   minification and organisation.

                   This change removes the block/shezar_program_completion/block.js file.

    TL-7950        The JavaScript loaded by the report table block has been converted to an AMD module and is only loaded when required
    TL-7951        The JavaScript loaded by element library has been converted to an AMD module
    TL-7959        The instance filter as used in the report builder been converted to an AMD module
    TL-7961        The cache now JavaScript as used in the report builder been converted to an AMD module
    TL-7987        Converted block rendering to use templates
    TL-8009        Converted shezar_print_active_users to templates
    TL-8011        Converted is_registered method to use templates
    TL-8012        Converted print_shezar_search method to use templates
    TL-8013        Converted print_shezar_notifications method to use templates
    TL-8014        Converted print_shezar_progressbar method to use templates
    TL-8015        Converted comment_template method to use templates
    TL-8016        Converted print_toolbars method to use templates
    TL-8017        Converted print_shezar_menu method to use templates
    TL-8018        Converted shezar_print_errorlog_link method to use templates
    TL-8019        Converted display_course_progress_icon to use templates.
    TL-8031        Converted print_my_team_nav to use templates.
    TL-8034        Converted print_report_manager to use templates
    TL-8035        Converted print_scheduled_reports to use templates.
    TL-8036        Converted print_icons_list to use templates.
    TL-8084        Converted html_table and html_writer::table to use templates
    TL-8200        Converted the My Reports page to use templates
    TL-8304        Converted Manage types pages to Mustache templates
    TL-8305        Converted manage frameworks to Mustache templates
    TL-8330        Converted print_competency_view_evidence to use a Mustache template
    TL-8331        Converted competency_view_related to use a template
    TL-8332        Converted goal assignment to Mustache templates
    TL-8334        Converted assigned goals to use a Mustache template
    TL-8335        Converted mygoals_company_table to use Mustache templates
    TL-8337        Converted mygoals_personal_table to use a Mustache template
    TL-8448        Converted render_single_button function to use templates
    TL-9070        Converted filter_dialogs to an AMD module
    TL-9192        Converted competency JavaScript functionality to AMD modules


API changes:

    TL-7473        Custom menu has been deprecated

                   The custom menu that could be edited through Site Administration > Appearance > Themes > Theme settings has been
                   deprecated. You may need to convert this into the functionality as provided by Site Administration > Appearance > Main
                   menu

    TL-7817        Added support for AMD modules to the jQuery DataTables plugin

                   In shezar 2.9, a small customisation was introduced to the jQuery DataTables library removing AMD support. This
                   customisation has now been removed (and updated the library to 1.10.11 in the process) as we have updated our JavaScript
                   to AMD modules.

                   To convert JavaScript functionality to support jQuery DataTables, ensure your JavaScript is written as an AMD module and
                   require both 'jquery' and 'shezar_core/datatables' in your module.

    TL-7981        Removed unused preferred_width function from blocks
    TL-7982        Removed unnecessary calls to local_js

                   Previously (in shezar 2.7 and below) making a call to local_js would load jQuery into the page. As of shezar 2.9, jQuery
                   is loaded by default on every page so this call is no longer needed if no jQuery plugins are required. This fix removed
                   all requests to local_js with no parameters.

    TL-8004        Separated shezar specific CSS from bootstrap bases theme
    TL-8027        Function function_or_method_exists() was deprecated
    TL-8055        Removed references to some deprecated functions
    TL-8057        Added element id to static and hard-frozen form field containers

                   This allows them to be addressed in JavaScript and by CSS, the same as non-static and non-hard-frozen field containers.

    TL-8071        Moved Hierarchy JavaScript from inline to an AMD module

                   Previously there was a large amount of JavaScript that was written in the HTML (instead of being in an external
                   JavaScript file) in Hierarchies. These have been replaced with 2 AMD modules

    TL-8188        shezar LMS 9 now supports PHP7

                   Note that support for PHP7 has not been backported to the stable release.
                   Any stable releases still have a maximum version of PHP5.6.

    TL-8369        html_writer should implement templatable
    TL-8405        JQuery updated to 2.2.0
    TL-8537        Fixed a redirection issue when signing up to a Seminar event
    TL-8562        Created export_for_template method on single_button class
    TL-8958        Added new API for lookup of classes in namespace
    TL-8998        Imported latest SVGGraph library
    TL-9101        Deprecated the shezar_message_accept_reject_action function

                   This function is not used within core shezar code and has been deprecated. It will be removed in shezar 10.0

    TL-9826        Created new 'I switch to "tab" tab' behat step


API change details:

    TL-6292        *  shezar_table::print_extended_headers() has been removed
    TL-6874        *  New \core\output\flex_icon class to allow use of font icons.
                   *  New pluginname/pix/flex_icons.php file to define and alias icons.
    TL-6879        *  admin/tool/shezar_sync/lib.php tool_shezar_sync_run first argument has been removed
    TL-7060        *  CSS class "stagelist" has been renamed "appraisal-stagelist"
                   *  CSS class "stagetitle" has been renamed "appraisal-stagetitle"
                   *  CSS class "stageinfo" has been renamed "appraisal-stageinfo"
                   *  CSS class "stageactions" has been renamed "appraisal-stageactions"
    TL-7452        *  Removed table facetoface_notice
                   *  Removed table facetoface_notice_data
                   *  Removed unused function facetoface_get_manageremailformat
                   *  Removed unused function facetoface_check_manageremail
                   *  Removed unused class mod_facetoface_sitenotice_form
    TL-7453        *  rb_param_option::$type is deprecated and will be removed in future versions
                   *  facetoface_print_session function added sixth argument $class
    TL-7455        *  rb_source_facetoface_summary::rb_display_link_f2f method is removed
                   *  rb_source_facetoface_summary::rb_display_link_f2f_session is removed
                   *  rb_source_facetoface_sessions::rb_display_facetoface_status method is removed
                   *  rb_source_facetoface_sessions::rb_display_link_f2f_session method is removed
    TL-7458        *  facetoface_sessions.registrationtimestart added a new database field registrationtimestart to facetoface_sessions
                   *  facetoface_sessions.registrationtimefinish added a new database field registrationtimefinish to facetoface_sessions
    TL-7461        *  Capability 'mod/facetoface:editsessions' was renamed to 'mod/facetoface:editevents'
                   *  Capability 'mod/facetoface:overbook' was renamed to 'mod/facetoface:signupwaitlist'
    TL-7463        *  rb_base_source::add_custom_fields_for added seventh argument $useshortname
                   *  $session->datetimeknown property of session objects fetched with facetoface_get_session() is removed.
                      $session->cntdates should be used instead
                   *  mod_facetoface_session_form class is removed
                   *  mod_facetoface_renderer::print_session_list_table added sixth and seventh optional arguments $currenturl and
                      $minimal
                   *  facetoface_save_session_room function is removed
                   *  facetoface_get_session_room function is replaced with facetoface_get_session_rooms
                   *  facetoface_room_html added second argument $backurl
                   *  facetoface_room_info_field added a new database table
                   *  facetoface_room_info_data added a new database table
                   *  facetoface_room_info_data_param added a new database table
                   *  facetoface_room.address removed a database field address from facetoface_room
                   *  facetoface_room.building removed a database field building from facetoface_room
                   *  facetoface_room.usercreated added a new database field usercreated to facetoface_room
                   *  facetoface_room.usermodified added a new database field usermodified to facetoface_room
                   *  facetoface_room.hidden added a new database field hidden to facetoface_room
                   *  facetoface_sessions_dates.roomid added a new database roomid hidden to facetoface_sessions_dates
                   *  facetoface_sessions.roomid removed a database field roomid from facetoface_sessions
                   *  facetoface_sessions.datetimeknown removed a database field datetimeknown from facetoface_sessions
                   *  facetoface_asset added a new database table
                   *  facetoface_asset_info_field added a new database table
                   *  facetoface_asset_info_data added a new database table
                   *  facetoface_asset_info_data_param added a new database table
                   *  facetoface_asset_dates added a new database table
                   *  mod_facetoface\rb\display\link_f2f_session class removed
                   *  mod_facetoface\rb\display\link_f2f class removed
                   *  mod_facetoface\rb\display\f2f_status class removed
    TL-7465        *  facetoface.approvaltype added a new database field approvaltype to facetoface
                   *  facetoface.approvalrole added a new database field approvalrole to facetoface
                   *  facetoface.approvalterms added a new database field approvalterms to facetoface
                   *  facetoface.approvaladmins added a new database field approvaladmins to facetoface
                   *  facetoface_signups.managerid added a new database field managerid to facetoface_signups
                   *  MDL_F2F_CONDITION_BOOKING_REQUEST this global constant has been replaced by
                      MDL_F2F_CONDITION_BOOKING_REQUEST_MANAGER
                   *  facetoface_message_substitutions added a 7th argument $approvalrole
                   *  facetoface_approval_required argument two has been removed and is no longer used
                   *  facetoface_user_signup there have been several changes to the function signature. Any uses in custom code should be
                      reviewed to ensure they are still compatible
    TL-7473        *  class custom_menu_item has been deprecated and will be removed in a future version
                   *  class custom_menu has been deprecated and will be removed in a future version
                   *  core_renderer::custom_menu() has been deprecated and will be removed in a future version
                   *  core_renderer::render_custom_menu() has been deprecated and will be removed in a future version
                   *  core_renderer::render_custom_menu_item() has been deprecated and will be removed in a future version
    TL-7944        *  facetoface_sessions.sendcapacityemail added a new database field sendcapacityemail to facetoface_sessions
                   *  facetoface_sessions.mincapacity field must be not null with default value 0
    TL-7963        *  dp_plan_evidence.description removed the database field description in dp_plan_evidence
                   *  dp_plan_evidence.evidencelink removed the database field evidencelink in dp_plan_evidence
                   *  dp_plan_evidence.institution removed the database field institution in dp_plan_evidence
                   *  dp_plan_evidence.datecompleted removed the database field datecompleted in dp_plan_evidence
    TL-7981        *  block_shezar_alerts::preferred_width() has been removed.
                   *  block_shezar_quicklinks::preferred_width() has been removed
                   *  block_shezar_stats::preferred_width() has been removed
                   *  block_shezar_tasks::preferred_width() has been removed
    TL-7982        *  local_js() with no arguments now shows a debugging message.
    TL-8009        *  shezar_core_renderer::shezar_print_active_users has been deprecated and will be removed in a future version
    TL-8012        *  shezar_core_renderer::print_shezar_search has been deprecated and will be removed in a future version
    TL-8013        *  shezar_core_renderer::print_shezar_notifications has been deprecated and will be removed in a future version
    TL-8014        *  shezar_core_renderer::print_shezar_progressbar has been deprecated and will be removed in a future version
    TL-8016        *  shezar_core_renderer::print_toolbars has been deprecated and will be removed in a future version
    TL-8017        *  shezar_core_renderer::print_shezar_menu has been deprecated and will be removed in a future version
    TL-8018        *  shezar_core_renderer::shezar_print_errorlog_link has been deprecated and will be removed in a future version
    TL-8019        *  shezar_core_renderer::display_course_progress_icon has been deprecated and will be removed in a future version
    TL-8023        *  Global constant COMPLETIONTYPE_OPTIONAL added for setting program course sets as optional
    TL-8031        *  shezar_core_renderer::print_my_team_nav has been deprecated and will be removed in a future version
    TL-8034        *  shezar_core_renderer::print_report_manager has been deprecated and will be removed in a future release
    TL-8035        *  shezar_core_renderer::print_scheduled_reports has been deprecated and will be removed in a future release
    TL-8036        *  shezar_core_renderer::print_icons_list has been deprecated and will be removed in a future release
    TL-8084        *  html_writer::table has been deprecated and will be removed in a future release
                   *  html_table::$tablealign has been deprecated and will be removed in a future release
                   *  html_table::$captionhide has been deprecated and will be removed in a future release
    TL-8118        *  shezar_compl_import_course.customfields added a new database field customfields to shezar_compl_import_course
                   *  shezar_compl_import_course.evidenceid added a new database field evidenceid to shezar_compl_import_course
                   *  shezar_compl_import_cert.customfields added a new database field customfields to shezar_compl_import_cert
                   *  shezar_compl_import_cert.evidenceid added a new database field evidenceid to shezar_compl_import_cert
                   *  shezar/completionimport/lib.php check_fields_exist added a third argument $customfields
                   *  shezar/completionimport/lib.php import_csv added a third argument $customfields
    TL-8187        *  facetoface_sessions.cancelledstatus added a new database field cancelledstatus to facetoface_sessions
                   *  facetoface_sessioncancel_info_data added a new database table
                   *  facetoface_sessioncancel_info_data_param added a new database table
                   *  facetoface_sessioncancel_info_field added a new database table
    TL-8330        *  shezar_hierarchy_renderer::print_competency_view_evidence has been deprecated and will be removed in a future
                      release
    TL-8331        *  shezar_hierarchy_renderer::print_competency_view_related has been deprecated and will be removed in a future release
    TL-8332        *  shezar_hierarchy_renderer::print_goal_view_assignments has been deprecated and will be removed in a future release
    TL-8334        *  shezar_hierarchy_renderer::print_assigned_goals has been deprecated and will be removed in a future version
    TL-8383        *  course_modules_completion.reaggregate added a new database field reaggregate to course_modules_completion
    TL-8513        *  a new user_learning API has been introduced, see changelog description for details
                   *  shezar/program/lib.php prog_get_all_programs added a ninth argument $onlycertifications
    TL-8537        *  session_options_signup_link has a new fourth argument, true by default, if true the user is redirected to the view
                      all sessions page after completing a signup or cancellation action.
                   *  print_session_list_table has a new eighth argument, true by default, if true the user is redirected to the view all
                      sessions page after completing a signup or cancellation action.
    TL-8636        *  addconfirm_form::get_user_list($userlist, $offset, $limit) added new function
                   *  removeconfirm_form::get_user_list($userlist, $offset, $limit) added new function
    TL-8779        *  dp_base_component::display_back_to_index_link deprecated.
    TL-8786        *  facetoface_asset renamed database field "type" to "allowconflicts" and changed data type from char(10) to int(1)
                   *  facetoface_room renamed database field "type" to "allowconflicts" and changed data type from char(10) to int(1)
    TL-8892        *  rb_source_facetoface_sessions::define_columnoptions time of signup value is changed
    TL-8895        *  rb_facetoface_base_source::add_facetoface_common_to_columns added a second argument $joinsessions
                   *  rb_facetoface_base_source::add_session_status_to_joinlist second argument $sessionidfield was split into two
                      arguments $join and $field
    TL-8945        *  After upgrade, Primary position assignment records will be the users' first job assignment (sortorder = 1) and
                      Secondary records will be the users' second job assignment (sortorder = 2). Aspirational records are moved to the
                      new gap_aspirational table.
                   *  Strings relating to position assignments were deprecated from shezar_hierarchy and shezar_core and added into the
                      shezar_job language file.
                   *  Moved config variables allowsignupposition, allowsignuporganisation and allowsignupmanager from shezar_hierarchy to
                      the shezar_job plugin.
                   *  The position_updated and position_viewed events have been changed to job_assignment_updated and
                      job_assignment_viewed. The create_from_instance functions now take a job_assignment object, the object id is
                      available to event observers in $data['objectid'], the userid is available in $data['relateduserid'].
                   *  Several behat data generators and steps relating to old position assignments have been removed. "the following job
                      assignments exist" should now be used to define manager, position, organisation, temp manager, etc.
                   *  The create_primary_position phpunit test generator was renamed to create_job_assignments.
                   *  The positionsenabled shezar_hierarchy setting is deprecated and no longer used.

                   *  shezar_is_manager has been deprecated but maintains a level of backwards compatibility and will be removed in a
                      future version. Instead, use job_assignment::is_managing.
                   *  shezar_get_staff has been deprecated but maintains a level of backwards compatibility and will be removed in a
                      future version. Instead, use job_assignment::get_staff() or job_assignment::get_staff_userids(), depending on the
                      situation.
                   *  shezar_get_manager has been deprecated but maintains a level of backwards compatibility and will be removed in a
                      future version. Code using this function should be redesigned to either use job_assignment::get_all() with the
                      $managerreqd parameter set to true, or use job_assignment::get_all_manager_userids(), depending on the situation. It
                      is also possible to use job_assignment::get_first(), but this is discouraged, because job assignment order can
                      easily be changed, so this is effectively an arbitrary selection.
                   *  shezar_get_most_primary_manager has been deprecated but maintains a level of backwards compatibility and will be
                      removed in a future version. Instead, use job_assignment::get_first(), or, preferably, redesign your code to work
                      with multiple managers and call job_assignment::get_all() with the $managerreqd parameter set to true, or
                      job_assignment::get_all_manager_userids().
                   *  shezar_update_temporary_manager has been deprecated but maintains a level of backwards compatibility and will be
                      removed in a future version. Instead, use job_assignment::update_temporary_managers().
                   *  shezar_unassign_temporary_manager has been deprecated but maintains a level of backwards compatibility and will be
                      removed in a future version. This functionality is now part of the job_assignment class - load the job_assignment
                      object and call update() with an empty tempmanagerjaid.
                   *  shezar_get_teamleader has been deprecated but maintains a level of backwards compatibility and will be removed in a
                      future version. Instead, load the job_assignment object and access the teamleaderid property.
                   *  shezar_get_appraiser has been deprecated but maintains a level of backwards compatibility and will be removed in a
                      future version. Instead, load the job_assignment object and access the appraiserid property.
                   *  shezar_update_temporary_managers has been deprecated but maintains a level of backwards compatibility and will be
                      removed in a future version
                   *  build_nojs_positionpicker has been deprecated but maintains a level of backwards compatibility and will be removed
                      in a future version
                   *  development_plan::get_manager has been deprecated but maintains a level of backwards compatibility and will be
                      removed in a future version
                   *  development_plan::send_alert has been deprecated but maintains a level of backwards compatibility and will be
                      removed in a future version
                   *  rb_source_facetoface_sessions::rb_filter_position_types_list has been deprecated but maintains a level of backwards
                      compatibility and will be removed in a future version
                   *  rb_source_facetoface_sessions::rb_display_position_type has been deprecated but maintains a level of backwards
                      compatibility and will be removed in a future version
                   *  appraisal::get_live_role_assignments has been deprecated but maintains a level of backwards compatibility and will
                      be removed in a future version
                   *  The file shezar/hierarchy/prefix/position/assign/manager.php has been deprecated but maintains a level of backwards
                      compatibility and will be removed in a future version. Instead use shezar/job/dialog/assign_manager_html.php.
                   *  The file shezar/hierarchy/prefix/position/assign/tempmanager.php has been deprecated but maintains a level of
                      backwards compatibility and will be removed in a future version. Instead use
                      shezar/job/dialog/assign_tempmanager_html.php.
                   *  pos_can_edit_position_assignment has been deprecated but maintains a level of backwards compatibility and will be
                      removed in a future version. Instead, use shezar_job_can_edit_job_assignments.
                   *  pos_get_current_position_data has been deprecated but maintains a level of backwards compatibility and will be
                      removed in a future version. Instead, load the relevant job_assignment object and access the positionid and
                      organisationid properties.
                   *  pos_get_most_primary_position_assignment has been deprecated but maintains a level of backwards compatibility and
                      will be removed in a future version. Instead, it is possible to use job_assignment::get_first(), but this is
                      discouraged, because job assignment order can easily be changed, so this is effectively an arbitrary selection.
                   *  get_position_assignments has been deprecated but maintains a level of backwards compatibility and will be removed in
                      a future version. Instead, use job_assignment::get_all().
                   *  shezar_core\task\update_temporary_managers_task has been deprecated but maintains a level of backwards compatibility
                      and will be removed in a future version. Instead, use shezar_job\task\update_temporary_managers_task.
                   *  user/positions.php has been deprecated but maintains a level of backwards compatibility and will be removed in a
                      future version. This functionality is now provided in shezar/job/jobassignment.php.

                   *  assign_user_position has been deprecated and throws an exception if called. This functionality is now being
                      performed when a job_assignment is created or updated.
                   *  appraisal::get_missingrole_users has been deprecated and throws an exception if called
                   *  appraisal::get_changedrole_users has been deprecated and throws an exception if called
                   *  position::position_label has been deprecated and throws an exception if called. Instead, use
                      position::job_position_label which returns a string formatted " ()".
                   *  The position_assignment class no longer contains any methods and an exception will be thrown from its constructor if
                      instantiated. Instead, job assignments should be managed through the job_assignment class interfaces.
                   *  prog_store_position_assignment has been deprecated and throws an exception if called. Now, when a user's position
                      changes in one of their job assignments, the positionassignmentdate is automatically updated.

                   *  appraisal::validate_roles argument one has been deprecated and is no longer used. Behaviour is now to compare
                      required appraisal roles against corresponding roles in appraisees' job assignments.
                   *  shezar_appraisal_renderer::display_appraisal_header argument three has been deprecated and is no longer used. Role
                      assignments are now loaded within this method.
                   *  profile_signup_manager argument three has been changed - this now must be a manager's job assignment id, rather than
                      a manager id.

                   *  pos_add_node_positions_links has been removed. This functionality is now part of shezar_job_myprofile_navigation.
                   *  shezar_hierarchy_myprofile_navigation has been removed. Instead, use shezar_job_myprofile_navigation.
                   *  shezar_program_observer::position_updated has been removed. Instead use job_assignment_updated, which is called when
                      the new job_assignment_updated event is triggered.
                   *  mod_facetoface_signup_form::add_position_selection_formelem has been removed
                   *  The JavaScript function rb_load_hierarchy_multi_filters has been removed
                   *  shezar/core/js/position.user.js has been removed
                   *  shezar/core/classes/event/position_updated.php has been removed, including the the class
                      shezar_core\event\position_updated
                   *  shezar/core/classes/event/position_viewed.php has been removed, including the the class
                      shezar_core\event\position_viewed
                   *  mod/facetoface/classes/event/attendee_position_updated.php has been removed, including the class
                      mod_facetoface\event\attendee_position_updated
                   *  mod/facetoface/attendee_position_form.php has been removed, including the class attendee_position_form
                   *  shezar/hierarchy/prefix/position/forms.php has been removed, including the class position_settings_form
                   *  shezar/hierarchy/prefix/position/settings.php has been removed, including the function update_pos_settings
                   *  user/positions_form.php has been removed, including the class user_position_assignment_form

                   *  The position_assignment table was renamed to job_assignment. This maintains record ids, so if you have a table which
                      referenced position_assignment.id, e.g. "posassignid", then that field does not need to be updated (although you
                      probably want to change the field name and fix the foreign key specification). Several other changes were made to
                      this table, including making idnumber user-unique and cannot be null (and it is editable in the user interface),
                      added fields for temporary manager, added a field for position assignment date (indicates when position was last
                      changed), timevalidfrom and timevalidto renamed to startdate and enddate, managerid and managerpath changed to
                      managerjaid and managerjapath (note that these new fields store job assignment data, not manager ids), and fullname
                      can now be empty (if accessed through the job_assignment class, a default string containing the user-unique idnumber
                      will be returned).
                   *  The temporary_manager table has been removed. Temporary manager data was moved into the job_assignment table.
                   *  The prog_pos_assignment table has been removed. The data in this table, which indicates on which date a user was
                      last assigned to their current position, has been moved into the job_assignment table, in the positionassignmentdate
                      field.

                   *  Global constants POSITION_TYPE_PRIMARY, POSITION_TYPE_SECONDARY and POSITION_TYPE_ASPIRATIONAL have all been
                      deprecated, as have the global variables $POSITION_TYPES and $POSITION_CODES.
                   *  The global constant ASSIGNTYPE_MANAGER has been deprecated. This was used for program assignments via manager
                      hierarchy. Custom code using this assignment type will need to be updated to use the managers' job assignments. The
                      new constant used for those assignments is ASSIGNTYPE_MANAGERJA.
    TL-8949        *  Any custom report sources using the old position functions will need to be updated to use the corresponding job
                      assignment function, also any custom columns or filters using the position assignment joins or fields will need to
                      be updated. The add_basic_user_content_options() function is an easy way to add the "show by user", "show by users
                      current position", and "show by users current organisation" content options to a report, just call it inside the
                      sources define_contentoptions() function (see shezar/reportbuilder/rb_sources/rb_source_user.php for an example)
                   *  Function add_basic_user_content_options() added to the base source
                   *  Function add_job_assignment_tables_to_joinlist() added to the base source
                   *  Function add_job_assignments_fields_to_columns() added to the base source
                   *  Function add_job_assignments_fields_to_filters() added to the base source
                   *  Function add_position_tables_to_joinlist() has been deprecated, please use  add_job_assignment_tables_to_joinlist()
                      instead
                   *  Function add_position_fields_to_columns() has been deprecated, please use  add_job_assignment_fields_to_columns()
                      instead
                   *  Function add_position_fields_to_filters() has been deprecated, please use  add_job_assignment_fields_to_filters()
                      instead
    TL-9141        *  customfield_datetime::edit_validate_field new method to override parent
                   *  customfield_datetime::edit_save_data new method to override parent
    TL-9156        *  facetoface.allowsignupnotedefault removed unused database field allowsignupnotedefault from facetoface
                   *  facetoface_sessions.availablesignupnote removed unused database field availablesignupnote from facetoface_sessions
    TL-9677        *  shezar_appraisal_renderer::display_appraisal_header: argument 3 has been deprecated
                   *  appraisal::validate_roles: argument has been deprecated
                   *  appraisal::get_missingrole_users: has been deprecated and will be removed in a future version
                   *  appraisal::get_changedrole_users: has been deprecated and will be removed in a future version
                   *  appraisal::get_live_role_assignments: has been deprecated and will be removed in a future version
                   *  mdl_appraisal_userassignment.jobassignmentid: new database field to track a linked job assignment to an appraisal
                   *  mdl_appraisal_userassignment.jobassignmentlastmodified: new database field to track if a linked job assignment has
                      be updated
    TL-10597       *  prog_content::get_courseset_groups added a second argument $trimoptional


Database changes:

    TL-6243        Added the ability to automatically create learning plans when new members are added to an audience
    TL-7452        Improved Seminar navigation
    TL-7458        Sign-up periods added to Seminar events
    TL-7459        Added links to further information on each room when signing up
    TL-7463        Added session assets and room improvements
    TL-7465        Added new approval options to Seminars
    TL-7944        Added seminar minimum bookings status and notification
    TL-7963        Added the ability to use custom fields within evidence
    TL-8118        Added the ability to import evidence custom field data in the completion Import tool
    TL-8187        Seminar events can now be cancelled
    TL-8383        Activity completion reaggregated during scheduled task after unlock and delete
    TL-8786        Refactored Seminar asset and room "allow conflicts" data storage
    TL-9156        Removed the setting "Users can enter requests when signing up" from the Seminar module


Deleted files:

    TL-7452        * mod/facetoface/sitenotice.php
                   * mod/facetoface/sitenotice_form.php
                   * mod/facetoface/sitenotices.php
    TL-7453        * mod/facetoface/bulkadd_attendees.php
                   * mod/facetoface/editattendees.html
                   * mod/facetoface/editattendees.php
    TL-7459        * mod/facetoface/amd/build/addpdroom.min.js
                   * mod/facetoface/amd/src/addpdroom.js
                   * mod/facetoface/classes/rb/display/link_f2f_session.php
                   * mod/facetoface/room/ajax/room_save.php
    TL-7465        * mod/facetoface/tests/behat/managerapproval_facetoface.feature
                   * mod/facetoface/tests/behat/selfapproval_facetoface.feature
    TL-7817        * shezar/core/js/lib/build/jquery.dataTables.min.js
    TL-7947        * blocks/shezar_program_completion/block.js
    TL-7950        * blocks/shezar_report_table/module.js
    TL-7951        * elementlibrary/js/competency.item.js
    TL-7959        * shezar/reportbuilder/js/instantfilter.js
    TL-7961        * shezar/reportbuilder/js/cachenow.js
    TL-7963        * shezar/plan/tests/behat/evidence_link.feature
    TL-8188        * shezar/core/lib/phpseclib/System/SSH_Agent.php
    TL-8405        * lib/jquery/jquery-1.11.3.min.js
                   * lib/jquery/jquery-2.1.4.min.js
                   * lib/jquery/jquery-migrate-1.2.1.min.js
    TL-8515        * shezar/core/classes/shezar/menu/mylearning.php
                   * shezar/dashboard/tests/behat/dashboard_disable.feature
                   * shezar/dashboard/tests/behat/dashboard_home.feature
    TL-8637        * blocks/facetoface/ChangeLog.txt
                   * blocks/facetoface/README.txt
                   * blocks/facetoface/block_facetoface.php
                   * blocks/facetoface/bookinghistory.php
                   * blocks/facetoface/calendar.php
                   * blocks/facetoface/db/access.php
                   * blocks/facetoface/export_form.php
                   * blocks/facetoface/index.php
                   * blocks/facetoface/lang/en/block_facetoface.php
                   * blocks/facetoface/lib.php
                   * blocks/facetoface/mysessions.php
                   * blocks/facetoface/mysignups.php
                   * blocks/facetoface/renderer.php
                   * blocks/facetoface/sessionfilter_form.php
                   * blocks/facetoface/styles.css
                   * blocks/facetoface/tabs.php
                   * blocks/facetoface/tests/behat/mysessions.feature
                   * blocks/facetoface/version.php
    TL-9070        * shezar/reportbuilder/filter_dialogs.js
    TL-9192        * shezar/core/js/competency.add.js
                   * shezar/core/js/competency.item.js
                   * shezar/core/js/competency.template.js
    TL-9274        * shezar/core/db/pre_any_upgrade.php
    TL-9493        * theme/base/layout/embedded.php
                   * theme/base/layout/frontpage.php
                   * theme/base/layout/report.php
                   * theme/base/pix/fp/add_file.png
                   * theme/base/pix/fp/add_file.svg
                   * theme/base/pix/fp/alias.png
                   * theme/base/pix/fp/alias_sm.png
                   * theme/base/pix/fp/check.png
                   * theme/base/pix/fp/create_folder.png
                   * theme/base/pix/fp/create_folder.svg
                   * theme/base/pix/fp/cross.png
                   * theme/base/pix/fp/dnd_arrow.gif
                   * theme/base/pix/fp/download_all.png
                   * theme/base/pix/fp/download_all.svg
                   * theme/base/pix/fp/help.png
                   * theme/base/pix/fp/help.svg
                   * theme/base/pix/fp/link.png
                   * theme/base/pix/fp/link_sm.png
                   * theme/base/pix/fp/logout.png
                   * theme/base/pix/fp/logout.svg
                   * theme/base/pix/fp/path_folder.png
                   * theme/base/pix/fp/path_folder_rtl.png
                   * theme/base/pix/fp/refresh.png
                   * theme/base/pix/fp/refresh.svg
                   * theme/base/pix/fp/search.png
                   * theme/base/pix/fp/search.svg
                   * theme/base/pix/fp/setting.png
                   * theme/base/pix/fp/setting.svg
                   * theme/base/pix/fp/view_icon_active.png
                   * theme/base/pix/fp/view_icon_active.svg
                   * theme/base/pix/fp/view_list_active.png
                   * theme/base/pix/fp/view_list_active.svg
                   * theme/base/pix/fp/view_tree_active.png
                   * theme/base/pix/fp/view_tree_active.svg
                   * theme/base/pix/horizontal-menu-submenu-indicator.png
                   * theme/base/pix/progress.gif
                   * theme/base/pix/sprite.png
                   * theme/base/pix/vertical-menu-submenu-indicator.png
                   * theme/base/pix/yui2-treeview-sprite-rtl.gif
                   * theme/base/style/admin.css
                   * theme/base/style/autocomplete.css
                   * theme/base/style/blocks.css
                   * theme/base/style/calendar.css
                   * theme/base/style/core.css
                   * theme/base/style/course.css
                   * theme/base/style/dock.css
                   * theme/base/style/editor.css
                   * theme/base/style/filemanager.css
                   * theme/base/style/grade.css
                   * theme/base/style/group.css
                   * theme/base/style/message.css
                   * theme/base/style/pagelayout.css
                   * theme/base/style/question.css
                   * theme/base/style/tabs.css
                   * theme/base/style/templates.css
                   * theme/base/style/shezar.css
                   * theme/base/style/shezar_jquery_datatables.css
                   * theme/base/style/shezar_jquery_treeview.css
                   * theme/base/style/shezar_jquery_ui_dialog.css
                   * theme/base/style/user.css
                   * theme/base/templates/core/notification_message.mustache
                   * theme/base/templates/core/notification_problem.mustache
                   * theme/base/templates/core/notification_redirect.mustache
                   * theme/base/templates/core/notification_success.mustache
                   * theme/bootstrapbase/style/font-shezar.css


Contributions:

    * Aldo Paradiso at MultaMedio - TL-5143
    * Anthony McLaughlin at Learning Pool - TL-7944
    * Chris Wharton at Catalyst EU - TL-6243
    * Eugene Venter at Catalyst NZ - TL-8023
    * Keelin Devenney at Learning Pool - TL-7458, TL-8187
    * Learning Pool - TL-7466
    * Lee Campbell at Learning Pool - TL-7455
    * Ryan Adams at Learning Pool - TL-7456, TL-7459


*/