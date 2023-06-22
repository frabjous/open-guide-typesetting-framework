<?php

session_start();
require 'libjt.php';
require $_SERVER["DOCUMENT_ROOT"] . '/kcklib/hostinfo.php';

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
    rage_quit("Document status file does not exist.");
}

$doc_status = json_decode(file_get_contents($doc_status_file));

function ke_redirect() {
    global $doc_status, $doc_folder;
    header('Location: ' . full_host() . '/ke/?file=' . urlencode( $doc_folder . '/' . $doc_status->texFile ) );
    exit(0);
}

if ($doc_status->texConverted) {
    ke_redirect();
}
// perform conversion
$conversion_result = convert_to_latex($doc_num);
if (!$conversion_result) {
    rage_quit("Conversion failed.");
}

// re-read doc status, which should have changed
$doc_status = json_decode(file_get_contents($doc_status_file));

ke_redirect();