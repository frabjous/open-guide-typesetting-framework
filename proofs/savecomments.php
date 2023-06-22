<?php
session_start();

function rage_quit() {
    echo 'false' . PHP_EOL;
    exit(0);
}

// make sure get parameters are set
foreach(array("pskey","doc","set","page","commentlist") as $val) {
    if (!isset($_POST[$val])) {
        rage_quit();
    }
}

require '../getjtsettings.php';

// read parameters
$pskey = $_POST["pskey"];
$doc = $_POST["doc"];
$set = $_POST["set"];
$page = $_POST["page"];
$commjson = $_POST["commentlist"];

// make sure folder exists, with key
$setdir = $jt_settings->datafolder . '/docs/' . $doc . '/proofs/' . $set;
if (!file_exists($setdir . '/pskey.txt')) {
    rage_quit();
}

//check key
if (trim(file_get_contents($setdir . '/pskey.txt')) != trim($pskey)) {
    rage_quit();
}

$saveresult = file_put_contents($setdir  . '/comms-' . $page . '.json', $commjson);

if ($saveresult === false) {
    rage_quit();
}

echo 'true';
exit(0);
