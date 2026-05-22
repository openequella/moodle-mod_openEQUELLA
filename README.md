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
**Site administration → Plugins → Activity modules → openEQUELLA Resource**:

- Set the **openEQUELLA URL**. The URI must be of the form
  `[institutionURL]/signon.do`.
- Set the **openEQUELLA action** to `structured`.
- Configure the **LTI settings**, if your openEQUELLA institution uses LTI.
  When LTI is enabled, Moodle uses LTI to open, launch and embed
  openEQUELLA resources. When LTI is not enabled, the plugin uses the
  Shared secrets settings for single sign-on instead.
- Fill in the **Shared secrets** settings. These settings are also required
  for the drag-and-drop upload feature, even if LTI is enabled.
- Configure the **SSO identification** setting to choose which Moodle user
  field is sent to openEQUELLA to identify the user during single sign-on. By
  default the Moodle username is used, but you can select another available
  user profile field instead. This is useful when your openEQUELLA institution
  identifies users by an attribute other than the Moodle username, such as an
  email address or staff/student ID.
- Review the other settings and adjust them to match your openEQUELLA
  institution's configuration.

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
