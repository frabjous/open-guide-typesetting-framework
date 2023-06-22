<?php

session_start();
require 'getjtsettings.php';
require $_SERVER["DOCUMENT_ROOT"] . '/kcklib/hostinfo.php';


function rage_quit($s = '') {
    echo "ERROR: $s.";
    exit(0);
}

if (!isset($_SESSION["_jt_user"])) {
    rage_quit("Not logged in");
}

if (!isset($_POST["docnum"])) {
    rage_quit("Document number not specified");
}


$doc_num = $_POST["docnum"]; 
    
$doc_folder = $jt_settings->datafolder . '/docs/' . $doc_num;

if (!is_dir($doc_folder)) {
    rage_quit("Specified document does not exist");
}

$doc_meta_file = $doc_folder . '/metadata.json';

file_put_contents($doc_meta_file, json_encode($_POST, JSON_UNESCAPED_UNICODE));

$path = full_path();
if (substr($path, -1) != '/') {
    $path = dirname($path);
}
if (substr($path, -1) != '/') {
    $path .= '/';
}
header('Location: ' . $path  );
exit(0);