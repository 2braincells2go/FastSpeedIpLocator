<?php
class IpLocator {
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
        $reflector         = new ReflectionClass('IpLocator');
        $this->thisPath    = dirname($reflector->getFileName()) . DIRECTORY_SEPARATOR;
        $this->FieldInfo   = array(
            'ip' => '',
            'country_code' => '',
            'country_name' => '',
            'region_name' => '',
            'city_name' => '',
            'latitude' => '',
            'longitude' => '',
            'zip_code' => '',
            'time_zone' => ''
        );
        $this->blocksArray = $this->GetFiles($this->thisPath . 'ip_blocks/');
    }
    public function InstallBlocks($maxRow = true, $deltemp = false) {
        $this->DeleteFiles($this->thisPath . 'ip_blocks/', 'blk', false);
        if ($deltemp) {
            $this->DeleteFiles($this->thisPath . 'update/', 'zip', true);
            $file = $this->thisPath . 'update/' . $this->GetFiles($this->thisPath . 'update/', 'zip', true);
            $zip  = new ZipArchive;
            $res  = $zip->open($file);
            if ($res) {
                $zip->extractTo($this->thisPath . 'update/');
                $zip->close();
            }
        }
        if ($deltemp)
            $this->DeleteFiles($this->thisPath . 'update/', 'csv', true);
        $file   = $this->thisPath . 'update/' . $this->GetFiles($this->thisPath . 'update/', 'csv', true);
        $maxRow = $maxRow === true ? $this->GetMaxRows($file) : $maxRow;
        $i      = 0;
        $spfile = $this->thisPath . 'ip_blocks/' . "0.blk";
        $handle = fopen($file, 'r') or die("Couldn't get handle");
        $splitHandle = fopen($spfile, 'a') or die("can't open file");
        if ($handle) {
            while (!feof($handle)) {
                $i++;
                $row = fgets($handle);
                if (!is_resource($splitHandle)) {
                    $fileld = str_getcsv($row, ',', '"', '\\');
                    $splitHandle = fopen($this->thisPath . 'ip_blocks/' . str_replace('"', '', $fileld[0]) . '.blk', 'a') or die("can't open file");
                }
                fwrite($splitHandle, $row);
                if ($i == $maxRow) {
                    fclose($splitHandle);
                    $i = 0;
                }
            }
        }
        fclose($handle);
        if ($deltemp)
            $this->DeleteFiles($this->thisPath . 'update/', 'csv', false);
    }
    public function LocateIp($ip = '') {
        $long = $this->ip2int($ip);
        $last = '0';
        foreach ($this->blocksArray as $value) {
            if ($long <= $value) {
                $retval = $this->GetIpData($long, $last);
                $retval = str_getcsv($retval, ',', '"', '\\');
                $i      = 1;
                unset($retval[0], $retval[1]);
                $retval[1] = $ip;
                foreach ($this->FieldInfo as $key => $value) {
                    $this->FieldInfo[$key] = str_replace('"', '', $retval[$i]);
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
    private function DeleteFiles($openFolder, $type = 'blk', $filt = false) {
        $ipFiles = array();
        $i       = 0;
        if ($handle = opendir($openFolder)) {
            while (false !== ($entry = readdir($handle))) {
                if ($filt && $entry != "." && $entry != ".." && strtolower(substr($entry, strlen($entry) - 3)) != $type) {
                    unlink($openFolder . $entry);
                } else if (!$filt && strtolower(substr($entry, strlen($entry) - 3)) == $type) {
                    unlink($openFolder . $entry);
                }
            }
            closedir($handle);
        }
        sort($ipFiles);
        return $ipFiles;
    }
    private function ip2int($ip) {
        $a = explode(".", trim($ip));
        if (sizeof($a) == 4) {
            return $a[0] * 256 * 256 * 256 + $a[1] * 256 * 256 + $a[2] * 256 + $a[3];
        } else {
            return 0;
        }
    }
    private function GetFiles($openFolder, $type = "blk", $one = false) {
        $ipFiles = array();
        $i       = 0;
        if ($handle = opendir($openFolder)) {
            while (false !== ($entry = readdir($handle))) {
                if ($entry != "." && $entry != ".." && strtolower(substr($entry, strlen($entry) - 3)) == $type) {
                    if ($one)
                        return $entry;
                    $splitted  = explode('.', $entry);
                    $ipFiles[] = $splitted[0];
                }
            }
            closedir($handle);
        }
        sort($ipFiles);
        return $ipFiles;
    }
    private function GetIpData($value, $ipblk) {
        $maybe = '';
        $handle = fopen($this->thisPath . 'ip_blocks/' . $ipblk . '.blk', "r") or die("Couldn't get handle");
        if ($handle) {
            while (!feof($handle)) {
                $buffer = fgets($handle);
                if (strlen($buffer) >= 8) {
                    $b       = str_getcsv($buffer, ',', '"', '\\');
                    $ip_from = trim($b[0]);
                    $ip_to   = trim($b[1]);
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
}
