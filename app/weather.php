<?php

class Weather
{
	private $db;
	private $source_url;

	//sets location
	public function __construct($location,$api_key)
	{
		$this->db = new SQLiteDatabase('db/data.db');
		$this->source_url = "http://free.worldweatheronline.com/feed/weather.ashx?format=json&num_of_days=5&q=".$location."&key=".$api_key;
		
		//reset weather log
//		if (!$this->db->queryExec('DROP TABLE weather_log',$error)) die($error);
//		if (!$this->db->queryExec('CREATE TABLE weather_log (date INTEGER PRIMARY KEY, precipMM REAL, temp_C INTEGER, weatherDesc TEXT, weatherCode INTEGER, weatherIconUrl TEXT);',$error)) die($error);
//		if (!$this->db->queryExec('DROP TABLE current_condition',$error)) die($error);
//		if (!$this->db->queryExec('CREATE TABLE current_condition (precipMM REAL, temp_C INTEGER, weatherDesc TEXT, weatherCode INTEGER, weatherIconUrl TEXT);',$error)) die($error);
//		if (!$this->db->queryExec('INSERT INTO current_condition VALUES (0, 0, "", 0, "");',$error)) die($error);		
	}
	
	//closes database
	public function __destruct() 
	{
		unset($this->db);
	}
	
	public function updateWeatherSource()
	{
		//Delete weather logs older than a week, and delete future logs to get fresh data next time
		if (!$this->db->queryExec('DELETE FROM weather_log WHERE date < '.date("Ymd",time()-(60*60*24*7)).';',$error)) die($error);
		
		//get weather info
		$ch = curl_init($this->source_url);
		$options = array(
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_HTTPHEADER => array('Content-type: application/json')
		);
		curl_setopt_array($ch, $options);
		$result = curl_exec($ch); // Getting jSON result string
		if ($result) $result = json_decode($result);
		
		$current_condition = $result->data->current_condition[0];
		if (!$this->db->queryExec('UPDATE current_condition SET precipMM = '.$current_condition->precipMM.', temp_C = '.$current_condition->temp_C.', weatherDesc = "'.$current_condition->weatherDesc[0]->value.'", weatherCode = '.$current_condition->weatherCode.', weatherIconUrl = "'.$current_condition->weatherIconUrl[0]->value.'";',$error)) die($error);
		
		foreach ($result->data->weather as $forecast) {
			if (!$this->db->queryExec('REPLACE INTO weather_log VALUES ('.str_replace("-", "", $forecast->date).', '.$forecast->precipMM.', '.$forecast->tempMaxC.', "'.$forecast->weatherDesc[0]->value.'", '.$forecast->weatherCode.', "'.$forecast->weatherIconUrl[0]->value.'");',$error)) die($error);
		}	
	}
	
	public function getWeatherIconUrl($date = 0)
	{
		$result;
		if ($date) $result = $this->db->arrayQuery('SELECT * FROM weather_log WHERE date = '.$date.';');
		else $result = $this->db->arrayQuery('SELECT * FROM current_condition;');
		foreach ($result as $row) {
			return $row['weatherIconUrl'];
		}
		return NULL;
	}
	
	public function getWeatherDesc($date = 0)
	{
		$result;
		if ($date) $result = $this->db->arrayQuery('SELECT * FROM weather_log WHERE date = '.$date.';');
		else $result = $this->db->arrayQuery('SELECT * FROM current_condition;');
		foreach ($result as $row) {
			return $row['weatherDesc'];
		}
		return NULL;
	}
	
	public function getTemperature($date = 0)
	{
		$result;
		if ($date) $result = $this->db->arrayQuery('SELECT * FROM weather_log WHERE date = '.$date.';');
		else $result = $this->db->arrayQuery('SELECT * FROM current_condition;');
		foreach ($result as $row) {
			return $row['temp_C'];
		}
		return NULL;
	}
	
	public function getWeatherCode($date = 0)
	{
		$result;
		if ($date) $result = $this->db->arrayQuery('SELECT * FROM weather_log WHERE date = '.$date.';');
		else $result = $this->db->arrayQuery('SELECT * FROM current_condition;');
		foreach ($result as $row) {
			return $row['weatherCode'];
		}
		return NULL;
	}
	
	public function isRaining($date = 0)
	{
		return ($this->getWeatherCode($date) >= 176) && ($this->getWeatherCode($date) != 248) && ($this->getWeatherCode($date) != 260);
	}
	
	public function getRainAmount($date = 0)
	{
		$result;
		if ($date) $result = $this->db->arrayQuery('SELECT * FROM weather_log WHERE date = '.$date.';');
		else $result = $this->db->arrayQuery('SELECT * FROM current_condition;');
		foreach ($result as $row) {
			return $row['precipMM'];
		}
		return NULL;
	}
	
	public function willRain($days)
	{
		for ($i = $days; $i > 0; $i--)
		{
			$date = date("Ymd",time()+(60*60*24*$i));
			if ($this->isRaining($date)) return true;
		}
		return false;
	}
	
	public function didRainAmount($days, $amount)
	{
		for ($i = 0; $i < $days; $i++)
		{
			$date = date("Ymd",time()-(60*60*24*$i));
			$amount -= $this->getRainAmount($date);
		}
		return ($amount <= 0);
	}
}

?>
