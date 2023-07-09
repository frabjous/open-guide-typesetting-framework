<?php

session_start();
require 'getjtsettings.php';
require $_SERVER["DOCUMENT_ROOT"] . '/kcklib/hostinfo.php';

function newDocStatus() {
    $sts = new StdClass();
    $sts->fileUpload = false;
    $sts->bibFinished = false;
    $sts->docxConverted = false;
    $sts->texConverted = false;
    $sts->archived = false;
    return $sts;
}

function padToTwo($x) {
    $s = strval($x);
    while (strlen($s) < 2) {
        $s = '0' . $s;
    }
    return $s;
}

function niceTime($ts) {
    $t = getdate($ts);
    return $t["hours"] . ':' . padToTwo($t["minutes"]) . ':' . padToTwo($t["seconds"]) . ' on ' . $t["mday"] . ' ' . substr($t["month"], 0, 3) . ' ' . $t["year"];
}

function get_proof_set_keys($docnum) {
    global $jt_settings;
    $doc_folder = $jt_settings->datafolder . '/docs/' . strval($docnum);
    $docproofsfolder = $doc_folder . '/proofs'; 
    $keylist = array();
    $setnum = 0;
    while (is_dir( $docproofsfolder . '/' . strval($setnum) ) ) {
        $keyfile = $docproofsfolder . '/' . strval($setnum) . '/pskey.txt';
        if (!file_exists( $keyfile ) ) {
            return $keylist;
        }
        $li = new StdClass();
        $li->pskey = trim(file_get_contents($keyfile));
        $li->mtime = filemtime($keyfile);
        array_push($keylist, $li);
        $setnum++;
    }

    return $keylist;

}

