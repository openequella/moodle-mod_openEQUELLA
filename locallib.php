<?php
// This file is part of the EQUELLA Moodle Integration - https://github.com/equella/moodle-module
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Library of functions for EQUELLA internal
 */

/**
 * Returns general link or file embedding html.
 * @param string $fullurl
 * @param string $clicktoopen
 * @param string $mimetype
 * @return string html
 */
function equella_embed_general($fullurl, $clicktoopen, $mimetype = null) {
    global $CFG, $PAGE;

    if ($fullurl instanceof moodle_url) {
        $fullurl = $fullurl->out();
    }

    $iframe = false;

    // IE can not embed stuff properly, that is why we use iframe instead.
    // Unfortunately this tag does not validate in xhtml strict mode,
    // but in any case it is undeprecated in HTML 5 - we will use it everywhere soon!
    if (check_browser_version('MSIE', 5)) {
        $iframe = true;
    }

    if ($iframe) {
        $code = <<<EOT
<div class="resourcecontent resourcegeneral">
  <iframe id="resourceobject" src="$fullurl">
    $clicktoopen
  </iframe>
</div>
EOT;
    } else {
        $param = '<param name="src" value="'.$fullurl.'" />';

        $code = <<<EOT
<div class="resourcecontent resourcegeneral">
  <object id="resourceobject" data="$fullurl" width="800" height="600">
    $param
    $clicktoopen
  </object>
</div>
EOT;
    }

    // the size is hardcoded in the object above intentionally because it is adjusted by the following function on-the-fly
    $PAGE->requires->js_init_call('M.util.init_maximised_embed', array('resourceobject'), true);

    return $code;
}
