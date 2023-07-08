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
    </head>
    <body>
        <main class="container" id="main-welcome" style="display: none;">
            <h2>Welcome!</h2>
        </main>
        <main class="container" id="main-login">
            <h2>Please log in</h2>
            <form>
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
                <button>log in</button>
                <p><a href="#main-forgotpassword">Forgot your password?</a></p>
            </form>
        </main>
    </body>
</html>
