<?php
// LICENSE: GNU GPL v3 You should have received a copy of the GNU General
// Public License along with this program. If not, see
// https://www.gnu.org/licenses/.

///////////////////// proofs/index.php /////////////////////////////////
// Serves the main page proof editing and commenting page             //
////////////////////////////////////////////////////////////////////////

session_start();

require_once(dirname(__FILE__) . '/proofsaccess.php');

require_once(dirname(__FILE__) . '/../php/readsettings.php');

$projectsettings = get_project_settings($project);
$title = $projectsettings->title ?? 'Open Guide';

$proofdir = "$datadir/$project/$assignment_type" . 's' .
    "/$assignment_id/proofs/$proofset";

if (!is_dir($proofdir)) {
    itsanogo('Proof set directory not found.');
}

$usehtml = false;
if (file_exists("$proofdir/$assignment_id.html")) {
    $usehtml = true;
}

$pdfpages = 0;
if ((file_exists("$proofdir/$assignment_id.pdf")) &&
    (is_dir("$proofdir/pages"))) {
    $pdfpages = count(scandir("$proofdir/pages")) - 2;
}

$files = scandir($proofdir);

$downloads = array();

foreach ($files as $file) {
    if ($file == '.' || $file == '..') { continue; }
    if (substr($file, 0, strlen($assignment_id)+1) ==
        $assignment_id . '.') {
        array_push($downloads, $file);
    }
}

$pdfparentstart = 1200;

$commentsfile = "$proofdir/saved-comments.json";

$commentsjson = '';
if (file_exists($commentsfile)) {
    $commentsjson = file_get_contents($commentsfile);
}

?><!DOCTYPE html>
<html lang="en">
<head>
<!-- standard metadata -->
<meta charset="utf-8">
<meta name="description" content="Open Guide Typesetting Proofs">
<meta name="author" content="Kevin C. Klement">
<meta name="copyright" content="Copyright 2023 © Kevin C. Klement">
<meta name="keywords" content="academic,typesetting,journal,anthology,guide,pages,proofs">
<meta name="dcterms.date" content="2023-07-23">

<!-- to disable search indexing -->
<meta name="robots" content="noindex,nofollow">

<!-- mobile ready -->
<meta name="viewport" content="width=device-width, initial-scale=1, user-scalable=yes">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="mobile-web-app-capable" content="yes">

<!-- web icon -->
<link rel="shortcut icon" href="../favicon.ico" type="image/x-icon">
<title>Page Proofs | <?php echo $title; ?></title>

<!-- simple css framework -->
<link rel="stylesheet" type="text/css" href="shared.css">

<style>

body {
    font-family: var(--ogstfont-family);
    font-size: 18px;
    background-color: var(--ogstbg);
    color: var(--ogstfg);
    display: flex;
    flex-direction: column;
    align-items: stretch;
    height: 100vh;
    width: 100vw;
    max-height: 100vh;
    max-width: 100vw;
    min-height: 0;
    margin: 0;
    overflow: hidden;
}
header {
    flex-shrink: 0;
    background-color: var(--ogstpanelbg);
    border-bottom: 1px ridge var(--ogstprimary);
    padding: 0.25rem 0.5rem 0.25rem 0.5rem;
    user-select: none;
}
main {
    flex-grow: 1;
    background-color: white;
    height: 100%;
    max-height: 100%;
    min-height: 0px;
}
footer {
    border-top: 1px ridge var(--ogstprimary);
    background-color: var(--ogstpanelbg);
    flex-shrink: 0;
    margin: 0;
    padding: 0.5rem 1rem 0.5rem 1rem;
    user-select: none;
}
footer p {
    margin: 0;
}
a, a:link, a:visited {
    text-decoration: none;
    color: var(--ogstprimary);
}
a:hover, a:link:hover, a:visited:hover {
    color: var(--ogstprimary-hover);
    text-decoration: underline;
}
#projecthdr #projectname {
    color: var(--ogstprimary);
}
#projecthdr #tsf {
    float: right;
}
#commoncontainer {
    padding: 0;
    margin: 0;
    height: 100%;
    width: 100%;
    max-height: 100%;
    max-width: 100%;
}

#instructionsholder,
#pdfholder,
#htmlholder {
    height: 100%;
    width: 100%;
    max-width: 100%;
    max-height: 100%;
}

#instructionsholder {
    overflow: auto;
}

#htmlholder {
    overflow: auto;
}

#pdfproofs {
    background-color: #4d606d;
    text-align: center;
    width: 100%;
    height: 100%;
    max-width: 100%;
    max-height: 100%;
    overflow-x: scroll;
    overflow-y: auto;
}
/* pdf parent is what should grow and shrink with zoom */
#pdfparent {
    width: <?php echo strval($pdfparentstart); ?>px;
    margin: auto;
}
#pdfpages {
    display: inline-block;
    width: 96%;
}
#pdfproofs #pdfpages div.pdfpage {
    background-color: white;
    display: inline-block;
    margin-top: 1rem;
    margin-bottom: 1rem;
    width: 100%;
}
#pdfproofs div.pdfpage img {
    width: 100%;
}
iframe {
    border: none;
    width: 100%;
    height: 100%;
    margin: 0;
    padding: 0;
    background-color: white;
}

#pdfholder, #htmlholder, #instructionsholder {
    display: none;
}

body.instructions #instructionsholder,
body.html #htmlholder,
body.pdf #pdfholder {
    display: block;
}

#toppanel #viewselector {
    float: left;
}

#toppanel #rightbuttons {
    float: right;
}

#toppanel #rightbuttons #submitbutton,
#toppanel div.viewoption {
    padding: 0.2rem 0.8rem;
    border: 2px solid var(--ogstinactive);
    color: var(--ogstinactive);
    border-radius: 1.5rem;
    margin-left: 0.5rem;
}

#toppanel div.viewoption {
    cursor: pointer;
}

#toppanel #rightbuttons #submitbutton.lookatme {
    border: 2px solid var(--ogstprimary);
    color: var(--ogstfg);
    cursor: pointer;
    animation-name: showoff;
    animation-duration: 0.7s;
    animation-direction: alternate;
    animation-iteration-count: infinite;
}

#toppanel #rightbuttons #submitbutton.submitting {
    border: 2px solid var(--ogstprimary);
    color: var(--ogstfg);
}

#toppanel #rightbuttons #submitbutton.lookatme:hover,
#toppanel div.viewoption:hover {
    background-color: var(--ogstbg);
    color: var(--ogstprimary);
}

body.instructions #toppanel div.viewoption.instructions,
body.html #toppanel div.viewoption.html,
body.pdf #toppanel div.viewoption.pdf {
    color: var(--ogstfg);
    border: 2px solid var(--ogstprimary-hover);
    background-color: var(--ogstbg);
    cursor: default;
}

#toppanel {
    text-align: center;
}

#toppanel div {
    display: inline-block;
}

#instructions {
    padding: 2rem;
}

#instructionscore {
    max-width: 40rem;
    margin-left: auto;
    margin-right: auto;
    text-rendering: optimizeLegibility;
    font-kerning: normal;
    text-align: left;
}

#instructionscore img {
    width: 35rem;
    max-width: 100%;
    border: 0.5px solid var(--ogstinactive);
}

body #toppanel div.pdfonly {
    display: none;
}

body.pdf #toppanel div.pdfonly {
    display: inline-block;
}

#toppanel div.pdfbuttons {
    margin-left: auto;
    margin-right: auto;
}

#toppanel #rightbuttons div.downloadbutton,
#toppanel div.pdfbuttons div.pdfbutton {
    position: relative;
    top: 0.3rem;
    font-size: 140%;
    color: var(--ogstprimary);
    cursor: pointer;
}

#toppanel #rightbuttons div.downloadbutton {
    margin-left: 0.3rem;
}


#toppanel div.pdfbuttons div.pdfbutton {
    margin-right: 0.5rem;
}

#toppanel #rightbuttons div.downloadbutton:hover,
#toppanel div.pdfbuttons div.pdfbutton:hover {
    color: var(--ogstprimary-hover);
}

