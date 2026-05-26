# Contributing to the Moodle Module for openEQUELLA

Thank you for your interest in contributing! This document describes how to
report issues, submit changes, and set up a local development environment for
working on the plugin.

## Reporting Issues and Submitting Pull Requests

- Open issues on the
  [issue tracker](https://github.com/openequella/moodle-mod_openEQUELLA/issues).
- Fork the repository and send pull requests for any defects or features that
  you would like to contribute back.

## Branching

- The `master` branch targets the latest supported Moodle version (currently
  Moodle 4.5.x).
- For older Moodle versions, choose the appropriate Git branch.

## Docker Installation For Testing Purposes

For a local Moodle test environment, use the
[moodlehq/moodle-docker](https://github.com/moodlehq/moodle-docker) project,
which provides Docker configuration aimed at Moodle developers and testers
and supports the latest Moodle versions.

Refer to that repository's documentation for prerequisites and the full
quick-start, then tailor the setup (database engine, Moodle version, ports,
etc.) to your needs. Once the containers are up, mount or copy this plugin
into `mod/equella` inside the Moodle webroot (`MOODLE_DOCKER_WWWROOT`) and
log in to Moodle to complete the plugin upgrade.

## Development: Drag and Drop (DND) Upload with Metadata Interception

This section applies when a site administrator has enabled **Intercept drag
and drop files → Auto contribute file to openEQUELLA with meta data** in the
plugin settings. When active, dragging a file onto a course page opens a
custom metadata modal (title, description, copyright, keywords) instead of
using Moodle's default upload behaviour.

The DND upload feature is built using TypeScript and Webpack, implementing Moodle's
<a href="https://docs.moodle.org/dev/Implementing_Course_drag_and_drop_upload_support_in_a_module">course drag and drop upload support API</a>
and making use of hooks. The compiled output follows Moodle's
<a href="https://moodledev.io/docs/4.5/guides/javascript">JavaScript development guidelines</a>
and <a href="https://moodledev.io/docs/4.5/guides/javascript/modules">AMD module format</a>
(`amd/src` → `amd/build`). The source code is located in the `tsrc` directory.

If you are modifying the DND feature, you must compile the TypeScript code
into the `amd/build` and `amd/src` directories for Moodle to recognize the
changes.

> **Note:** The `amd/build` and `amd/src` directories are not committed to
> this repository, so contributors who clone the repo must complete the
> prerequisites and build steps below before the DND upload feature will
> work. End users installing the plugin from the
> [Releases](https://github.com/openequella/moodle-mod_openEQUELLA/releases)
> page do **not** need to do this — the release assets already include the
> compiled `amd` folder.

### Prerequisites

* [Node.js](https://nodejs.org/): Use [nvm](https://github.com/nvm-sh/nvm)
  to install the version defined in `tsrc/.nvmrc`.

### Build Instructions

1. Navigate to the source directory:

   ```sh
   cd tsrc
   ```

2. Install the required dependencies:

   ```sh
   npm ci
   ```

3. Build the bundled AMD modules:

   ```sh
   npm run build
   ```

### Continuous Integration

The GitHub Actions workflow at `.github/workflows/ci.yaml` runs on pushes to master, release/*, and tags, and on pull requests targeting master. It runs lint, TypeScript unit tests, and build steps, and packages the plugin into `.zip` and `.tar.gz` artifacts.
