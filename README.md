Moodle Module For openEQUELLA Integration
=========================================

This plugin (`mod_equella`) integrates [openEQUELLA](https://openequella.github.io/)
with [Moodle](https://moodle.org/), allowing instructors and content authors to
search for, select and embed resources stored in openEQUELLA directly from
within Moodle courses.

The latest release of this module requires Moodle v4.5.x.

Installation
------------

1. Go to the
   [Releases](https://github.com/openequella/moodle-mod_openEQUELLA/releases)
   page and download the latest release archive from **Assets**.
2. Install the plugin in one of the following ways:

   - **Via Moodle's plugin installer:** As an administrator, go to
     **Site administration → Plugins → Install plugins** and upload the
     downloaded archive, then follow the on-screen upgrade prompts.
   - **Manually:** Extract the archive, rename the resulting directory to
     `equella`, place it at `<moodle>/mod/equella`, then log in as an
     administrator and follow the upgrade prompts.

Configuration
-------------

After installation, configure the plugin from
**Site administration → Plugins → Activity modules → openEQUELLA**:

- Set the **openEQUELLA URL**. The URI must be of the form
  `[institutionURL]/signon.do`.
- Set the **openEQUELLA action** to `structured`.
- Fill in the **Shared secrets** settings. The Moodle module's drag-and-drop
  feature requires the shared secrets field to be populated in order to work.
- Configure the **SSO identification** setting to choose which Moodle user
  field is sent to openEQUELLA to identify the user during single sign-on. By
  default the Moodle username is used, but you can select another available
  user profile field instead. This is useful when your openEQUELLA institution
  identifies users by an attribute other than the Moodle username (for
  example, a corporate ID or email address), so that the same person is
  matched consistently across both systems.

Once configured, the **openEQUELLA** activity will be available to add to any
course.

Support
-------

If you encounter a problem or have a feature request, please open an issue on
the [issue tracker](https://github.com/openequella/moodle-mod_openEQUELLA/issues).

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
