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

if (!isset($_POST["newdoc"])) {
    rage_quit("No document number specified");
}

$doc_num = $_POST["newdoc"];

$doc_folder = $jt_settings->datafolder . '/docs/' . $doc_num;

if (!is_dir($doc_folder)) {
    mkdir($doc_folder , 0755 , true);
}

$doc_meta_file = $doc_folder . '/metadata.json';

if (!file_exists($doc_meta_file)) {
    file_put_contents($doc_meta_file, '{}');
}

$path = full_path();
if (substr($path, -1) != '/') {
    $path = dirname($path);
}
if (substr($path, -1) != '/') {
    $path .= '/';
}
header('Location: ' . $path . 'editproject.php?doc=' . urlencode($doc_num) );
exit(0);