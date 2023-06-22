<?php

session_start();
require 'getjtsettings.php';

function rage_quit($s = '') {
    echo "ERROR: $s.";
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

$doc_meta_file = $doc_folder . '/metadata.json';

if (!file_exists($doc_meta_file)) {
    rage_quit("Document specified does not exist");
} 

$doc_meta = json_decode(file_get_contents($doc_meta_file));

?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8" />
        <meta name="description" content="<?php echo $jt_settings->journal_name; ?> typesetting site" />
        <meta name="author" content="<?php echo $jt_settings->contact_name; ?>" />
        <meta name="copyright" content="© <?php echo getdate()["year"] . ' ' . $jt_settings->contact_name; ?>" />
        <meta name="keywords" content="journal,typesetting" />
        <meta name="robots" content="noindex,nofollow" />  
        <meta name="viewport" content="width=device-width, initial-scale=1" />
        <meta name="apple-mobile-web-app-capable" content="yes" />
        <meta name="mobile-web-app-capable" content="yes" />
        <link rel="shortcut icon" href="favicon.ico" type="image/x-icon" />
        <!-- <script type="text/javascript" charset="utf-8" src="/kcklib/kckdialog.js"></script>
<script type="text/javascript" charset="utf-8" src="/kcklib/ajax.js"></script>
<link rel="stylesheet" type="text/css" href="/kcklib/kckdialog.css" /> -->
        <title>Document properties: document number <?php echo $doc_num; ?></title>
        <style>
            #logoutstrip {
                background-color: rgb(0,0,0,0.6);
                position: fixed;
                top: 3px;
                right: 3px;
                border-radius: 5px;
                display: inline-block;
                padding: 1ex;
                text-align: right;
            }
            #logoutstrip a, #logoutstrip a:link, #logoutstrip a:visited {
                color: #CCCCFF;
            }
            fieldset span {
                display: block;
                white-space: nowrap;
                margin-bottom: 1ex;
            }
            input, button {
                margin-right: 0.5em;
            }
            fieldset {
                margin-bottom: 1ex;
            }
            input[type="number"] {
                width: 4em;
            }
        </style>
        <script>
            
            window.docMetaData = <?php echo trim(file_get_contents($doc_meta_file)); ?>;
            
            function formswap() {
                var art = document.getElementById("wt-article").checked;
                if (art) {
                    document.getElementById("art-only-fields").style.display = "block";
                    document.getElementById("rev-only-fields").style.display = "none";
                } else {
                    document.getElementById("rev-only-fields").style.display = "block";
                    document.getElementById("art-only-fields").style.display = "none";
                }
            }
            function cancel() {
                window.location.href = './';
            }
            
            window.onload = function() {
                if ((window.docMetaData.hasOwnProperty("worktype")) && (window.docMetaData.worktype == "review")) {
                    document.getElementById("wt-article").checked = false;
                    document.getElementById("wt-review").checked = true;
                    formswap();
                }
                var fieldsToFill = ["title",
                                    "firstauthor",
                                    "firstemail",
                                    "firstaffiliation",
                                    "secondauthor",
                                    "secondemail",
                                    "secondaffiliation",
                                    "reviewedtitle",
                                    "reviewedauthor",
                                    "reviewededitor",
                                    "secondreviewedauthor",
                                    "secondreviewededitor",
                                    "reviewpubdetails",
                                    "volume",
                                    "volnumber",
                                    "specialvolume",
                                    "specialvolumeeditors"
                                   ];
                for (var i=0; i<fieldsToFill.length; i++) {
                    var f = fieldsToFill[i];
                    if (window.docMetaData.hasOwnProperty(f)) {
                        document.getElementById(f).value = window.docMetaData[f];
                    }
                }
            }
            
        </script>

    </head>
    <body>
        <div id="logoutstrip"><a href="logout.php">log out</a></div>
        <h1>Document properties: document number <?php echo $doc_num; ?></h1>
        <form action="savedocumentproperties.php" method="post" >
            <input type="hidden" value="<?php echo $doc_num; ?>" name="docnum" id="docnum" />
            <fieldset>
                <legend>Type</legend>
                <div>
                    <span><input type="radio" name="worktype" id="wt-article" value="article" checked onchange="formswap();" /> <label for="wt-article">Article</label> &nbsp; 
                    <input type="radio" name="worktype" id="wt-review" value="review" onchange="formswap();" /> <label for="wt-review">Review</label></span>
                </div>
            </fieldset>
            <fieldset>
                <legend>Metadata</legend>
                <div id="art-only-fields">
                    <span><input type="text" name="title" id="title" /> <label for="title">Title</label></span>
                </div>
                <span><input type="text" name="firstauthor" id="firstauthor" required /> <label for="firstauthor">First author name</label></span>
                <span><input type="email" name="firstemail" id="firstemail" required /> <label for="firstemail">First author email</label></span>
                <span><input type="text" name="firstaffiliation" id="firstaffiliation" required /> <label for="firstaffiliation">First author affiliation</label></span>
                <span><input type="text" name="secondauthor" id="secondauthor" /> <label for="secondauthor">Second author (if applicable)</label></span>
                <span><input type="email" name="secondemail" id="secondemail" /> <label for="secondemail">Second author email</label></span>
                <span><input type="text" name="secondaffiliation" id="secondaffiliation" /> <label for="secondaffiliation">Second author affiliation</label></span>
                <div id="rev-only-fields" style="display: none;">
                    <span><input type="text" name="reviewedtitle" id="reviewedtitle" /> <label for="reviewedtitle">Title of reviewed work</label></span>
                    <span><input type="text" name="reviewedauthor" id="reviewedauthor" /> <label for="reviewedauthor">(First) Author of reviewed work</label></span>
                    <span><input type="text" name="secondreviewedauthor" id="secondreviewedauthor" /> <label for="secondreviewedauthor">Second author of reviewed work</label></span>
                    <span><input type="text" name="reviewededitor" id="reviewededitor" /> <label for="reviewededitor">(First) Editor of reviewed work</label></span>
                    <span><input type="text" name="secondreviewededitor" id="secondreviewededitor" /> <label for="secondreviewededitor">Second editor of reviewed work</label></span>
                    <span><input type="text" name="reviewedpubdetails" id="reviewedpubdetails" /> <label for="reviewedpubdetails">Publication details</label></span>
                    Publication details format:  Place: Publisher, Year. Pages. $ Cost Hardcover. ISBN XXX-X-XX-XXXXXXX. 
                </div>
            </fieldset>

            <fieldset>
                <legend>Volume Info</legend>
                Volume <input type="number" name="volume" id="volume" value="5" required /> Number <input type="number" name="volnumber" id="volnumber" required value="1" />
                <span><input type="text" name="specialvolume" id="specialvolume" /> <label for="specialvolume">Special volume title (if applicable)</label></span>
                <span><input type="text" name="specialvolumeeditors" id="specialvolumeeditors" /> <label for="specialvolumeeditors">Special volume editors</label></span>
            </fieldset>

            <button type="button" onclick="cancel();">cancel</button>
            <button type="submit" >save</button>
        </form>
    </body>
</html>