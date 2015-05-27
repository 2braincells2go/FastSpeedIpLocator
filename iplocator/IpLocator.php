<?php
/**
 * IpLocator v.1.0.0 PHP Class
 * Author: Tóth András
 * Web: http://atandrastoth.co.uk
 * email: atandrastoth@gmail.com
 * Licensed under the MIT license
 */
class IpLocator
{
    private $ipType = 'ipV4';
    private $blocksArray = array();
    private $FieldInfo = array();
    private $thisPath = "";
    
    public static function getInstance() {
        static $instance = null;
        if (null === $instance) {
            $instance = new static();
        }
        return $instance;
    }
    
    protected function __construct() {
        $reflector = new ReflectionClass('IpLocator');
        $this->thisPath = dirname($reflector->getFileName()) . DIRECTORY_SEPARATOR;
        $this->FieldInfo = array('ip' => '', 'country_code' => '', 'country_name' => '', 'region_name' => '', 'city_name' => '', 'latitude' => '', 'longitude' => '', 'zip_code' => '', 'time_zone' => '', 'area_code' => '');
        $this->blocksArray = $this->GetFiles($this->thisPath . 'ip_blocks' . DIRECTORY_SEPARATOR, '*');
        if (sizeof($this->blocksArray) !== 0) $this->ipType = file_exists($this->thisPath . 'ip_blocks' . DIRECTORY_SEPARATOR . '0.ipV4') ? $this->ipType : 'ipV6';
    }
    
    public function InstallBlocks($zipFile, $maxRow = true, $deltemp = false) {
        $this->DeleteFiles($this->thisPath . 'ip_blocks' . DIRECTORY_SEPARATOR);
        $this->DeleteFiles($this->thisPath . 'update' . DIRECTORY_SEPARATOR, 'zip');
        $file = $this->thisPath . $zipFile;
        $zip = new ZipArchive;
        $res = $zip->open($file);
        if ($res) {
            $zip->extractTo($this->thisPath . 'update' . DIRECTORY_SEPARATOR);
            $zip->close();
        }
        if ($deltemp) $this->DeleteFiles($this->thisPath . 'update' . DIRECTORY_SEPARATOR, 'csv');
        $file = $this->GetFiles($this->thisPath . 'update' . DIRECTORY_SEPARATOR, '[cC][sS][vV]', true);
        $matches = array();
        preg_match("/IPV6/", $file, $matches);
        $this->ipType = (sizeof($matches) !== 0) ? 'ipV6' : 'ipV4';
        $maxRow = $maxRow === true ? $this->GetMaxRows($file) : $maxRow;
        $i = 0;
        $spfile = $this->thisPath . 'ip_blocks' . DIRECTORY_SEPARATOR . "0." . $this->ipType;
        $handle = fopen($file, 'r') or die("Couldn't get handle");
        $splitHandle = fopen($spfile, 'a') or die("can't open file");
        if ($handle) {
            while (!feof($handle)) {
                $i++;
                $row = fgets($handle);
                if (!is_resource($splitHandle)) {
                    $fileld = str_getcsv($row, ',', '"', '\\');
                    $splitHandle = fopen($this->thisPath . 'ip_blocks' . DIRECTORY_SEPARATOR . str_replace('"', '', $fileld[0]) . '.' . $this->ipType, 'a') or die("can't open file");
                }
                fwrite($splitHandle, $row);
                if ($i == $maxRow) {
                    fclose($splitHandle);
                    $i = 0;
                }
            }
        }
        fclose($handle);
        if ($deltemp) $this->DeleteFiles($this->thisPath . 'update' . DIRECTORY_SEPARATOR);
    }
    
    public function LocateIp($ip = '') {
        $ip = trim($ip);
        if ($this->ipType == 'ipV6') {
            if (strpos($ip, ':') === false) {
                $long = $this->Ipv6ToLongV2($this->IPv4To6($ip)) [1];
            } 
            else {
                $long = $this->Ipv6ToLongV1($ip);
            }
        } 
        else {
            $long = abs(ip2long($ip));
        }
        $last = '0';
        foreach ($this->blocksArray as $value) {
            if ($long <= $value) {
                $retval = $this->GetIpData($long, $last);
                $retval = str_getcsv($retval, ',', '"', '\\');
                $i = 1;
                unset($retval[0], $retval[1]);
                $retval[1] = $ip;
                foreach ($this->FieldInfo as $key => $value) {
                    if (isset($retval[$i])) {
                        $this->FieldInfo[$key] = str_replace('"', '', $retval[$i]);
                    } 
                    else {
                        unset($this->FieldInfo[$key]);
                    }
                    $i++;
                }
                return $this->FieldInfo;
            }
            $last = $value;
        }
    }
    