#toppanel input#pagejump {
    width: 5.3rem;
    padding: 0.3rem;
    text-align: center;
    margin-right: 0.4rem;
}

div.pdfpage {
    position: relative;
}

div.pdfpage img {
    user-select: none;
    user-drag: none;
}

div.pdfpage .pdfcommentmarker {
    border: 0.5px solid rgba(200, 200, 200, 0.5);
}

div.pdfpage .pdfcommentmarker.drawing {
    background-color: var(--ogstpurple);
}

body.editormode .pdfcommentmarker.drawing {
    background-color: var(--ogstgreen);
}

.pdfcommentmarker.deletion {
    background-color: var(--ogstpink);
}

.pdfcommentmarker.insertion {
    background-color: var(--ogstbluey);
}

.pdfcommentmarker.comment {
    background-color: var(--ogstyellow);
}

.pdfcommentmarker.query {
    background-color: var(--ogstgreen);
}

div#errormessage {
    display: none;
    background-color: var(--ogstpink);
    padding: 0.5rem;
    border: 2px solid var(--ogstred);
    color: var(--ogstfg);
}

div#errormessage > span {
    color: var(--ogstred);
}

div#errormessage.okmsg {
    border: 2px solid var(--ogstprimary);
    background-color: var(--ogstpanelbg);
}

</style>

<script type="module">

import downloadFile from '../open-guide-editor/open-guide-misc/download.mjs';

import postData from '../open-guide-editor/open-guide-misc/fetch.mjs';

// initial setup

const w = window;
w.accesskey = '<?php echo $key; ?>';
w.projectname = '<?php echo $project; ?>';
w.username = '<?php echo $username; ?>';
w.assignmentId = '<?php echo $assignment_id; ?>';
w.assignmentType = '<?php echo $assignment_type; ?>';
w.proofset = '<?php echo $proofset; ?>';
w.iseditor = <?php echo json_encode($iseditor); ?>;
w.usehtml = <?php echo json_encode($usehtml); ?>;
w.pdfpp = <?php echo strval($pdfpages); ?>;
w.downloads = <?php echo json_encode($downloads); ?>;
w.pdfzoom = <?php echo strval($pdfparentstart); ?>;
<?php if ($commentsjson && $commentsjson != '') {
    echo 'w.savedcomments = ';
    echo $commentsjson;
    echo ';' . PHP_EOL;
} ?>
<?php if ((isset($_GET["starton"])) && (
    ($_GET["starton"] == 'instructions') ||
    ($_GET["starton"] == 'pdf') ||
    ($_GET["starton"] == 'html'))) {
        echo 'w.starton = "' . $_GET["starton"] . '";' . PHP_EOL;
    }
?>
w.nummarkers = 0;
w.anychangesmade = false;
for (const id of [
    'toppanel',
    'instructionsholder',
    'instructions',
    'errormessage',
    'htmlholder',
    'htmlproofs',
    'pdfholder',
    'pdfproofs',
    'pdfparent',
    'pdfpages'
]) {
    w[id] = document.getElementById(id);
}

//
// FUNCTIONS
//

// functions for making json requests to server

function clearError() {
    errormessage.classList.remove('okmsg');
    errormessage.style.display = 'none';
}

function reportError(msg) {
    changeMode('instructions');
    errormessage.classList.remove('okmsg');
    errormessage.style.display = 'block';
    errormessage.innerHTML =
        '<span>Error when interacting with server.</span> (' + msg +
        ') Check your internet connection. If the problem persists, ' +
        ' consult your ' + ((window.iseditor) ? 'site administrator' :
        'editor') + '.';
    errormessage.scrollIntoView({ block: 'nearest' });
}

function okmessage(msg) {
    changeMode('instructions');
    errormessage.style.display = 'block';
    errormessage.classList.add('okmsg');
    errormessage.innerHTML = msg;
    errormessage.scrollIntoView({ block: 'nearest' });
}

async function jsonrequest(req) {
    clearError();
    const resp = await postData('jsonrequest.php?key=' +
        encodeURIComponent(window.accesskey), req);
    if ((!("respObj" in resp)) || resp.respObj?.error || resp.error) {
        let msg = ((resp?.errMsg) ? resp.errMsg + ' ' : '') +
            ((resp?.respObj?.errMsg) ? resp.respObj.errMsg : '');
        if (msg == '') { msg = 'unknown error'; }
        reportError(msg);
        return false;
    }
    return resp.respObj;
}

// general function for adding elements
function addelem(opts) {
    if (!('tag' in opts)) { return; }
    const elem = document.createElement(opts.tag);
    if ('parent' in opts) {
        opts.parent.appendChild(elem);
    }
    if ('classes' in opts) {
        for (const cl of opts.classes) {
            elem.classList.add(cl);
        }
    }
    for (const opt in opts) {
        if (opt == 'tag' || opt == 'parent' || opt == 'classes') {
            continue;
        }
        elem[opt] = opts[opt];
    }
    return elem;
}

// function for changing between modes
function changeMode(which) {
    document.body.classList.remove('pdf','html','instructions');
    document.body.classList.add(which);
}

// two functions for changing the zoom level
function changeZoom(inc) {
    if (inc === 'fitwidth') {
        window.pdfzoom = (document.body.clientWidth - 36);
    } else {
        window.pdfzoom = window.pdfzoom + inc;
    }
    // don't let it disappear completely
    if (window.pdfzoom <= 100) {
        window.pdfzoom = 100;
    }
    window.pdfparent.style.width = window.pdfzoom.toString() + 'px';
}

function zoomInOut(inout = true) {
    let inc = 200;
    if (window.pdfzoom < 800) { inc = 100; }
    if (window.pdfzoom > 1800) { inc = 400; }
    if (window.pdfzoom > 2800) { inc = 800; }
    if (!inout) { inc = (0-inc); }
    changeZoom(inc);
}

// functions for comment elements

function purgeTraces() {
    const id = this.id;
    if (this?.mywidget?.insertionpoint) {
        const ip = this.mywidget.insertionpoint;
        ip.parentNode.removeChild(ip);
    }
    const tt = htmld.getElementsByClassName(id + '-change');
    while (tt.length > 0) {
        const t = tt[tt.length - 1];
        let rep = htmld.createTextNode(t.innerText);
        t.parentNode.insertBefore(rep,t);
        t.parentNode.removeChild(t);
    }
}

async function deleteComment() {
    // remove from DOM
    if (this?.mywidget?.mymarker) {
        const m = this.mywidget.mymarker;
        m.parentNode.removeChild(m);
    }
    if (this.ishtml) {
        this.purgeTraces();
    }
    // remove off server
    if (this.eversaved) {
        const req = {
            requesttype: 'deletecomment',
            commentid: this.id
        };
        if (this.ishtml) {
            req.bodyhtml = htmld.body.innerHTML;
        }
        const resp = await jsonrequest(req);
        if (!resp) { return; }
    }
    w.submitbutton.updateMe();
}

function isBadParent(e) {
    const tN = e.tagName.toLowerCase();
    return (tN == 'body' || tN == 'main' || tN == 'article');
}

