<?php
session_start();

function nf_quit() {
    http_response_code(404);
    exit(0);
}

// make sure get parameters are set
foreach(array("pskey","doc","set","page") as $val) {
    if (!isset($_GET[$val])) {
        nf_quit();
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
    nf_quit();
}

//check key
if (trim(file_get_contents($setdir . '/pskey.txt')) != trim($pskey)) {
    nf_quit();
}

//check to see if page exists
$file = $setdir . '/page-' . $page . '.png';
if (!file_exists($file)) {
    nf_quit();
}

header("Content-Type:image/png");
header("Content-Length:".filesize($file));
readfile($file);
exit(0);