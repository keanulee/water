<?php

class Weather
{
	private $db;
	private $source_url;

	//sets location
	public function __construct($location,$api_key)
	{
		$this->db = new SQLite3('db/data.db');
		$this->source_url = "http://api.worldweatheronline.com/free/v1/weather.ashx?format=json&num_of_days=5&q=".$location."&key=".$api_key;
	}
	
	//closes database
	public function __destruct() 
	{
		$this->db->close();
	}
	
	public function updateWeatherSource()
	{
		//Delete weather logs older than a week, and delete future logs to get fresh data next time
		if (!$this->db->exec('DELETE FROM weather_log WHERE date < '.date("Ymd",time()-(60*60*24*7)).';')) die($this->db->lastErrorMsg());
		
		//get weather info
		$ch = curl_init($this->source_url);
		$options = array(
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_HTTPHEADER => array('Content-type: application/json')
		);
		curl_setopt_array($ch, $options);
		$result = curl_exec($ch); // Getting jSON result string
		if ($result) $result = json_decode($result);
		
		if (empty($result->data->weather)) return false;
		
		$current_condition = $result->data->current_condition[0];
		if (!$this->db->exec('UPDATE current_condition SET precipMM = "'.$current_condition->precipMM.'", temp_C = "'.$current_condition->temp_C.'", weatherDesc = "'.$current_condition->weatherDesc[0]->value.'", weatherCode = "'.$current_condition->weatherCode.'", weatherIconUrl = "'.$current_condition->weatherIconUrl[0]->value.'";')) die($this->db->lastErrorMsg());
		
		foreach ($result->data->weather as $forecast) {
			if (!$this->db->exec('REPLACE INTO weather_log VALUES ("'.str_replace("-", "", $forecast->date).'", "'.$forecast->precipMM.'", "'.$forecast->tempMaxC.'", "'.$forecast->weatherDesc[0]->value.'", "'.$forecast->weatherCode.'", "'.$forecast->weatherIconUrl[0]->value.'");')) die($this->db->lastErrorMsg());
		}
		
		return true;
	}
	
	public function getWeatherIconUrl($date = 0)
	{
		$result;
		if ($date) $result = $this->db->query('SELECT * FROM weather_log WHERE date = '.$date.';');
		else $result = $this->db->query('SELECT * FROM current_condition;');
		while ($row = $result->fetchArray()) {
			return $row['weatherIconUrl'];
		}
		return NULL;
	}
	
	public function getWeatherDesc($date = 0)
	{
		$result;
		if ($date) $result = $this->db->query('SELECT * FROM weather_log WHERE date = '.$date.';');
		else $result = $this->db->query('SELECT * FROM current_condition;');
		while ($row = $result->fetchArray()) {
			return $row['weatherDesc'];
		}
		return NULL;
	}
	
	public function getTemperature($date = 0)
	{
		$result;
		if ($date) $result = $this->db->query('SELECT * FROM weather_log WHERE date = '.$date.';');
		else $result = $this->db->query('SELECT * FROM current_condition;');
		while ($row = $result->fetchArray()) {
			return $row['temp_C'];
		}
		return NULL;
	}
	
	public function getWeatherCode($date = 0)
	{
		$result;
		if ($date) $result = $this->db->query('SELECT * FROM weather_log WHERE date = '.$date.';');
		else $result = $this->db->query('SELECT * FROM current_condition;');
		while ($row = $result->fetchArray()) {
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
		if ($date) $result = $this->db->query('SELECT * FROM weather_log WHERE date = '.$date.';');
		else $result = $this->db->query('SELECT * FROM current_condition;');
		while ($row = $result->fetchArray()) {
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
