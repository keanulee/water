<?php

class Device
{	
	private $db;
	private $ipaddress;
	private $port;
	private $amount_per_min;
	private $num_zones;
	private $status;
	private $simulated;
	
	//authenticates with device and gets its status. opens database
	public function __construct($ipaddress, $port, $simulated, $amount_per_min, $num_zones)
	{
		$this->db = new SQLite3('db/data.db');
		$this->ipaddress = $ipaddress;
		$this->port = $port;
		$this->simulated = $simulated;
		$this->amount_per_min = $amount_per_min;
		$this->num_zones = $num_zones;
		
		//device status simulator
		if ($this->simulated) {
			$result = $this->db->query('SELECT status FROM device_status;');
			while ($row = $result->fetchArray()) {
				if ($row[0] == 2) $this->status = "Currently Watering";
				if ($row[0] == 1) $this->status = "About to water";
				if ($row[0] == 0) $this->status = "Ready";
			}
		}
		
		else {
			$attempts = 3;
			for ($i=0; $i<$attempts; $i++)
			{
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

					break;
				}
				else if ($i == ($attempts-1)) {
					$this->status = "No connection";
				}
			}
			
			curl_close($ch);
		}
	}
	
	//closes database
	public function __destruct() 
	{
		//device status simulator
		if ($this->simulated) {
			if ($this->status == "Currently Watering")
				if (!$this->db->exec('UPDATE device_status SET status = 2;')) die($this->db->lastErrorMsg());
			if ($this->status == "About to water")
				if (!$this->db->exec('UPDATE device_status SET status = 1;')) die($this->db->lastErrorMsg());
			if ($this->status == "Ready")
				if (!$this->db->exec('UPDATE device_status SET status = 0;')) die($this->db->lastErrorMsg());
		}

		//Delete weather logs older than a week
		if (!$this->db->exec('DELETE FROM water_log WHERE date < '.date("Ymd",time()-(60*60*24*7)).';')) die($this->db->lastErrorMsg());

		$this->db->close();
	}

	public function runCycle($duration, $delay = 0)
	{
		$ch = curl_init(); 
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);  

        curl_setopt($ch, CURLOPT_URL, 'http://'.$this->ipaddress.':'.$this->port.'/result.cgi?xi='.$delay.':'.$duration.':'.$duration.':0:0:0:0:0:0'); 
        curl_exec($ch);
		
        curl_close($ch);
		
		//device status simulator
		if ($this->simulated) {
			if ($delay) $this->status = "About to water";
			else $this->status = "Currently Watering";
		}
		
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
		if ($this->simulated) {
			$this->status = "Ready";
		}
		
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
		$result = $this->db->query('SELECT sum(duration) FROM water_log WHERE date = '.$date.';');
		while ($row = $result->fetchArray()) {
			return round($row[0] * $this->amount_per_min,1);
		}
		return 0;
	}
	
	public function getTimeLeft() {
		$result = $this->db->query('SELECT date,time,duration FROM water_log ORDER BY date DESC,time DESC LIMIT 1;');
		while ($row = $result->fetchArray()) {
			$start_time = mktime((int)($row["time"]/100), $row["time"]%100, 0);
			$end_time = $start_time + $row["duration"]*60*$this->num_zones;
			return (int)(($end_time-time())/60)+1;
		}
		return 0;
	}
	
	public function addToWaterLog($start_time,$duration)
	{
		$end_time = $start_time + $duration*60*$this->num_zones;
		
		if (date("Ymd",$end_time) > date('Ymd',$start_time)) {
			$new_day = mktime(0, 0, 0, date("n",$end_time), date("d",$end_time), date("Y",$end_time));
			if (!$this->db->exec('INSERT INTO water_log VALUES ('.date('Ymd',$start_time).','.date('Gi',$start_time).','.(int)(($new_day-$start_time)/60).');')) die($this->db->lastErrorMsg());
			if (!$this->db->exec('INSERT INTO water_log VALUES ('.date('Ymd',$end_time).',0,'.(int)(($end_time-$new_day)/60).');')) die($this->db->lastErrorMsg());
		}
		else if (!$this->db->exec('INSERT INTO water_log VALUES ('.date('Ymd',$start_time).','.date('Gi',$start_time).','.$duration.');')) die($this->db->lastErrorMsg());
	}
	
	public function cleanWaterLog()
	{
		if (!$this->db->exec('DELETE FROM water_log WHERE date > '.date("Ymd").';')) die($this->db->lastErrorMsg());
		if (!$this->db->exec('DELETE FROM water_log WHERE date = '.date("Ymd").' AND time > '.date("Gi").';')) die($this->db->lastErrorMsg());
		
		$result = $this->db->query('SELECT * FROM water_log WHERE date = '.date("Ymd").';');
		while ($row = $result->fetchArray()) {
			$start_time = mktime((int)($row["time"]/100), $row["time"]%100, 0);
			$end_time = $start_time + $row["duration"]*60*$this->num_zones;
			if ($end_time > time()) {
				if (!$this->db->exec('DELETE FROM water_log WHERE date = '.$row["date"].' AND time = '.$row["time"].';')) die($this->db->lastErrorMsg());
			}
		}
	}
}

?>
