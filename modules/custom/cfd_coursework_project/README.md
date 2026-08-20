# Coursework Project Submissions

Provides the **Coursework Project Submissions** workflow.

## Module identity

- Machine name: `cfd_coursework_project`
- URL prefix: `/coursework-project`
- PHP namespace: `Drupal\cfd_coursework_project`
- Upload directory: `cfd_uploads/coursework_project_uploads/`

## Database

The module uses the existing Drupal database. Run `database_setup.sql` once
before enabling the module to create independent coursework tables. The script
copies only the CFD software, solver, and simulation-type lookup data.
Coursework titles, proposals, submissions, files, and QR-code records start
empty.

Create these writable directories before accepting uploads:

- `cfd_uploads/coursework_project_uploads/`
- `cfd_uploads/coursework_project_titles_resource_files/`

Visit `/admin/settings/coursework-project` and configure all notification
addresses and allowed file-extension lists before granting user access. Upload
validation deliberately fails closed while an allowlist is empty.

Create or confirm the site content alias
`/coursework-project/term-and-conditions`; the proposal form links to that page,
but the legal text is site content and is not created by this module.

The database script contains an optional, commented query for copying the
existing Case Study project-title list. Use it only if the CFD team explicitly
wants the same titles, and copy the corresponding resource files into the new
coursework resource directory.

The cloned software-version table intentionally retains its existing
`case_study_version` column name so the table can be copied without making
assumptions about its database-specific column definition.
