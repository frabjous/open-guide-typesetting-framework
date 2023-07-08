<?php

require_once 'getjtsettings.php';

function texxy_fix($s) {
   $s = mb_ereg_replace('\be\.g\. ','e.g.\\ ',$s);
   $s = mb_ereg_replace('\bi\.e\. ','i.e.\\ ',$s);
   $s = mb_ereg_replace('\bE\.g\. ','E.g.\\ ',$s);
   $s = mb_ereg_replace('\bI\.e\. ','I.e.\\ ',$s);
   $s = mb_ereg_replace('\betc\. ','etc.\\ ',$s);
   $s = mb_ereg_replace('\bviz\. ','viz.\\ ',$s);
   $s = mb_ereg_replace('\bpp\. ','pp.~',$s);
   $s = mb_ereg_replace('\bp\. ','p.~',$s);
   $s = mb_ereg_replace('\bchap\. ','chap.~',$s);
   $s = mb_ereg_replace('\b([A-Z])\. ([A-Z])\. ([A-Z])\.,','\1.\\,\2.\\,\3.,',$s);
   $s = mb_ereg_replace('\b([A-Z])\. ([A-Z])\.,','\1.\\,\2.,',$s);
   $s = mb_ereg_replace('\b([A-Z])\.([A-Z])\.([A-Z])\.,','\1.\\,\2.\\,\3.,',$s);
   $s = mb_ereg_replace('\b([A-Z])\.([A-Z])\.,','\1.\\,\2.,',$s);
   $s = mb_ereg_replace('\b([A-Z])\. ([A-Z])\. ([A-Z])\. ','\1.\\,\2.\\,\3.\\ ',$s);
   $s = mb_ereg_replace('\b([A-Z])\. ([A-Z])\. ','\1.\\,\2.\\ ',$s);
   $s = mb_ereg_replace('\b([A-Z])\.([A-Z])\.([A-Z])\. ','\1.\\,\2.\\,\3.\\ ',$s);
   $s = mb_ereg_replace('\b([A-Z])\.([A-Z])\.','\1.\\,\2.\\ ',$s);
   $s = mb_ereg_replace('\bvol\. ','vol.~',$s);
   $s = mb_ereg_replace(' -- ','---',$s);
   $s = mb_ereg_replace('{\[}','[',$s);
   $s = mb_ereg_replace('{\]}',']',$s);
   return $s;
}

function tex_escape($s) {
    $r = str_replace('$','\$',$s);
    return $r;
}

function generate_password($length = 8) {
   $chars =  'ABCDEFGHIJKLMNPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
   $str = '';
   $max = strlen($chars) - 1;
   for ($i=0; $i < $length; $i++)
      $str .= $chars[random_int(0, $max)];
   return $str;
}

function make_new_proof_set($docnum) {
    global $jt_settings;
    $doc_folder = $jt_settings->datafolder . '/docs/' . strval($docnum);
    $doc_status_file = $doc_folder . '/status.json';
    if (!file_exists($doc_status_file)) {
        return false;
    }
    $doc_status = json_decode(file_get_contents($doc_status_file));
    if (!isset($doc_status->texFile)) {
        return false;
    }
    $pdffilename = $doc_folder . '/' . substr($doc_status->texFile,0,-4) . '.pdf';
    if (!file_exists($pdffilename)) {
        return false;
    }
    
    $docproofsfolder = $doc_folder . '/proofs'; 
    // look for proofs folder for doc; create it if it doesn't exist
    if (!is_dir($docproofsfolder)) {
        mkdir($docproofsfolder, 0755, true);
    }
    // find first set number not already used; create folder for it
    $setnum = 0;
    while (is_dir($docproofsfolder . '/' . strval($setnum))) {
        $setnum++;
    }
    $proofsetfolder = $docproofsfolder . '/' . strval($setnum);
    mkdir($proofsetfolder, 0755, true);

    // save name of editor creating the set
    if (isset($_SESSION["_jt_user"])) {
        file_put_contents($proofsetfolder . '/editorname.txt', $_SESSION["_jt_user"]);
    }

    // create images from pdf
    exec('mutool draw -r 300 -o "' . $proofsetfolder . '/page-%d.png" "' . $pdffilename . '"', $o, $e);
    // if it didn't work, return an error
    if ($e != 0) {
        return false;
    }
    // create "key" for proof set
    $setkey = generate_password(20);
    file_put_contents($proofsetfolder . '/pskey.txt', $setkey);

    //copy file over
    copy($pdffilename, $proofsetfolder . '/' . basename($pdffilename));
    $rv = new StdClass();
    $rv->pskey = $setkey;
    $rv->setnum = $setnum;
    return $rv;
}

