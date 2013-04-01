# Water

Water is an Irrigation controller web app for EtherRain devices.
For more details about the EtherRain Networked Sprinkler Controller, please visit
http://www.quicksmart.com/qs_etherrain.html.

# Setup

I have not streamlined the setup process (yet...), but here is a guideline of what needs to be done.

## Database

Water uses a SQLite database. I have included a sample DB file in the repository. The database structure can
be observed by looking at the commented lines in the `app/` directory.

## Configuration file

In `config.php`:

```php
//EtherRain device settings
$ipaddress = "192.168.0.1";
$port = 8080;
```

This is the network location of the EtherRain device. It is how the web server will interact with the device

```php
//location settings (for time and weather)
$timezone = "America/Edmonton";
$location = "calgary,canada";
```

This is the physical location of the EtherRain device. It is used to determine time and weather.

```php
//for worldweatheronline.com
$api_key = "get_key_from_worldweatheronline.com";
```

This is for the weather source. It is required.

# Cron Job

Add a cron job to run cron.php while in its directory every 5 minutes. Optionally, log the output by appending it to a file.
Weather will be updated on the hour.

```
*/5 * * * * cd /path/to/water/; php -q cron.php >> cron_log.txt;
```

# License

This work is licensed under a Creative Commons Attribution-NonCommercial-ShareAlike 3.0 Unported License.
See http://creativecommons.org/licenses/by-nc-sa/3.0/.