async function saveComment() {
    const req = {
        requesttype: 'savecomment',
        commentinfo: {
            id: this.id,
            commenttype: this.mytype
        }
    };
    for (const x of ['del','ins','comment','response']) {
        const inp = this[x+'input'];
        if (inp && inp.value != '') {
            req.commentinfo[x] = inp.value;
        }
    }
    if (this?.mywidget?.insertionpoint) {
        this.mywidget.insertionpoint.innerHTML =
            this.insinput.value;
    }
    if (this.addressedcb && this.addressedcb.checked) {
        req.commentinfo.hasbeenaddressed = true;
    }
    if (this?.mywidget?.mymarker) {
        const marker = this.mywidget.mymarker;
        if (marker?.mypage) {
            req.commentinfo.page = marker?.mypage.id;
        }
        if (marker?.anchorPP) {
            req.commentinfo.anchorPP = marker.anchorPP;
        }
        if (marker?.wanderPP) {
            req.commentinfo.wanderPP = marker.wanderPP;
        }
    }
    if (this.ishtml) {
        req.bodyhtml = htmld.body.innerHTML;
    }
    let wherefound = this;
    while (wherefound && (!isBadParent(wherefound.parentNode))) {
        wherefound = wherefound.parentNode;
    }
    // try to find good determination of position in document
    const bodychildren = {};
    let mainParent = htmld.body;
    const artart = mainParent.getElementsByTagName("article");
    if (artart && (artart.length > 0)) {
        mainParent = artart[0];
    }
    for (const child of mainParent.childNodes) {
        if (child.tagName) {
            const tagname = child.tagName.toLowerCase();
            if (!(tagname in bodychildren)) {
                bodychildren[tagname] = 0;
            }
            if (tagname == 'h1') {
                for (const tname in bodychildren) {
                    if (tname != 'h1') {
                        bodychildren[tname] = 0;
                    }
                }
            }
            bodychildren[tagname] = bodychildren[tagname] + 1;
            if (child == wherefound) {
                req.commentinfo.topleveltag = tagname;
                req.commentinfo.position = bodychildren[tagname];
                req.commentinfo.section = (bodychildren['h1'] ?? 0);
                break;
            }
        }
    }
    this.makeSaving();
    const resp = await jsonrequest(req);
    if (!resp) {
        this.makeUnsaved();
        return;
    }
    w.anychangesmade = true;
    this.makeSaved();
    w.submitbutton.updateMe();
}

function makeSaved() {
    this.eversaved = true;
    this.classList.remove('unsaved', 'saving');
    this.savebutton.innerHTML = '(saved)';
    this.savebutton.classList.add('disabled');
    this.minimize(true);
}

function makeSaving() {
    this.classList.add('saving');
    this.savebutton.innerHTML = 'saving <span class="material-symbols-outlined' +
        ' rotating">sync</span>';
}

function makeUnsaved() {
    this.classList.remove('saving');
    this.classList.add('unsaved');
    this.savebutton.innerHTML = this.savebutton.origHTML;
    this.savebutton.classList.remove('disabled');
    if (w?.submitbutton?.updateMe) {
        w.submitbutton.updateMe();
    }
}

function minimize(b) {
    if (!this?.mywidget) { return; }
    const widg = this.mywidget;
    // minimize
    if (b) {
        widg.classList.add('minimized');
        if (widg?.mymarker?.innermarker) {
            const innermarker = widg.mymarker.innermarker;
            innermarker.onclick = (e) => {
                this.minimize(false);
                e.stopPropagation();
                e.preventDefault();
            }
            innermarker.onpointerdown = (e) => {
                e.stopPropagation();
                e.preventDefault();
            }
            innermarker.onpointerup = (e) => {
                e.stopPropagation();
                e.preventDefault();
            }
            innermarker.style.cursor = 'pointer';
        }
        if (this.ishtml) {
            let chch = htmld.getElementsByClassName(this.id + '-change');
            if (chch) {
                for (const ch of chch) {
                    if (!(ch.classList.contains('ogstchange'))) {
                        continue;
                    }
                    ch.onclick = ((e) => {
                        this.minimize(false);
                        e.preventDefault();
                        e.stopPropagation();
                    });
                    ch.onpointerdown = ((e) => {
                        e.preventDefault();
                        e.stopPropagation();
                    });
                    ch.onpointerup = ((e) => {
                        e.preventDefault();
                        e.stopPropagation();
                    });
                    ch.style.cursor = 'pointer';
                }
            }
        }
        if (!("minimizemarker" in widg)) {
            widg.minimizemarker = addelem({
                tag: 'span',
                parent: widg,
                mywidget: widg,
                classes: ['minimizemarker'],
                innerHTML:
                    '<span class="material-symbols-outlined">comment</span>' +
                    '<span class="material-symbols-outlined">expand_less</span>',
                onclick: function () {
                    this.mywidget.commentform.minimize(false);
                }
            });
        }
        return;
    }
    // unminimize
    widg.classList.remove('minimized');
    if (widg?.mymarker?.innermarker) {
        const innermarker = widg.mymarker.innermarker;
        innermarker.onclick = (e) => (true);
        innermarker,onpointerdown = (e) => (true);
        innermarker,onpointerup = (e) => (true);
        innermarker.style.cursor = 'default';
    }
    if (this.ishtml) {
    let chch = htmld.getElementsByClassName(this.id + '-change');
        if (chch) {
            for (const ch of chch) {
                if (!(ch.classList.contains("ogstchange"))) { continue; }
                chch.onclick = ((e) => (true));
                chch.onpointerdown = ((e) => (true));
                chch.onpointerup = ((e) => (true));
                ch.style.cursor = 'default';
            }
        }
    }
}

function makeCommentForm(widg, ctype, id) {
    const commentform = addelem({
        tag: 'span',
        parent: widg,
        id: id,
        mytype: ctype,
        mywidget: widg,
        onpointerdown: function(e) {
            e.preventDefault();
            e.stopPropagation();
        },
        onpointerup: function(e) {
            e.preventDefault();
            e.stopPropagation();
        },
        onkeydown: function(e) {
            e.forcethrough = true;
        },
        classes: ['commentform', ctype]
    });
    commentform.dellabel = addelem({
        tag: 'label',
        parent: commentform,
        classes: ['del'],
        innerHTML: 'text to remove'
    });
    commentform.delinput = addelem({
        tag: 'textarea',
        classes: ['del'],
        parent: commentform
    });
    commentform.inslabel = addelem({
        tag: 'label',
        parent: commentform,
        classes: ['ins'],
        innerHTML: 'text to insert'
    });
    commentform.insinput = addelem({
        tag: 'textarea',
        classes: ['ins'],
        parent: commentform,
        mycommentform: commentform,
        oninput: function(e) {
            const commentform = this.mycommentform;
            if (commentform.makeUnsaved) {
                commentform.makeUnsaved();
            }
            // fill in insertion block
            if (commentform?.mywidget?.insertionpoint) {
                commentform.mywidget.insertionpoint.innerHTML = this.value;
            }
        }
    });
    commentform.commentlabel = addelem({
        tag: 'label',
        parent: commentform,
        classes: ['comment'],
        innerHTML: ((ctype == 'query') ? 'query' : 'comment')
    });
    commentform.commentinput = addelem({
        tag: 'textarea',
        classes: ['comment'],
        parent: commentform
    });
    if (ctype == 'query' && !window.iseditor) {
        commentform.commentinput.readOnly = true;
    }
    commentform.responselabel = addelem({
        tag: 'label',
        classes: ['response'],
        parent: commentform,
        innerHTML: 'response'
    });
    commentform.responseinput = addelem({
        tag: 'textarea',
        classes: ['response'],
        parent: commentform
    });
    for (const x of ['del','comment','response']) {
        commentform[x+'input'].oninput = () => {
            if (commentform.makeUnsaved) { commentform.makeUnsaved(); }
        }
    }
    commentform.addressedarea = addelem({
        tag: 'span',
        parent: commentform,
        classes: ['commentformaddressedarea']
    });
    commentform.addressedcb = addelem({
        tag: 'input',
        type: 'checkbox',
        id: commentform.id + 'addressed',
        mycommentform: commentform,
        parent: commentform.addressedarea,
        onchange: function() {
            const commentform = this.mycommentform;
            if (this.checked) {
                commentform.saveComment();
            } else {
                commentform.makeUnsaved();
            }
        }
    });
    commentform.addressedlabel = addelem({
        tag: 'label',
        htmlFor: commentform.id + 'addressed',
        parent: commentform.addressedarea,
        classes: ['commentformaddressedlabel'],
        innerHTML: 'has been addressed'
    });
    commentform.buttons = addelem({
        tag: 'span',
        parent: commentform,
        classes: ['commentformbuttons']
    });
    commentform.rightbuttons = addelem({
        tag: 'span',
        parent: commentform.buttons,
        classes: ['commentformrightbuttons']
    });
    commentform.leftbuttons = addelem({
        tag: 'span',
        parent: commentform.buttons,
        classes: ['commentformleftbuttons']
    });
    commentform.removebutton = addelem({
        tag: 'span',
        parent: commentform.leftbuttons,
        title: 'delete this comment',
        classes: ['commentformbutton', 'removebutton'],
        mycommentform: commentform,
        innerHTML: '<span class="material-symbols-outlined">' +
            'delete_forever</span>',
        onclick: function() {
            this.mycommentform.deleteComment();
        }
    });
    commentform.savebutton = addelem({
        tag: 'span',
        title: 'save this comment',
        parent: commentform.rightbuttons,
        mycommentform: commentform,
        classes: ['commentformbutton','savebutton'],
        innerHTML: 'save <span class="material-symbols-outlined">' +
        'save</span>',
        onclick: function() {
            if (this.classList.contains('disabled')) { return; }
            if (this.mycommentform.classList.contains('saving')) {
                return;
            }
            this.mycommentform.saveComment();
        }
    });
    commentform.savebutton.origHTML = commentform.savebutton.innerHTML;
    commentform.minimizebutton = addelem({
        tag: 'span',
        title: 'minimize',
        mycommentform: commentform,
        parent: commentform.rightbuttons,
        classes: ['commentformbutton', 'minimize'],
        innerHTML: '<span class="material-symbols-outlined">' +
            'expand_more</span>',
            onclick: function() {
            this.mycommentform.minimize(true);
        }
    });
    commentform.clearer = addelem({
        tag: 'br',
        parent: commentform.buttons
    });
    commentform.onclick = function(e) {
        e.stopPropagation();
    }
    commentform.onpointerdown = function(e) {
        e.stopPropagation();
    }
    commentform.setAttribute('contenteditable',false);
    commentform.deleteComment = deleteComment;
    commentform.saveComment = saveComment;
    commentform.makeSaved = makeSaved;
    commentform.makeUnsaved = makeUnsaved;
    commentform.makeSaving = makeSaving;
    commentform.purgeTraces = purgeTraces;
    commentform.minimize = minimize;
    commentform.eversaved = false;
    commentform.makeUnsaved();
    return commentform;
}

