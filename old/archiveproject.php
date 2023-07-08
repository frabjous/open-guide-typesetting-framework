<?php
session_start();
require_once 'getjtsettings.php';

function rage_quit($s) {
    global $err_page_msg;
    $err_page_msg = $s;
    include 'error_page.php';
    exit(0);
}

if (!isset($_SESSION["_jt_user"])) {
    rage_quit("Not logged in");
}

if (!isset($_GET["doc"])) {
    rage_quit("No document number specified.");
}

$doc_num = $_GET["doc"];

$doc_folder = $jt_settings->datafolder . '/docs/' . $doc_num;

$doc_status_file = $doc_folder . '/status.json';

if (!file_exists($doc_status_file)) {
    rage_quit("Status file does not exist.");
}

$doc_status = json_decode(file_get_contents($doc_status_file));

if ((!isset($doc_status->archived)) || ($doc_status->archived)) {
    $doc_status->archived = false;
} else {
    $doc_status->archived = true;
}

file_put_contents($doc_status_file, json_encode($doc_status, JSON_UNESCAPED_UNICODE));

header('Location: ' . $_SERVER["HTTP_REFERER"]);
exit(0);