function send_email($to, $subject, $contents) {
    global $jt_settings;
    $success = true;
    $message = "<html><head>\r\n<title>$subject</title>\r\n</head><body><div>\r\n" . 
        $contents .   "\r\n</div></body></html>";
    if (file_exists($jt_settings->datafolder . '/customemail.php')) {
        require_once $jt_settings->datafolder . '/customemail.php';
    }
    if (function_exists('jt_custom_email')) {
        $success = jt_custom_email($to, $subject, $message);
    } else {
        $headers = "MIME-Version: 1.0\r\n";
        $headers .= "Content-type:text/html;charset=UTF-8\r\n";
        $headers .= "From: {$jt_settings->journal_name} <{$jt_settings->contact_email}>\r\n";
        $success = mail($to,$subject,$message,$headers);
    }
    if ($success) {
        $emaillogfile = $jt_settings->datafolder . '/emailssent.txt';
        file_put_contents($emaillogfile, "\r\n" .
            '======================================================' . 
            "\r\n" . date('D, d M Y H:i:s') . "\r\n\r\n" .
            "TO=" . $to . "\r\n" . "SUBJECT=$subject\r\n\r\n" . 
            $message . "\r\n", FILE_APPEND);
    } else {
        error_log("Error sending email to $to ($subject)");
    }
    return $success;
}

function compressed_last_name($name) {
    $ar = explode(' ', trim($name));
    if (count($ar) < 2) {
        return $name;
    }
    return str_replace(' ','',$ar[(count($ar) - 1)]);
}

function names_to_fn($meta) {
    if ((isset($meta->firstauthor)) && ($meta->firstauthor != '')) {
        $rv = compressed_last_name($meta->firstauthor);
    } else {
        return 'Anonymous';
    }
    if ((isset($meta->secondauthor)) && ($meta->secondauthor != '')) {
        $rv .= '-' . compressed_last_name($meta->secondauthor);
    }
    return $rv;
}

function latex_header($meta) {
    global $jt_settings;
    if (!isset($meta->worktype)) {
        return 'ERROR:No worktype set.';
    }
    $rv = "\\documentclass";
    if ($meta->worktype == 'review') {
        $rv .= '[review]';
    }
    $rv .= "{{$jt_settings->document_class}}
\\author{{$meta->firstauthor}}
\\email{{$meta->firstemail}}
\\affiliation{{$meta->firstaffiliation}}  
";
    if ((isset($meta->secondauthor)) && ($meta->secondauthor != '')) {
        $rv .= "\\secondauthor{{$meta->secondauthor}}
\\secondemail{{$meta->secondemail}}
\\secondaffiliation{{$meta->secondaffiliation}}
";
    }
    if ($meta->worktype == 'review') {
        
        if ($meta->reviewedtitle != '') {
            $rv .= '\reviewedtitle{' . $meta->reviewedtitle . '}' . PHP_EOL;
        }
        if ($meta->reviewedauthor != '') {
            $rv .= '\reviewedauthor{' . $meta->reviewedauthor . '}' . PHP_EOL;
        }
        if ($meta->secondreviewedauthor != '') {
            $rv .= '\secondreviewedauthor{' . $meta->secondreviewedauthor . '}' . PHP_EOL;
        }
        if ($meta->reviewededitor != '') {
            $rv .= '\reviewededitor{' . $meta->reviewededitor . '}' . PHP_EOL;
        }
        if ($meta->secondreviewededitor != '') {
            $rv .= '\secondreviewededitor{' . $meta->secondreviewededitor . '}' . PHP_EOL;
        }
        if ($meta->reviewedtitle != '') {
            $rv .= '\reviewedpubdetails{' . tex_escape($meta->reviewedpubdetails) . '}' . PHP_EOL;
        }
        
    } else {
        
        $rv .="\\title{{$meta->title}}" . PHP_EOL;
    }

    if (isset($meta->specialvolume) && ($meta->specialvolume != '')) {
        $rv .= PHP_EOL . '\specialvolume{' . $meta->specialvolume . '}' . PHP_EOL;
    }
    if (isset($meta->specialvolumeeditors) && ($meta->specialvolumeeditors != '')) {
        $rv .= '\specialvolumeeditors{' . $meta->specialvolumeeditors . '}' . PHP_EOL;
    }

    $rv .= PHP_EOL . '\volume{' . $meta->volume . '}' . PHP_EOL;
    $rv .= '\volnumber{' . $meta->volnumber . '}' . PHP_EOL;

    return $rv;
}

