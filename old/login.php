<?php

session_start();
if ((isset($_SERVER["HTTP_REFERER"])) && (!isset($_SESSION["loginreferer"]))) {
    $_SESSION["loginreferer"] = $_SERVER["HTTP_REFERER"];
    if (mb_ereg_match('.*login\.php', $_SESSION["loginreferer"])) {
        $_SESSION["loginreferer"] = dirname($_SESSION["loginreferer"]);
    }
}

require 'getjtsettings.php';

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
        <script type="text/javascript" charset="utf-8" src="/kcklib/kckdialog.js"></script>
        <script type="text/javascript" charset="utf-8" src="/kcklib/ajax.js"></script>
        <link rel="stylesheet" type="text/css" href="/kcklib/kckdialog.css" />
        <link rel="shortcut icon" href="favicon.ico" type="image/x-icon" />
        <title><?php echo $jt_settings->journal_name; ?> typesetting login</title>
        <style>
            input,button {
                margin-top: 1ex;
            }
            .fakelink {
                color: blue;
                text-decoration: underline;
                cursor: pointer;
                margin-top: 1ex;
            }
            .errmsg {
                font-weight: bold;
                margin-top: 1ex;
                margin-bottom: 1ex;
                color: DarkRed;
            }
            .resetmsg {
                font-weight: bold;
                margin-top: 1ex;
                margin-bottom: 1ex;
                color: blue;
            }
        </style>
        <script>
            function showResetForm() {
                document.getElementById("loginform").style.display = "none";
                document.getElementById("forgot").style.display = "none";
                document.getElementById("resetpwdform").style.display = "block";
                document.getElementById("email").value = document.getElementById("loginid").value;
            }
            
            
            function submitReset() {
                var email = document.getElementById("email").value;
                var re = /\S+@\S+\.\S+/;
                if (!re.test(email)) {
                    kckErrAlert("Email address is not properly formatted.");
                    return;
                }
                var fD = new FormData();
                kckWaitScreen();
                fD.append("email",email);
                AJAXPostRequest('accountinvite.php', fD, function(text) {
                    kckRemoveWait();
                    try {
                        var resObj = JSON.parse(text);
                    } catch(err) {
                        kckErrAlert("There was an error processing data returned from the server. " + err + " " + text);
                        return;
                    }
                    if (resObj.success) {
                        kckAlert("Instructions on resetting your email was sent to your email address.");
                        document.getElementById("email").value = "";
                        document.getElementById("loginform").style.display = "block";
                        document.getElementById("forgot").style.display = "block";
                        document.getElementById("resetpwdform").style.display = "none";
                        return;
                    } else {
                        kckErrAlert("There was an error requesting the password reset: " + resObj.errmsg);
                        return;
                    }
                }, function(text) {
                    kckRemoveWait();
                    kckErrAlert("There was a server error when attempting to reset your password. Server reports: " + text);
                    return;
                });
            }
            
        </script>
    </head>
    <body>
        <h1><?php echo $jt_settings->journal_name; ?></h1>
        <h3>Typesetting framework login</h3>
        <?php
        
        if (isset($_GET["fail"])) {
            echo '<div class="errmsg">Login attempt failed.</div>' . PHP_EOL;
        }
               
        ?>
        <form method="post" action="processlogin.php" id="loginform">
            <fieldset>
                <legend>Editor login</legend>
                <input type="text" id="loginid" name="loginid" required />
                <label for="loginid">Username/email</label><br />
                <input type="password" id="loginpwd" name="loginpwd" required />
                <label for="loginpwd">Password</label><br />
                <input type="submit" value="log in" />
            </fieldset>
        </form>
        <div class="fakelink" id="forgot" onclick="showResetForm()">Forgot password?</div>
        <form method="post" action="accountinvite.php" id="resetpwdform" style="display: none;">
            <fieldset>
                <legend>Reset password</legend>
                <input type="email" id="email" name="email" required>
                <label for="email">Email</label><br />
                <button type="button" onclick="submitReset();">reset password</button>
            </fieldset>
        </form>
    </body>
</html>