function makeCommentTypeSelector(parnode) {

    const commentselector = addelem({
        parent: parnode,
        tag: 'div',
        classes: ['commentselector'],
        mywidget: parnode,
        onpointerdown: function(e) {
            e.preventDefault();
            e.stopPropagation();
        },
        onpointerup: function(e) {
            e.preventDefault();
            e.stopPropagation();
        }
    });
    commentselector.setAttribute('contenteditable',false);

    const adddel = addelem({
        parent: commentselector,
        tag: 'div',
        classes: ['commenttype','del'],
        title: 'mark selection for deletion',
        innerHTML: 'deletion',
        mywidget: parnode,
        onmousedown: function (e) { e.preventDefault(); },
        onpointerdown: function (e) {
            e.preventDefault();
            e.stopPropagation();
        },
        onclick: function (e) {
            e.preventDefault();
            this.mywidget.makeType('deletion');
        }
    });

    const addins = addelem({
        parent: commentselector,
        tag: 'div',
        classes: ['commenttype','ins'],
        title: 'mark spot for insertion',
        innerHTML: 'insertion',
        mywidget: parnode,
        onmousedown: function (e) { e.preventDefault(); },
        onpointerdown: function (e) {
            e.preventDefault();
            e.stopPropagation();
        },
        onclick: function (e) { this.mywidget.makeType('insertion'); }
    });

    const addcomm = addelem({
        parent: commentselector,
        tag: 'div',
        classes: ['commenttype','comment'],
        title: 'add a comment',
        innerHTML: 'comment',
        mywidget: parnode,
        onmousedown: function (e) {
            e.preventDefault();
        },
        onpointerdown: function (e) {
            e.preventDefault();
            e.stopPropagation();
        },
        onclick: function (e) { this.mywidget.makeType('comment'); }
    });

    if (window.iseditor) {
        const addquery = addelem({
            parent: commentselector,
            tag: 'div',
            classes: ['commenttype','query'],
            title: 'add a query',
            innerHTML: 'query',
            mywidget: parnode,
            onmousedown: function (e) {
                e.preventDefault();
            },
            onpointerdown: function (e) {
                e.preventDefault();
                e.stopPropagation();
            },
            onclick: function (e) {
                this.mywidget.makeType('query');
            }
        });
    }

    const cancx = addelem({
        parent: commentselector,
        tag: 'div',
        classes: ['commentselectorcancel'],
        title: 'cancel',
        innerHTML: '<span class="material-symbols-outlined">close</span>',
        mywidget: parnode,
        onpointerdown: function(e) {
            e.preventDefault();
            e.stopPropagation();
        },
        onclick: function(e) {
            const m = this.mywidget?.mymarker;
            if (m) {
                m.mypage.isdrawing = false;
                if (m.mypage.drawingmarker) {
                    delete (m.mypage.drawingmarker);
                }
                m.parentNode.removeChild(m);
                return;
            }
            if (this.mywidget.classList.contains('commentselectorholder')) {
                this.mywidget.style.display = 'none';
            }
        }
    });

    return commentselector;
}

