<?php
include_once("fitbit.php");

$arr = explode(" ", $_GET["state"]);
$project_id = (int)$_GET['pid'];

$record_ids = json_decode(\REDCap::getData($project_id,'json',null,'record_id'));
$target_rid = null;
foreach($record_ids as $record) {
    $rid = $record->record_id;
    if ($arr[1] == md5("hpst" . $rid)) {
        $target_rid = $rid;
        break;
    }
}

$fitbit = new \Vanderbilt\CopeFitbitTrackerExternalModule\Fitbit($target_rid,$module,$project_id);
$arr = $fitbit->link_account($project_id);

if ($arr[0] === true) {
    $msg = '<div style="color: #155724;
    background-color: #d4edda;
    border-color: #c3e6cb;
    position: relative;
    padding: 0.75rem 1.25rem;
    margin-bottom: 1rem;
    border: 1px solid transparent;
    border-radius: 0.25rem;">
        You have successfully linked your Fitbit account to REDCap.
    </div>';
    \REDCap::logEvent("Cope Fitbit Tracker", "Participant successfully linked Fitbit account (record ID: $target_rid)", null, null, null, $project_id);
} else {
    $msg = '<div style="color: #856404;
    background-color: #fff3cd;
    border-color: #ffeeba;
    position: relative;
    padding: 0.75rem 1.25rem;
    margin-bottom: 1rem;
    border: 1px solid transparent;
    border-radius: 0.25rem;">
        REDCap couldn\'t link your Fitbit account. Please try again or contact a Health Plus administrator.
    </div>';
    \REDCap::logEvent("Cope Fitbit Tracker", "Failed to link Fitbit account for record ID: $target_rid. \nErrors: " . $arr[1], null, null, null, $project_id);
}
echo $msg;
?>