<!DOCTYPE html>
<html lang="en">
    <head>
        <!-- standard metadata -->
        <meta charset="utf-8">
        <meta name="description" content="Open Guide Typesetting Framework">
        <meta name="author" content="Kevin C. Klement">
        <meta name="copyright" content="© Your name">
        <meta name="keywords" content="this,that,someotherthing">
        <meta name="dcterms.date" content="TODAYSDATE">

        <!-- facebook opengraph stuff -->
        <meta property="og:title" content="title">
        <meta property="og:image" content="image_url">
        <meta property="og:description" content="A description of my site">

        <!-- if you want to disable search indexing -->
        <meta name="robots" content="noindex,nofollow">

        <!-- if mobile ready -->
        <meta name="viewport" content="width=device-width, initial-scale=1, user-scalable=yes">
        <meta name="apple-mobile-web-app-capable" content="yes">
        <meta name="mobile-web-app-capable" content="yes">

        <!-- web icon -->
        <link rel="shortcut icon" href="favicon.ico" type="image/x-icon">
        <title>Open Guide Typesetting Framework</title>



        <!-- pics css -->
        <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/@picocss/pico@1/css/pico.min.css">
        <!-- javascript file -->
        <!-- <script type="text/javascript" charset="utf-8" src="/kcklib/kckdialog.js"></script> -->
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
            nav li.projecttitle {
                font-size: 200%;
                font-weight: bold;
                color: var(--primary);
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
                left: 0.6rem;
            }
            #themetogglefakecontents {
                visibility: hidden;
            }
        </style>
        <script type="module">
            import ogst from './js/ogst.mjs';
            window.ogst = ogst;
        </script>
    </head>
    <body>

        <header class="container-fluid"><nav>
            <ul><li class="projecttitle">Open Guide Typesetting Framework</li></ul>
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
                    href="javascript:ogst.logout()"
                >log out</a></li>
            </ul>
        </nav></header>

        <main class="container" id="login">
            <h2>Please log in</h2>
            <form onsubmit="event.preventDefault();">
                <label for="ogstname">
                    <input
                        name="ogstname"
                        type="text"
                        id="ogstname"
                        placeholder="Username"
                    >
                </label>
                <label for="ogstpwd">
                    <input
                        name="ogstpwd"
                        type="password"
                        id="ogstpwd"
                        placeholder="Password"
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
                <button type="button" onclick="ogst.login()">log in</button>
                <p><a href="#forgotpwd">Forgot your password?</a></p>
            </form>
        </main>

        <main class="container" id="forgotpwd">
            <h2>Password reset</h2>
            <form onsubmit="event.preventDefault();">
                <label for="ogstpwdreset">
                    <input
                        id="ogstpwdreset"
                        name="ogstpwdreset"
                        type="email"
                        placeholder="email address"
                    >
                </label>
                <button type="button" onclick="ogst.resetpwd()">
                    email a password reset link
                </button>
            </form>
        </main>

        <footer class="container-fluid">
            <p><small>The Open Guide Typesetting Framework is Copyright 2023 © <a href="https://people.umass.edu/klement">Kevin C. Klement</a>. This is free software, which can be redistributed and/or modified under the terms of the <a href="https://www.gnu.org/licenses/gpl.html">GNU General Public License (GPL), version 3</a>. See the <a href="https://github.com/frabjous/open-guide-typesetting-framework">project github page</a> for more information.</small></p>
        </footer>

    </body>
</html>