function output_project_div($project) {
    global $jt_settings;
    echo '<div class="projectdiv';
    if ($project->status->archived) {
        echo ' archived';
    }
    echo '">' . PHP_EOL;
    echo '<strong>('  . $project->docnum . ')</strong>' . PHP_EOL;
    if ($project->status->archived) {
        echo ' <strong>ARCHIVED</strong> ';
    }
    if ((isset($project->metadata->worktype)) && ($project->metadata->worktype == "review")) {
        echo ' Review of ';
        if (isset($project->metadata->reviewedtitle)) {
            echo '<em>' . $project->metadata->reviewedtitle . '</em>';
        }
    } else {
        echo '“';
        if (isset($project->metadata->title)) {
            echo $project->metadata->title;
        }
        echo '”';
    }
    if (isset($project->metadata->firstauthor)) {
        echo ' by ' . $project->metadata->firstauthor;
    }
    if ((isset($project->metadata->secondauthor)) && ($project->metadata->secondauthor != '')) {
        echo ' and ' . $project->metadata->secondauthor;
    }
    echo '<br />' . PHP_EOL;
    echo '<div class="linkdiv">' . PHP_EOL;
    echo '<a href="editproject.php?doc=' . urlencode($project->docnum) . '">edit metadata</a>';
    echo '<br />';
    if ($project->status->fileUpload) {
        $extension = strtoupper(pathinfo($project->status->uploadFilename)["extension"]);
        $timeupload = niceTime($project->status->uploadTime);
        echo $extension . ' file uploaded ' . $timeupload . ' ';
        echo '&nbsp; <a target="_blank" href="getuploadedfile.php?doc=' . urlencode($project->docnum) . '">download</a> &nbsp; ';

    }
    echo '<span class="fakelink" onclick="uploadForm(\'' . $project->docnum . '\', false);">';
    if ($project->status->fileUpload) {
        echo 'replace file';
    } else {
        echo 'upload file';
    }
    echo '</span>';
    if ($project->status->fileUpload) {
        echo '<br />';
        echo 'Supplementary files: ';
        if ((isset($project->status->supplementFiles)) 
            && (count($project->status->supplementFiles) > 0)) {
            echo implode(', ',$project->status->supplementFiles);
            echo ' ';
        }
        echo '<span class="fakelink" onclick="uploadForm(\'' . $project->docnum . '\', true);">add</span><br />';
        
        if ($project->status->bibFinished) {
            $timebibfinished = niceTime($project->status->bibFinishedTime);
            echo 'Bibliography completed ' . $timebibfinished;
        } else {
            echo '<a href="editbib.php?doc=' . urlencode($project->docnum) . '">edit bibliography</a>';
        }
    }
    if ($project->status->bibFinished) {
        echo '<br />' . PHP_EOL;
        if ($project->status->texConverted) {
            echo '<a href="/ke/?file=' . urlencode($jt_settings->datafolder . '/docs/' . $project->docnum .'/' . $project->status->texFile ) . '">edit LaTeX file</a>';
        } else {
            echo '<a href="createlatexfile.php?doc=' . urlencode($project->docnum) . '">create LaTeX file</a>';
        }
    }
    echo '<br />' . PHP_EOL;
    if ((isset($project->status->texFile)) && (file_exists( $jt_settings->datafolder . '/docs/' . strval($project->docnum) . '/' . substr($project->status->texFile,0,-4) . '.pdf' ))) {
        // proof set list
        echo 'Proof sets:' . PHP_EOL;
        echo '<br />' . PHP_EOL;
        echo '<ol class="proofslist">' . PHP_EOL;
        $pk_list = get_proof_set_keys ( $project->docnum );
        for ($i=0; $i<count($pk_list); $i++) {
            $pli = $pk_list[$i];
            echo '<li>';
            echo 'Created ' . niceTime($pli->mtime) . ' ';
            echo ' &nbsp; <a href="proofs/?doc=' . urlencode($project->docnum) . '&set=' . $i . '&pskey=' . urlencode($pli->pskey) . '&editormode=true">editor link</a> ';
            echo ' &nbsp; <a href="proofs/?doc=' . urlencode($project->docnum) . '&set=' . $i . '&pskey=' . urlencode($pli->pskey) . '">author link</a> ';
            echo '</li>' . PHP_EOL;
        }
        echo '<li><a href="proofs/newproofset.php?doc=' . urlencode($project->docnum) . '">create new proof set</a></li>' . PHP_EOL;

        echo '</ol>' . PHP_EOL;
        
        // optimize link
        $recent_opt = false;
        $opt_filename = $jt_settings->datafolder . '/docs/' . strval($project->docnum) . '/' . substr($project->status->texFile,0,-4) . '-optimized.pdf';
        if (file_exists($opt_filename)) {
            $texf_modified = filemtime( $jt_settings->datafolder . '/docs/' . strval($project->docnum) . '/' . $project->status->texFile );
            $opt_modified = filemtime( $opt_filename );
            if ($texf_modified < $opt_modified) {
                $recent_opt = true;
                echo 'Optimized PDF created ' . niceTime($opt_modified) . ' &nbsp; <a href="getoptpdf.php?doc=' . urlencode($project->docnum) . '">download</a><br />';
            }
        }
        if (!$recent_opt) {
            echo '<a href="optimizepdf.php?doc=' . urlencode($project->docnum) . '">create optimized PDF</a><br />';
        }
        
    }
    
    
    // extraction options if tex file exists
    if ((isset($project->status->texFile)) && (file_exists($jt_settings->datafolder . '/docs/' . strval($project->docnum) . '/' . $project->status->texFile))) {
        echo '<span class="fakelink" onclick="getAbstract(\'' . $project->docnum . '\');">extract abstract</span> &nbsp; ';
        echo '<span class="fakelink" onclick="getReferences(\'' . $project->docnum . '\');">extract references</span>';
        echo '<br />' . PHP_EOL;
        echo '<br />';
    }
    
    
    echo '<a href="archiveproject.php?doc=' . urlencode($project->docnum) . '">';
    if ($project->status->archived) {
        echo 'unarchive';
    } else {
        echo 'archive';
    }
    echo '</a>';

    echo '</div>' . PHP_EOL; // end of link div

    echo '</div>'  . PHP_EOL; // end of project div
}

