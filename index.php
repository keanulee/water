<?php

include 'config.php';

date_default_timezone_set($timezone);

include 'app/device.php';
include 'app/scheduler.php';
include 'app/weather.php';

$device = new Device($ipaddress, $port);
$scheduler = new Scheduler();
$weather = new Weather($location,$api_key);

?>
<!doctype html>
<html>

<head>
<meta name="viewport" content="initial-scale=1.0,maximum-scale=1.0" />

<link rel="apple-touch-icon-precomposed" sizes="57x57" href="img/touch-icon-57.png" />
<link rel="apple-touch-icon-precomposed" sizes="72x72" href="img/touch-icon-72.png" />
<link rel="apple-touch-icon-precomposed" sizes="114x114" href="img/touch-icon-114.png" />
<link rel="apple-touch-icon-precomposed" sizes="144x144" href="img/touch-icon-144.png" />
<link type="text/css" rel="stylesheet" href="css/bootstrap.min.css" />
<link type="text/css" rel="stylesheet" href="css/bootstrap-responsive.min.css" />
<link type="text/css" rel="stylesheet" href="css/style.css" />

<script src="js/jquery-1.7.1.min.js"></script>
<script src="js/bootstrap.min.js"></script>

<title>Water</title>
	
</head>

<body>
	
<div class="container">
	
<div class="row">
	<div id="current_conditions" class="span3">
		<div class="well">
			<?php echo date("l, F j, h:i A"); ?><br />
			<br />
			
			<img class="pull-right" src="<?php echo $weather->getWeatherIconUrl(); ?>" alt="<?php echo $weather->getWeatherDesc(); ?>" /> 
			Currently
			<br />
			<?php echo $weather->getWeatherDesc(); ?><br />
			<?php echo $weather->getTemperature(); ?>&deg; C<br />
			<br />

			<img class="pull-right" src="<?php echo $weather->getWeatherIconUrl(date("Ymd",time() + (60*60*24))); ?>" alt="<?php echo $weather->getWeatherDesc(date("Ymd",time() + (60*60*24))); ?>" /> 
			Tomorrow
			<br />
			<?php echo $weather->getWeatherDesc(date("Ymd",time() + (60*60*24))); ?><br />
			<?php echo $weather->getTemperature(date("Ymd",time() + (60*60*24))); ?>&deg; C<br />
			</div>
	</div>
	<div id="past_conditions" class="span6">
		<div class="well">
			<table class="table table-striped table-bordered">
				<tr>
					<?php $daysOfHistory = 4; ?>
					<?php for ($i = 1-$daysOfHistory; $i <= 0; $i++): ?>
					<th>
						<span class="hidden-desktop"><?php echo date("D",time() + (60*60*24*$i)); ?></span>
						<span class="visible-desktop"><?php echo date("l",time() + (60*60*24*$i)); ?></span>
					</th>
					<?php endfor; ?>
					<th class="water_type"></th>
				</tr>
				<tr id="amount_of_percipitation">
					<?php for ($i = 1-$daysOfHistory; $i <= 0; $i++): ?>
					<td><?php echo $weather->getRainAmount(date("Ymd",time() + (60*60*24*$i))); ?> mm</td>
					<?php endfor; ?>
					<td class="water_type">rain</td>
				</tr>
				<tr id="amount_of_water">
					<?php for ($i = 1-$daysOfHistory; $i <= 0; $i++): ?>
					<td><?php echo $device->getWateredAmount(date("Ymd",time() + (60*60*24*$i))); ?> mm</td>
					<?php endfor; ?>
					<td class="water_type">water</td>
				</tr>
				<tr id="amount_of_both">
					<?php for ($i = 1-$daysOfHistory; $i <= 0; $i++): ?>
					<td><?php echo $weather->getRainAmount(date("Ymd",time() + (60*60*24*$i))) + $device->getWateredAmount(date("Ymd",time() + (60*60*24*$i))); ?> mm</td>
					<?php endfor; ?>
					<td class="water_type">total</td>
				</tr>
			</table>
		</div>
	</div>
	<div id="single_run" class="span3" style="text-align: center;">
		<form name="single_run_form" class="well" action="submit.php" method="post">
			<span>Status</span><br />
			<?php if ($device->isReady()): ?>
			<span class="label label-success">Ready</span>
			<br /><br />

			<input type="number" name="single_duration" value="<?php echo $scheduler->getSetting('single_duration'); ?>" min="0" class="input-mini" /> mins (each zone)<br />
			<input type="submit" name="start" value="Water Now" class="btn btn-success" />


			<?php else: ?>
			<span class="label label-info"><?php echo $device->getStatus(); ?></span>
			<br /><br />

			<input type="number" name="single_duration" value="<?php echo $device->getTimeLeft(); ?>" min="0" disabled="disabled" class="input-mini" /> mins left<br />
			<input type="submit" name="stop" value="Stop Watering" class="btn btn-danger" />


			<?php endif; ?>
			<br /><br />
			
			<?php if ($scheduler->getNextRunTime()): ?>Scheduled to water for <?php echo $scheduler->getSetting('duration') ?> mins on <?php echo date("l, F j",$scheduler->getNextRunTime()); ?> at <?php echo date("h:i A",$scheduler->getNextRunTime()); ?>
			<?php else: ?>Not scheduled to water
			<?php endif; ?>
		</form>
	</div>
