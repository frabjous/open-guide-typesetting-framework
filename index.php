<?php
// LICENSE: GNU GPL v3 You should have received a copy of the GNU General
// Public License along with this program. If not, see
// https://www.gnu.org/licenses/.

//////////////////////// index.php ///////////////////////////////
// serves the main page of the open guide typesetting framework //
//////////////////////////////////////////////////////////////////

session_start();

// read settings
require_once('php/readsettings.php');

if ($settings_error != '') {
    header('Content-Type: text/plain; charset=UTF-8');
    echo 'The Open Guide Typesetting Framework is not properly configured: '
        . $settings_error;
    exit();
}

// read project from url parameter if given
$project = '';
$username = '';
$accesskey = '';
if (isset($_GET["project"])) { $project = $_GET["project"];}

// load authentication library
require_once('php/libauthentication.php');

// check if cookie login is successful if matches requested project
$cookie_login = false;
if (($cookie_projectname != '') &&
    ($project == '' || $project == $cookie_project)) {
    $cookie_login = verify_by_accesskey(
        $cookie_projectname,
        $cookie_username,
        $cookie_loginaccesskey
    );
}

// if cookie login successful, set the variables
if ($cookie_login) {
    $username = $cookie_username;
    $project = $cookie_projectname;
    $accesskey = $cookie_loginaccesskey;
    grant_oge_access($project);
}

$newpwdmode = false;
$newpasswordlink = false;
if (isset($_GET["newpwd"]) && isset($_GET["user"])) {
    if (verify_newpwd_link($project, $_GET["user"], $_GET["newpwd"])) {
        $newpwdmode = true;
        $username = $_GET["user"];
        $newpasswordlink = $_GET["newpwd"];
    } else {
        header('Content-Type: text/plain; charset=UTF-8');
        echo 'An invalid or expired link for a new password was given.';
        echo ' You may need to ask your project leader for help ';
        echo 'or do another password reset request.';
        exit();
    }
}

