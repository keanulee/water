<?php

class Device
{	
	private $db;
	private $ipaddress;
	private $port;
	private $amount_per_min;
	private $num_zones;
	private $status;
	
	//authenticates with device and gets its status. opens database
	public function __construct($ipaddress, $port)
	{
		$this->db = new SQLiteDatabase('db/data.db');
		$this->ipaddress = $ipaddress;
		$this->port = $port;
		$this->amount_per_min = 0.635;
		$this->num_zones = 2;
		
        $ch = curl_init(); 
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 

        curl_setopt($ch, CURLOPT_URL, 'http://'.$this->ipaddress.':'.$this->port.'/ergetcfg.cgi?lu=admin&lp=pw');
        curl_exec($ch); 

        curl_setopt($ch, CURLOPT_URL, 'http://'.$this->ipaddress.':'.$this->port.'/result.cgi?xs'); 
        $output = curl_exec($ch);
		
		preg_match('/os: .. <br>/', $output, $matches);
		if ($matches[0]) {
			$status = $matches[0][4].$matches[0][5];
			if ($status == "BZ") $this->status = "Currently Watering";
			else if ($status == "WT") $this->status = "About to water";
			else if ($status == "RD") $this->status = "Ready";
			else $this->status = "Unknown";
		}
		
        curl_close($ch);
		
		//device status simulator
//		if (!$this->db->queryExec('DROP TABLE device_status',$error)) die($error);
//		if (!$this->db->queryExec('CREATE TABLE device_status (status INTEGER);',$error)) die($error);
//		if (!$this->db->queryExec('INSERT INTO device_status VALUES (0);',$error)) die($error);
//		$result = $this->db->arrayQuery('SELECT status FROM device_status;');
//		foreach ($result as $row) {
//			if ($row[0] == 2) $this->status = "Currently Watering";
//			if ($row[0] == 1) $this->status = "About to water";
//			if ($row[0] == 0) $this->status = "Ready";
//		}

		//reset water log
//		if (!$this->db->queryExec('DROP TABLE water_log',$error)) die($error);
//		if (!$this->db->queryExec('CREATE TABLE water_log (date INTEGER, time INTEGER, duration INTEGER);',$error)) die($error);
	}
	
	//closes database
	public function __destruct() 
	{
		//device status simulator
//		if ($this->status == "Currently Watering")
//			if (!$this->db->queryExec('UPDATE device_status SET status = 2;',$error)) die($error);
//		if ($this->status == "About to water")
//			if (!$this->db->queryExec('UPDATE device_status SET status = 1;',$error)) die($error);
//		if ($this->status == "Ready")
//			if (!$this->db->queryExec('UPDATE device_status SET status = 0;',$error)) die($error);

		//Delete weather logs older than a week
		if (!$this->db->queryExec('DELETE FROM water_log WHERE date < '.date("Ymd",time()-(60*60*24*7)).';',$error)) die($error);

		unset($this->db);
	}

	public function runCycle($duration, $delay = 0)
	{
		$ch = curl_init(); 
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);  

        curl_setopt($ch, CURLOPT_URL, 'http://'.$this->ipaddress.':'.$this->port.'/result.cgi?xi='.$delay.':'.$duration.':'.$duration.':0:0:0:0:0:0'); 
        curl_exec($ch);
		
        curl_close($ch);
		
		//device status simulator
//		if ($delay) $this->status = "About to water";
//		else $this->status = "Currently Watering";
		
		$this->addToWaterLog(time()+$delay*60, $duration);
	}
	
	public function stopCycle()
	{
		$ch = curl_init(); 
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);  

        curl_setopt($ch, CURLOPT_URL, 'http://'.$this->ipaddress.':'.$this->port.'/result.cgi?xr'); 
        curl_exec($ch);
		
        curl_close($ch);
		
		//device status simulator
//		$this->status = "Ready";
		
		$this->cleanWaterLog();
	}
	
	public function getStatus()
	{
		return $this->status;
	}
	
	public function isReady()
	{
		return ($this->getStatus() == "Ready");
	}
	
	public function getWateredAmount($date)
	{
		$result = $this->db->arrayQuery('SELECT sum(duration) FROM water_log WHERE date = '.$date.';');
		foreach ($result as $row) {
			return round($row[0] * $this->amount_per_min,1);
		}
		return 0;
	}
	
	public function getTimeLeft() {
		$result = $this->db->arrayQuery('SELECT date,time,duration FROM water_log ORDER BY date DESC,time DESC LIMIT 1;');
		foreach ($result as $row) {
			$start_time = mktime((int)($row["time"]/100), $row["time"]%100, 0);
			$end_time = $start_time + $row["duration"]*60*$this->num_zones;
			return (int)(($end_time-time())/60);
		}
		return 0;
	}
	
	public function addToWaterLog($start_time,$duration)
	{
		$end_time = $start_time + $duration*60*$this->num_zones;
		
		if (date("Ymd",$end_time) > date('Ymd',$start_time)) {
			$new_day = mktime(0, 0, 0, date("n",$end_time), date("d",$end_time), date("Y",$end_time));
			if (!$this->db->queryExec('INSERT INTO water_log VALUES ('.date('Ymd',$start_time).','.date('Gi',$start_time).','.(int)(($new_day-$start_time)/60).');',$error)) die($error);
			if (!$this->db->queryExec('INSERT INTO water_log VALUES ('.date('Ymd',$end_time).',0,'.(int)(($end_time-$new_day)/60).');',$error)) die($error);
		}
		else if (!$this->db->queryExec('INSERT INTO water_log VALUES ('.date('Ymd',$start_time).','.date('Gi',$start_time).','.$duration.');',$error)) die($error);
	}
	
	public function cleanWaterLog()
	{
		if (!$this->db->queryExec('DELETE FROM water_log WHERE date > '.date("Ymd").';',$error)) die($error);
		if (!$this->db->queryExec('DELETE FROM water_log WHERE date = '.date("Ymd").' AND time > '.date("Gi").';',$error)) die($error);
		
		$result = $this->db->arrayQuery('SELECT * FROM water_log WHERE date = '.date("Ymd").';');
		foreach ($result as $row) {
			$start_time = mktime((int)($row["time"]/100), $row["time"]%100, 0);
			$end_time = $start_time + $row["duration"]*60*$this->num_zones;
			if ($end_time > time()) {
				if (!$this->db->queryExec('DELETE FROM water_log WHERE date = '.$row["date"].' AND time = '.$row["time"].';',$error)) die($error);
			}
		}
	}
}

?>
