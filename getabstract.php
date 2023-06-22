<?php

session_start();
require_once 'getjtsettings.php';
require_once 'libjtpipe.php';

function rage_quit($s) {
    echo '--ERROR: ' . $s . PHP_EOL;
    exit(0);
}

if (!isset($_SESSION["_jt_user"])) {
    rage_quit("Not logged in");
}

if (!isset($_GET["doc"])) {
    rage_quit("No document number given");
}

$doc_num = $_GET["doc"];

$doc_folder = $jt_settings->datafolder . '/docs/' . $doc_num;

$status_file = $doc_folder . '/status.json';

if (!file_exists($status_file)) {
    rage_quit("Status file not found.");
}

$doc_status = json_decode(file_get_contents($status_file));

if ((!isset($doc_status->texFile)) || ($doc_status->texFile == '')) {
    rage_quit("TeX filename not set.");
}

$tex_file = $doc_folder . '/' . $doc_status->texFile;

if (!file_exists($tex_file)) {
    rage_quit("TeX file not found.");
}

$tex_file_contents = file_get_contents($tex_file);

$first_break = explode('\\begin{abstract}', $tex_file_contents);

if (count($first_break) < 2) {
    rage_quit("TeX file does not have an abstract.");
}

$tex_abstract_text = trim(explode('\\end{abstract}', $first_break[1])[0]);


$pandoc_result = pipe_to_command('pandoc -f latex -t html5',$tex_abstract_text);

if ($pandoc_result->returnvalue != 0) {
    rage_quit("Pandoc conversion of abstract failed. Error output " . $pandoc_result->stderr);
}

echo $pandoc_result->stdout;
exit(0);