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

//if users click the cancel button redirect them here and break out of the iFrame/object tag.
require_once("../../config.php");
require_once("lib.php");
$course = optional_param('course',0, PARAM_INT);
?>

<html>
    <head>
        <title>Please wait while you are redirected</title>
        <script type="text/javascript">
        function redirect(url)
        {
            window.open(url, "_top", "");
        }
        </script>
    </head>
    <body onload="redirect('<?php echo $CFG->wwwroot.'/course/view.php?id='.$course; ?>');">
    Please wait while you are redirected...
    </body>
</html>