?><!DOCTYPE html>
<html lang="en">
    <head>
        <!-- standard metadata -->
        <meta charset="utf-8">
        <meta name="description" content="Open Guide Typesetting Framework">
        <meta name="author" content="Kevin C. Klement">
        <meta name="copyright" content="Copyright 2023 © Kevin C. Klement">
        <meta name="keywords" content="academic,typesetting,journal,anthology,guide">
        <meta name="dcterms.date" content="2023-07-08">

        <!--to disable search indexing -->
        <meta name="robots" content="noindex,nofollow">

        <!-- mobile ready -->
        <meta name="viewport" content="width=device-width, initial-scale=1, user-scalable=yes">
        <meta name="apple-mobile-web-app-capable" content="yes">
        <meta name="mobile-web-app-capable" content="yes">

        <!-- web icon -->
        <link rel="shortcut icon" href="favicon.ico" type="image/x-icon">
        <title>Open Guide Typesetting Framework</title>

        <!-- simple css framework -->
        <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/@picocss/pico@1/css/pico.min.css">

        <style>
            @import url('https://fonts.googleapis.com/css2?family=Nunito+Sans:ital,wght@0,400;0,700;1,400;1,700&display=swap');
            @import url('https://fonts.googleapis.com/css?family=Material+Symbols+Outlined');
            :root {
                --font-family: 'Nunito Sans', 'Roboto', 'Noto Sans', 'TeX Gyre Heros', 'Arimo', 'Helvetica', 'Arial', sans-serif;
            }
            body > footer,
            body > header,
            body > header.container-fluid {
                padding: 0;
                margin: 0;
            }
            header > nav {
                vertical-align: baseline;
                background-color: var(--secondary-focus);
                border-bottom: 1px solid var(--primary);
                padding-left: 0.5rem;
                padding-right: 1rem;
                margin: 0;
            }

            nav a[role="button"] {
                height: 2.4rem;
                white-space: nowrap;
            }
            nav a[role="button"] span.material-symbols-outlined {
                position: relative;
                top: 0.2rem;
            }
            nav li#projecttitle {
                user-select: none;
                cursor: pointer;
            }
            nav li#projecttitle span:first-child {
                font-size: 200%;
                font-weight: bold;
                color: var(--primary);
            }
            nav li#projecttitle span:last-child {
                display: none;
            }
            body > footer.container-fluid {
                border-top: 1px solid var(--primary);
                padding: 0.5rem;
            }
            #themetoggle {
                position: relative;
            }
            #themetoggleicon {
                position: absolute;
                top: 0.6rem;
                left: 0.45rem;
            }
            #themetogglefakecontents {
                visibility: hidden;
            }
            #mainmsg {
                margin-top: 3rem;
            }
            #newpwdmsg, #resetmsg, #loginmsg, #mainmsg {
                border: 3px solid var(--form-element-invalid-border-color);
                border-radius: 5px;
                padding: 0.5rem;
            }
            .cardmsg {
                color: var(--form-element-invalid-border-color);
                font-weight: bold;
            }
            .cardmsg.okmsg {
                color: inherit;
            }
            #newpwdmsg.okmsg, #resetmsg.okmsg, #mainmsg.okmsg {
                border: 3px solid var(--primary);
            }
            body > main.container {
                padding-top: 0;
            }
            .mainnav a {
                margin-right: 1rem;
                margin-top: 1rem;
            }
            .ogstview > h2,
            #projectmain #projectcontents {
                padding-top: 3rem;
            }
            .userstable .userinfocell span:first-child {
                font-weight: bold;
            }
            .userstable .userinfocell span:last-child {
                font-size: 90%;
            }
            .userstable .userinfocell span:last-child a {
                font-family: monospace;
            }
            .userstable td:last-child {
                text-align: right;
            }
            .userstable td:last-child .material-symbols-outlined {
                cursor: pointer;
                color: var(--form-element-invalid-border-color);
            }
            section article.assignment details div.fieldlistbuttondiv {
                text-align: right;
            }
            section article.assignment details div.fieldlistbuttondiv a {
                margin-left: 1rem;
                padding-left: 0.4rem;
                padding-right: 0.4rem;
                padding-top: 0.4rem;
                padding-bottom: 0rem;
            }
            section article.assignment details textarea {
                height: 12rem;
                resize: none;
            }
            section article.assignment header.assignmenttop {
                padding-top: 1.2rem;
                padding-bottom: 1.2rem;
            }
            section article.assignment header.assignmenttop > div {
                display: flex;
                flex-direction: row;
            }
            section article.assignment header.assignmenttop > div > div:nth-child(2) {
                flex-grow: 1;
                padding-left: 1rem;
                padding-right: 1rem;
            }
            section article.assignment header.assignmenttop > div > div:first-child {
                font-weight: bold;
            }
            section article.assignment header.assignmenttop button {
                float: right;
                width: 8rem;
                margin-bottom: 0;
            }
            section article.assignment header.assignmenttop input {
                width: 12rem;
                margin-bottom: 0;
            }
            section article.assignment div.assignmentinner summary {
                font-weight: bold;
                color: var(--primary);
            }
            section article.assignment table.auxuploadtable td:last-child {
                text-align: right;
            }
            section article.assignment table.auxuploadtable td:last-child span:first-child {
                cursor: pointer;
                color: var(--primary);
            }
            section article.assignment table.auxuploadtable td:last-child span:last-child {
                cursor: pointer;
                color: var(--form-element-invalid-border-color);
            }
            section article.assignment div.fakegrid > div,
            section article.assignment div.fakegrid > span,
            section article.assignment div.fakegrid > label,
            section article.assignment div.fakegrid > input[type="file"],
            section article.assignment div.fakegrid > input {
                display: inline;
                width: 18rem;
            }
            section article.assignment div.fakegrid > input[type="file"] {
                margin-left: 1rem;
            }
            section article.assignment table tr.warningrow:hover {
                background-color: hsl(330, 40%, 50%, 0.3);
            }
            section article.assignment table tr td.warncell {
                width: 3rem;
                color: var(--code-value-color);
            }
            section article.assignment table tr td.editcell {
                width: 3rem;
            }
            section article.assignment table tr td.editcell a,
            section article.assignment table tr td.editcell a:link,
            section article.assignment table tr td.editcell a:hover {
                text-decoration: none;
                color: var(--color);
            }
            section article.assignment details.ogst-assignmentblock div.editmainfilelabel,
            section article.assignment details.ogst-assignmentblock div.bibitemlabel {
                font-weight: bold;
                margin-bottom: 0.5rem;
            }
            section article.assignment details.ogst-assignmentblock div.bibitem {
                border-bottom: 2px solid var(--muted-color);
                margin-bottom: 1rem;
            }
            section article.assignment details.ogst-assignmentblock button.bibremovebtn:hover {
                color: var(--form-element-invalid-border-color);
            }
            section article.assignment details.ogst-assignmentblock button.bibremovebtn span {
                position: relative;
                top: 0.2rem;
            }
            section article.assignment details.ogst-assignmentblock div.bibmiddlebtns {
                margin-bottom: 0.3rem;
            }
            section article.assignment details.ogst-assignmentblock div.bibitem td textarea {
                height: 5rem;
                resize: none;
            }
            section article.assignment details.ogst-assignmentblock div.bibnamefieldbuttons {
                text-align: right;
            }
            section article.assignment details.ogst-assignmentblock div.bibnamefieldbuttons a {
                padding-left: 0.4rem;
                padding-right: 0.4rem;
                padding-top: 0.4rem;
                padding-bottom: 0.1rem;
                margin-left: 1rem;
            }
            section article.assignment details.ogst-assignmentblock div.bibitem table td:first-child {
                text-align: right;
                padding-top: 1rem;
                vertical-align: top;
            }
            section article.assignment details.ogst-assignmentblock table.editionstable span.pubsdl,
            section article.assignment details.ogst-assignmentblock table.proofslist span.proofsdl {
                color: var(--primary);
                cursor: pointer;
            }
            section article.assignment details.ogst-assignmentblock div.pubversionextros label {
                margin-top: 1rem;
            }

            section article.assignment details.ogst-assignmentblock div.pubversionextros textarea {
                resize: none;
                height: 10rem;
            }
            section article.assignment details.ogst-assignmentblock div.pubversionextros div.copybuttondiv {
                text-align: right;
            }
        </style>
        <script>
            // starting globals
            window.isloggedin = <?php echo json_encode(
                ($username != '' && !$newpwdmode)
            ); ?>;
            window.username = '<?php echo $username; ?>';
            window.loginaccesskey = '<?php echo $accesskey; ?>';
            window.projectname = '<?php echo $project; ?>';
            window.projects = <?php echo json_encode($projects); ?>;
            <?php if ($newpwdmode) { echo "window.newpwdlink = '" .
                $newpasswordlink . "';"; } ?>
            window.datadir = '<?php echo $datadir; ?>';
        </script>
        <script type="module">
            import ogst from './js/ogst.mjs';
            window.ogst = ogst;
        </script>
    </head>
    <body>

        <header class="container-fluid"><nav>
            <ul><li id="projecttitle"><span>Open Guide Typesetting
                Framework</span><span><br>&nbsp;typesetting framework</span>
            </li></ul>
            <ul>
                <li><a
                    id="themetoggle"
                    href="javascript:ogst.changetheme()"
                    role="button"
                    tabindex="-1"
                    title="toggle light/dark theme"
                >
                    <span
                        id="themetoggleicon" class="material-symbols-outlined"
                    >light_mode</span>
                    <span id="themetogglefakecontents">XX</span>

                </a></li>
                <li><a
                    id="logoutbutton"
                    role="button"
                    tabindex="-1"
                    href="javascript:ogst.logout();"
                >log out</a></li>
            </ul>
        </nav></header>

        <main class="container">

            <div class="ogstview" id="chooseproject">
                <h2>Please choose a project</h2>
                <form onsubmit="event.preventDefault();">
                    <details role="list">
                        <summary aria-haspopup="listbox">Choose one…</summary>
                        <ul role="listbox">
                            <?php foreach ($projects as $projectname => $project) {
                                echo '<li><a href="javascript:ogst.chooseproject(\'' .
                                $projectname . '\');">' .
                                $project->title . '</a></li>';
                            }
                            ?>
                        </ul>
                    </details>
                </form>
            </div>

            <div class="ogstview" id="login">
                <h2>Please log in</h2>
                <p id="loginmsg" style="display: none;"></p>
                <form onsubmit="event.preventDefault();">
                    <label for="ogstname">
                        <input
                            name="ogstname"
                            type="text"
                            id="ogstname"
                            placeholder="Username"
                            required
                        >
                    </label>
                    <label for="ogstpwd">
                        <input
                            name="ogstpwd"
                            type="password"
                            id="ogstpwd"
                            placeholder="Password"
                            required
                        >
                    </label>
                    <fieldset>
                        <label for="ogstremember">
                            <input
                                type="checkbox"
                                role="switch"
                                id="ogstremember"
                                name="ogstremember"
                            > remember me on this device
                        </label>
                    </fieldset>
                    <button type="button" onclick="ogst.login();">log in</button>
                    <p><a href="#forgotpwd">Forgot your password?</a></p>
                </form>
            </div>

            <div class="ogstview" id="forgotpwd">
                <h2>Password reset</h2>
                <p id="resetmsg" style="display: none;"></p>
                <form onsubmit="event.preventDefault();">
                    <label for="ogstpwdreset">
                        <input
                            id="ogstpwdreset"
                            name="ogstpwdreset"
                            type="email"
                            placeholder="email address"
                            required
                        >
                    </label>
                    <button
                        type="button" 
                        id="pwdresetbutton"
                        onclick="ogst.resetpwd();"
                    >
                        email a password reset link
                    </button>
                </form>
            </div>

            <div class="ogstview" id="newpwd">
                <h2>Set new password</h2>
                <p id="newpwdmsg" style="display: none;"></p>
                <form onsubmit="event.preventDefault();" autcomplete="off">
                    <label for="ogstnewpwd1">
                        <input
                            id="ogstnewpwd1"
                            name="ogstnewpwd1"
                            type="password"
                            placeholder="enter new password"
                            autocomplete="off"
                            required
                        >
                    </label>
                    <label for="ogstnewpwd2">
                        <input
                            id="ogstnewpwd2"
                            name="ogstnewpwd2"
                            type="password"
                            autocomplete="off"
                            placeholder="re-enter new password"
                            required
                        >
                    </label>
                    <button
                        type="button" 
                        id="newpwdbutton"
                        onclick="ogst.setnewpwd();"
                    >
                        set new password
                    </button>
                </form>
            </div>

            <div class="ogstview" id="projectmain">
            </div>

        </main>

        <footer class="container-fluid">
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
