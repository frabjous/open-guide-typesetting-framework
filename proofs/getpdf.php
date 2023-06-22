<?php
session_start();

function nf_quit() {
    http_response_code(404);
    exit(0);
}

// make sure get parameters are set
foreach(array("pskey","doc","set") as $val) {
    if (!isset($_GET[$val])) {
        nf_quit();
    }
}

require '../getjtsettings.php';

// read parameters
$pskey = $_GET["pskey"];
$doc = $_GET["doc"];
$set = $_GET["set"];

// make sure folder exists, with key
$setdir = $jt_settings->datafolder . '/docs/' . $doc . '/proofs/' . $set;
if (!file_exists($setdir . '/pskey.txt')) {
    nf_quit();
}

//check key
if (trim(file_get_contents($setdir . '/pskey.txt')) != trim($pskey)) {
    nf_quit();
}

// determine file
$pdffile = '';
$file_list = scandir($setdir);
foreach ($file_list as $pfile) {
    if (strtolower(substr($pfile, -4)) == '.pdf') {
        $pdffile = $setdir . '/' . $pfile;
        break;
    }
}
if ($pdffile == '') {
    nf_quit();
}


header("Content-Type:application/pdf");
header("Content-Length:".filesize($pdffile));
header('Content-Disposition: attachment; filename="' . basename($pdffile) . '"');
readfile($pdffile);
exit(0);