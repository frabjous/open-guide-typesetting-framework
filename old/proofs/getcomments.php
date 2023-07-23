<?php
session_start();

function rage_quit() {
    echo 'ERROR' . PHP_EOL;
    exit(0);
}

// make sure get parameters are set
foreach(array("pskey","doc","set","page") as $val) {
    if (!isset($_GET[$val])) {
        rage_quit();
    }
}

require '../getjtsettings.php';

// read parameters
$pskey = $_GET["pskey"];
$doc = $_GET["doc"];
$set = $_GET["set"];
$page = $_GET["page"];

// make sure folder exists, with key
$setdir = $jt_settings->datafolder . '/docs/' . $doc . '/proofs/' . $set;
if (!file_exists($setdir . '/pskey.txt')) {
    rage_quit();
}

//check key
if (trim(file_get_contents($setdir . '/pskey.txt')) != trim($pskey)) {
    rage_quit();
}

$rv = new StdClass();
$rv->pagenum = intval($page);

if (file_exists($setdir . '/comms-' . $page . '.json')) {
    $rv->comms = json_decode(file_get_contents($setdir . '/comms-' . $page . '.json'));
} else {
    $rv->comms = array();
}

echo json_encode($rv);
exit(0);