// check if logged in; if not, redirect to login page
if (!isset($_SESSION["_jt_user"])) {
    $path = full_path();
    if (substr($path, -1) != '/') {
        $path = dirname($path);
    }
    if (substr($path, -1) != '/') {
        $path .= '/';
    }
    $_SESSION["loginreferer"] = full_url();
    header('Location: ' . $path . 'login.php');
    exit(0);
}

// get project list
$projects = array();
$docfolders = scandir($jt_settings->datafolder . '/docs');
foreach ($docfolders as $folder) {
    $fullfolderpath = $jt_settings->datafolder . '/docs/' . $folder;

    // ignore docs folder itself, its parent, and non-folders
    if (($folder == '.') || ($folder == '..') || (!is_dir($fullfolderpath))) {
        continue;
    }

    $project = new StdClass();
    $project->docnum = $folder;

    $doc_meta_file = $fullfolderpath . '/metadata.json';
    $doc_status_file = $fullfolderpath . '/status.json';

    if (!file_exists($doc_meta_file)) {
        $project->metadata = new StdClass();
        file_put_contents($doc_meta_file, '{}');
    } else {
        $project->metadata = json_decode(file_get_contents($doc_meta_file));
    }

    if (!file_exists($doc_status_file)) {
        $project->status = newDocStatus();
        file_put_contents($doc_status_file, json_encode($project->status, JSON_UNESCAPED_UNICODE));
    } else {
        $project->status = json_decode(file_get_contents($doc_status_file));
    }

    array_push($projects, $project);
}
unset($project);

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
        <script type="text/javascript" charset="utf-8" src="/kcklib/kckdialog.js"></script>
        <script type="text/javascript" charset="utf-8" src="/kcklib/ajax.js"></script>
        <link rel="stylesheet" type="text/css" href="/kcklib/kckdialog.css" />

        <title><?php echo $jt_settings->journal_name; ?> typesetting framework</title>
        <style>
            input, button {
                margin-top: 1ex;
            }
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
            .projectdiv {
                border: 2px solid black;
                padding: 1ex;
                margin-bottom: 2ex;
            }
            .linkdiv {
                margin-top: 1ex;
            }
            .fakelink {
                color: blue;
                text-decoration: underline;
                cursor: pointer;
            }
            #topcbdiv {
                margin-bottom: 1ex;
            }
            .archived {
                display: none;
                background-color: LightGray;
            }
            fieldset textarea {
                width: 100%;
                height: 18ex;
            }
        </style>
        <script>
            window.uploadFormElem = {};
            window.uploadFD = {};
            function changePassword() {
                if (document.getElementById("newpassword1").value != document.getElementById("newpassword2").value) {
                    kckErrAlert("New passwords do not match.");
                    return false;
                }
                if (document.getElementById("newpassword1").value == '') {
                    kckErrAlert("Please enter a new password.");
                    return false;
                }
                var fD = new FormData();
                fD.append("newpassword1",document.getElementById("newpassword1").value);
                fD.append("newpassword2",document.getElementById("newpassword2").value);
                AJAXPostRequest('changepassword.php', fD, function(text) {
                    try {
                        var resObj = JSON.parse(text);
                    } catch(err) {
                        kckErrAlert("There was an error (" + err + ") processing information from server: " + text);
                        return false;
                    }
                    if (resObj.error) {
                        kckErrAlert(resObj.errmsg);
                    } else {
                        kckAlert("Password changed. Please check email for confirmation.");
                    }
                    return true;
                });
            }
            
            
            function addUser() {
                var uname = document.getElementById("newusername").value;               
                var fullname = document.getElementById("newuserfullname").value;
                var email = document.getElementById("newuseremail").value;
                if ((email == '') && (uname == '') && (email == ''))  {
                    kckErrAlert("At least one field must be filled in.");
                    return;
                }
                if (email != '') {
                    var re = /\S+@\S+\.\S+/;
                    if (!re.test(email)) {
                        kckErrAlert("Email address is not properly formatted.");
                        return;
                    }
                }

                var fD = new FormData();
                kckWaitScreen();
                fD.append("username",uname);
                fD.append("fullname",fullname);
                fD.append("email",email);
                AJAXPostRequest('accountinvite.php', fD, function(text) {
                    kckRemoveWait();
                    try {
                        var resObj = JSON.parse(text);
                    } catch(err) {
                        kckErrAlert("There was an error processing data returned from the server. " + err + " " + text);
                        return;
                    }
                    if (resObj.success) {
                        kckAlert("An invitation was sent. They should check their email for information on creating their account and/or changing their password.");
                        document.getElementById("newusername").value = "";
                        document.getElementById("newuserfullname").value = "";
                        document.getElementById("newuseremail").value = "";
                        return;
                    } else {
                        kckErrAlert("There was an error creating the new user or resetting their password: " + resObj.errmsg);
                        return;
                    }
                }, function(text) {
                    kckRemoveWait();
                    kckErrAlert("There was a server error when attempting to create the new user. Server reports: " + text);
                    return;
                });
            }

            function processUploadResponse(text) {
                try {
                    var resObj = JSON.parse(text);
                } catch(err) {
                    kckErrAlert("There was an error parsing data from server: " + err + ". Response: " + text);
                    return;
                }
                if (resObj.error) {
                    kckErrAlert("There was an error in the file upload. The server says: " + resObj.errmsg);
                    return;
                }
                location.reload();
            }

            function doUpload() {
                AJAXPostRequest('uploadfile.php', window.uploadFD, 
                                function(text) {
                    kckRemoveWait();
                    processUploadResponse(text);
                }, 
                                function(text) {
                    kckRemoveWait();
                    kckErrAlert("There was a server error when attempting to upload the file.");
                });
            }

            function startUpload() {
                var ufInput = document.getElementById("uploadfile");
                if (!ufInput) {
                    document.body.removeChild(window.uploadFormElem);
                    kckErrAlert("Input not found.");
                    return;
                }
                if (ufInput.files.length < 1) {
                    document.body.removeChild(window.uploadFormElem);
                    kckErrAlert("No file chosen.");
                    return;
                }
                if (ufInput.files.length > 1) {
                    document.body.removeChild(window.uploadFormElem);
                    kckErrAlert("Too many files chosen.");
                    return;
                }
                window.uploadFD = new FormData();
                window.uploadFD.append("docnum", document.getElementById("uploaddocnum").value);
                window.uploadFD.append("uploadfile", ufInput.files[0], ufInput.files[0].name);
                if (document.getElementById("supplement")) {
                    window.uploadFD.append("supplement", document.getElementById("supplement").value);
                }
                
                kckWaitScreen();
                setTimeout(
                    function() {
                        doUpload();
                    }
                    , 800);
            }

            function hideShowArchive() {
                var archs = document.getElementsByClassName("archived");
                for (var i=0; i<archs.length; i++) {
                    var e=archs[i];
                    if (document.getElementById("hideshowarchivecb").checked) {
                        e.style.display = 'block';
                    } else {
                        e.style.display = 'none';
                    }
                }
            }

            function uploadForm(docnum, suppl) {
                var formHTML = '<fieldset><legend>Choose file to upload.</legend>';
                formHTML += '<input type="hidden" id="uploaddocnum" value="' + docnum + '" />';
                if (suppl) {
                    formHTML += '<input type="hidden" id="supplement" name="supplement" value="yes" />';
                }
                formHTML += '<input type="file" name="uploadfile" id="uploadfile" />';
                formHTML += '<br />';
                formHTML += '<button type="button" onclick="document.body.removeChild(window.uploadFormElem)">cancel</button>';
                formHTML += '<button type="button" onclick="startUpload();">upload</button>';
                formHTML += '</fieldset>';
                window.uploadFormElem = kckPopupForm(formHTML);
            }
            
            function copyExtract() {
                try {
                    var eta = document.getElementById("extracttextarea");
                    eta.select();
                    document.execCommand('copy');
                } catch(err) {};
            }
            
            function getAbstract(docnum) {
                kckWaitScreen();
                AJAXGetRequest('getabstract.php','doc=' + docnum,function(text) {
                    kckRemoveWait();
                    if (text.substring(0,8) == '--ERROR:') {
                        kckErrAlert('There was an error fetching the abstract. The server reports: ' + text.substring(9));
                        return;
                    }
                    var formHTML = '<fieldset><legend>abstract (HTML)</legend>';
                    formHTML += '<textarea id="extracttextarea"></textarea><br />';
                    formHTML += '<button type="button" onclick="copyExtract();">copy</button>';
                    formHTML += '</fieldset>';
                    window.extractForm = kckPopupForm(formHTML);
                    text = text.trim();
                    if ((text.substring(0,3) == '<p>') && (text.substring( text.length -4 ) == '</p>')) {
                        text = text.substring(3);
                        text = text.substring(0, text.length - 4 );
                    }
                    document.getElementById("extracttextarea").value = text;
                    copyExtract();
                });
            }

            function getReferences(docnum) {
                kckWaitScreen();
                AJAXGetRequest('getreferences.php','doc=' + docnum,function(text) {
                    kckRemoveWait();
                    if (text.substring(0,8) == '--ERROR:') {
                        kckErrAlert('There was an error fetching the references. The server reports: ' + text.substring(9));
                        return;
                    }
                    var formHTML = '<fieldset><legend>references (plain text)</legend>';
                    formHTML += '<textarea id="extracttextarea"></textarea><br />';
                    formHTML += '<button type="button" onclick="copyExtract();">copy</button>';
                    formHTML += '</fieldset>';
                    window.extractForm = kckPopupForm(formHTML);
                    text = text.trim();
                    if (text.substring(0,10) == 'References')  {
                        text = text.substring(10).trim();
                    }
                    document.getElementById("extracttextarea").value = text;
                    copyExtract();
                });
            }

            
            window.onload = function() {
                document.getElementById("hideshowarchivecb").checked = false;
                setTimeout( function() {
                    document.getElementById("newpassword1").value = '';
                    document.getElementById("newpassword2").value = '';
                }, 100); 
            }
            
            

        </script>
    </head>
    <body>
        <div id="logoutstrip"><a href="logout.php">log out</a></div>
        <h1><?php echo $jt_settings->journal_name; ?></h1>
        <h3>Typesetting framework</h3>
        <h2>Projects</h2>
        <div id="topcbdiv">
            <input type="checkbox" onchange="hideShowArchive()" id="hideshowarchivecb" /><label for="hideshowarchivecb">Show archived projects</label>
        </div>
        <div id="projectlist">
            <?php

            echo PHP_EOL . PHP_EOL;
            foreach ($projects as $project) {
                output_project_div($project);
            }
            unset($project);
            echo PHP_EOL . PHP_EOL;


            ?>
        </div>
        <fieldset>
            <legend>Create new project</legend>
            <form method="post" action="newdocument.php">
                <input type="text" id="newdoc" name="newdoc" required /> 
                <label for="newdoc">document number</label><br />
                <button type="submit">create</button>
            </form>
        </fieldset>

        <h2>Change password</h2>
        <fieldset>
            <input type="password" id="newpassword1" name="newpassword1" autocomplete="new-password" value=""  />
            <label for="newpassword1">new password</label><br />
            <input type="password" id="newpassword2" name="newpassword2" autocomplete="new-password" value=""  />
            <label for="newpassword2">repeat password</label><br />
            <button type="button" onclick="changePassword()">change my password</button>
        </fieldset>
        
        <h2>Add a user/reset another user’s password</h2>
        <fieldset>
            <input id="newusername" name="newusername" />
            <label for="newusername">new username/login id</label><br />
            <input name="newuserfullname" id="newuserfullname" />
            <label for="newuserfullname">full name</label><br />
            <input type="email" name="newuseremail" id="newuseremail" />
            <label for="newuseremail">email</label><br />
            <button type="button" onclick="addUser()">add/reset user</button>
        </fieldset>
        
    </body>
</html>

