Moodle Module For openEQUELLA Integration
=========================================

This plugin (`mod_equella`) integrates [openEQUELLA](https://openequella.github.io/)
with [Moodle](https://moodle.org/), allowing instructors and content authors to
search for, select and embed resources stored in openEQUELLA directly from
within Moodle courses.

Requirements
------------

- This module requires openEQUELLA 4.1 QA3 or higher.
- The `master` branch works with Moodle 2.7 and up. For earlier Moodle
  versions, use the appropriate Git branch.

Installation
------------

The recommended way to install the plugin is to download a packaged release
from GitHub:

1. Go to the
   [Releases](https://github.com/openequella/moodle-mod_equella/releases)
   page of this repository.
2. Under the latest release, download the `moodle-mod_equella-<version>.zip`
   archive (listed under **Assets**).
3. Extract the archive. It will produce a directory named `moodle-mod_equella`.
4. **Rename the extracted directory to `equella`.** The plugin will not work
   correctly if the directory is not named `equella`, because the plugin
   component is `mod_equella` and Moodle expects to find it at `mod/equella`.
5. Copy (or move) the renamed `equella` directory into the `mod` directory of
   your Moodle installation, so its final location is `<moodle>/mod/equella`.
6. Log in to your Moodle site as an administrator. Moodle will detect the new
   plugin and prompt you to complete the upgrade — follow the on-screen
   instructions to finish the installation.

Alternatively, an administrator may install the plugin through Moodle's
**Site administration → Plugins → Install plugins** page by uploading the
release ZIP file directly. After the upload, rename the plugin folder to
`equella` if prompted, then continue with the upgrade.

Configuration
-------------

After installation, configure the plugin from
**Site administration → Plugins → Activity modules → openEQUELLA**:

- Set the **openEQUELLA URL** to your openEQUELLA sign-on endpoint. This must
  use the URI `/[institutionname]/signon.do`, for example
  `https://equella.example.org/vanilla/signon.do`.
- Set the **openEQUELLA action** to `structured` if you are using openEQUELLA
  6.1 or later.
- Review the other settings (shared secrets, drag-and-drop options, etc.) and
  adjust them to match your openEQUELLA institution's configuration.

Once configured, the **openEQUELLA** activity will be available to add to any
course.

Support
-------

If you encounter a problem or have a feature request, please open an issue on
the [issue tracker](https://github.com/openequella/moodle-mod_equella/issues).

More Information
----------------

For more information regarding integration with Moodle from openEQUELLA, see
the [Moodle Integration Guide](http://openequella.github.io/guides/MoodleIntegrationGuide.html).

Contributing
------------

Interested in contributing to the plugin or setting up a local development
environment? See [CONTRIBUTING.md](CONTRIBUTING.md) for issue reporting
guidelines, a Docker-based test setup, and instructions for building the
TypeScript drag-and-drop upload feature.
