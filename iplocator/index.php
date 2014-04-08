<form action="." method="post">
	<input type="text" name="inp">	
	<input type="submit" name="look" value="Get info">
	<?php
	if (!file_exists('ip_blocks/0.blk')) echo '<input type="submit" name="install" value="install">'; 
	?>
</form>

<?php
require_once('IpLocator.php');
if (isset($_POST['look']) && (isset($_POST['inp']) || $_POST['inp'] != '')) {
	$curTime = microtime(true);
	$loc = IpLocator::getInstance();
	$res =  $loc->LocateIp($_POST['inp']);
	$elp = (round(microtime(true) - $curTime,3)*1000);
	echo 'ip => '.$res['ip'].'<br>';
	echo 'country_code => '.$res['country_code'].'<br>';
	echo 'country_name => '.$res['country_name'].'<br>';
	echo 'region_name => '.$res['region_name'].'<br>';
	echo 'city_name => '.$res['city_name'].'<br>';
	echo 'latitude => '.$res['latitude'].'<br>';
	echo 'longitude => '.$res['longitude'].'<br>';
	echo 'zip_code => '.$res['zip_code'].'<br>';
	echo 'time_zone => '.$res['time_zone'].'<br>';

	echo '<br>Parsing time: '. $elp .' microseconds.'.'<br><br>'; 
    exit;
}
if (isset($_POST['install']) && file_exists('update/IP2LOCATION-LITE-DB11.CSV.ZIP')) {
	$loc = IpLocator::getInstance();
	//InstallBlocks(true for optimize best performance OR rows per indexed file, true for delete temp files);15.6965
	$loc->InstallBlocks(true, true);
    unset($loc);
    exit;
}else if (isset($_POST['install']) && !file_exists('update/IP2LOCATION-LITE-DB11.CSV.ZIP')) {
	echo 'Data file (update/IP2LOCATION-LITE-DB11.ZIP) is not exist!<br>';
	echo 'Register to <a href = "http://www.ip2location.com//">www.ip2location.com</a><br>';
	echo 'Download IP2LOCATION-LITE-DB11.ZIP file and copy to update folder.';
}
?>