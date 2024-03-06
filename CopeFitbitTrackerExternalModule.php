<?php
namespace Vanderbilt\CopeFitbitTrackerExternalModule;

use REDCap;
use ExternalModules\AbstractExternalModule;
use ExternalModules\ExternalModules;

class CopeFitbitTrackerExternalModule extends AbstractExternalModule
{

    public function __construct()
    {
        parent::__construct();
    }

    function redcap_survey_acknowledgement_page($project_id, $record, $instrument, $event_id){
        include_once("fitbit.php");

        $q = $this->query("SELECT value FROM redcap_data WHERE project_id=? AND field_name=? AND record=?",[$project_id,'options',$record]);
        $row = $q->fetch_assoc();
		if ($instrument == 'registration' && $row['value'] == '3') {
            $fitbit = new \Fitbit($record,$this,$project_id);
            if (!$fitbit->auth_timestamp) {
                $hyperlink = $fitbit->make_auth_link($this);

            }
            echo '<script>
                    parent.location.href = "'.$hyperlink .'"
                </script>';
        }
    }

    function update_activity($cronAttributes){
        include_once("fitbit.php");
        foreach ($this->getProjectsWithModuleEnabled() as $project_id) {
            $start_date = date("Y-m-d",strtotime($this->getProjectSetting('start_date',$project_id)));
            $end_date = date("Y-m-d",strtotime($this->getProjectSetting('end_date',$project_id)));
            $end_date_seven_days_date = date("Y-m-d",strtotime("+7 days",strtotime($this->getProjectSetting('end_date',$project_id))));
            $today = date ("Y-m-d");

            if($start_date != "" && $end_date != "") {
                $record_ids = json_decode(\REDCap::getData($project_id, 'json', null, 'record_id'));
                foreach ($record_ids as $record) {
					$current_date = $start_date;
                    $rid = $record->record_id;
                    $fitbit_obj = new \Fitbit($rid, $this, $project_id);
					if($fitbit_obj && $fitbit_obj->access_token) {
						#If the today is in the date range OR we need to check after +7 days for updates
						if ((strtotime($today) >= strtotime($start_date) && strtotime($today) <= strtotime($end_date)) || (strtotime($today) <= strtotime($end_date_seven_days_date))) {
							while (strtotime($current_date) <= strtotime($today)) {
								$seven_days_date = date("Y-m-d", strtotime("+7 days", strtotime($current_date)));
								#only check date if it's no more than +7 days
								if ($today <= $seven_days_date) {
									$activity = $fitbit_obj->get_activity($current_date);
									if($activity[0] && $activity[1]) {
										$this->save_activity($project_id, $rid, $current_date, $activity[1]);
									}
								}
								$current_date = date("Y-m-d", strtotime("+1 days", strtotime($current_date)));
							}
						}
					}
                }
            }
        }
    }

    function save_activity($project_id, $rid, $date, $activity){
        $Proj = new \Project($project_id);
        $event_id = $Proj->firstEventId;
        $start_date = date("Y-m-d",strtotime($this->getProjectSetting('start_date',$project_id)));

        #We asumen the fist instance it's the starting date if not we check by saved date and/or create new entries
        if($date == $start_date){
            $instanceId = 1;
        }else{
            $instance_found = false;
            $instance_data = \REDCap::getData($project_id,'array',$rid,'redcap_repeat_instance');
            $datai = $instance_data[$rid]['repeat_instances'][$event_id]['fitbit_activity_data'];
			
			## Set default value for $datai to prevent PHP8 errors
			$datai = $datai ?: [];
            foreach ($datai as $instance => $instance_data){
                if($instance_data['fb_date'] == $date){
                    $instanceId = $instance;
                    $instance_found = true;
                    break;
                }
            }
            if(!$instance_found) {
                $instanceId = datediff($date,$start_date,"d") + 1;
            }
        }

        $array_repeat_instances = array();
        $activity['fb_date'] = $date;
        $array_repeat_instances[$rid]['repeat_instances'][$event_id]['fitbit_activity_data'][$instanceId] = $activity;
        $results = \REDCap::saveData($project_id, 'array', $array_repeat_instances,'overwrite', 'YMD', 'flat', '', true, true, true, false, true, array(), true, false, 1, false, '');
    }

    function update_sleep($cronAttributes){
        include_once("fitbit.php");
        foreach ($this->getProjectsWithModuleEnabled() as $project_id) {
            $start_date = date("Y-m-d",strtotime($this->getProjectSetting('start_date',$project_id)));
            $end_date = date("Y-m-d",strtotime($this->getProjectSetting('end_date',$project_id)));
            $end_date_seven_days_date = date("Y-m-d",strtotime("+7 days",strtotime($this->getProjectSetting('end_date',$project_id))));
            $today = date ("Y-m-d");

            if($start_date != "" && $end_date != "") {
                $record_ids = json_decode(\REDCap::getData($project_id, 'json', null, 'record_id'));
                foreach ($record_ids as $record) {
					$current_date = $start_date;
                    $rid = $record->record_id;
                    $fitbit_obj = new \Fitbit($rid, $this, $project_id);
					if($fitbit_obj && $fitbit_obj->access_token) {
						#If the today is in the date range OR we need to check after +7 days for updates
						if ((strtotime($today) >= strtotime($start_date) && strtotime($today) <= strtotime($end_date)) || (strtotime($today) <= strtotime($end_date_seven_days_date))) {
							while (strtotime($current_date) <= strtotime($today)) {
								$seven_days_date = date("Y-m-d", strtotime("+7 days", strtotime($current_date)));
								#only check date if it's no more than +7 days
								if ($today <= $seven_days_date) {
									$sleep = $fitbit_obj->get_sleep($current_date);
									if($sleep[0] && $sleep[1]) {
										$this->save_sleep($project_id, $rid, $current_date, $sleep[1]);
									}
								}
								$current_date = date("Y-m-d", strtotime("+1 days", strtotime($current_date)));
							}
						}
					}
                }
            }
        }
    }

    function save_sleep($project_id, $rid, $date, $sleep){
        $Proj = new \Project($project_id);
        $event_id = $Proj->firstEventId;
        $start_date = date("Y-m-d",strtotime($this->getProjectSetting('start_date',$project_id)));

        #We asumen the fist instance it's the starting date if not we check by saved date and/or create new entries
        if($date == $start_date){
            $instanceId = 1;
        }else{
            $instance_found = false;
            $instance_data = \REDCap::getData($project_id,'array',$rid,'redcap_repeat_instance');
            $datai = $instance_data[$rid]['repeat_instances'][$event_id]['fitbit_sleep_data'];
			
			## Set default value for $datai to prevent PHP8 errors
			$datai = $datai ?: [];
            foreach ($datai as $instance => $instance_data){
                if($instance_data['fb_date_sleep'] == $date){
                    $instanceId = $instance;
                    $instance_found = true;
                    break;
                }
            }
            if(!$instance_found) {
                $instanceId = datediff($date,$start_date,"d") + 1;
            }
        }

        $array_repeat_instances = array();
        $aux = array();
        $aux['fb_date_sleep'] = $date;
        if($sleep[1] != null && $sleep[1] != "") {
            $aux['sleep_1'] = $sleep;
        }else{
            $aux['sleep_1'] = 0;
        }
        $array_repeat_instances[$rid]['repeat_instances'][$event_id]['fitbit_sleep_data'][$instanceId] = $aux;
        $results = \REDCap::saveData($project_id, 'array', $array_repeat_instances,'overwrite', 'YMD', 'flat', '', true, true, true, false, true, array(), true, false, 1, false, '');
    }
}
?>
