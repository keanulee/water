<?php

class Scheduler
{
	private $db;
	private $settings;
	
	//opens database, stores settings locally
	public function __construct()
	{
		$this->db = new SQLiteDatabase('db/data.db');
		
		//reset settings
//		if (!$this->db->queryExec('DROP TABLE settings',$error)) die($error);
//		if (!$this->db->queryExec('CREATE TABLE settings (single_duration INTEGER, duration INTEGER, time INTEGER, sunday INTEGER, monday INTEGER, tuesday INTEGER, wednesday INTEGER, thursday INTEGER, friday INTEGER, saturday INTEGER, past_rain INTEGER, past_max_amount INTEGER, past_num_days INTEGER, present_rain INTEGER, future_rain INTEGER, future_num_days INTEGER);',$error)) die($error);
//		if (!$this->db->queryExec('INSERT INTO settings VALUES (30,20,300,0,1,0,0,1,0,0,0,20,5,1,1,1);',$error)) die($error);
	
		$result = $this->db->arrayQuery('SELECT * FROM settings;');
		
		foreach ($result as $row) {
			$this->settings = $row;
		}
	}
	
	//closes database
	public function __destruct() 
	{
		if (!$this->db->queryExec('UPDATE settings SET single_duration = '.(int)$this->settings['single_duration'].', duration = '.(int)$this->settings['duration'].', time = '.(int)$this->settings['time'].', sunday = '.(int)$this->settings['sunday'].', monday = '.(int)$this->settings['monday'].', tuesday = '.(int)$this->settings['tuesday'].', wednesday = '.(int)$this->settings['wednesday'].', thursday = '.(int)$this->settings['thursday'].', friday = '.(int)$this->settings['friday'].', saturday = '.(int)$this->settings['saturday'].', past_rain = '.(int)$this->settings['past_rain'].', past_max_amount = '.(int)$this->settings['past_max_amount'].', past_num_days = '.(int)$this->settings['past_num_days'].', present_rain = '.(int)$this->settings['present_rain'].', future_rain = '.(int)$this->settings['future_rain'].', future_num_days = '.(int)$this->settings['future_num_days'].';',$error)) die($error);
	
		unset($this->db);
	}
	
	public function getSetting($setting)
	{
		return $this->settings[$setting];
	}
	
	public function setSetting($setting,$value)
	{
		if ($value == "on") $value = 1;
		elseif ($value == "off") $value = 0;
		$this->settings[$setting] = (int)$value;
	}
	
	public function getNextRunTime()
	{
		$next_time = mktime((int)($this->getSetting('time')/100), $this->getSetting('time')%100, 0);
		if (time() >= $next_time) $next_time += (60*60*24);
		
		for ($i = 0; $i < 7; $i++) {
			$next_day = (int)date("w",$next_time);
			if ($next_day == 0 && $this->getSetting('sunday')) return $next_time;
			if ($next_day == 1 && $this->getSetting('monday')) return $next_time;
			if ($next_day == 2 && $this->getSetting('tuesday')) return $next_time;
			if ($next_day == 3 && $this->getSetting('wednesday')) return $next_time;
			if ($next_day == 4 && $this->getSetting('thursday')) return $next_time;
			if ($next_day == 5 && $this->getSetting('friday')) return $next_time;
			if ($next_day == 6 && $this->getSetting('saturday')) return $next_time;
			
			$next_time += (60*60*24);
		}
		return 0;
	}
}

?>
