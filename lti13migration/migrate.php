<?php
require_once('../../../config.php');

function buildUrl($page) {
    return (new moodle_url($page)) -> out(false);
}

$runningTasksPage = buildUrl('/admin/tool/task/runningtasks.php');
$taskLogsPage = buildUrl('/admin/tasklogs.php');
$purgeCachePage = buildUrl('/admin/purgecaches.php');

$task = new \mod_equella\task\lti13_migration_task();
$task -> set_custom_data($_GET['ltiTypeName']);
\core\task\manager::queue_adhoc_task($task);
?>

<!DOCTYPE html>
<html lang="en">
<head><title>LTI 1.3 Migration</title>
</head>
<body>
  <h1>Update oEQ resource links to use LTI 1.3</h1>

  <p>
    A Moodle adhoc task has been submitted to do the migration.
  </p>
  <p>
    However, when the task will start depends on how often the Moodle cron script is executed.
  </p>
  <p>
    <?php
      echo "
        You can check whether the task is in progress in the 
        <a href='$runningTasksPage' target='_blank'>Running tasks page</a>, 
        and you can find out the task result in the <a href='$taskLogsPage' target='_blank'>Task logs page</a>.";
    ?>
  </p>
  <p>
    <?php 
      echo "
        Once the task is completed, if none of the oEQ resources have been updated, you need to clear your caches.
        You can do that in the <a href='$purgeCachePage' target='_blank'>Purge caches page</a>.";
    ?>
  </p>
</body>
</html>

