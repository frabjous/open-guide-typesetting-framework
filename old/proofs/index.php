<?php

session_start(); 
require '../getjtsettings.php';

// function for handling page loading errors
function page_error($s) {
    global $err_page_msg, $jt_settings;
    $err_page_msg = $s . " Contact {$jt_settings->contact_name} (<a href=\"mailto:{$jt_settings->contact_email}\">{$jt_settings->contact_email}</a>) if you need assistance.";
    include('../error_page.php');
    exit;

}

// read document number and set of proofs from part following ? in URL
$jt_doc_num = '';
$jt_set_num = 0;
if (!(isset($_GET["doc"]))) {
    page_error('This page requires that a document number be given to work.');
}
$jt_doc_num = $_GET["doc"];
if (isset($_GET["set"])) {
    $jt_set_num = intval($_GET["set"]);
}

// see if proof number exists
$jt_proofsfolder = $jt_settings->datafolder . '/docs/' . $jt_doc_num . '/proofs/' . strval($jt_set_num);
if (!is_dir($jt_proofsfolder)) {
    page_error('The requested set of proofs could not be found.');
}

// check proof set key
if (!(isset($_GET["pskey"]))) {
    page_error("The key to access the requested proof set was not provided.");
}
$jt_ps_key = $_GET['pskey'];
$jt_pskeyfn = $jt_proofsfolder . '/pskey.txt';
if (!(file_exists($jt_pskeyfn))) {
    page_error('The access key for the requested proof set has not been set.');
}
$jt_right_ps_key = trim(file_get_contents($jt_pskeyfn));
if ($jt_ps_key != $jt_right_ps_key) {
    page_error('The access key for the requested proof set that was given is incorrect.');
}

//checkifeditormode
$editor_mode = false;
if ((isset($_GET["editormode"])) && ($_GET["editormode"] == "true")) {
    $editor_mode = true;
}

// figure out number of pages
$totpages = 1;
while (file_exists($jt_proofsfolder . '/page-' . strval($totpages) . '.png')) {
    $totpages++;
}
$totpages--;
if ($totpages < 1) {
    page_error('The page proofs do not appear to be ready yet.');
}


