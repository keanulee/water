# Water

Water is an Irrigation controller web app for EtherRain devices.
For more details about the EtherRain Networked Sprinkler Controller, please visit
http://www.quicksmart.com/qs_etherrain.html.

# Setup

I have not streamlined the setup process (yet...), but here is a guideline of what needs to be done.

## Database

Water uses a SQLite database. To create the database, run `setup.php`. The database can be reset by deleting `db/data.db` and re-running `setup.php`.

## Configuration file

In `config.php`:

```php
// EtherRain device settings
$ipaddress = "192.168.0.1";
$port = 8080;
$simulated = true;
$amount_per_min = 0.635;
$num_zones = 2;
```

This includes the network location of the EtherRain device; it is how the web server will interact with the device. You can choose to simulate the device instead of actually triggering a device. Amount per minute is how many mm of water the lawn gets per minute of watering, and number of zones is used to calculate how much watering time is left.

```php
// Location settings (for time and weather)
$timezone = "America/Edmonton";
$location = "calgary,canada";
```

This is the physical location of the EtherRain device. It is used to determine time and weather.

```php
// For worldweatheronline.com
$api_key = "get_key_from_worldweatheronline.com";
```

This is for the weather source. It is required.

# Cron Job

Add a cron job to run cron.php while in its directory every 5 minutes. Optionally, log the output by appending it to a file.
Weather will be updated on the hour.

```
*/5 * * * * cd /path/to/water/; php -q cron.php >> cron_log.txt;
```