function makeHtmlType(ctype, id = false) {
    if (!id) {
        id = 'comment' + ((new Date()).getTime().toString());
    }
    const selection = this?.myselection;
    let deltext = '';
    let marker = {};
    if (selection) {
        if (selection.toString() != '') {
            deltext = selection.toString();
        }
        const position = selection.anchorNode.compareDocumentPosition(
                selection.focusNode);
        let anchorfirst;
        let onlyoneselected = false;
        if (position && Node.DOCUMENT_POSITION_FOLLOWING) {
            anchorfirst = true;
        } else if (position && Node.DOCUMENT_POSITION_PRECEDING) {
            anchorfirst = false;
        } else {
            onlyoneselected = true;
        }
        let firstnodeoffset = selection.anchorOffset;
        let firstnode = selection.anchorNode;
        let endnode = selection.focusNode;
        let endnodeoffset = selection.focusOffset;
        if (!anchorfirst) {
            firstnode = selection.focusNode;
            firstnodeoffset = selection.focusOffset;
            endnode = selection.anchorNode;
            endnodeoffset = selection.anchorOffset;
        }
        let ctypetagtype = 'span';
        let ctypeclasses = [ id + '-change', 'ogstchange' ];
        if (ctype == 'deletion') {
            ctypetagtype = 'del';
        }
        if (ctype == 'query') {
            ctypeclasses.push('ogstquery');
        }
        if (ctype == 'comment') {
            ctypeclasses.push('ogstcomment');
        }
        // handle other nodes
        let tt = getTextNodes(htmld.body);
        tt = tt.filter((t) => (selection.containsNode(t)));
        tt = tt.filter((t) => (t != firstnode && t != endnode));
        for (const t of tt) {
            const tTC = t.textContent;
            const repNode = addelem({
                tag: ctypetagtype,
                classes: ctypeclasses,
                innerHTML: tTC
            });
            const tparNode = t.parentNode;
            tparNode.insertBefore(repNode, t);
            tparNode.removeChild(t);
        }

        // handle first node
        const fnTC = firstnode.textContent;
        let fnPre = fnTC.substring(0, firstnodeoffset);
        let fnMid = '';
        let fnPost = fnTC.substring(firstnodeoffset);
        if (onlyoneselected) {
            const minoffset = Math.min(firstnodeoffset, endnodeoffset);
            const maxoffset = Math.max(firstnodeoffset, endnodeoffset);
            fnPre = fnTC.substring(0, minoffset);
            fnMid = fnTC.substring(minoffset,maxoffset);
            fnPost = fnTC.substring(maxoffset);
        }
        const parNode = firstnode.parentNode;
        if (fnPre != '') {
            const preNode = addelem({
                tag: 'span',
                innerHTML: fnPre,
                classes: [id + '-change']
            });
            parNode.insertBefore(preNode, firstnode);
        }
        marker = addelem({
            tag: 'span',
            classes: ['htmlcommentmarker','proofsetaddition',
                id+'-marker'],
        });
        marker.setAttribute('commenteditable',false);
        parNode.insertBefore(marker, firstnode);
        marker.commentwidget = widgify(marker, {});
        if (this.classList.contains('noselection')) {
            marker.visiblemarker = addelem({
                tag: 'span',
                classes: ['visiblemarker', ctype, id+'-visiblemarker'],
                parent: marker.innermarker
            });
        }
        const yloc = marker.offsetTop;
        if ((yloc > 0) && (yloc < 350)) {
            marker.commentwidget.classList.add("underneath");
        }
        const xloc = marker.offsetLeft;
        const sw = htmlw.innerWidth;
        if (((xloc+375) > sw) && (xloc > (sw/2))) {
            marker.commentwidget.classList.add("pushleft");
        }
        marker.commentwidget.classList.remove('selecting');
        marker.commentwidget.makeType = function() {};
        if (fnMid != '') {
            const midNode = addelem({
                tag: ctypetagtype,
                classes: ctypeclasses,
                innerHTML: fnMid,
            });
            parNode.insertBefore(midNode, firstnode);
        }
        if (firstnode == endnode) {
            marker.commentwidget.insertionpoint = addelem({
                tag: 'ins',
                id: id+'-insertionpoint',
                classes: ctypeclasses
            });
            parNode.insertBefore(marker.commentwidget.insertionpoint,
                firstnode);
        }
        if (fnPost != '') {
            let posttagt = 'span';
            let postclasses = [id + '-change'];
            if (firstnode != endnode) {
                posttagt = ctypetagtype;
                postclasses = ctypeclasses;
            }
            const postNode = addelem({
                tag: posttagt,
                classes: postclasses,
                innerHTML: fnPost
            });
            parNode.insertBefore(postNode, firstnode);
        }
        parNode.removeChild(firstnode);
        // handle last node
        if (firstnode != endnode) {
            const enTC = endnode.textContent;
            const enPre = enTC.substring(0, endnodeoffset);
            const enPost = enTC.substring(endnodeoffset);
            const eparNode = endnode.parentNode;
            if (enPre != '') {
                const epreNode = addelem({
                    tag: ctypetagtype,
                    innerHTML: enPre,
                    classes: ctypeclasses
                });
                eparNode.insertBefore(epreNode, endnode);
            }
            marker.commentwidget.insertionpoint = addelem({
                tag: 'ins',
                id: id+'-insertionpoint',
                classes: ctypeclasses
            });
            eparNode.insertBefore(marker.commentwidget.insertionpoint,
                endnode);
            if (enPost != '') {
                const epostNode = addelem({
                    classes: [id +'-change'],
                    tag: 'span',
                    innerHTML: enPost
                });
                eparNode.insertBefore(epostNode, endnode);
            }
            eparNode.removeChild(endnode);
        }
        selection.collapse(null);
        if (htmlw.commentselectorholder) {
            htmlw.commentselectorholder.style.display = 'none'
        }
        marker.classList.add(ctype);
        if (marker?.commentwidget) {
            marker.commentwidget.classList.add(ctype);
            marker.commentwidget.commentform = makeCommentForm(
                marker.commentwidget, ctype, id);
            if (ctype == 'deletion' && deltext != '') {
                const delinput = marker.commentwidget.commentform.delinput;
                delinput.value = deltext;
                delinput.readOnly = true;
            }
            marker.commentwidget.commentform.ishtml = true;
        }
    } else {
        marker = this;
        marker.setAttribute("commenteditable", false);
        marker.commentwidget = widgify(marker, {});
        if (this.hasvm) {
            marker.visiblemarker = addelem({
                tag: 'span',
                classes: ['visiblemarker', ctype, id + '-visiblemarker'],
                parent: marker.innermarker
            });
        }
        const yloc = marker.offsetTop;
        if ((yloc > 0) && (yloc < 350)) {
            marker.commentwidget.classList.add("underneath");
        }
        const xloc = marker.offsetLeft;
        const sw = htmlw.innerWidth;
        if ((xloc+375) > sw && (xloc > (sw/2))) {
            marker.commentwidget.classList.add("pushleft");
        }
        marker.commentwidget.classList.remove('selecting');
        marker.commentwidget.makeType = function() {};
        marker.classList.add(ctype);
        if (marker?.commentwidget) {
            marker.commentwidget.classList.add(ctype);
            marker.commentwidget.commentform = makeCommentForm(
                marker.commentwidget, ctype, id);
            marker.commentwidget.commentform.ishtml = true;
        }
    }
}

function makePdfType(ctype, id = false) {
    const alltypes = ['drawing','deletion','query','comment','insertion'];
    for (const thistype of alltypes) {
        if (this?.mymarker) {
            this.mymarker.classList.remove(thistype);
        }
        this.classList.remove(thistype);
    }
    if (this?.mymarker) {
        this.mymarker.classList.add(ctype);
        // detach it as the drawing marker so it is not overwritten
        if (this.mymarker?.mypage?.drawingmarker) {
            delete(this.mymarker.mypage.drawingmarker);
        }
    }
    this.classList.remove('selecting');
    this.classList.add(ctype);
    if (this?.myselector) {
        this.myselector.parentNode.removeChild(this.myselector);
        delete(this.myselector);
    }
    if (!id) {
        id = 'comment' + ((new Date()).getTime().toString());
    }
    this.commentform = makeCommentForm(this, ctype, id);
}

// Functions for drawing boxes

function updatePosition(pp) {
    if (!this?.anchorPP) { return; }
    if (!pp?.x || !pp?.y) { return; }
    const anchorx = this.anchorPP.x;
    const anchory = this.anchorPP.y;
    const minx = Math.min(anchorx, pp.x);
    const maxx = Math.max(anchorx, pp.x);
    const miny = Math.min(anchory, pp.y);
    const maxy = Math.max(anchory, pp.y);
    this.style.left = minx.toString() + '%';
    this.style.right = (100 - maxx).toString() + '%';
    this.style.top = miny.toString() + '%';
    this.style.bottom = (100 - maxy).toString() + '%';
    this.wanderPP = pp;
}

function pointerPerc(elem, evnt) {
    var bcr = elem.getBoundingClientRect();
    var w = bcr.right - bcr.left;
    var h = bcr.bottom - bcr.top;
    var rx = evnt.clientX - bcr.left;
    var ry = evnt.clientY - bcr.top;
    var xp = (rx/w) * 100;
    var yp = (ry/h) * 100;
    return { x: xp, y: yp };
}

function createPdfCommentMarker(elem) {
    const marker = addelem({
        tag: 'div',
        classes: ['pdfcommentmarker'],
        mypage: elem,
        parent: elem
    });
    marker.style.position = 'absolute';
    marker.style.display = 'inline-block';
    w.nummarkers = w.nummarkers + 1;
    marker.updatePosition = updatePosition;
    return marker;
}

function startdraw(elem, evnt) {
    if (elem.isdrawing) { return; }
    elem.isdrawing = true;
    if (elem.drawingmarker) {
        canceldraw(elem, evnt);
    }
    elem.drawingmarker = createPdfCommentMarker(elem);
    const marker = elem.drawingmarker;
    marker.classList.add('drawing');
    marker.anchorPP = pointerPerc(elem, evnt);
    marker.updatePosition(marker.anchorPP);
    elem.isdrawing = true;
}

function continuedraw(elem, evnt) {
    if (!elem.isdrawing) { return; }
    const newPP = pointerPerc(elem, evnt);
    elem.drawingmarker.updatePosition(newPP);
}

function canceldraw(elem, evnt) {
    if (!elem.isdrawing) { return; }
    const marker = elem.drawingmarker;
    marker.parentNode.removeChild(marker);
    delete(elem.drawingmarker);
    elem.isdrawing = false;
}

function widgify(marker, elem) {
    marker.innermarker = addelem({
        parent: marker,
        classes: ['innermarker'],
        tag: 'span',
        onpointerdown: function(e) {
            e.stopPropagation();
        },
    });
    const commentwidget = addelem({
        parent: marker.innermarker,
        mymarker: marker,
        tag: 'span',
        classes: ['commentwidget','selecting'],
        onpointerdown: function(e) {
            e.stopPropagation();
        },
        makeType: makePdfType
    });
    const anchorPP = marker?.anchorPP ?? false;
    const newPP = marker?.wanderPP ?? false;
    if (anchorPP && newPP) {
        if (elem?.id == 'page1' & (anchorPP.y < 25 || newPP.y < 25)) {
            commentwidget.classList.add('underneath');
        }
        if (anchorPP.x > 80 || newPP.x > 80) {
            commentwidget.classList.add('pushleft');
        }
    }
    return commentwidget;
}

