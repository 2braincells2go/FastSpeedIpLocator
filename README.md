FastSpeedIpLocator
==================

Fast ip parsing from local file

Data file (update/IP2LOCATION-LITE-DB11.CSV.ZIP) is not exist!<br>
Register to <a href = "http://www.ip2location.com//">www.ip2location.com</a><br>
Download IP2LOCATION-LITE-DB11.CSV.ZIP file and copy to update folder.<br>
AVG parsing time on my server: 15.6965 us.<br>
Demo: <a href="http://atandrastoth.co.uk/main/pages/phpclasses/iplocator/">Live Demo</a><br>
install:
<pre>
require_once('IpLocator.php');
$loc = IpLocator::getInstance();
//InstallBlocks(true for optimize best performance OR 
//rows per indexed file,true for delete temp files);
$loc->InstallBlocks(true, true);
unset($loc);
</pre>
usage:
<pre>
require_once('IpLocator.php');
$loc = IpLocator::getInstance();
$res =  $loc->LocateIp($ip);
	echo 'ip => '.$res['ip'];
	echo 'country_code => '.$res['country_code'];
	echo 'country_name => '.$res['country_name'];
	echo 'region_name => '.$res['region_name'];
	echo 'city_name => '.$res['city_name'];
	echo 'latitude => '.$res['latitude'];
	echo 'longitude => '.$res['longitude'];
	echo 'zip_code => '.$res['zip_code'];
	echo 'time_zone => '.$res['time_zone'];
</pre>
Detailed description coming soon...
 
Author: Tóth András

