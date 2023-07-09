<?php

require_once 'getjtsettings.php';
require_once 'libjtpipe.php';

function keyfor($s) {
    $comma_split = explode(',',$s);
    $name_lower = strtolower( mb_ereg_replace('[^A-Za-z]', '', $comma_split[0] ) );
    $year_chunk = mb_ereg_replace('^[^0-9]*','',$s);
    $year_break = preg_split('/[^0-9a-z]/',$year_chunk,2);
    $year = $year_break[0];
    return $name_lower . $year;
}

function initialize_bibdata_for_tex($doc_folder, $fn) {
    $bibdata = new StdClass();
    
    $ffn = $doc_folder . '/' . $fn;
    if (file_exists($ffn)) {
        $tex_contents = file_get_contents($ffn);
        $bib_split = explode('\\begin{thebibliography}', $tex_contents);
        if (count($bib_split) > 1) {
            $bib_guts = explode('\\end{thebibliography}', $bib_split[1])[0];
            $bibitems = explode('\\bibitem', $bib_guts);
            for ($i=1; $i<count($bibitems); $i++) {
                $entry = trim($bibitems[$i]);
                if ($entry[0] == '[') {
                    $entry = explode(']', $entry)[1] ?? $entry;
                    $entry = trim($entry);
                }
                $ekey = 'newentry';
                if ($entry[0] == '{') {
                    $ekey = mb_substr( explode('}', $entry)[0], 1);
                    $entry = explode('}', $entry, 2)[1] ?? '';
                }
                $bibdata->{$ekey} = new StdClass();
                $bibdata->{$ekey}->originalText = $entry;
            }
        }
    }
    
    file_put_contents($doc_folder . '/bibdata.json', json_encode($bibdata, JSON_PRETTY_PRINT+JSON_UNESCAPED_UNICODE));
    return true;
}

function initialize_bibdata($doc_num) {
    global $jt_settings;
    $doc_folder = $jt_settings->datafolder . '/docs/' . $doc_num;
    $doc_status_file = $doc_folder . '/status.json';
    if (!file_exists($doc_status_file)) {
        return false;
    }
    $doc_status = json_decode(file_get_contents($doc_status_file));
    if (!$doc_status->fileUpload) {
        return false;
    }
    $in_filename = $doc_status->uploadFilename;
    $extension = strtolower(pathinfo($in_filename)["extension"]);
    if ($extension == 'tex') {
        return initialize_bibdata_for_tex($doc_folder, $in_filename);
    }
    $pandoc_exts = array('md','markdown','htm','html','xhtml','tex','epub','docx','odt','docbook','xml');
    if (!in_array($extension, $pandoc_exts)) {
        if (!$doc_status->docxConverted) {
            return false;
        }
        $in_filename = 'uploadedfile.docx';
    }
    $full_path = $doc_folder . '/' . $in_filename;
    if (!file_exists($full_path)) {
        return false;
    }
    exec('pandoc --wrap none -t plain "' . $full_path . '"', $output_lines, $e);
    if ($e != 0) {
        return false;
    }
 
    $start_found = false;
    
    $num_lines = count($output_lines);
    
    $entry_lines = array();

    
    // look for references, bibliography, works cited at start of line
    for ($i=($num_lines - 1); $i>=0; $i--) {
        $line = $output_lines[$i];
        if ((mb_ereg_match('\breferences\b', $line, 'i')) || 
        (mb_ereg_match('\bbibliography\b', $line, 'i')) ||
        (mb_ereg_match('\bworks cited\b', $line, 'i'))) {
            $start_found = true;
            break;
        }
        if (trim($line) != '') {
            array_unshift($entry_lines, $line);
        }
        
    }
    
    // if not found, look in middle of line
    if (!$start_found) {
        for ($i=($num_lines - 1); $i>=0; $i--) {
            $line = $output_lines[$i];
            if ((mb_ereg_match('.*\breferences\b', $line, 'i')) || 
                (mb_ereg_match('.*\bbibliography\b', $line, 'i')) ||
                (mb_ereg_match('.*\bworks cited\b', $line, 'i'))) {
                $start_found = true;
                break;
            }
            if (trim($line) != '') {
                array_unshift($entry_lines, $line);
            }

        }
    }
    
    if (!$start_found) {
        $entry_lines = array();
    }
    
    // remove footnotes
    for ($i=0; $i<count($entry_lines); $i++) {
        if (substr($entry_lines[$i],0,3) == '[1]') {
            array_splice($entry_lines, $i);
            break;
        }
    }
    
    // reinstate repeated names
    $curr_name = '';
    for ($i=0; $i<count($entry_lines); $i++) {
        $entry = $entry_lines[$i];
        if (mb_ereg_match('[A-Za-z]', $entry)) {
            $curr_name = trim(mb_ereg_replace('[^A-Za-z., ].*','',$entry));
        } else {
            $first_char = mb_substr($entry, 0, 1);
            $c = 0;
            $ch = $first_char;
            while (($ch == $first_char) || ($ch == '.') || ($ch == ',') || ($ch == ' ')) {
                $c++;
                $ch = mb_substr($entry, $c, 1);
            }
            $entry_lines[$i] = $curr_name . ' ' . mb_substr($entry, $c);
        }
    }
    
     // create entities
    $bibdata = new StdClass();
    
    foreach ($entry_lines as $entry) {
        $entry_object = new StdClass();
        $entry_object->originalText = $entry;
        $bibdata->{keyfor($entry)} = $entry_object;
    }
    
    file_put_contents($doc_folder . '/bibdata.json', json_encode($bibdata, JSON_PRETTY_PRINT+JSON_UNESCAPED_UNICODE));
    return true;
    
}