    private function GetMaxRows($fl) {
        $i = 0;
        if ($handle = fopen($fl, "r")) {
            while (!feof($handle)) {
                $buffer = fgets($handle);
                $i++;
            }
        }
        return round(sqrt($i));
    }
    
    private function DeleteFiles($dir, $excludeType = 'null') {
        $it = new RecursiveDirectoryIterator($dir);
        $it = new RecursiveIteratorIterator($it, RecursiveIteratorIterator::CHILD_FIRST);
        foreach ($it as $file) {
            if ('.' === $file->getBasename() || '..' === $file->getBasename() || strtolower(pathinfo($file, PATHINFO_EXTENSION)) === strtolower($excludeType)) continue;
            if ($file->isDir()) rmdir($file->getPathname());
            else unlink($file->getPathname());
        }
    }
    
    private function GetFiles($openFolder, $type = "ipV4", $maxSizeFile = false) {
        $ipFiles = glob($openFolder . '*.' . $type, GLOB_BRACE);
        if ($maxSizeFile) {
            $docs = array();
            foreach ($ipFiles as $path) {
                $docs[$path] = filesize($path);
            }
            asort($docs, SORT_NUMERIC);
            end($docs);
            return key($docs);
        }
        foreach ($ipFiles as $key => $value) {
            $ipFiles[$key] = pathinfo(basename($value), PATHINFO_FILENAME);
        }
        sort($ipFiles, SORT_NUMERIC);
        return $ipFiles;
    }
    
    private function GetIpData($value, $ipblk) {
        $maybe = '';
        $handle = fopen($this->thisPath . 'ip_blocks' . DIRECTORY_SEPARATOR . $ipblk . '.' . $this->ipType, "r") or die("Couldn't get handle");
        if ($handle) {
            while (!feof($handle)) {
                $buffer = fgets($handle);
                if (strlen($buffer) >= 8) {
                    $b = str_getcsv($buffer, ',', '"', '\\');
                    $ip_from = trim($b[0]);
                    $ip_to = trim($b[1]);
                    if ($ip_from <= $value && $ip_to >= $value) {
                        fclose($handle);
                        return $buffer;
                    }
                    if ($ip_from <= $value) {
                        $maybe = $buffer;
                    }
                }
            }
            fclose($handle);
            return $maybe;
        }
    }
    
    private function IPv4To6($Ip) {
        static $Mask = '::ffff:';
        $IPv6 = (strpos($Ip, '::') === 0);
        $IPv4 = (strpos($Ip, '.') > 0);
        
        if (!$IPv4 && !$IPv6) return false;
        if ($IPv6 && $IPv4) $Ip = substr($Ip, strrpos($Ip, ':') + 1);
        elseif (!$IPv4) return $Ip;
        
        $Ip = array_pad(explode('.', $Ip), 4, 0);
        if (count($Ip) > 4) return false;
        for ($i = 0; $i < 4; $i++) if ($Ip[$i] > 255) return false;
        
        $Part7 = base_convert(($Ip[0] * 256) + $Ip[1], 10, 16);
        $Part8 = base_convert(($Ip[2] * 256) + $Ip[3], 10, 16);
        return $Mask . $Part7 . ':' . $Part8;
    }
    
    private function ExpandIPv6Notation($Ip) {
        if (strpos($Ip, '::') !== false) $Ip = str_replace('::', str_repeat(':0', 8 - substr_count($Ip, ':')) . ':', $Ip);
        if (strpos($Ip, ':') === 0) $Ip = '0' . $Ip;
        return $Ip;
    }
    
    private function Ipv6ToLongV2($Ip, $DatabaseParts = 2) {
        $Ip = $this->ExpandIPv6Notation($Ip);
        $Parts = explode(':', $Ip);
        $Ip = array('', '');
        for ($i = 0; $i < 4; $i++) $Ip[0].= str_pad(base_convert($Parts[$i], 16, 2), 16, 0, STR_PAD_LEFT);
        for ($i = 4; $i < 8; $i++) $Ip[1].= str_pad(base_convert($Parts[$i], 16, 2), 16, 0, STR_PAD_LEFT);
        
        if ($DatabaseParts == 2) return array(base_convert($Ip[0], 2, 10), base_convert($Ip[1], 2, 10));
        else return base_convert($Ip[0], 2, 10) + base_convert($Ip[1], 2, 10);
    }
    private function Ipv6ToLongV1($ip) {
        $binNum = '';
        foreach (unpack('C*', inet_pton($ip)) as $byte) {
            $binNum.= str_pad(decbin($byte), 8, "0", STR_PAD_LEFT);
        }
        return base_convert(ltrim($binNum, '0'), 2, 10);
    }
}
