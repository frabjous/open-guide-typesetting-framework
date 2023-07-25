<?php
// LICENSE: GNU GPL v3 You should have received a copy of the GNU General
// Public License along with this program. If not, see
// https://www.gnu.org/licenses/.

///////////////////// proofs/index.php /////////////////////////////////
// Serves the main page proof editing and commenting page             //
////////////////////////////////////////////////////////////////////////

session_start();

require_once(dirname(__FILE__) . '/proofsaccess.php');

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
        <title>Page title</title>

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
                margin: 0;
            }
            header {
                flex-shrink: 0;
                background-color: var(--panelbg);
                border-bottom: 1px ridge var(--primary);
            }
            main {
                flex-grow: 1;
                background-color: white;
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
        </style>

        <script>
            window.accesskey = '<?php echo $key; ?>';
            window.projectname = '<?php echo $project; ?>';
            window.username = '<?php echo $username; ?>';
            window.assignmentId = '<?php echo $assignment_id; ?>';
            window.assignmentType = '<?php echo $assignment_type; ?>';
            window.proofset = '<?php echo $proofset; ?>';
            window.iseditor = <?php echo json_encode($iseditor); ?>;
        </script>
        <!-- css file -->
        <!-- <link rel="stylesheet" type="text/css" href="/kcklib/kckdialog.css"> -->
        <!-- javascript file -->
        <!-- <script type="text/javascript" charset="utf-8" src="/kcklib/kckdialog.js"></script> -->

    </head>
    <body>
        <header>Here</header>
        <main></main>
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
