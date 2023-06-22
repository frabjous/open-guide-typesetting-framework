<?php

session_start();

require 'getjtsettings.php';

$rv = new StdClass();
$rv->error = false;

function send_and_exit() {
    global $rv;
    echo json_encode($rv, JSON_UNESCAPED_UNICODE);
    exit(0);
}

function rage_quit($m) {
    global $rv;
    $rv->error = true;
    $rv->errmsg = $m;
    send_and_exit();
}

// quit if not logged in
if (!isset($_SESSION["_jt_user"])) {
    rage_quit("Not logged in");
}

// quit if no document number given; else read it
if (!isset($_POST["docnum"])) {
    rage_quit("Doc num not provided to file upload.");
}
$doc_num = $_POST["docnum"];

// quit if no file uploaded; else read its name and extension
if (!isset($_FILES["uploadfile"])) {
    rage_quit("No file uploaded.");
} 
// quit if error in upload
if ($_FILES["uploadfile"]["error"] != 0) {
    rage_quit("There was an error in the uploading process.");
}
$upload_filename = $_FILES["uploadfile"]["tmp_name"];
$pthinfo = pathinfo($_FILES["uploadfile"]["name"]);
$basename = $pthinfo["basename"];
$extension = strtolower($pthinfo["extension"]);
    
$doc_folder = $jt_settings->datafolder . '/docs/' . $doc_num;

// quit if status file doesn't exist
$status_file = $doc_folder . '/status.json';
if (!file_exists($status_file)) {
    rage_quit("Status file not found.");
}
$doc_status = json_decode(file_get_contents($status_file));

$supplement = false;
if ((isset($_POST["supplement"])) && ($_POST["supplement"]=="yes")) {
    $supplement = true;
}

// keep old file if it exists
if ($supplement) {
    // SUPPL FILES
    if (file_exists("$doc_folder/$basename")) {
        $backupfnbase = "$basename";
        while (file_exists("$doc_folder/$backupfnbase")) {
            $backupfnbase = "bak-" . $backupfnbase;
        }
        rename("$doc_folder/$basename","$doc_folder/$backupfnbase");
    }

} else {
    // MAIN FILES

    if ($doc_status->fileUpload) {
        $old_upload_filename = $doc_folder . '/' . $doc_status->uploadFilename;
        $old_upload_renamed = $doc_folder . '/' . $doc_status->uploadTime . '-'  . $doc_status->uploadFilename;
        rename($old_upload_filename, $old_upload_renamed);
    }
}

if ($supplement) {
    $save_name = "$doc_folder/$basename";
    move_uploaded_file($upload_filename, $save_name);
    if (!isset($doc_status->supplementFiles)) {
        $doc_status->supplementFiles = array();
    }
    if (!in_array($basename, $doc_status->supplementFiles)) {
        array_push($doc_status->supplementFiles, $basename);
    }
} else {
    // main file is called uploadedfile
    $save_name = 'uploadedfile.' . $extension;
    move_uploaded_file($upload_filename, $doc_folder . '/' . $save_name);
    $doc_status->fileUpload = true;
    $doc_status->uploadFilename = $save_name;
    $doc_status->uploadTime = time();
    // check if extension is something pandoc can handle
    $pandoc_exts = array('md','markdown','htm','html','xhtml','tex','epub','docx','odt','docbook','xml');
    if (!in_array($extension, $pandoc_exts)) {
        // convert to docx
        exec('soffice --headless --convert-to "docx:Office Open XML Text" --outdir "' . $doc_folder . '" "' . $doc_folder . '/' . $doc_status->uploadFilename . '"', $o, $e);
        if ($e != 0) {
            rage_quit("Could not convert document to Word format. Either Libre Office cannot handle this file format, or something else went wrong.");
        }
        $docx_filename = 'uploadedfile.docx';
        if (!file_exists($doc_folder . '/' . $docx_filename)) {
            rage_quit("Something went wrong in the conversion to Word process. File not found.");
        }
        $doc_status->docxConverted = true;
        $doc_status->docxFilename = $docx_filename;
    }
}

file_put_contents($status_file, json_encode($doc_status, JSON_UNESCAPED_UNICODE));

send_and_exit();