function get_extension($fn) {
   $e='';
   if (array_key_exists("extension", pathinfo($fn))) {
      $e = strtolower(pathinfo($fn)["extension"]); 
   }
   return $e;
}

function get_basename_no_ext($fn) {
   $e='';
   if (array_key_exists("filename", pathinfo($fn))) {
      $e = pathinfo($fn)["filename"]; 
   }
   return $e;
}

function str_to_anystyle($input) {

   // strip annoying stuff
   $input = mb_ereg_replace('_','',$input);
   $input = mb_ereg_replace('“','',$input);
   $input = mb_ereg_replace('”','',$input);
   $input = mb_ereg_replace('[‘’]',"'",$input);
   $input = mb_ereg_replace("'s","’s",$input);
   $input = mb_ereg_replace("'",'',$input);
   $input = mb_ereg_replace("’s","'s",$input);
   $input = mb_ereg_replace("–","--",$input);

   $input = str_replace(PHP_EOL . PHP_EOL, 'kckckck', $input);
   $input = str_replace(PHP_EOL, ' ', $input);
   $lines = explode('kckckck', $input);

   // anystyle parser

   $ch = curl_init('');
   curl_setopt($ch, CURLOPT_URL, "https://anystyle.io/parse/references");
   curl_setopt( $ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json;charset=UTF-8'));
   curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
   curl_setopt( $ch, CURLOPT_POST, true );


   $post_fields = new StdClass();
   $post_fields->format = "json";
   $post_fields->access_token = "e85dbf8753f2fbf3c344935bc49566ed";
   $post_fields->references = $lines;


   curl_setopt( $ch, CURLOPT_POSTFIELDS, json_encode($post_fields) );


   $rv = curl_exec($ch);
   if (curl_error($ch)) {
      curl_close($ch);
      return false;
   }

   $obj = json_decode($rv);
   curl_close($ch);
   return $obj;
}

function untex($str) {
   $result = pipe_to_command('pandoc -f latex -t plain', $str);
   if ($result->returnvalue != 0) {
      return 'Bibliography could not be converted';   
   }
   return $result->stdout;
}

function fix_pages($s) {
   $a = preg_split('/[-–]+/', $s);
   if (count($a) == 2) {
      if (mb_strlen($a[1]) > 2) {
         if (mb_substr($a[1], 0, -2) == mb_substr($a[0], 0, -2)) {
            $a[1] = mb_substr($a[1], -2);
         } else {
            $ctr = 2;
            $offset = -2;
            while (( (mb_substr($a[1], 0, $offset) != mb_substr($a[0], 0, $offset)) ) && ($ctr < mb_strlen($a[1]))) {
               $ctr++;
               $offset--;
            }
            if ($ctr < mb_strlen($a[1])) {
               $a[1] = mb_substr($a[1], $offset);
            }
         }
      }
      if (mb_strlen($a[1]) < 2) {
         if (mb_strlen($a[0]) > 1) {
            $a[1] = mb_substr($a[0], -2, 1) . $a[1];
         }
      }
   }
   $s = implode('--', $a);
   return $s;
}

function fix_publisher($s) {
   $s = mb_ereg_replace('OUP','Oxford University Press',$s);
   $s = mb_ereg_replace('CUP','Cambridge University Press',$s);
   return $s;
}

function convert_anystyle_item($in) {
   $out = new StdClass();
   $out->entrykey = '';
   $out->entrytype = 'article';
   $out->entryfields = new StdClass();
   if (isset($in->type)) {
      $out->entrytype = $in->type;
   }
   if (isset($in->author)) {
      $out->entryfields->author = $in->author;
      $out->entrykey .=  str_replace(' ', '', explode(',',$in->author)[0])  . '-';
   } else {
      if (isset($in->editor)) {
         $out->entrykey .=  str_replace(' ', '', explode(',',$in->editor)[0])  . '-';
      }
   }
   if (isset($in->editor)) {
      $out->entryfields->editor = $in->editor;
   }
   if (isset($in->date)) {
      $out->entryfields->year = $in->date;
      $out->entrykey .= $in->date;
   }
   if (isset($in->location)) {
      $out->entryfields->address = $in->location;
   }
   if (isset($in->title)) {
      $out->entryfields->title = $in->title;
   }
   if (isset($in->booktitle)) {
      $out->entryfields->booktitle = $in->booktitle;
   }
   if (isset($in->publisher)) {
      $out->entryfields->publisher = fix_publisher($in->publisher);
   }
   if (isset($in->pages)) {
      $out->entryfields->pages = fix_pages($in->pages);
   }
   if (isset($in->journal)) {
      $out->entryfields->journal = $in->journal;
   }
   if (isset($in->volume)) {
      $out->entryfields->volume = $in->volume;
   }
   if (isset($in->number)) {
      $out->entryfields->number = $in->number;
   }
   if (isset($in->note)) {
      $out->entryfields->note = $in->note;
   }
   if (isset($in->url)) {
      $out->entryfields->url = $in->url;
   }
   if (isset($in->crossref)) {
      $out->entryfields->crossref = $in->crossref;
   }
   return $out;
}

function convert_from_anystyle($obj) {
   return array_map("convert_anystyle_item", $obj);
}

function use_editor_as_author($ef) {
   if ((!(property_exists($ef, "author"))) and (property_exists($ef, "editor"))) {
      return true;
   }
   return false; 
}

function author_or_editor($ef) {
   if (use_editor_as_author($ef)) {
      return $ef->editor;
   } else {
      return $ef->author ?? '';
   }
}

function bib_order_compare ($a, $b) {
   $af = $a->entryfields;
   $bf = $b->entryfields;
   $na = author_or_editor($af);
   $nb = author_or_editor($bf);
   $ana = author_array($na);
   $anb = author_array($nb);
   $csa = author_names_citation($ana);
   $csb = author_names_citation($anb);
   $name_compare = strcasecmp($csa, $csb);
   if ($name_compare != 0) {
      return $name_compare;
   }
   if ((property_exists($af, "year")) and (property_exists($bf, "year"))) {
      $year_compare = strcasecmp($af->year, $bf->year);
      if ($year_compare != 0) {
         return $year_compare;
      }
   }
   if ((property_exists($af, "title")) and (property_exists($bf, "title"))) {
      $title_compare = strcasecmp($af->title, $bf->title);
      if ($title_compare != 0) {
         return $title_compare;
      }
   }
   return 0;
}

function sort_bib($bib) {
   usort($bib, "bib_order_compare");
   return $bib;
}

function mark_duplicates($bib) {
   for ($i=0; $i<count($bib); $i++) {
      $bibentry = &$bib[$i];
      $ef = &$bibentry->entryfields;
      $bibentry->is_new_author = true;
      if ($i != 0) {
         $preve = &$bib[($i-1)];
         $nprev = author_or_editor($preve->entryfields);
         $nthis = author_or_editor($ef);
         if ($nprev == $nthis) {
            $bibentry->is_new_author = false;    
            if (($ef->year) == ($preve->entryfields->year)) {
               if (property_exists($preve, 'yearletter')) {
                  $bibentry->yearletter = chr( ord($preve->yearletter) + 1 );
               } else {
                  $preve->yearletter = 'a';
                  $bibentry->yearletter = 'b';
               }
            } 
         }
      }
   }
   return $bib;
}


function author_array($a) {
   $ia=mb_split("\s+and\s+",$a);
   $ra=array();
   foreach ($ia as $name) {
      $nameObj = new stdClass();
      $split_by_comma = mb_split(',',$name,2);
      if (count($split_by_comma) == 2) {
         $nameObj->surname = trim($split_by_comma[0]);
         $nameObj->firstname = trim($split_by_comma[1]);
      } else {
         $split_by_space = mb_split('\s+',$name);
         if (count($split_by_space) >= 2) {
            $nameObj->surname = trim($split_by_space[(count($split_by_space) - 1)]);
            $nameObj->firstname = trim( implode( array_slice($split_by_space, 0, (count($split_by_space) - 1) ) ) );
         } else {
            $nameObj->surname = trim($name);
         }
      }
      array_push($ra, $nameObj);
   }
   return $ra;
}

function author_names_citation($aa) {
   $s = '';
   if (count($aa) > 0) {
      $s .= $aa[0]->surname;
   }
   if (count($aa) == 2) {
      return ($s . '\ and ' . $aa[1]->surname);
   }
   if (count($aa) == 3) {
      return ($s . ', ' . $aa[1]->surname . '\ and ' . $aa[2]->surname);
   }
   if (count($aa) > 3) {
      return ($s . " et al.");
   }
   return $s;
}

function initial_author_format($aa) {
   $s = '';
   if (count($aa) > 0) {
      $s .= $aa[0]->surname;
      if (property_exists($aa[0], "firstname")) {
         $s .= ", " . $aa[0]->firstname;
      }
   }
   for ($i = 1; $i < count($aa); $i++) {
      if (($i + 1) == count($aa)) {
         $s .= " and ";
      } else {
         $s .= ", ";
      }
      if (property_exists($aa[$i], "firstname")) {
         $s .= $aa[$i]->firstname . ' ';
      }
      $s .= $aa[$i]->surname;
   }
   return $s;
}

function regular_author_list($aa) {
   $s = '';
   for ($i = 0; $i < count($aa); $i++) {
      if ($i !=0) {
         if (($i + 1) == count($aa)) {
            $s .= " and ";
         } else {
            $s .= ", ";
         }
      }
      if (property_exists($aa[$i], "firstname")) {
         $s .= $aa[$i]->firstname . ' ';
      }
      $s .= $aa[$i]->surname;
   }
   return $s;
}

function get_entry_tex($e) {
   $s = '\bibitem[{';
   $ef=$e->entryfields;

   //author citation
   $a = author_or_editor($ef);
   $aa = author_array($a);
   $s .= author_names_citation($aa);
   $s .= '(';
   $s .= $ef->year ?? 'yyyy';
   if (property_exists($e, 'yearletter')) {
      $s .= $e->yearletter;    
   }

   // entry key
   $s .= ')}]{';
   $s .= $e->entrykey;
   $s .= '}' . PHP_EOL;


   // author/editor name
   if ($e->is_new_author) {
      $s.=initial_author_format($aa);
   } else {
      $s.='\rule[0.5ex]{2.5em}{0.5pt}';
   }
   $s .= ', ';

   // "Ed" for editor
   if (use_editor_as_author($ef)) {
      $s .= "ed";
      if (count($aa) > 1) {
         $s .= "s";
      }
      $s .="., ";
   }

   // year
   $s .= $ef->year ?? 'yyyy';
   if (property_exists($e, 'yearletter')) {
      $s .= $e->yearletter;    
   }
   $s .= '. ' . PHP_EOL;

   // title
   if (($e->entrytype == "article") || ($e->entrytype == "incollection")) {
      $s .= '\enquote{' . $ef->title . '.}';
   } elseif ($e->entrytype == "book") {
      $s .= '\booktitle{' . $ef->title . '}';
   } else {
      $s .= $ef->title ?? '';
   }

   // incollection specific stuff
   $do_inc_as_book = false;
   if ($e->entrytype == "incollection") {
      $s .= " In ";
      if (property_exists($ef, "crossref")) {
         $s .= "\citet{" . strtolower($ef->crossref) . "}";
      } else {
         $s .= '\booktitle{' . $ef->booktitle . '}';
         $do_inc_as_book = true;
      }
   }

   // additional book details
   if ($e->entrytype != "article") {
      if (property_exists($ef, "edition")) {
         $s .= ", " . $ef->edition;
      }
      if ((property_exists($ef, "editor")) and (!(use_editor_as_author($ef)))) {
         $aa = author_array($ef->editor);
         $s .= ", edited by " . regular_author_list($aa);
      }
      if (property_exists($ef, "pages")) {
         $s .= ", pp.~" . $ef->pages;
      }
      $s .= ". ";
   }
   $s .= PHP_EOL;

   // journal remainder 
   if ($e->entrytype == "article") {
      $s .= '\booktitle{' . $ef->journal .'} ' . $ef->volume . ': ' . $ef->pages . '.';
      $s .= PHP_EOL;
   }

   // book or bookish incollection remainder
   if (($e->entrytype == "book") or ($do_inc_as_book)) {
      if (isset($ef->address)) {
         $s .= $ef->address; 
      }
      if (isset($ef->publisher)) {
         $s .= ': ' . $ef->publisher . '.';
      }
      $s .= PHP_EOL;
   }

   // note if exists
   if (property_exists($ef, "note")) {
      $s .= $ef->note;
      // add a period if there isn't one
      if (mb_ereg('\.[^A-Za-z0-9]*$', $ef->note) == false) {
         $s .= '.';
      }
      $s .= PHP_EOL;
   }


   // finish up
   return $s;
}


function fill_in_bib($bib) {
   for ($i=0; $i<count($bib); $i++) {
      if (property_exists($bib[$i]->entryfields, "crossref")) {
         $j=0;
         while ($j < count($bib)) {
            if (strtolower($bib[$j]->entrykey) == strtolower($bib[$i]->entryfields->crossref)) {
               break;
            }
            $j++;
         }
         if (strtolower($bib[$j]->entrykey) != strtolower($bib[$i]->entryfields->crossref)) {
            continue;
         }
      } else {
         continue;
      }
      if ( (!(property_exists($bib[$i]->entryfields, "author"))) and
          (property_exists($bib[$j]->entryfields, "author")) ) {
         $bib[$i]->entryfields->author = $bib[$j]->entryfields->author;
      }
      if ( (!(property_exists($bib[$i]->entryfields, "year"))) and
          (property_exists($bib[$j]->entryfields, "year")) ) {
         $bib[$i]->entryfields->year = $bib[$j]->entryfields->year;
      }

   }
   return $bib;
}

function bib_to_bibentries($bib) {
   $bib = fill_in_bib($bib);
   $bib = sort_bib($bib);
   $bib = mark_duplicates($bib);
   $r = '\begin{thebibliography}{49}' . PHP_EOL;
   $r .= '    \newcommand{\enquote}[1]{``#1\'\'}' . PHP_EOL;
   $r .= '    \newcommand{\booktitle}[1]{\emph{#1}}' . PHP_EOL;
   $r .= PHP_EOL;
   foreach ($bib as $bibentry) {
      $r .= get_entry_tex($bibentry) . PHP_EOL;
   }
   $r .= '\end{thebibliography}' . PHP_EOL;
   return $r;
}

?>