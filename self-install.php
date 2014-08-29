<?php

// script to deploy web app!
// Dave Wu, Aug 2014
//
// Copy this folder to target and run This:
// sudo php -f <this-file-name>.php
//

// some functions
//
function rrmdir($dir) {
    if( is_dir($dir) ) {
        $objects = scandir($dir);
        foreach($objects as $obj) {
            if($obj != '.' and $obj != '..') {
                if(filetype($dir.'/'.$obj) == "dir")
                    rrmdir($dir.'/'.$obj);
                else
                    unlink($dir.'/'.$obj);
            }
        }
        reset($objects);
        rmdir($dir);
    }
}

//
// 1. read yaml config
//
require_once "vendor/autoload.php";
use Symfony\Component\Yaml\Yaml;
$config = Yaml::parse(file_get_contents('config.yml'));
$siteconf = $config['siteconf'];

//
// 2. stop site
//
$output = shell_exec('a2dissite uni');
echo "$output";

//
// 3. modify new site conf & copy it to sites-available
//
$str = file_get_contents('uni.conf');
$str = str_replace('{port}', $siteconf['port'], $str);
$str = str_replace('{docroot}', $siteconf['docroot'], $str);
file_put_contents('uni.conf', $str);
copy('uni.conf', '/etc/apache2/sites-available/uni.conf'); // will replace original one

//
// 4. clean original site file and copy new one
//
if( file_exists($siteconf['dir']) and is_dir($siteconf['dir']) ) {
    rrmdir($siteconf['dir']);
}
$output = shell_exec('mkdir ' . $siteconf['dir']);
echo "$output";
$output = shell_exec('cp -r * ' . $siteconf['dir']);
echo "$output";

//
// 5. enable new site
//
$output = shell_exec('a2ensite uni');
echo "$output";

//
// 6. reload apache2
//
$output = shell_exec('service apache2 reload');
echo "$output";

//
// 7. restore db
//
$dbconf = $config['db-dev'];
$db = $dbconf['database'];
$user = $dbconf['username'];
$pwd = $dbconf['password'];

$output = shell_exec('mysql -u ' . $user . ' -p' . $pwd . ' -e "DROP DATABASE IF EXISTS ' . $db . ';"');
echo "$output";

$output = shell_exec('mysql -u ' . $user . ' -p' . $pwd . ' -e "CREATE DATABASE ' . $db . ';"');
echo "$output";

$output = shell_exec('mysql -u ' . $user . ' -p' . $pwd . ' ' . $db . ' < db.sql');
echo "$output";
