<?php
/** @var Vanderbilt\HealthPlusStepsTrackerExternalModule\HealthPlusStepsTrackerExternalModule $module */
if(!\ExternalModules\ExternalModules::isSuperUser()) {
	die();
}
echo "test";
$module->update_activity([]);

