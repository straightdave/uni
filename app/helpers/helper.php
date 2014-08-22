<?php

function getVersionCode() {
    $v = phpversion();
    preg_match('/\d+\.\d+\.\d+/', $v, $matches);
    return $matches[0];
}

function isVersionHigherThan($v) {
    try{
        $cv = getVersionCode();
        list($cv_1, $cv_2, $cv_3) = split('\.', $cv, 3);
        list($v_1, $v_2, $v_3) = split('\.', $v, 3);
        //print $cv_1 . $cv_2 . $cv_3;
        //print $v_1 . $v_2 . $v_3;

        return ( (int)$cv_1 > (int)$v_1 ) or
               ( (int)$cv_1 == (int)$v_1 and (int)$cv_2 > (int)$v_2 ) or
               ( (int)$cv_1 == (int)$v_1 and (int)$cv_2 == (int)$v_2 and (int)$cv_3 >= (int)$v_3 );
    }
    catch(Exception $e){
        return false;
    }
}

function hasSetGETParams($array) {
    foreach($array as $item)
        if( !isset($_GET[$item]) or is_null($_GET[$item]) or empty($_GET[$item]) )
            return false;
    return true;
}

function adt() {
    $t = (new DateTime('now'))->format('Y-m-d H:i:s');
    return '[' . $t . substr((string)microtime(), 1, 8) . '] ';
}
