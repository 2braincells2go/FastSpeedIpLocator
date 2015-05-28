<!DOCTYPE html>
<html>
    <head>
        <title>IpLocator</title>
    </head>
    <body>
        <form action="." method="post">
            <input type="text" name="inp">
            <input type="submit" name="look" value="Get info">
            <input type="submit" name="install" value="install">
        </form>
        <?php
        ini_set('max_execution_time', 300);
        require_once ('IpLocator.php');
        if (isset($_POST['look']) && $_POST['inp'] != '') {
            $curTime = microtime(true);
            $loc = IpLocator::getInstance();
            $res = $loc->LocateIp($_POST['inp']);
            $elp = (round(microtime(true) - $curTime, 3) * 1000);
            echo '<br>';
            if($res){
                foreach ($res as $key => $value) {
                    echo $key . ' => ' . $value . '<br>';
                }
            }
            echo '<br>';
            echo 'Elpassed time: ' . $elp . ' Us';
        }
        $filename = 'update/*.[zZ][iI][pP]';
        if (isset($_POST['install']) && count(glob($filename)) > 0) {
            $loc = IpLocator::getInstance();
            
            //InstallBlocks(true for optimize best performance OR rows per indexed file, true for delete temp files);15.6965
            $loc->InstallBlocks(glob($filename) [0], true, true);
            unset($loc);
            exit;
        } 
        else if (isset($_POST['install']) && count(glob($filename)) === 0) {
            echo 'Data file (update/*.ZIP) is not exist!<br>';
            echo 'Register to <a href = "http://www.ip2location.com//">www.ip2location.com</a><br>';
            echo 'Download IP Location Database CSV in Zip format and copy to update folder.';
        }
        ?>
    </body>
</html>