function enddraw(elem, evnt) {
    if (!elem.isdrawing) { return; }
    const newPP = pointerPerc(elem, evnt);
    const marker = elem.drawingmarker
    const anchorPP = marker.anchorPP;
    if (newPP.x == anchorPP.x || newPP.y == anchorPP.y) {
        canceldraw(elem, evnt);
        return;
    }
    marker.updatePosition(newPP);
    elem.isdrawing = false;
    marker.commentwidget = widgify(marker, elem);
    const commentwidget = marker.commentwidget;
    if (!w.iseditor) {
        commentwidget.myselector = makeCommentTypeSelector(commentwidget);
    } else {
        commentwidget.makeType('query');
    }
}

// Function for submitting changes
async function submitToEditors() {
    submitbutton.classList.remove('lookatme');
    submitbutton.classList.add('submitting');
    const req = {
        requesttype: 'submit'
    }
    submitbutton.innerHTML = '<span class="material-symbols-outlined ' +
        'rotating">sync</span> submitting …'
    const resp = await jsonrequest(req);
    submitbutton.classList.remove('submitting');
    submitbutton.innerHTML = 'submit';
    if (resp) {
        w.anychangesmade = false;
        submitbutton.updateMe();
    }
    if (!resp) {
        submitbutton.classList.add('lookatme');
        return;
    }
    okmessage('Thank you for your comments and corrections. They have ' +
        'been submitted to the editors. You may close this window now. ' +
        'If you need to make any additional changes, you may visit this ' +
        'page with the same URL, add more comments, and resubmit.');
}

//
// HTML functions
//

function getTextNodes(node) {
    let rv = [];
    for (node=node.firstChild; node; node=node.nextSibling) {
        if (node.nodeType == 3) {
            rv.push(node);
        } else {
            rv = rv.concat(getTextNodes(node));
        }
    }
    return rv;
}

function htmlSelectionChange(e) {
    const selection = htmlw.getSelection();
    // don't let it work outside of a text block
    if (selection.anchorNode?.tagName) { return; }
    if (!htmlw.commentselectorholder) {
        htmlw.commentselectorholder = addelem({
            tag: 'div',
            classes: ['commentselectorholder','proofsetaddition'],
            parent: htmld.body
        });
        htmlw.commentselectorholder.setAttribute('contenteditable',false);
    }
    if (!htmlw.commentselector) {
        htmlw.commentselector = makeCommentTypeSelector(
            htmlw.commentselectorholder
        );
    }
    htmlw.commentselectorholder.style.display = 'inline-block';
    htmlw.commentselectorholder.style.left =
        (e.layerX - 20).toString() + 'px';
    htmlw.commentselectorholder.style.top =
        (e.layerY - 65).toString() + 'px';
    if (selection.isCollapsed) {
        htmlw.commentselectorholder.classList.add("noselection");
    } else {
        htmlw.commentselectorholder.classList.remove("noselection");
    }
    htmlw.commentselectorholder.myselection = selection;
    htmlw.commentselectorholder.makeType = makeHtmlType;
}

//
// FILL IN THE PANEL
//

// we put what's on the right first to keep it up top
const rightbuttons = addelem({
    parent: toppanel,
    tag: 'div',
    id: 'rightbuttons'
});

const dllabel =addelem({
    parent: rightbuttons,
    tag: 'div',
    innerHTML: 'download:'
});

const exticons = {
    "md": "draft",
    "epub": "install_mobile",
    "html": "public",
    "pdf": "picture_as_pdf",
    "zip": "folder_zip"
}

for (const file of w.downloads) {
    const ext = file.split('.').reverse()[0];
    let icon = 'download';
    if (ext in exticons) {
        icon = exticons[ext];
    }
    const dlbtn = addelem({
        tag: 'div',
        parent: rightbuttons,
        title: 'download ' + ext + ' file',
        myext: ext,
        myfilename: file,
        classes: ['downloadbutton'],
        innerHTML: '<span class="material-symbols-outlined">' + icon +
            '</span>',
        onclick: function() {
            downloadFile('proofservelet.php?download=true&ext=' +
                encodeURIComponent(this.myext) + '&key=' +
                encodeURIComponent(window.accesskey),
                this.myfilename);
        }
    });
}

const submitbutton = addelem({
    parent: rightbuttons,
    tag: 'div',
    innerHTML: 'submit',
    tooltip : 'submit changes to editor',
    title: ((w.iseditor) ? '(button for author use only)' :
        '(no changes to submit)'),
    id: 'submitbutton',
    classes: ['disabled'],
    updateMe: function() {
        // never change it in editor mode
        if (w.iseditor) { return; }
        let readytosubmit = w.anychangesmade;
        if (readytosubmit) {
            clearError();
            const uu = document.getElementsByClassName("unsaved");
            if (uu.length > 0) {
                readytosubmit = false
            }
            const huhu = htmld.getElementsByClassName("unsaved");
            if (huhu.length > 0) {
                readytosubmit = false;
            }
        }
        if (readytosubmit) {
            this.classList.remove('disabled');
            this.classList.add('lookatme');
            this.title = this.tooltip;
        } else {
            this.classList.add('disabled');
            this.classList.remove('lookatme');
            if (w.anychangesmade) {
                this.title = 'please save any open comments before ' +
                    'submititng';
            } else {
                this.title = '(no changes to submit)'
            }
        }
    },
    onclick: function() {
        if (this.classList.contains('disabled')) { return; }
        if (this.classList.contains('submitting')) { return; }
        submitToEditors();
    }
});

// view selection choices
const viewselector = addelem({
    parent: toppanel,
    tag: 'div',
    id: 'viewselector'
});

const viewlabel = addelem({
    parent: viewselector,
    tag: 'div',
    innerHTML: 'view:'
});

const instructionselect = addelem({
    parent: viewselector,
    tag: 'div',
    innerHTML: 'instructions',
    title: 'view instructions',
    classes: ['viewoption','instructions'],
    onclick: function(e) {
        e.preventDefault();
        changeMode('instructions');
    }
});

if (usehtml) {
    const htmlselect = addelem({
        parent: viewselector,
        tag: 'div',
        innerHTML: 'html proofs',
        title: 'view html proofs',
        classes: ['viewoption','html'],
        onclick: function(e) {
            e.preventDefault();
            changeMode('html');
        }
    });
}

if (pdfpp > 0) {
    const pdfselect = addelem({
        parent: viewselector,
        tag: 'div',
        innerHTML: 'pdf proofs',
        title: 'view pdf proofs',
        classes: ['viewoption','pdf'],
        onclick: function(e) {
            e.preventDefault();
            changeMode('pdf');
        }
    });
}

if (pdfpp > 0) {
    const pdfbuttons = addelem({
        parent: toppanel,
        tag: 'div',
        classes: ['pdfbuttons','pdfonly']
    });
    const zoomout = addelem({
        parent: pdfbuttons,
        innerHTML: '<span class="material-symbols-outlined">zoom_out</span>',
        classes: ['pdfbutton'],
        tag: 'div',
        onclick: function() { zoomInOut(false); }
    });
    const fit = addelem({
        parent: pdfbuttons,
        innerHTML: '<span class="material-symbols-outlined">fit_width</span>',
        classes: ['pdfbutton'],
        tag: 'div',
        onclick: function() { changeZoom('fitwidth'); }
    });
    const zoomin = addelem({
        parent: pdfbuttons,
        innerHTML: '<span class="material-symbols-outlined">zoom_in</span>',
        classes: ['pdfbutton'],
        tag: 'div',
        onclick: function() { zoomInOut(true); }
    });
    const pagejump = addelem({
        parent: pdfbuttons,
        tag: 'input',
        type: 'number',
        min: 1,
        max: pdfpp,
        placeholder: 'goto page',
        id: 'pagejump',
        onchange: function() {
            const id = 'page' + this.value;
            const page = document.getElementById(id);
            if (page) {
                page.scrollIntoView({ block: 'nearest' });
            }
            // clear old value
            this.value = '';
        }
    });
    const oftotal = addelem({
        parent: pdfbuttons,
        tag: 'div',
        innerHTML: ' / ' + pdfpp.toString()
    });
}

