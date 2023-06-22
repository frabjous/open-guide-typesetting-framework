<?php

session_start();

require 'getjtsettings.php';

function rage_quit($s) {
    echo 'ERROR: ' . $s . '.' . PHP_EOL;
    exit(0);
}

// quit if not logged in
if (!isset($_SESSION["_jt_user"])) {
    rage_quit("Not logged in");
}

// quit if no document number given; else read it
if (!isset($_GET["doc"])) {
    rage_quit("Doc num not provided.");
}
$doc_num = $_GET["doc"];

// check if status folder exists
$doc_folder = $jt_settings->datafolder . '/docs/' . $doc_num;
$status_file = $doc_folder . '/status.json';
if (!file_exists($status_file)) {
    rage_quit("Status file not found.");
}
$doc_status = json_decode(file_get_contents($status_file));

// quit if nothing uploade
if (!isset($doc_status->texFile)) {
    rage_quit("File not yet converted to LaTeX.");
}

$filename = $doc_folder . '/' . substr($doc_status->texFile,0,-4) . '-optimized.pdf';

if (!file_exists($filename)) {
    rage_quit("Optimized PDF file not found.");
}
   
//Get file type and set it as Content Type
$finfo = finfo_open(FILEINFO_MIME_TYPE);
header('Content-Type: ' . finfo_file($finfo, $filename));
finfo_close($finfo);

//Use Content-Disposition: attachment to specify the filename
header('Content-Disposition: attachment; filename='.basename($filename));

//No cache
header('Expires: 0');
header('Cache-Control: must-revalidate');
header('Pragma: public');

//Define file size
header('Content-Length: ' . filesize($filename));

ob_clean();
flush();
readfile($filename);
exit;