function get_abstract_info($texlines) {
    $num_lines = count($texlines);
    $rv = new StdClass();
    $rv->found = false;
    // look for word abstract
    for ($i=0; (($i<$num_lines) && ($i<16)) ; $i++) {
        $line = $texlines[$i];
        if (mb_ereg_match('.*\babstract\b',$line,'i')) {
            $rv->found=true;
            break;
        }
    }
    if (!$rv->found) {
        return $rv;
    }
    
    // see if abstract on same line
    if (mb_ereg_match('.*\babstract\b.*[A-Za-z]',$line,'i')) {
        $rv->lineno = $i;
        $rv->text = trim(preg_split('/\babstract\b/i',$line,2)[1]);
        $rv->text = mb_ereg_replace('^[^A-Za-z]*','',$rv->text);
        return $rv;
    }
    
    // otherwise take next non-empty line
    $i++;
    while ((trim($texlines[$i] != '')) && (($i<$num_lines) && ($i<17)) ) {
        $i++;
    }
    $rv->lineno = $i;
    $rv->text = $texlines[$i];
    return $rv;
    
}


// following functions stolen from original nbib_auto.php
function new_bibitem($n, $y, $k) {
    $rv = new StdClass();
    $rv->citekey = $k;
    $rv->name = $n;
    $rv->year = $y;
    return $rv;
}

function cites_process_line($line, $bibitem) {
    $newline = $line;
    $newline = mb_ereg_replace(
            $bibitem->name . ' \(' . $bibitem->year . '\)',
            '\citet{' . $bibitem->citekey . '}',
            $newline
        );
    $newline = mb_ereg_replace(
            '\(' . $bibitem->name . ' ' . $bibitem->year . '\)',
            '\citep{' . $bibitem->citekey . '}',
            $newline
        );
    $newline = mb_ereg_replace(
            $bibitem->name . ' \(' . $bibitem->year . '[,;]\s*([^\)]*)\)',
            '\citet[\\1]{' . $bibitem->citekey . '}',
            $newline
        );
    $newline = mb_ereg_replace(
            '\(' . $bibitem->name . ' ' . $bibitem->year . '[,;]\s*([^\)]*)\)',
            '\citep[\\1]{' . $bibitem->citekey . '}',
            $newline
        );
    $newline = mb_ereg_replace(
            $bibitem->name . ' ' . $bibitem->year ,
            '\citealt{' . $bibitem->citekey . '}',
            $newline
        );
    return $newline;
}

function auto_insert_cites($intext) {
    # build bibitem array
    $first_bibitem_line = -1;
    $bibitems = array();

    $lines = explode(PHP_EOL, $intext);
    
    for ($i=0; $i<count($lines); $i++) {
        $line = $lines[$i];
        if (mb_ereg_match('.*\\\bibitem', $line)) {
            if ($first_bibitem_line == -1) {
                $first_bibitem_line = $i;
            }
            $rest_of_line = trim(explode('bibitem',$line)[1]);
            $name_and_year = trim( explode('}]', explode('[{', $rest_of_line)[1])[0] );
            $name = trim(explode('(', $name_and_year)[0]);
            $year = trim(explode(')', explode('(', $name_and_year)[1])[0]);
            $citekey = trim(
                explode('}',
                        explode(
                            '{',
                            explode('}]', $rest_of_line)[1]
                        )[1]
                       )[0]
            );
            array_push($bibitems, new_bibitem($name, $year, $citekey));
        }
    }

    foreach($bibitems as $bibitem) {
        for ($i=0; $i<$first_bibitem_line; $i++) {
            $lines[$i] = cites_process_line($lines[$i], $bibitem);
        }
    }

    unset($line);
    $rv = '';
    foreach ($lines as $line) {
        $rv .= rtrim($line) . PHP_EOL;
    }
    return $rv;
}

