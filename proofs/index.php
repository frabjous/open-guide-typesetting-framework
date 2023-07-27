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
    --purple: rgb(148,0,255);
    --green: rgb(67,160,71,0.3);
    --pink: rgba(255,20,189,0.2);
    --bluey: rgb(10,132,255,0.3);
    --yellow: rgb(255,255,81,0.3);


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
    width: 1200px;
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

#toppanel div.viewoption {
    padding: 0.2rem 0.8rem;
    border: 2px solid var(--inactive);
    color: var(--inactive);
    border-radius: 1.5rem;
    margin-left: 0.5rem;
    cursor: pointer;
}

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

#toppanel div {
    display: inline-block;
}

#instructions {
    padding: 2rem;
}

#commentselector {
    margin-left: 1rem;
    margin-right: 1rem;
}

body.instructions #commentselector {
    visibility: hidden;
}

#commentselector .commenttype {
    margin-left: 0.5rem;
    padding: 0.2rem;
    border: 1px solid var(--inactive);
    cursor: pointer;
}

#commentselector .commenttype.del {
    text-decoration: line-through;
    text-decoration-color: var(--red);
    background-color: var(--pink);
}

#commentselector .commenttype.ins {
    background-color: var(--bluey);
}

#commentselector .commenttype.comment {
    background-color: var(--yellow);
}

#commentselector .commenttype:hover,
#commentselector .commenttype.del:hover,
#commentselector .commenttype.ins:hover,
#commentselector .commenttype.comment:hover {
    background-color: var(--bg);
    border: 1px solid var(--primary-hover);
}

body #toppanel div.pdfonly {
    display: none;
}

body.pdf #toppanel div.pdfonly {
    display: inline-block;
}

#toppanel div.pdfbuttons div.pdfbutton {
    position: relative;
    top: 0.3rem;
    font-size: 140%;
    color: var(--primary);
    cursor: pointer;
    margin-right: 0.5rem;
}

#toppanel div.pdfbuttons div.pdfbutton {
    color: var(--primary-hover);
}

#toppanel input#pagejump {
    width: 5.3rem;
    padding: 0.3rem;
    text-align: center;
}

</style>

<script type="module">

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

function changeMode(which) {
    document.body.classList.remove('pdf','html','instructions');
    document.body.classList.add(which);
}

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

const commentselector = addelem({
    parent: toppanel,
    tag: 'div',
    id: 'commentselector'
});

const commentlabel = addelem({
    parent: commentselector,
    tag: 'div',
    innerHTML: 'add: '
});

const adddel = addelem({
    parent: commentselector,
    tag: 'div',
    classes: ['commenttype','del'],
    title: 'mark selection for deletion',
    innerHTML: 'deletion'
});

const addins = addelem({
    parent: commentselector,
    tag: 'div',
    classes: ['commenttype','ins'],
    title: 'mark spot for insertion',
    innerHTML: 'insertion'
});

const addcomm = addelem({
    parent: commentselector,
    tag: 'div',
    classes: ['commenttype','comment'],
    title: 'add a comment',
    innerHTML: 'comment'
});

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
        tag: 'div'
    });
    const fit = addelem({
        parent: pdfbuttons,
        innerHTML: '<span class="material-symbols-outlined">fit_width</span>',
        classes: ['pdfbutton'],
        tag: 'div'
    });
    const zoomin = addelem({
        parent: pdfbuttons,
        innerHTML: '<span class="material-symbols-outlined">zoom_in</span>',
        classes: ['pdfbutton'],
        tag: 'div'
    });
    const pagejump = addelem({
        parent: pdfbuttons,
        tag: 'input',
        type: 'number',
        min: 1,
        max: pdfpp,
        placeholder: 'goto page',
        id: 'pagejump'
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
}

// old panel has zoom -, zoom +, comment/insertion/deletion toggle,
// PDF download, page - and page +, jump to page, and I'm done

</script>

</head>
<body>
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
            '" alt="pdf page ' . strval($i) . '"></div>' . PHP_EOL;
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
