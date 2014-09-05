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
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle. If not, see <http://www.gnu.org/licenses/>.
define('CLI_SCRIPT', true);
require_once (dirname(dirname(dirname(__FILE__))) . '/config.php');
require_once ($CFG->dirroot . '/mod/equella/common/lib.php');
require_once ($CFG->dirroot . '/course/lib.php');
require_once ($CFG->libdir . '/filelib.php');

// Find all Equella items that are missing mimetypes or where they may be incorrect
// and fetch the proper types. Rebuilds course cache at the end.

global $DB, $CFG;
$updated = 0;
$skipped = 0;
$time_start = microtime(true);

// Re-use the same CURL instance to maintain the same EQUELLA session for all URL checks
$ch = curl_init();
curl_setopt($ch, CURLOPT_NOBODY, true);
curl_setopt($ch, CURLOPT_FAILONERROR, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 1);
curl_setopt($ch, CURLOPT_TIMEOUT, 1);
$temp = tmpfile();
curl_setopt($ch, CURLOPT_COOKIEFILE, $temp);
curl_setopt($ch, CURLOPT_COOKIEJAR, $temp);

echo "Checking Equella Items for wrong or missing mime types \n";

$rs = $DB->get_recordset('equella');

foreach ($rs as $resource) {

    if ($resource->mimetype == 'document/unknown' || $resource->mimetype == null) {
        $url = equella_appendtoken($resource->url, equella_getssotoken_api());
        curl_setopt($ch, CURLOPT_URL, $url);
        if (curl_exec($ch)) {
            $info = curl_getinfo($ch);
            $content_type = $info['content_type'];
            $pos = strpos($content_type, ';');
            if ($pos !== false) {
                $content_type = substr($content_type, 0, $pos);
            }
            echo "CHANGED ID: " . $resource->id . " | ";
            echo "Updated to: " . $content_type . " | ";
            echo "Previously: " . $resource->mimetype . "\n";
            $resource->mimetype = $content_type;
            $DB->update_record('equella', $resource);
            $updated++;
        } else {
            echo "SKIPPED ID: $resource->id as CURL failed \n";
            $skipped++;
        }
    }

}

$rs->close();
curl_close($ch);
echo "Total Updated: $updated \n";
echo "Total Skipped: $skipped \n";
rebuild_course_cache();
$time_end = microtime(true);
$time = $time_end - $time_start;
echo "Execution Time: $time seconds";
// End of File