//
// SET UP PDF LISTENERS
//

window.pdfpages.addEventListener('keydown', function(e) {
    const w = this.clientWidth;
    const h = this.clientHeight;
    const pageamount = (h/window.pdfpp)*0.6;
    const hamount = h/10;
    const wamount = w/10;
    //pageup, pagedown changes pages
    if (e.key == 'PageDown') {
        e.preventDefault();
        this.scrollTop = this.scrollTop + pageamount;
        return;
    }
    if (e.key == 'PageUp') {
        e.preventDefault();
        this.scrollTop = this.scrollTop - pageamount;
        return;
    }
    // arrow up, down, etc. scrolls
    if (e.key == 'ArrowUp') {
        e.preventDefault();
        this.scrollTop = this.scrollTop - hamount;
        return;
    }
    if (e.key == 'ArrowDown') {
        e.preventDefault();
        this.scrollTop = this.scrollTop + hamount;
        return;
    }
    if (e.key == 'ArrowRight') {
        e.preventDefault();
        this.scrollLeft = this.scrollLeft + wamount;
        return;
    }
    if (e.key == 'ArrowLeft') {
        e.preventDefault();
        this.scrollLeft = this.scrollLeft - wamount;
        return;
    }
});

// listener for creating pdf boxes
if (w.pdfpp > 0) {
    for (const page of pdfpages.getElementsByClassName("pdfpage")) {
        page.isdrawing = false;
        page.onpointerdown = function(e) {
            if (!this.isdrawing) {
                startdraw(this, e);
            }
        }
        page.onpointermove = function(e) {
            if (this.isdrawing) {
                continuedraw(this, e);
            }
        }
        page.onpointerup = function(e) {
            if (this.isdrawing) {
                enddraw(this, e);
            }
        }
        page.onpointercancel = function(e) {
            if (this.isdrawing) {
                canceldraw(this, e);
            }
        }
        page.onpointerleave = function(e) {
            if (this.isdrawing) {
                canceldraw(this, e);
            }
        }
    }
}

// restore pdf comments
if (("savedcomments" in w) && ("pdf" in w.savedcomments)) {
    for (const commentid in w.savedcomments.pdf) {
        const commentinfo = w.savedcomments.pdf[commentid];
        if (!commentinfo?.page) { continue; }
        const page = document.getElementById(commentinfo.page);
        if (!page) { continue; }
        const marker = createPdfCommentMarker(page);
        marker.anchorPP = commentinfo.anchorPP ?? { x: 0, y: 0 };
        marker.updatePosition(commentinfo.wanderPP ?? { x: 10, y: 10 });
        marker.commentwidget = widgify(marker, page);
        if (!commentinfo?.commenttype) { continue; }
        marker.commentwidget.makeType(commentinfo.commenttype, commentid);
        if (!marker.commentwidget.commentform) { continue; }
        const commentform = marker.commentwidget.commentform;
        // restore input fields
        for (const x of ['comment','ins','del','response']) {
            if (x in commentinfo) {
                commentform[x + 'input'].value = commentinfo[x];
            }
        }
        // restore check box
        commentform.addressedcb.checked = (("hasbeenaddressed" in commentinfo)
            && (commentinfo.hasbeenaddressed));
        commentform.makeSaved();
        // even saved queries should start open for non-editors
        if (commentinfo?.commenttype == 'query' &&
            commentform.responseinput.value == '' &&
            (!window.iseditor)) {
            commentform.minimize(false);
        }
    }
}

// set pdf zoom level
if (document.body.clientWidth < 1200) {
   changeZoom('fitwidth');
}

//
// SET UP HTML PROOFS
//

window.htmlw = {}; window.htmld = {};

function setUpHtml() {
    if (htmlproofs.contentWindow) {
        window.htmlw = htmlproofs.contentWindow;
    }

    if (htmlproofs.contentDocument) {
        window.htmld = htmlproofs.contentDocument;
    }
    if (!htmld.body) { return; }

    // remove any old commentselector
    const hh = htmld.getElementsByClassName("commentselectorholder");
    if (hh) { for (const h of hh) { h.parentNode.removeChild(h); } };

    // fix old comments
    if (("savedcomments" in w) && ("html" in w.savedcomments)) {
        for (const commentid in w.savedcomments.html) {
            const commentinfo = w.savedcomments.html[commentid];
            const markers = htmld.getElementsByClassName(commentid + "-marker");
            if (!markers || markers.length == 0) { continue; }
            const marker = markers[0];
            const vv = marker.getElementsByClassName("visiblemarker");
            const hasvm = (vv && vv.length> 0);
            // clear it out
            marker.innerHTML = '';
            marker.hasvm = hasvm;
            marker.makeType = makeHtmlType;
            marker.makeType(commentinfo.commenttype, commentid);
            if (!marker.commentwidget.commentform) { continue; }
            const commentform = marker.commentwidget.commentform;
            // restore values
            for (const x of ['comment', 'ins', 'del', 'response']) {
                if (x in commentinfo) {
                    commentform[x + 'input'].value = commentinfo[x];
                }
            }
            // make deletion deletioninput readOnly
            if (commentinfo.commenttype == 'deletion') {
                commentform.delinput.readyOnly = true;
            }
            // restore check box
            commentform.addressedcb.checked = (("hasbeenaddressed" in
                commentinfo) && (commentinfo.hasbeenaddressed));
            // restore insertion point
            const inspt = htmld.getElementById(commentid + '-insertionpoint');
            if (inspt) {
                marker.commentwidget.insertionpoint = inspt;
            }
            commentform.makeSaved();
            if (commentinfo?.commenttype == 'query' &&
                commentform.responseinput.value == '' &&
                (!window.iseditor)) {
                commentform.minimize(false);
            }
        }
    }

    // make editable
    htmld.body.setAttribute('contenteditable',true);
    htmld.body.setAttribute('spellcheck',false);
    // prevent actual editing?
    htmld.body.addEventListener('keydown', (e) => {
        if (e.forcethrough) { return true; }
        if (
            ((!e.metaKey && !e.ctrlKey && !e.altKey) && (e.key.length == 1)) ||
            (e.key == 'Backspace' || e.key == 'Delete') ||
            (e.ctrlKey && (e.key == 'x' || e.key == 'X' || e.key == 'v' || e.key == 'V')) ||
            (e.shiftKey && e.key == 'Insert') ||
            (e.key == 'Enter') ||
            (e.keyCode > 128)
        ) {
            e.preventDefault();
        }
    });

    htmld.body.addEventListener('paste', (e) => {
        e.preventDefault();
    });

    htmld.body.addEventListener('cut', (e) => {
        e.preventDefault();
    });

    // context menus sometimes have their own way of deleting
    htmld.body.addEventListener('contextmenu', (e) => {
        e.preventDefault();
        return false;
    });

    // add listener for selection
    htmld.onpointerup = htmlSelectionChange;

    // apply editormode to html body
    if (document.body.classList.contains('editormode')) {
        htmld.body.classList.add('editormode');
    }

    // load CSS
    addelem({
        tag: 'link',
        rel: 'stylesheet',
        type: 'text/css',
        href: 'shared.css',
        parent: htmld.head
    });

}

setUpHtml();
htmlproofs.onload = setUpHtml;

// show one of the three main body elements
if ("starton" in w) {
    changeMode(w.starton);
} else {
    if (iseditor) {
        if (w.usehtml) {
            changeMode('html');
        } else {
            changeMode('pdf');
        }
    } else {
        changeMode('instructions');
    }
}

</script>

