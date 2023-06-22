<?php

session_start();
require_once 'libjtopt.php';
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
    rage_quit("Document status file cannot be found.");
}

$doc_status = json_decode(file_get_contents($doc_status_file));

if (!isset($doc_status->texFile)) {
    rage_quit("No TeX file exists for that document.");
}

$pdffile = $doc_folder . '/' . substr($doc_status->texFile, 0, -4) . '.pdf';

if (!file_exists($pdffile)) {
    rage_quit("PDF file not found.");
}

$result = optimitize_pdf($pdffile);

if ($result === false) {
    rage_quit("PDF could not be optimized.");
}

header('Location: ' . $_SERVER["HTTP_REFERER"]);
exit(0);