function latex_to_latex($doc_folder, $texfile, $hdr, $outtitle) {
    
    $texfile_fullpath = $doc_folder . '/' . $texfile;
    if (!file_exists($texfile_fullpath)) {
        return false;
    }
    $oldtex = file_get_contents($texfile_fullpath);
    $pkgs_to_add = array();
    
    // look for abstract
    $has_abs = false;
    $abs_break = explode('\\begin{abstract}',$oldtex,2);
    if (count($abs_break) > 1) {
        $e_abs_break = explode('\\end{abstract}',$abs_break[1],2);
        if (count($e_abs_break) > 1) {
            $has_abs = true;
            $abs_text = $e_abs_break[0];
            $oldtex = $abs_break[0] . $e_abs_break[1];
        }
    }
    
    
    // find old header and read packages fom it
    $bd_break = explode('\\begin{document}', $oldtex, 2);
    if (count($bd_break) > 1) {
        $post_bd = $bd_break[1];
        $oldhdr = $bd_break[0];
        $pkg_cmds = explode('\\usepackage{',$oldhdr);
        for ($i=1; $i<count($pkg_cmds); $i++) {
            $tpc = $pkg_cmds[$i];
            $prts = explode('}',$tpc,2);
            if (count($prts) > 1) {
                $pp = explode(',',$prts[0]);
                for ($j=0; $j<count($pp); $j++) {
                    array_push($pkgs_to_add, $pp[$j]);
                }
            }
        }
    } else {
        $post_bd = $oldtex;
    }
    
    // add back old packages
    $newtex = $hdr . PHP_EOL;
    for ($k=0; $k<count($pkgs_to_add); $k++) {
        $newtex .= '\\usepackage{' . $pkgs_to_add[$k] . '}' . PHP_EOL;
    }
    $newtex .= PHP_EOL . '\\begin{document}' . PHP_EOL;
    
    if ($has_abs) {
        $newtex .= PHP_EOL . '\\begin{abstract}' . $abs_text . '\\end{abstract}' . PHP_EOL;
    } else {
        $newtex .= PHP_EOL . '\\begin{abstract}'. PHP_EOL . 'ABSTRACT NEEDED' . PHP_EOL . '\\end{abstract}' . PHP_EOL;
    }
    
    $newtex .= PHP_EOL . '\\maketitle' . PHP_EOL;
    
    $post_bd = str_replace('\\maketitle', '', $post_bd);
    
    // look for bibliography
    $pre_bib = '';
    $bibbreak = explode('\\bibliography',$post_bd);
    if (count($bibbreak) > 1) {
        $pre_bib = $bibbreak[0];
    } else {
        $bibbreak = explode('\\printbibliography',$post_bd);
        if (count($bibbreak) > 1) {
            $pre_bib = $bibbreak[0];
        } else {
            $bibbreak = explode('\\begin{thebibliography}',$post_bd);
            if (count($bibbreak) > 1) {
                $pre_bib = $bib_break[0];
            } else {
                $pre_bib = $post_bd;
            }
        }
    }
    $newtex .= $pre_bib . PHP_EOL;
    
    $newtex .= PHP_EOL . '\subsection*{Acknowledgements}' . PHP_EOL;
    
    $newtex .= PHP_EOL . '\signoff' . PHP_EOL;
    
    if (file_exists($doc_folder . '/bibliography.bbl')) {
        $newtex .= file_get_contents($doc_folder . '/bibliography.bbl');
    }
    
    $newtex .= PHP_EOL . '\\end{document}' . PHP_EOL;
    
    $newtex = texxy_fix($newtex);
    
    file_put_contents($doc_folder . '/' . $outtitle, $newtex);
    return true;
    
}