</head>
<body<?php if ($iseditor) { echo ' class="editormode"'; } ?>>
    <header>
        <div id="projecthdr">
            <div id="tsf">
                typesetting framework
            </div>
            <div id="projectname"><?php echo $title; ?></div>
        </div>
        <div id="toppanel">
        </div>
    </header>
    <main>
        <div id="commoncontainer">
            <div id="instructionsholder">
                <div id="instructions">
                    <div id="errormessage"></div>
                    <div id="instructionscore">
                        <h1>Instructions</h1>
<?php if ($iseditor) { ?>
<h2>Editor instructions</h2>
<p>
Usage of the proofs pages for editors is mostly the same as for authors (see below).
The main difference is that while visiting a proofs page using the editor link, all comments left will automatically be of the (green) “query” type.
The presumption is that the editor will add any queries they have about the document before sending the author link for the proofs to the author(s).
When the authors visit the proofs page, these comments will be open by default and the authors can fill in their responses to the queries.
The editor should revisit the page after the authors submit their comments and corrections (which the editor should be notified about via email).
Editors can click the “has been addressed” checkbox for each correction/comment to mark it as dealt with.
</p>

<p>
The submit button is also disabled in editor mode, as editors do not need to submit their comments to themselves.
</p>

<p>
If, for whatever reason, an editor wishes to add a different kind of comment/correction, e.g., an insertion or deletion, they should use the author link instead. Both should be listed in the document’s “proofs” listing one the typesetting framework current project page.
</p>
<?php } ?>
<?php if ($usehtml) { ?>
<h2>HTML page proofs</h2>
<p>
When you are ready to view the html proofs, click the “html proofs” button on the panel above.
Please read over the proofs carefully.
</p>

<p>
There are three kinds of comments or corrections authors can leave on proofs: (a) deletions (pink), which indicate certain text should be deleted, with the optional possibility of replacement text to be inserted, (b) insertions (blue), which indicate that text should be inserted (only), and (c) comments (yellow), which can be used for any other kind of comment or question.
</p>

<p>
To add a comment or correction, either select the relevant part of the document with the mouse, or, if inserting text without removing anything, click the point where text should be inserted.
A small box should pop up when you release the mouse button (or lift your finger on a touch screen) giving you an option of what kind of comment/correction to leave.
(Deletions require some text be selected; insertions without deletions require the opposite.
Comments can be left in either kind of case.)
Once the type is chosen, a small form should pop up wherein you can specify your requested changes or comments.
When this is filled out, click the “save” button on the lower right of the form.
</p>

<div><img src="images/deletion.gif" alt="[animation of deletion correction]"></div>

<p>
Once saved, the comment form will minimize, but can be brought back up by clicking the small comment icon next to the marker for the comment.
It can be re-minimized by clicking the small button to the right of the “(saved)” indicator.
A comment can be completely removed by clicking the trash can icon in the lower left of the form.
</p>

<p>
You may see green comment markers and forms already open when you examine the proofs.
These are queries about the document left by the editor(s).
You can fill in the “response” field, and then save the comment to answer their query.
</p>

<div><img src="images/editorcomm.gif" alt="[animation of editor query]"></div>

<p>
Once you have reviewed the entire document, and saved all your comments and corrections, you can <?php if ($pdfpages > 0) { ?>move on to the pdf proofs, or <?php } ?>submit your comments to the editor(s).
</p>
<?php } ?>
<?php if ($pdfpages > 0) { ?>
<h2>PDF page proofs</h2>
<?php if ($usehtml) { ?>
<p>
You do not need to leave duplicate comments on both the html and pdf proofs.
Generally, comments regarding changes to the text should be left on the html proofs.
Comments specific to the pdf, e.g., those involving page breaks or running headers, etc., can be left on the pdf proofs.
</p>
<?php } ?>
<p>
To view the pdf proofs, click the “pdf proofs” button on the panel above.
</p>

<p>
The proofs consist of a series of images converted from the pages of the pdf.
To see the actual pdf itself, you can download it using the pdf icon in the upper right.
However, comments and corrections to the pdf should be indicated here on this page.
</p>

<p>
To leave a comment or correction on a pdf page, click (or touch) part of the page, and while the mouse button is down (or your finger is touching the touch screen), draw a box by moving the mouse (or your finger), and then release.
This should trigger a pop-up menu, on which you can choose what kind of comment or correction to leave.
<?php if ($usehtml) { ?>As with html proofs, t<?php } else { ?>T<?php } ?>here are three types you can choose from: (a) deletions (pink), indicating that text should be deleted, with the possibility of suggesting replacement text, (b) insertions (blue), indicating that text should be inserted (only), and (c) general comments (yellow), which may contain any other kind of comment or question.
</p>

<p>
Once you choose the type, a small dialogue window should appear allowing you to fill in the details of your comment or correction.
When done, click the “save” button in the lower right of the pop-up.
</p>

<div><img src="images/pdfcomment.gif" alt="[animation of pdf comment]"></div>

<p>
The comment will minimize once saved, but you can unminimize it by clicking again on the marker.
You can re-minimize it by clicking on the small icon next to the “(saved)” indicator.
You can also wholly remove a comment by clicking on the red trash can icon in the lower left of the pop-up form.
</p>

<p>
You may also see green comment boxes.
These are queries left by the editor(s) about the document.
You can fill in the “response” field for each query and save the query.
</p>

<p>
When you are done adding comments and corrections, and all of them have been saved, you may submit your comments and corrections.
</p>
<?php } ?>
<h2>Submitting your comments and corrections</h2>
<p>
When comments and corrections have been made, and all of them have been saved, you can click the flashing “submit” button in the upper right of the screen.
(If it is not enabled, most likely there is a comment that has not yet been saved.
Make sure they are all saved<?php if (($usehtml) && ($pdfpages > 0)) { ?> on both the pdf and html proofs<?php } ?> before submitting.)
When changes and comments are submitted, the editor who created the proofs is notified by email.
</p>

<p>
You can make further changes by revisiting the page later on with the same url. You can also wait until a subsequent visit to submit your comments to the editors if you are not ready yet to submit. Simply make sure that any comments/corrections you wish to preserve are saved before doing so.
</p>

<h2>Downloads</h2>
<p>
You can download (clean) files of the proofs by clicking the small icons in the upper right part of the screen.
These may include other file formats that cannot be previewed in this browser window.
</p>

<h2>Problems or questions</h2>
<p>
If you have trouble with the site, please contact either the editor who emailed you the link, or the project site administrator<?php if (isset($project_settings->contactname)) { echo ', ' . $project_settings->contactname; } if (isset($project_settings->contactemail)) { echo ' ⟨<a href="mailto:' . $project_settings->contactemail . '" target="_blank">' . $project_settings->contactemail . '</a>⟩'; } ?>.
</p>
                    </div>
                </div>
            </div>
            <div id="htmlholder">
                <iframe id="htmlproofs" <?php if ($usehtml) {
                        echo 'src="proofservelet.php?key=' .
                        rawurlencode($key) . '&html=true"';
                    }; ?>>
                </iframe>
            </div>
            <div id="pdfholder">
                <div id="pdfproofs">
                    <div id="pdfparent">
                        <div id="pdfpages">
<?php
if ($pdfpages != 0) {
    for ($i=1; $i<=$pdfpages; $i++) {
        echo '<div class="pdfpage" id="page' . strval($i) .
            '"><img src="proofservelet.php?key=' .
            rawurlencode($key) . '&pdfpage=' . strval($i) .
            '" alt="pdf page ' . strval($i) .
            '" draggable="false"></div>' . PHP_EOL;
    }
}
?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
    <footer>
        <p><small>The Open Guide Typesetting Framework is Copyright
        2023 © <a href="https://people.umass.edu/klement"
        target="_blank">Kevin C. Klement</a>. This is free software,
        which can be redistributed and/or modified under the terms of the
        <a href="https://www.gnu.org/licenses/gpl.html" target="_blank">
        GNU General Public License (GPL), version 3</a>. See the <a
        href="https://github.com/frabjous/open-guide-typesetting-framework"
        target="_blank">project github page</a> for more information.
        </small></p>
    </footer>
</body>
</html>
