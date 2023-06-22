<?php

session_start();
require 'getjtsettings.php';

$rv = new StdClass();

$rv->saved = false;
$rv->finalized = false;

function send_and_exit() {
    global $rv;
    echo json_encode($rv);
    exit(0);
}


if (!isset($_SESSION["_jt_user"])) {
    send_and_exit();
}

if (!isset($_POST["docnum"])) {
    send_and_exit();
}

if (!isset($_POST["bibjson"])) {
    send_and_exit();
}

$doc_num = $_POST["docnum"]; 
    
$doc_folder = $jt_settings->datafolder . '/docs/' . $doc_num;

if (!is_dir($doc_folder)) {
    send_and_exit();
}

$doc_bibdata_filename = $doc_folder . '/bibdata.json';

$bibdata = json_decode($_POST["bibjson"]) ?? 'nothing';

if ($bibdata === 'nothing') {
    send_and_exit();
}

$saved = file_put_contents($doc_bibdata_filename, json_encode($bibdata, JSON_UNESCAPED_UNICODE+JSON_PRETTY_PRINT));

if ($saved == false) {
    send_and_exit();
}
$rv->saved = true;

if ((!isset($_POST["finalize"])) || ($_POST["finalize"] != "true")) {
    send_and_exit();
}

$doc_status_file = $doc_folder . '/status.json';

if (!file_exists($doc_status_file)) {
    send_and_exit();
}

$doc_status = json_decode(file_get_contents($doc_status_file));

// create bbl file
$bblHdr = '
\begin{thebibliography}{49}
\newcommand{\enquote}[1]{``#1\'\'}
\newcommand{\booktitle}[1]{\emph{#1}}

';
    
$bblEnd = '\end{thebibliography}
';

$bblCore = '';
foreach($bibdata as $bibkey => $bibprops) {
    if (isset($bibprops->bblText)) {
        $bblCore .= $bibprops->bblText . PHP_EOL . PHP_EOL;
    }
}

$saved = file_put_contents($doc_folder . '/bibliography.bbl', $bblHdr . $bblCore . $bblEnd);

if ($saved == false) {
    send_and_exit();
}

$doc_status->bibFinished = true;
$doc_status->bibFinishedTime = time();

file_put_contents($doc_status_file, json_encode($doc_status, JSON_UNESCAPED_UNICODE));

$rv->finalized = true;
send_and_exit();