function convert_to_latex($docnum) {
    global $jt_settings;
    $doc_folder = $jt_settings->datafolder . '/docs/' . strval($docnum);
    if (!is_dir($doc_folder)) {
        return false;
    }
    $status_file = $doc_folder . '/status.json';
    $meta_file = $doc_folder . '/metadata.json';
    if ((!file_exists($status_file)) || (!file_exists($meta_file))) {
        return false;
    }
    $doc_status = json_decode(file_get_contents($status_file));
    $doc_meta = json_decode(file_get_contents($meta_file));
    if (!isset($doc_status->uploadFilename)) {
        return false;
    }
    $texfile_name = $doc_meta->volume . '.' . $doc_meta->volnumber . '-' . names_to_fn($doc_meta) . '.tex';
    $input_filename = $doc_status->uploadFilename;
    $extension = strtolower(pathinfo($input_filename)["extension"]);
    if ($extension == 'tex') {
        
        if (latex_to_latex($doc_folder, $input_filename, latex_header($doc_meta), $texfile_name)) {
            $doc_status->texConverted = true;
            $doc_status->texFile = $texfile_name;
            file_put_contents($status_file, json_encode($doc_status, JSON_UNESCAPED_UNICODE));
            return true;
        } else {
            return false;
        }
    }
        
    $pandoc_exts = array('md','markdown','htm','html','xhtml','epub','docx','odt','docbook','xml');
    if (!in_array($extension, $pandoc_exts)) {
        if (!$doc_status->docxConverted) {
            return false;
        }
        $input_filename = 'uploadedfile.docx';
    }
    if (!file_exists($doc_folder . '/' . $input_filename)) {
        return false;
    }

    exec('pandoc -t latex --wrap=none "' . $doc_folder . '/' . $input_filename  . '"', $converted_lines, $e);
    if ($e != 0) {
        return false;
    }
    
    $texfile_contents = latex_header($doc_meta);
    $texfile_contents .= PHP_EOL . '\begin{document}' . PHP_EOL;
    
    // handle abstract
    if ($doc_meta->worktype != 'review') {
        $abs_info = get_abstract_info($converted_lines);
        $texfile_contents .= PHP_EOL . '\begin{abstract}' . PHP_EOL; 
        if ($abs_info->found) {
            $texfile_contents .= $abs_info->text;
            array_splice($converted_lines, 0, ($abs_info->lineno + 1));
        } else {
            $texfile_contents .=  'ABSTRACT NEEDED';
        }
        $texfile_contents .= PHP_EOL . '\end{abstract}'. PHP_EOL;
    }
    
    $texfile_contents .= PHP_EOL . '\maketitle' . PHP_EOL;
        
    // remove bibliography if necessary
    $has_bib = file_exists($doc_folder . '/bibliography.bbl');
    if ($has_bib) {
        for ($i=(count($converted_lines) - 1); $i>=5; $i--) {
            $line = $converted_lines[$i];
            if ((preg_match('/\bbibliography\b[^A-Za-z]*$/i',$line)) ||
                 (preg_match('/\bworks cited\b[^A-Za-z]*$/i',$line)) ||
                 (preg_match('/\breferences\b[^A-Za-z]*$/i',$line))) {
                     array_splice($converted_lines, $i);
                     break;
            }
        }
    }
    
    $texfile_contents .= implode(PHP_EOL, $converted_lines);
    
    $texfile_contents .= PHP_EOL . '\subsection*{Acknowledgements}' . PHP_EOL;
    
    $texfile_contents .= PHP_EOL . '\signoff' . PHP_EOL;
    
    if ($has_bib) {
        $texfile_contents .= file_get_contents($doc_folder . '/bibliography.bbl');
    }
    
    $texfile_contents .= PHP_EOL . '\end{document}';
    $texfile_contents = auto_insert_cites($texfile_contents);
    
    $texfile_contents = texxy_fix($texfile_contents);
    
    file_put_contents($doc_folder . '/' . $texfile_name, $texfile_contents);
    $doc_status->texConverted = true;
    $doc_status->texFile = $texfile_name;
    file_put_contents($status_file, json_encode($doc_status, JSON_UNESCAPED_UNICODE));
    return true;
}
