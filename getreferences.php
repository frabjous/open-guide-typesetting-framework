<?php

session_start();
require_once 'getjtsettings.php';
require_once 'libjtpipe.php';

function tempdir() {
    $tempfile=tempnam(sys_get_temp_dir(),'');
    if (file_exists($tempfile)) { unlink($tempfile); }
    mkdir($tempfile);
    if (is_dir($tempfile)) { return $tempfile; }
}

function rage_quit($s) {
    echo '--ERROR: ' . $s . PHP_EOL;
    exit(0);
}


function latex_file($str) {
    return '
\documentclass[12pt]{article}
\usepackage[utf8]{inputenc}
\usepackage[papersize={100in,100in}]{geometry}
\usepackage{natbib}
\usepackage{hyperref}
\usepackage[T1]{fontenc}
\newcommand{\nudge}{}
\newcommand{\rbibrule}{}
\usepackage{tabularx}
\usepackage{xspace}

\begin{document}\pagestyle{empty}
\begin{thebibliography}' . $str . '
\end{thebibliography}
\end{document}';
}

if (!isset($_SESSION["_jt_user"])) {
    rage_quit("Not logged in");
}

if (!isset($_GET["doc"])) {
    rage_quit("No document number given");
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

$first_break = explode('\\begin{thebibliography}', $tex_file_contents);

if (count($first_break) < 2) {
    rage_quit("The TeX file does not have a bibliography.");
}

$tex_references_text = trim(explode('\\end{thebibliography}', $first_break[1])[0]);

$min_latex = latex_file($tex_references_text);

$tempdir = tempdir();
chdir($tempdir);

file_put_contents('extract.tex', $min_latex);

exec('pdflatex -interaction=nonstopmode extract.tex', $o, $e);
if ($e != 0) {
    rage_quit('Could not compile minimum LaTeX file.');
}
exec('pdflatex -interaction=nonstopmode extract.tex', $o, $e);
if ($e != 0) {
    rage_quit('Could not compile minimum LaTeX file.');
}
exec('ebook-convert extract.pdf extract.txt', $o, $e);
if ($e != 0) {
    rage_quit('Could not convert PDF to text.');
}
echo str_replace(PHP_EOL . ',', PHP_EOL . '———,', file_get_contents('extract.txt'));
chdir(sys_get_temp_dir());
array_map('unlink', glob($tempdir . "/*"));
rmdir($tempdir);
exit(0);
