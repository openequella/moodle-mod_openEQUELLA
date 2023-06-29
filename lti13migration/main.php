<!--
This file is part of the EQUELLA module - http://git.io/vUuof

Moodle is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

Moodle is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with Moodle. If not, see <http://www.gnu.org/licenses/>.
-->

<!DOCTYPE html>
<html lang="en">
<head><title>LTI 1.3 Migration</title>
</head>
<body>

<?php
require_once('../../../config.php');
global $DB;

$ltiToolNames = $DB->get_fieldset_sql("SELECT name FROM {lti_types} WHERE ltiversion='1.3.0'");
$options = implode(array_map(function ($name) { return "<option value='$name'>$name</option>"; }, $ltiToolNames));

echo "
  <h1>Update oEQ resource links to use LTI 1.3</h1>

  <p>
    Before starting the migration, you can download a CSV which will list all courses which contain items that will be
    affected by the migration. This CSV can be used for review, and no system modifications will occur.
  </p>
  <form method='GET' action='downloadcsv.php'>
    <input type='submit' value='Download CSV'>
  </form>

  <p>
    This migration will enable existing openEQUELLA links to be opened using LTI 1.3 technology. openEQUELLA items shown
    in courses may display slightly differently (e.g. use different thumbnails) after the migration. Please ensure that 
    you have backed up your Moodle data before proceeding.
  </p>
  <form method='GET' action='migrate.php'>

    <label for=\"ltiTypeName\">Please select the LTI 1.3 tool you want to use in the migration</label>
    <select name='ltiTypeName'> $options </select> 
    <input type='submit' value='Start migration'  onclick='return confirm(\"Please ensure that you have backed up your Moodle data before proceeding.\")'>
  </form>"
?>

</body>
</html>
