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
            :root {
                --font-family: 'Nunito Sans', 'Roboto', 'Noto Sans', 'TeX Gyre Heros', 'Arimo', 'Helvetica', 'Arial', sans-serif;
            }
        </style>
<script type="module">
window.changeTheme = function() {
    document.getElementsByTagName("html")[0].dataset.theme = "light";
}
        </script>
    </head>
    <body>

        <nav>
            <ul><li>Open Guide Typesetting Framework</li></ul>
            <ul><li><a id="themebutton" href="#" role="button" onclick="changeTheme()">Link</a><li></ul>
        </nav>

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

    </body>
</html>
