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
<title><?php echo $title; ?> Page Proofs</title>

<!-- simple css framework -->
<!--link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/@picocss/pico@1/css/pico.min.css" -->

<style>
@import url('https://fonts.googleapis.com/css2?family=Nunito+Sans:ital,wght@0,400;0,700;1,400;1,700&display=swap');
@import url('https://fonts.googleapis.com/css?family=Material+Symbols+Outlined');
:root {
    --font-family: 'Nunito Sans', 'Roboto', 'Noto Sans', 'TeX Gyre Heros', 'Arimo', 'Helvetica', 'Arial', sans-serif;
    --primary: hsl(195, 90%, 32%);
    --primary-hover: hsl(195, 90%, 42%);
    --panelbg: rgba(89, 107, 120, 0.125);
    --inactive: hsl(205, 10%, 50%);
    --fg: hsl(205, 30%, 15%);
    --bg: hsl(205, 20%, 94%);
    --red: #c62828;
    --purple: rgb(148,0,255,0.3);
    --green: rgb(87,180,71,0.3);
    --pink: rgba(255,20,189,0.2);
    --bluey: rgb(10,132,255,0.3);
    --yellow: rgb(255,255,81,0.3);
}

@keyframes showoff {
    from {background-color: var(--yellow);}
    to {background-color: var(--bluey);}
}

body {
    font-family: var(--font-family);
    font-size: 18px;
    background-color: var(--bg);
    color: var(--fg);
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
    background-color: var(--panelbg);
    border-bottom: 1px ridge var(--primary);
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
    border-top: 1px ridge var(--primary);
    background-color: var(--panelbg);
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
    color: var(--primary);
}
a:hover, a:link:hover, a:visited:hover {
    color: var(--primary-hover);
    text-decoration: underline;
}
#projecthdr #projectname {
    color: var(--primary);
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
#pdfholder,
#htmlholder {
    height: 100%;
    width: 100%;
    max-width: 100%;
    max-height: 100%;
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
    border: 2px solid var(--inactive);
    color: var(--inactive);
    border-radius: 1.5rem;
    margin-left: 0.5rem;
    cursor: pointer;
}

#toppanel #rightbuttons #submitbutton.lookatme {
    border: 2px solid var(--primary);
    color: var(--fg);
    animation-name: showoff;
    animation-duration: 0.7s;
    animation-direction: alternate;
    animation-iteration-count: infinite;
}

#toppanel #rightbuttons #submitbutton:hover,
#toppanel div.viewoption:hover {
    background-color: var(--bg);
    color: var(--primary);
}

body.instructions #toppanel div.viewoption.instructions,
body.html #toppanel div.viewoption.html,
body.pdf #toppanel div.viewoption.pdf {
    color: var(--fg);
    border: 2px solid var(--primary-hover);
    background-color: var(--bg);
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

.commentselector {
    white-space: nowrap;
}

.commentselector div {
    display: inline-block;
}

.commentselector .commenttype {
    padding: 0.2rem;
    border: 1px solid var(--inactive);
    cursor: pointer;
    border-radius: 0.2rem;
    margin: 0.2rem;
}

.commentselector .commenttype.del {
    text-decoration: line-through;
    text-decoration-color: var(--red);
    background-color: var(--pink);
}

.commentselector .commenttype.ins {
    background-color: var(--bluey);
}

.commentselector .commenttype.comment {
    background-color: var(--yellow);
}

.commentselector .commenttype.query {
    background-color: var(--green);
}

.commentselector .commentselectorcancel {
    cursor: pointer;
    color: var(--red);
    position: relative;
    top: 0.4rem;
}

.commentselector .commenttype:hover,
.commentselector .commenttype.del:hover,
.commentselector .commenttype.ins:hover,
.commentselector .commenttype.comment:hover {
    background-color: var(--bg);
    border: 1px solid var(--primary-hover);
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
    color: var(--primary);
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
    color: var(--primary-hover);
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
    background-color: var(--purple);
}

body.editormode .pdfcommentmarker.drawing {
    background-color: var(--green);
}

.pdfcommentmarker.deletion {
    background-color: var(--pink);
}

.pdfcommentmarker.insertion {
    background-color: var(--bluey);
}

.pdfcommentmarker.comment {
    background-color: var(--yellow);
}

.pdfcommentmarker.query {
    background-color: var(--green);
}

div.innermarker {
    position: relative;
    width: 100%;
    height: 100%;
}

div.commentwidget {
    position: absolute;
    background-color: var(--bg);
    bottom: 100%;
    left: -1rem;
}

div.commentwidget.underneath {
    background-color: var(--bg);
    top: 100%;
    bottom: auto;
}

div.commentwidget.selecting {
    padding: 0.5rem 2rem 0.5rem 2rem;
    border-radius: 2rem;
}

div.commentwidget.deletion,
div.commentwidget.insertion,
div.commentwidget.comment,
div.commentwidget.query {
    border-radius: 1rem;
}

div.commentform {
    width: 18rem;
    padding: 1rem 1.5rem 1rem 1rem;
    border-radius: 1rem;
}

div.commentform label {
    display: block;
}

div.commentform textarea {
    resize: none;
    width: 100%;
    height: 4rem;
    font-family: var(--font-family);
}

