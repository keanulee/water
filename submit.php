<?php

include 'config.php';

date_default_timezone_set($timezone);

include 'app/device.php';
include 'app/scheduler.php';

$device = new Device($ipaddress, $port);
$scheduler = new Scheduler();

if ($_POST['start']) {
	$scheduler->setSetting('single_duration',$_POST['single_duration']);
	$device->runCycle($_POST['single_duration']);
}
elseif ($_POST['stop']) {
	$device->stopCycle();
}
elseif ($_POST['save']) {
	$scheduler->setSetting('duration',$_POST['duration']);
	$scheduler->setSetting('time',  str_replace(":", "", $_POST['time']));
	$scheduler->setSetting('sunday',$_POST['sunday']);
	$scheduler->setSetting('monday',$_POST['monday']);
	$scheduler->setSetting('tuesday',$_POST['tuesday']);
	$scheduler->setSetting('wednesday',$_POST['wednesday']);
	$scheduler->setSetting('thursday',$_POST['thursday']);
	$scheduler->setSetting('friday',$_POST['friday']);
	$scheduler->setSetting('saturday',$_POST['saturday']);
	$scheduler->setSetting('past_rain',$_POST['past_rain']);
	$scheduler->setSetting('past_max_amount',$_POST['past_max_amount']);
	$scheduler->setSetting('past_num_days',$_POST['past_num_days']);
	$scheduler->setSetting('present_rain',$_POST['present_rain']);
	$scheduler->setSetting('future_rain',$_POST['future_rain']);
	$scheduler->setSetting('future_num_days',$_POST['future_num_days']);
}

header ('HTTP/1.1 301 Moved Permanently');
header("Location: ./")
?>