?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8" />
        <meta name="description" content="<?php echo $jt_settings->journal_name; ?> typesetting site" />
        <meta name="author" content="<?php echo $jt_settings->contact_name; ?>" />
        <meta name="copyright" content="© <?php echo getdate()["year"] . ' ' . $jt_settings->contact_name; ?>" />
        <meta name="keywords" content="journal,typeseting" />
        <meta name="robots" content="noindex,nofollow" />  
        <meta name="viewport" content="width=device-width, initial-scale=1" />
        <meta name="apple-mobile-web-app-capable" content="yes" />
        <meta name="mobile-web-app-capable" content="yes" />
        <link rel="shortcut icon" href="favicon.ico" type="image/x-icon" />
        <title><?php echo $jt_settings->journal_name; ?> page proofs</title>
        <script type="text/javascript" charset="utf-8" src="/kcklib/ajax.js"></script>
        <link rel="stylesheet" type="text/css" href="/kcklib/kckdialog.css" />
        <script type="text/javascript" charset="utf-8" src="/kcklib/kckdialog.js"></script>
        <style>
            * {
                box-sizing: border-box;
            }
            body {
                margin: 0; padding: 0;
            }
            input[type="number"] {
                width: 3.5em;
                text-align: center;
            }
            #left {
                float: left;
                display: inline-block;
                width: 25%;
                height: 100vh;
                background-color: black;
                overflow: auto;
                padding: 0.5ex;
            }
            #right { 
                float: right;
                width: 75%;
                display: flex;
                height: 100vh;
                flex-direction: column;
            }
            #top {
                min-height: 50px;
                background-color: #6B9BAF;
                width: 100%;
                vertical-align: middle;
                padding: 1ex;
            }
            #top div {
                vertical-align: middle;
                color: white;
                display: inline-block;
                margin-right: 1em;
                user-select: none;
            }
            #top div a, #top div a:link, #top div a:visited {
                color: white;
                text-decoration: none;
            }
            #top div:hover a, #top div:hover a:link, #top div:hover a:visited {
                text-decoration: underline;
            }
            #top div:hover {
                cursor: pointer;
                text-decoration: underline;
            }
            #top div.special:hover {
                text-decoration: none;
            }
            #top div label, #top div:hover label, #top div label:hover  {
                text-decoration: none !important;
                cursor: default;
                color: LightGray;
            }
            #bottom {
                width: 100%;
                background-color: gray;
                overflow: auto;
                text-justify: center;
            }
            #pdfimgwrapper {
                border: 2px solid black;
                margin: auto;
                margin-top: 2ex;
                margin-bottom: 2ex;
                position: relative;
            }
            #pdfimgwrapper img {
                width: 100%;
                user-select: none;
                -webkit-user-drag: none;
                user-drag: none;
                border: 0;
                margin: 0;
                vertical-align: bottom;
            }
            .drawbox {
                display: inline-block;
                position: absolute;
                border: 1px solid gray;
                z-index: 100;
            }
            .drawbox.done {
                cursor: pointer;
            }
            .drawbox.comment, #choosecomment, #ltgtb td.comment, .commentEditInner.comment {
                background-color: rgba(255,255,0,0.5);
            }
            .drawbox.insertion, #chooseinsertion,  #ltgtb td.insertion, .commentEditInner.insertion {
                background-color: rgba(120,120,255,0.5);
            }
            .drawbox.deletion, #choosedeletion, #ltgtb td.deletion, .commentEditInner.deletion {
                background-color: rgba(255,120,120,0.5);
            }
            .drawbox.query, #ltgtb td.query, .commentEditInner.query {
                background-color: rgba(100,255,100,0.5);
            }
            td.comment:before {
                content: '[COMMENT:] ';
            }
            td.deletion:before {
                content: '[DELETION:] ';
            }
            td.insertion:before {
                content: '[INSERTION:] ';
            }
            td.query:before {
                content: '[QUERY:] ';
            }
            #instructions {
                position: fixed;
                width: 100vw;
                height: 100vh;
                background-color: rgba(0,0,0,0.7);
                z-index: 4000;
                display: flex;
                align-content: center;
                justify-content: center;
                
            }
            #instructions > div {
                font-family: sans-serif;
                background-color: white;
                z-index: 4005;
                display: inline-block;
                align-self: center;
                padding: 3em;
                border-radius: 2em;
                max-width: 80%;
            }
            #instructions > div > div {
                max-height: 50vh;
                overflow: auto;
                margin-bottom: 1ex;
                border: 1px solid gray;
                padding: 1ex;
            }
            #instructions img {
                max-width: 100%;
            }
            #top > div#commTypeSelections {
                padding: 0.25ex;
                background-color: white;
            }
            #top > div#commTypeSelections > span {
                display: inline-block;
                width: 7em;
                text-align: center;
                border: 2px solid #AAAAAA;
                margin-right: 0.25em;
                color: gray;
            }
            #top > div#commTypeSelections > span:last-child {
                margin-right: 0;
            }
            #top > div#commTypeSelections > span.chosen {
                border: 2px solid #6666FF;
                color: black;
            }
            #listing {
                border-collapse: collapse;
                border-spacing: 0;
                color: black;
                background-color: white;
                width: 100%;
            }
            #listing td {
                font-size: 80%;
                width: 100%;
                padding: 2px;
                border-bottom: 2px solid black;
                cursor: pointer;
            }
            .commentEditOuter {
                width: 50vw;
                display: inline-block;
                z-index: 2015;
                vertical-align: middle;
                text-align: left;
                margin-left: auto;
                margin-bottom: auto;
                border: 2px solid black;
                background-color: white;
                margin-top: 50px;
                padding: 0ex;
            }
            .commentEditInner {
                padding: 1ex;
            }
            .commentEditInner button {
                margin-top: 1ex;
                margin-right: 1ex;
            }
            .commentEditInner textarea {
                display: block;
                width: 100%;
                height: 18ex;
                clear: both;
            }
            .commentEditInner select {
                display: block;
                margin-bottom: 1ex;
            }
            
            <?php if (!$editor_mode): ?>
            
            .editoronly {
                display: none !important;
            }

            <?php endif; ?>
            
        </style>
        <script>
            window.pdfImgWidth = 95;
            window.isDrawing = false;
            window.totalPages = <?php echo $totpages; ?>;
            window.currentPage = 0;
            
            <?php if ($editor_mode): ?>
            
            window.editorMode = true;
            window.drawClass = "query";
            
            <?php else: ?>

            window.editorMode = false;
            window.drawClass = "comment";
            
            <?php endif; ?>
            
            function imgZoomIn() {
                var w = document.getElementById("pdfimgwrapper");
                window.pdfImgWidth += 20;
                w.style.width = window.pdfImgWidth + "%";
            }
            function imgZoomOut() {
                var w = document.getElementById("pdfimgwrapper");
                window.pdfImgWidth -= 20;
                if (window.pdfImgWidth < 10) {
                    window.pdfImgWidth = 10;
                }
                w.style.width = window.pdfImgWidth + "%";
            }
            function handleClick(e) {
                console.log(e);
            }
            function clickPercs(e) {
                var bcr = window.pdfImg.getBoundingClientRect();
                var w = bcr.right - bcr.left;
                var h = bcr.bottom - bcr.top;
                var rx = e.clientX - bcr.left;
                var ry = e.clientY - bcr.top;
                var xp = (rx/w) * 100;
                var yp = (ry/h) * 100;
                return { "X": xp, "Y": yp };
            }
            function newElem(tagtype, parentnode, clsses) {
                if (typeof clsses === "undefined") clsses = [];
                var e = document.createElement(tagtype);
                parentnode.appendChild(e);
                for (var i=0; i<clsses.length; i++) {
                    e.classList.add(clsses[i]);
                }
                return e;
            }
            function newDrawBox(startCP, drawclass) {
                var b = newElem("div", document.getElementById("pdfimgwrapper") , ["drawbox", drawclass]);
                b.startCP = startCP;
                b.setPosition = function(cP) {
                    var startCP = this.startCP;
                    if (startCP.X < cP.X ) {
                        this.style.left = startCP.X + '%';
                        this.style.right = (100 - cP.X) + '%';
                    } else {
                        this.style.right = (100 - startCP.X) + '%';
                        this.style.left = cP.X + '%';
                    }
                    if (startCP.Y < cP.Y ) {
                        this.style.top = startCP.Y + '%';
                        this.style.bottom = (100 - cP.Y) + '%';
                    } else {
                        this.style.bottom = (100 - startCP.Y) + '%';
                        this.style.top = cP.Y + '%';
                    }
                }
                return b;
            }

            function loadComments(comms) {
                // empty old comments
                var tdtd = window.ltb.getElementsByTagName("td");
                while (tdtd.length > 0) {
                    tdtd[0].removeMe();
                }
                
                // 
                for (var i=0; i<comms.length; i++) {
                    var comm = comms[i];
                    // create drawing box
                    var b = newDrawBox(comm.startCP, comm.commClass);
                    b.endCP = comm.endCP;
                    b.setPosition(comm.endCP);
                    // create listing
                    var lg = newListing(b);
                    lg.setNote(comm.note);
                    lg.isNew = false;
                    lg.isAddressed = comm.isAddressed;
                    // prompt
                    if (comm.commClass == "query") {
                        if (comm.hasOwnProperty("prompt")) {
                            lg.setPrompt(comm.prompt);
                        }
                    }
                }
            }
            
            function fetchComments(pagenum) {
                AJAXGetRequest('getcomments.php', 
                               'doc=<?php echo $jt_doc_num; ?>&pskey=<?php echo $jt_ps_key; ?>&set=<?php echo $jt_set_num;?>&page=' + pagenum.toString(),
                               function(text) {
                                   try {
                                       var resObj = JSON.parse(text);
                                   } catch(err) {
                                       kckRemoveWait();
                                       kckErrAlert("Could not parse information from server.");
                                       return;
                                   }
                                   kckRemoveWait();
                                   loadComments(resObj.comms);
                                   window.currentPage = resObj.pagenum;
                                   document.getElementById("jumptopage").value = resObj.pagenum;
                               }
                );
            }
            function saveComments() {
                var commList = [];
                var tdtd = window.ltb.getElementsByTagName("td");
                for (var i=0; i<tdtd.length; i++) {
                    var c = {};
                    c.note = tdtd[i].myNote;
                    c.startCP = tdtd[i].myBox.startCP;
                    c.endCP = tdtd[i].myBox.endCP;
                    if (tdtd[i].classList.contains("comment")) {
                        c.commClass = "comment";
                    } else if (tdtd[i].classList.contains("insertion")) {
                        c.commClass = "insertion";
                    } else if (tdtd[i].classList.contains("deletion")) {
                        c.commClass = "deletion";
                    } else if (tdtd[i].classList.contains("query")) {
                        c.commClass = "query";
                    }
                    if (c.commClass == "query") {
                        c.prompt = tdtd[i].myPrompt;
                    }
                    c.isAddressed = tdtd[i].isAddressed;
                    commList.push(c);
                }
                var fD = new FormData();
                fD.append("commentlist", JSON.stringify(commList));
                fD.append("page", window.currentPage);
                fD.append("pskey", "<?php echo $jt_ps_key; ?>");
                fD.append("doc", "<?php echo $jt_doc_num; ?>");
                fD.append("set", "<?php echo $jt_set_num; ?>");
                kckWaitScreen();
                AJAXPostRequest("savecomments.php", fD, function(text) {
                    if (text.trim() == 'true') {
                        kckRemoveWait();
                    } else {
                        kckRemoveWait();
                        kckErrAlert("There was a problem saving your comments on the server. Please inform the editors.");
                    }
                }, function(text) {
                    kckRemoveWait();
                    kckErrAlert("There was a problem saving your comments on the server. Please inform the editors.");
                    
                });
                          
            }
            function jumptoPage(pagenum) {
                pagenum = parseInt(pagenum);
                if ((pagenum<1) || (pagenum>window.totalPages)) {
                    document.getElementById("jumptopage").value = window.currentPage;
                    return;
                }
                gotoPage(pagenum);
            }
            
            function gotoPage(pagenum) {
                if ((pagenum<1) || (pagenum>window.totalPages)) {
                    return;
                }
                kckWaitScreen();
                window.pdfImg.src = 'getpng.php?doc=<?php echo $jt_doc_num; ?>&pskey=<?php echo $jt_ps_key; ?>&set=<?php echo $jt_set_num;?>&page=' + pagenum.toString();
                fetchComments(pagenum);
            }
            
            function prevPage() {
                if (window.currentPage > 1) {
                    gotoPage(window.currentPage - 1);
                }
            }
            
            function nextPage() {
                if (window.currentPage < window.totalPages) {
                    gotoPage(window.currentPage + 1);
                }
            }
            function chooseType(e) {
                document.getElementById("choosecomment").classList.remove("chosen");
                document.getElementById("chooseinsertion").classList.remove("chosen");
                document.getElementById("choosedeletion").classList.remove("chosen");
                e.classList.add("chosen");
                window.drawClass = e.id.substring(6);
            }
            function changeComClass(e, c) {
                e.classList.remove("comment","insertion","deletion","query");
                e.classList.add(c);
            }
            function newListing(drwbox) {
                var newtr = newElem("tr", window.ltb);
                var cc = [];
                if (drwbox.classList.contains("comment")) {
                    cc.push("comment");
                }
                if (drwbox.classList.contains("insertion")) {
                    cc.push("insertion");
                }
                if (drwbox.classList.contains("deletion")) {
                    cc.push("deletion");
                }
                if (drwbox.classList.contains("query")) {
                    cc.push("query");
                }
                var newtd = newElem("td", newtr, cc);
                newtd.myBox = drwbox;
                newtd.myBox.myTD = newtd;
                cc.push("commentEditInner");
                
                newtd.removeMe = function() {
                    this.myBox.parentNode.removeChild(this.myBox);
                    this.parentNode.parentNode.removeChild(this.parentNode);
                }
                
                newtd.myNote = '';
                if (newtd.classList.contains("query")) {
                    newtd.myPrompt = '';
                }
                newtd.setNote = function(t) {
                    this.myNote = t;
                    if (this.classList.contains("query")) {
                        this.innerHTML = '<em>Q: </em>' + this.myPrompt + ' <em>A: </em>' + t.replace(/&/g,'&amp;').replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;").replace(/'/g, "&#039;");
                    } else {
                        this.innerHTML = t.replace(/&/g,'&amp;').replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;").replace(/'/g, "&#039;");
                    }
                }
                if (newtd.classList.contains("query")) {
                    newtd.setPrompt = function(pr) {
                        this.myPrompt = pr;
                        this.innerHTML = '<em>Q: </em>' + this.myPrompt.replace(/&/g,'&amp;').replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;").replace(/'/g, "&#039;") + ' <em>A: </em>' + this.myNote.replace(/&/g,'&amp;').replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;").replace(/'/g, "&#039;");
                    }
                }
                newtd.isNew = true;
                newtd.isAddressed = false;
                
                newtd.editComment = function() {
                    blurAll();
                    var bigDiv = creAdd("div", document.body, ["alertbg"], "commentEdit");
                    var smallDivO = creAdd("div", bigDiv, ["commentEditOuter"]);
                    var eccc = ["commentEditInner"];
                    var ectype = '';
                    if (this.classList.contains("comment")) {
                        eccc.push("comment");
                        ectype = "comment";
                    }
                    if (this.classList.contains("insertion")) {
                        eccc.push("insertion");
                        ectype = "insertion";
                    }
                    if (this.classList.contains("deletion")) {
                        eccc.push("deletion");
                        ectype = "deletion";
                    }                   
                    if (this.classList.contains("query")) {
                        eccc.push("query");
                        ectype = "query";
                    }                   
                    var smallDivI = creAdd("div", smallDivO, eccc);
                    bigDiv.colorDiv = smallDivI;
                    bigDiv.myTD = this;
                    
                    if (!this.classList.contains("query")) {
                        bigDiv.classSelect = creAdd("select", smallDivI);
                        var tyty = ["comment", "insertion", "deletion"];
                        for (var i=0; i<tyty.length; i++) {
                            var ty = tyty[i];
                            var o = creAdd("option", bigDiv.classSelect);
                            o.value = ty;
                            if (ty == ectype) {
                                o.selected = true;
                            } else {
                                o.selected = false;
                            }
                            o.innerHTML = ty;
                        }
                        bigDiv.classSelect.myBD = bigDiv;
                        bigDiv.classSelect.onchange = function() {
                            changeComClass(this.myBD.colorDiv, this.value);
                        }
                    }
                    
                    if (this.classList.contains("query")) {
                        var l = creAdd("label", smallDivI);
                        l.innerHTML = "Query:";
                        if (window.editorMode) {
                            bigDiv.prTA = creAdd("textarea",smallDivI);
                            bigDiv.prTA.value = this.myPrompt;
                            var al = creAdd("label",smallDivI);
                            al.innerHTML = "Answer:";
                        } else {
                            bigDiv.prmpt = creAdd("div", smallDivI, ["queryprompt"]);
                            bigDiv.prmpt.innerHTML = this.myPrompt.replace(/&/g,'&amp;').replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;").replace(/'/g, "&#039;");
                        }
                    } else {
                        var l = creAdd("label", smallDivI);
                        l.innerHTML = "Note:";
                    }
                    bigDiv.myTA = creAdd("textarea",smallDivI);
                    bigDiv.myTA.value = this.myNote;
                    
                    if ((window.editorMode) || (!this.classList.contains("query"))) {
                        bigDiv.delB = creAdd("button",smallDivI);
                        bigDiv.delB.type = "button";
                        bigDiv.delB.innerHTML = "remove";
                        bigDiv.delB.myBD = bigDiv;
                        bigDiv.delB.onclick = function() {
                            this.myBD.myTD.removeMe();
                            this.myBD.parentNode.removeChild(this.myBD);
                            if (!this.myBD.myTD.isNew) {
                                saveComments();
                            }
                        }
                    }
                    
                    bigDiv.saveB = creAdd("button",smallDivI);
                    bigDiv.saveB.type = "button";
                    bigDiv.saveB.innerHTML = "save";
                    bigDiv.saveB.myBD = bigDiv;
                    bigDiv.saveB.onclick = function() {
                        this.myBD.myTD.isNew = false;
                        if (this.myBD.myTD.classList.contains("query")) {
                            if (window.editorMode) {
                                this.myBD.myTD.setPrompt(this.myBD.prTA.value);
                            }
                        } else {
                            changeComClass(this.myBD.myTD, this.myBD.classSelect.value);
                            changeComClass(this.myBD.myTD.myBox, this.myBD.classSelect.value);
                        }
                        this.myBD.myTD.setNote(this.myBD.myTA.value);
                        this.myBD.myTD.isAddressed = this.myBD.isAddressedCB.checked;
                        this.myBD.parentNode.removeChild(this.myBD);
                        saveComments();
                    }
                    var newbr = creAdd("br",smallDivI,["editoronly"]);
                    bigDiv.isAddressedCB = creAdd("input",smallDivI,["editoronly"]);
                    bigDiv.isAddressedCB.type = "checkbox";
                    bigDiv.isAddressedCB.checked = this.isAddressed;
                    bigDiv.isAddressedCB.id = "cb" + Date.now().toString();
                    bigDiv.isAddressedLabel = creAdd("label",smallDivI,["editoronly"]);
                    bigDiv.isAddressedLabel.htmlFor = bigDiv.isAddressedCB.id;
                    bigDiv.isAddressedLabel.innerHTML = " Has been addressed";
                    
                }
                newtd.onclick = newtd.editComment;
                newtd.myBox.onclick = function() {
                    this.myTD.editComment();
                }
                
                return newtd;
            }
            
            function submitAllComments() {
                kckYesNoBox(
                    "(Please note your work is automatically saved. If you are not yet done with all comments, but need to leave the site, you can close this window, and simply visit the same link again later.)<br /><br />Have you finished all your comments—<em>on all pages</em>—and are ready to submit them to the editor(s)?<br />",
                    function() {
                        window.location.href = 'finishedits.php?doc=<?php echo $jt_doc_num; ?>&pskey=<?php echo $jt_ps_key; ?>&set=<?php echo $jt_set_num;?>';    
                    }
                );
            }
            
            window.onload = function() {
                window.pdfImg = document.getElementById("pdfpage");
                window.ltb = document.getElementById("ltgtb");
                document.getElementById("pdfpage").onmousedown = function(e) {
                    window.isDrawing = true;
                    var cP = clickPercs(e);
                    window.drawingBox = newDrawBox(cP, window.drawClass);
                }
               // document.getElementById("pdfpage").onmousemove = function(e) {
                document.body.onmousemove = function(e) {
                    if (!(window.isDrawing)) {
                        return;
                    }
                    var cP = clickPercs(e);
                    window.drawingBox.setPosition(cP);
                }
                document.body.onmouseup = function(e) {
                    if (window.isDrawing) {
                        window.isDrawing = false;
                        window.drawingBox.classList.add("done");
                        var endCP = clickPercs(e);
                        window.drawingBox.endCP = endCP;
                        if ((endCP.X == window.drawingBox.startCP.X) && (endCP.X == window.drawingBox.startCP.X)) {
                            window.drawingBox.parentNode.removeChild(window.drawingBox);
                            return;
                        }
                        var ltg = newListing(window.drawingBox);
                        ltg.editComment();
                    }
                }
                gotoPage(1);
                if (window.editorMode) {
                    document.getElementById('instructions').style.display = 'none';
                }
            }
        </script>
    </head>
    <body>
        <div id="left">
            <table id="listing">
                <tbody id="ltgtb">
                </tbody>
            </table>
        </div>
        <div id="right">
            <div id="top"> 
                <div onclick="imgZoomOut()" title="make image smaller">zoom −</div>  
                <div onclick="imgZoomIn()" title="make image larger">zoom +</div>
                <?php if (!$editor_mode): ?><div id="commTypeSelections" title="change note type">
                    <span onclick="chooseType(this)" id="choosecomment" class="chosen">comment</span><span onclick="chooseType(this)" id="chooseinsertion">insertion</span><span onclick="chooseType(this)" id="choosedeletion">deletion</span>
                </div><?php endif; ?>
                <div><a href="getpdf.php?doc=<?php echo $jt_doc_num; ?>&pskey=<?php echo $jt_ps_key; ?>&set=<?php echo $jt_set_num;?>" target="_blank">PDF</a></div>
                <div onclick="prevPage()" title="view previous page">page −</div>  
                <div onclick="nextPage()" title="view next page">page +</div>
                <div class="special"><label for="jumptopage">Jump to page </label><input type="number" id="jumptopage" value="1" min="1" max="<?php echo $totpages; ?>" onchange="jumptoPage(this.value);" /><label> of <?php echo $totpages; ?></label></div>
                <?php if (!$editor_mode): ?><div onclick="submitAllComments()" title="submit notes to editors">I’m done!</div><?php endif; ?>
            </div>
            <div id="bottom">
                <div id="pdfimgwrapper" style="width: 95%; " >
                    <img src="loading.png" id="pdfpage" alt="pdfpage" userdraggable="false" ondragstart="return false;" />
                </div>
            </div>                 
        </div>
        <div id="instructions">
            <div>
                <h2>Instructions</h2>
                <div>
                    <p>For the purpose of this process, your document has been converted to a series of images. Because they are images, the text cannot be selected, and any hyperlinks do not work. You can, however, download the original PDF from the link on the toolbar, in which such things do work.</p>
                    <p>On each page, highlight any part which you would like to change, by holding down the left mouse button while drawing a rectangle, like so:</p>
                    <p><img src="draw.gif" alt="drawing a box" /></p>
                    <p>When you release the button, a box should appear in which you can enter a comment.</p>
                    <p><img src="comment.gif" alt="entering a comment" /></p>
                    <p>When you’re done entering the comment, click save.</p>
                    <p>To change a comment, click either its marker, or the listing on the left, to bring the box back up. You can also remove the comment from the box.</p>
                    <p>You may choose between different color markers (for insertions, deletions, etc.) from the toolbar.</p>
                    <p>Navigate through the pages using the “page −” and “page +” links, or the “Jump to page” numerical input. The listing on the left shows only the comments for the visible page.</p>
                    <p>You may see some pre-existing green markers for “queries”, or questions, from the editors. Click on the marker, or the corresponding listing on the left, in order to bring up a box in which you can respond to the query.</p>
                    <p>You can make the page image smaller or bigger with the “zoom −” and “zoom +” links on the toolbar.</p>
                    <p>When you’re done adding all your comments <em>for all the pages of your document</em>, click the “I’m done” button to notify the journal editors.</p>
                    <p>Comments are automatically saved on the server side, even prior to being submitted to the editor, so don’t worry about accidental page closure or browser crashes. Just use the same link to revisit this site.</p>
                    <p>Please note this site is not currently set up to be used with a touch-screen rather than a mouse or touch-pad. Perhaps this will change in the future.</p>
                </div>
                <button type="button" onclick="document.getElementById('instructions').style.display = 'none';">close</button>
                                            
            </div>
        </div>
    </body>
</html>