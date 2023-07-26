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
            }
            body {
                font-family: var(--font-family);
                font-size: 18px;
                background-color: white;
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
                max-height: 100%;
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
            }
            #pdfholder {
                height: 100%;
                width: 100%;
                max-width: 100%;
                overflow: auto;
            }
            #pdfproofs {
                background-color: #4d606d;
                text-align: center;
            }
            #pdfproofs div.pdfpage {
                background-color: white;
                display: inline-block;
                margin-top: 1rem;
                margin-bottom: 1rem;
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
            #htmlholder, #instructionsholder {
                display: none;
            }
        </style>

        <script type="module">
            window.accesskey = '<?php echo $key; ?>';
            window.projectname = '<?php echo $project; ?>';
            window.username = '<?php echo $username; ?>';
            window.assignmentId = '<?php echo $assignment_id; ?>';
            window.assignmentType = '<?php echo $assignment_type; ?>';
            window.proofset = '<?php echo $proofset; ?>';
            window.iseditor = <?php echo json_encode($iseditor); ?>;
            window.usehtml = <?php echo json_encode($usehtml); ?>;
            window.pdfpages = <?php echo strval($pdfpages); ?>;
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
                window[id] = document.getElementById(id);
            }
        </script>
        <!-- css file -->
        <!-- <link rel="stylesheet" type="text/css" href="/kcklib/kckdialog.css"> -->
        <!-- javascript file -->
        <!-- <script type="text/javascript" charset="utf-8" src="/kcklib/kckdialog.js"></script> -->

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
                <div id="intructionsholder">
                    <div id="instructions">
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
