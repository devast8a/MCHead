<?php
/** This work is free. You can redistribute it and/or modify it under the
terms of the Do What The Fuck You Want To Public License, Version 2,
as published by Sam Hocevar. See http://www.wtfpl.net/ for more details. **/

/** Settings **/
$timeout    =   86400; // A day in seconds
$skinServer =  'http://skins.minecraft.net/MinecraftSkins/';

/** To install set this to true, load the page then set to false. **/
$install = false;

/************************************************************************/
/************************************************************************/

/* Cache Control */
date_default_timezone_set('UTC');
header('Cache-Control: maxage='.$timeout);
header('Expires: ' . date('D, d M Y H:i:s', time() + $timeout) . " 'GMT'");

if($install){
    $playerName = '.default';
    $request = '16';
    $filename = 'cache/16/.default.png';
    $skinurl = 'http://media-mcw.cursecdn.com/d/d2/Char.png';
    
    function _check($n){
        if(!file_exists($n)){
            mkdir($n);
        }
    }
    
    _check('cache');
    _check('cache/16');
    _check('cache/32');
}else{
    if(!isset($_GET['u']) || !isset($_GET['s'])){
        header("HTTP/1.0 404 Not Found");
        return;
    }

    $playerName = $_GET['u'];
    $request = $_GET['s'];

    /* Validate input */
    $request = in_array($request, Array('16', '32')) ? $request : '16';
    $playerName = preg_match('([a-zA-Z0-9_]{1,16})', $playerName, $match);

    if(count($match) == 0){
        header("HTTP/1.0 404 Not Found");
        return;
    }

    $playerName = $match[0];
    $filename = 'cache/' . $request . '/' . $playerName . '.png';
    $skinurl = $skinServer . $playerName . '.png';
}

function getPNG($filename){
    header('Content-type: image/png');
    echo file_get_contents($filename);
}

function getDefault($playerName,$request){
    copy('cache/16/.default.png', 'cache/16/'.$playerName.'.png');
    copy('cache/32/.default.png', 'cache/32/'.$playerName.'.png');
    return getPNG('cache/' . $request . '/.default.png');
}

function extractHead($size, $image, $playerName){
    $head = imagecreatetruecolor($size, $size);
    imagecopyresized($head, $image, 0, 0, 8, 8, $size, $size, 8, 8);
    imagepng($head, 'cache/'.$size.'/'.$playerName.'.png');
    imagedestroy($head);
}

/* Fetch the skin from the cache */
if(file_exists($filename)){
    $stamp = filectime($filename);

    if($stamp !== FALSE && time() - $stamp < $timeout){
        return getPNG($filename);
    }
}

/* Fetch the skin from the specified skin server */
$c = curl_init($skinurl);
curl_setopt($c, CURLOPT_RETURNTRANSFER, true);

$contents = curl_exec($c);

if(curl_error($c) || curl_getinfo($c, CURLINFO_HTTP_CODE) == '404'){
    return getDefault($playerName, $request);
}

curl_close($c);

file_put_contents('cache/'.$playerName.'.png', $contents);

$image = imagecreatefromstring($contents);
extractHead(16, $image, $playerName);
extractHead(32, $image, $playerName);
imagedestroy($image);

return getPNG($filename);