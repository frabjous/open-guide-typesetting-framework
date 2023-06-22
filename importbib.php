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

// quit if no file uploaded; else read its name and extension
if (!isset($_FILES["uploadfile"])) {
    rage_quit("No file uploaded.");
} 
// quit if error in upload
if ($_FILES["uploadfile"]["error"] != 0) {
    rage_quit("There was an error in the uploading process.");
}
$upload_filename = $_FILES["uploadfile"]["tmp_name"];

function chat($s, $i) {
   return mb_substr($s, $i, 1);
}

function fix_name($n) {
    $csplit = explode(',',$n);
    if (count($csplit) > 1) {
        return $n;
    }
    $ssplit = explode(' ',$n);
    if (count($ssplit) < 2) {
        return $n;
    }
    $spot = count($ssplit) - 1;
    $badsplits = ['von','van','de','des'];
    while ((in_array( strtolower($ssplit[$spot - 1]), $badsplits)) && ($spot > 0)) {
        $spot--;
    }
    return implode(' ', array_slice($ssplit, $spot)) . ', ' . implode(' ', array_slice($ssplit, 0, $spot));
    
}

function ae_to_array($s) {
    $s = mb_ereg_replace('\s*\\\*\&\s*',' and ',$s);
    $ss = explode(' and ', $s);
    return array_map("fix_name", $ss);
}

function file_to_bibobject($fn) {
   $bibobj = new StdClass();
   $bfstring = file_get_contents($fn);
   $bfssize = mb_strlen($bfstring);
   $conv_index = 0;
   $laststart = 0;
   
   // main loop through entries
   while ($conv_index < $bfssize) {
      if (chat($bfstring, $conv_index) == '@') {
         $laststart = $conv_index;
         // create new entry
         
         // read entry type
         $push_ahead = $conv_index;
         while (chat($bfstring, $push_ahead) != '{') {
            $push_ahead++;
         }
         $entrytype = trim(mb_strtolower(mb_substr($bfstring, ($conv_index + 1), (($push_ahead - $conv_index) - 1))));
         
         // read entry key
         $key_name_start = $push_ahead;
         while (chat($bfstring, $push_ahead) != ',') {
            $push_ahead++;
         }
         $entrykey = trim(mb_substr($bfstring, ($key_name_start + 1), (($push_ahead - $key_name_start) - 1)));
          
         // get entry guts
         $guts_start = $push_ahead;
         $bracketbalance = 1;
         while ( ! (($bracketbalance == 1) and (chat($bfstring, $push_ahead) == '}') and (chat($bfstring, ($push_ahead - 1) ) != '\\' ) )) {
            $c = chat($bfstring, $push_ahead);
            $bc = chat($bfstring, ($push_ahead - 1));
            if (($c == '{') and ($bc != '\\')) {
               $bracketbalance++;
            } 
            if (($c == '}') and ($bc != '\\')) {
               $bracketbalance--;
            }
            $push_ahead++;
         }
         $guts = trim(mb_substr($bfstring, ($guts_start + 1), (($push_ahead - $guts_start) - 1 )));
         $be = get_entryfields($guts);
          
         // add entry to object
         $be->entrytype = $entrytype;
         $be->originalText = mb_substr($bfstring, $laststart, ($push_ahead - $laststart) );
          
         // fix editor and authors
         if (isset($be->author)) {
             $be->authorArray = ae_to_array($be->author);
         }
         if (isset($be->editor)) {
             $be->editorArray = ae_to_array($be->editor);
         }
          
         $bibobj->{$entrykey} = $be;
          
          
         // update index
         $conv_index = $push_ahead + 1;
          
      } else {
         $conv_index++;
      }
   }
   return $bibobj;
}

function get_entryfields($g) {
   $ef = new StdClass();
   $ch = 0;
   $gl = mb_strlen($g);

   
   // loop through fields
   while ($ch < $gl) {
      // move up to first alpabetical character
      while ((!(mb_ereg_match( '[A-Za-z]', chat($g, $ch)))) and ($ch < $gl)  ) {
         $ch++;
      }
      if ($ch == $gl) { break; }

      
      // read field name
      $lookahead = $ch;
      while (chat($g, $lookahead) != '=') {
         $lookahead++;
         if ($lookahead == $gl) { break; }
      }
      if ($lookahead == $gl) { break; }
      $fieldname = trim(mb_strtolower(mb_substr($g, $ch, ($lookahead - $ch))));
      
      // read for " or {
      $ch = $lookahead + 1;
      while ((chat($g, $ch) != '"') and (chat($g, $ch) != '{')) {
         $ch++;
         if ($ch == $gl) { break; }
      }
      if ($ch == $gl) {break; }
      $chat = chat($g, $ch);
      if ($chat == '"') {
         $mymatch = '"';
      } else {
         $mymatch = '}';
      }
     
      // read until matching thingy found
      $ch++;
      $layers = 1;
      $lookahead = $ch;

      while (!(
               (chat($g, $lookahead) == $mymatch) and
               (chat($g, ($lookahead - 1)) != '\\') and
               ($layers == 1)
            )) {
         $c = chat($g, $lookahead);
         $cb = chat($g, ($lookahead - 1));
         if (($c == '{') and ($cb != '\\')) {
            $layers++;
         }
         if (($c == '}') and ($cb != '\\')) {
            $layers--;
         }
         $lookahead++;
      }
      $fieldvalue = trim(mb_substr($g, $ch, ($lookahead - $ch)));
      
      $ef->{$fieldname} = $fieldvalue;
      
      $ch = $lookahead + 1;
   }
   
   //$ef->{'guts'} = $g;
   return $ef;
}

$rv->bibobj = file_to_bibobject($upload_filename);
send_and_exit();