div.commentform.query {
    background-color: var(--green);
}

div.commentform.comment {
    background-color: var(--yellow);
}

div.commentform.insertion {
    background-color: var(--bluey);
}

div.commentform.deletion {
    background-color: var(--pink);
}

div.commentform.query .ins,
div.commentform.query .del,
div.commentform.comment .del,
div.commentform.comment .response,
div.commentform.comment .ins,
div.commentform.deletion .response,
div.commentform.insertion .del,
div.commentform.insertion .response {
    display: none;
}

div.commentform div.commentformbuttons div.commentformbutton {
    color: var(--primary);
    cursor: pointer;
}

div.commentform div.commentformbuttons div.commentformbutton.removebutton {
    color: var(--red);
}

</style>

<script type="module">

import downloadFile from '../open-guide-editor/open-guide-misc/download.mjs';

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
w.nummarkers = 0;
for (const id of [
    'toppanel',
    'instructionsholder',
    'instructions',
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

function makeCommentForm(widg, ctype) {
    const commentform = addelem({
        tag: 'div',
        parent: widg,
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
        parent: commentform
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
    commentform.buttons = addelem({
        tag: 'div',
        parent: commentform,
        classes: ['commentformbuttons']
    });
    commentform.rightbuttons = addelem({
        tag: 'div',
        parent: commentform.buttons,
        classes: ['commentformrightbuttons']
    });
    commentform.leftbuttons = addelem({
        tag: 'div',
        parent: commentform.buttons,
        classes: ['commentformrightbuttons']
    });
    commentform.removebutton = addelem({
        tag: 'div',
        parent: commentform.leftbuttons,
        title: 'delete this comment',
        classes: ['commentformbutton', 'removebutton'],
        innerHTML: '<span class="material-symbols-outlined">' +
            'delete_forever</span>'
    });
    commentform.savebutton = addelem({
        tag: 'div',
        title: 'save this comment',
        parent: commentform.rightbuttons,
        classes: ['commentformbutton'],
        innerHTML: 'save <span class="material-symbols-outlined">' +
            'save</span>'
    });
    commentform.minimizebutton = addelem({
        tag: 'div',
        title: 'minimize',
        parent: commentform.rightbuttons,
        classes: ['commentformbutton', 'minimize'],
        innerHTML: '<span class="material-symbols-outlined">' +
            'expand_more</span>'
    });
    return commentform;
}

function makeCommentTypeSelector(parnode) {

    const commentselector = addelem({
        parent: parnode,
        tag: 'div',
        classes: ['commentselector'],
        mywidget: parnode
    });

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
            const m = this.mywidget.mymarker;
            m.mypage.isdrawing = false;
            if (m.mypage.drawingmarker) {
                delete (m.mypage.drawingmarker);
            }
            m.parentNode.removeChild(m);
        }
    });

    return commentselector;
}

function makeType(ctype) {
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
    this.commentform = makeCommentForm(this, ctype);
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
    marker.style.zIndex = (w.nummarkers * 4).toString();
    marker.myzindex = (w.nummarkers * 4);
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
    const innermarker = addelem({
        parent: marker,
        classes: ['innermarker'],
        tag: 'div',
        onpointerdown: function(e) {
            e.stopPropagation();
        },
    });
    const commentwidget = addelem({
        parent: innermarker,
        mymarker: marker,
        tag: 'div',
        classes: ['commentwidget','selecting'],
        onpointerdown: function(e) {
            e.stopPropagation();
        },
        makeType: makeType
    });
    commentwidget.style.zIndex = (marker.myzindex + 2).toString();
    if (elem?.id == 'page1' & (anchorPP.y < 25 || newPP.y < 25)) {
        commentwidget.classList.add('underneath');
    }
    if (!w.iseditor) {
        commentwidget.myselector = makeCommentTypeSelector(commentwidget);
    } else {
        commentwidget.makeType('query');
    }
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
    title: 'submit changes to editors',
    id: 'submitbutton'
});

// TODO: fix this
setTimeout(() => submitbutton.classList.add('lookatme'), 4000);

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
                page.scrollIntoView();
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

/*
let selection = window.getSelection();
let position = selection.anchorNode.compareDocumentPosition(selection.focusNode);
if (position & Node.DOCUMENT_POSITION_FOLLOWING)
  alert("Left-to-right selection");
else if (position & Node.DOCUMENT_POSITION_PRECEDING)
  alert("Right-to-left selection");
else
  alert("Only one node selected");
 */
// show one of the three main body elements


if (htmlproofs.contentWindow) {
    window.htmlw = htmlproofs.contentWindow;
}

htmlw.onload = function() {
}

if (iseditor) {
    changeMode('html');
} else {
    changeMode('instructions');
}

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

// add listener to pdf pages
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
        /*page.onpointercancel = function(e) {
            if (this.isdrawing) {
                canceldraw(this, e);
            }
        }*/
        page.onpointerleave = function(e) {
            if (this.isdrawing) {
                canceldraw(this, e);
            }
        }
    }
}

if (document.body.clientWidth < 1200) {
   changeZoom('fitwidth');
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
                    <h1>Instructions</h1>
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