</div>

<div class="row">
<div class="span12">
<div class="well">
<form name="scheduler_form" class="form-inline" action="submit.php" method="post">
<div class="row">
	<div id="scheduler_options" class="span9">
		<table>
			<tr>
				<th><label for="sunday">Sun<span class="hidden-phone">day</span></label><br />
					<input type="checkbox" name="sunday" id="sunday" <?php if ($scheduler->getSetting('sunday')) echo 'checked="checked" ' ?>/>
				</th>
				<th><label for="monday">Mon<span class="hidden-phone">day</span></label><br />
					<input type="checkbox" name="monday" id="monday" <?php if ($scheduler->getSetting('monday')) echo 'checked="checked" ' ?>/>
				</th>
				<th><label for="tuesday">Tues<span class="hidden-phone">day</span></label><br />
					<input type="checkbox" name="tuesday" id="tuesday" <?php if ($scheduler->getSetting('tuesday')) echo 'checked="checked" ' ?>/>
				</th>
				<th><label for="wednesday">Wed<span class="hidden-phone">nesday</span></label><br />
					<input type="checkbox" name="wednesday" id="wednesday" <?php if ($scheduler->getSetting('wednesday')) echo 'checked="checked" ' ?>/>
				</th>
				<th><label for="thursday">Thurs<span class="hidden-phone">day</span></label><br />
					<input type="checkbox" name="thursday" id="thursday" <?php if ($scheduler->getSetting('thursday')) echo 'checked="checked" ' ?>/>
				</th>
				<th><label for="friday">Fri<span class="hidden-phone">day</span></label><br />
					<input type="checkbox" name="friday" id="friday" <?php if ($scheduler->getSetting('friday')) echo 'checked="checked" ' ?>/>
				</th>
				<th><label for="saturday">Sat<span class="hidden-phone">urday</span></label><br />
					<input type="checkbox" name="saturday" id="saturday" <?php if ($scheduler->getSetting('saturday')) echo 'checked="checked" ' ?>/>
				</th>
			</tr>
		</table>
		<br />

		<?php
		$hour = (int)($scheduler->getSetting('time')/100);
		if ($hour < 10) $hour = "0".$hour;
		$minute = (int)($scheduler->getSetting('time')%100);
		if ($minute < 10) $minute = "0".$minute;
		?>
		<input type="time" name="time" value="<?php echo $hour.":".$minute ?>" class="input-small" />
		for  
		<input type="number" name="duration" value="<?php echo $scheduler->getSetting('duration') ?>" min="0" class="input-mini" />
		mins (each zone)
		<br /><br />

		<input type="checkbox" name="past_rain" id="past_rain" <?php if ($scheduler->getSetting('past_rain')) echo 'checked="checked" ' ?> />
		<label for="past_rain">Only if it rained less than</label> <input type="number" name="past_max_amount" value="<?php echo $scheduler->getSetting('past_max_amount') ?>" min="0" class="input-mini" /> <label for="past_rain">mm in the last</label> <input type="number" name="past_num_days" value="<?php echo $scheduler->getSetting('past_num_days') ?>" min="0" class="input-mini" /> <label for="past_rain">day(s)</label>
		<br />

		<input type="checkbox" name="present_rain" id="present_rain" <?php if ($scheduler->getSetting('present_rain')) echo 'checked="checked" ' ?> />
		<label for="present_rain">Only if it is not raining</label>
		<br />

		<input type="checkbox" name="future_rain" id="future_rain" <?php if ($scheduler->getSetting('future_rain')) echo 'checked="checked" ' ?> />
		<label for="future_rain">Only if it will not rain in the next</label> <input type="number" name="future_num_days" value="<?php echo $scheduler->getSetting('future_num_days') ?>" min="0" class="input-mini" /> <label for="future_rain">day(s)</label>

	</div>

	<div id="save_menu" class="span2" style="text-align: center; line-height: 40px;">
		<input type="submit" name="save" value="Save Changes" class="btn btn-primary" /><br />
		<input type="reset" value="Cancel" class="btn" />
	</div>
</div>
</form>
</div>
</div>
</div>
	
</div>
	
</body>

</html>