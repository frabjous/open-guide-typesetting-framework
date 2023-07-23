<?php
session_start();
require '../libjt.php';
require $_SERVER["DOCUMENT_ROOT"] . '/kcklib/hostinfo.php';

function rage_quit($s) {
    global $err_page_msg;
    $err_page_msg = $s;
    include '../error_page.php';
    exit(0);
}

if (!isset($_SESSION["_jt_user"])) {
    rage_quit("Not logged in");
}

if (!isset($_GET["doc"])) {
    rage_quit("Document number not specified");
}

$doc_num = $_GET["doc"];

$doc_folder = $jt_settings->datafolder . '/docs/' . $doc_num;

if (!is_dir($doc_folder)) {
    rage_quit("Document number does not exist.");
}

$result = make_new_proof_set($doc_num);

if ($result === false) {
    rage_quit("Proof set creation failed for some reason. Perhaps the PDF does not exist.");
}

$redirect = dirname(full_path()) . '/?doc=' . urlencode($doc_num) . '&set=' . urlencode($result->setnum) . '&pskey=' . urlencode($result->pskey) . '&editormode=true';
header('Location: ' . $redirect);
exit(0);
