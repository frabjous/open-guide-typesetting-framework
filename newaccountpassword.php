<?php

session_start();
require_once 'getjtsettings.php';

function rage_quit($msg) {
    echo $msg;
    exit(1);
}

if (!isset($_GET["token"])) {
    rage_quit("ERROR. No invitation token key given. This page cannot be used without one.");
}
$tokenkey = $_GET["token"];

$invites_file = $jt_settings->datafolder . '/invites.json';

if (!file_exists($invites_file)) {
    rage_quit("ERROR. Invitation file does not exist.");
}

$invites = json_decode(file_get_contents($invites_file));

if (!isset($invites->{$tokenkey})) {
    rage_quit("ERROR. That password creation token does not exist or has already been used.");
}

$invite_data = $invites->{$tokenkey};

?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <!-- standard metadata -->
        <meta charset="utf-8" />
        <meta name="description" content="<?php echo $jt_settings->journal_name; ?> account and password creation" />
        <meta name="author" content="<?php echo $jt_settings->contact_name; ?>" />
        <meta name="copyright" content="© <?php echo getdate()["year"] . ' ' . $jt_settings->contact_name; ?>" />
        <meta name="keywords" content="account,creation,passwords,typesetting,framework,journal" />

        <!-- if you want to disable search indexing -->
        <meta name="robots" content="noindex,nofollow" />  

        <!-- if mobile ready -->
        <meta name="viewport" content="width=device-width, initial-scale=1" />
        <meta name="apple-mobile-web-app-capable" content="yes" />
        <meta name="mobile-web-app-capable" content="yes" />

        <!-- web icon -->
        <link rel="shortcut icon" href="favicon.ico" type="image/x-icon" />
        <title><?php echo $jt_settings->journal_name ?> typesetting framework: Create/edit account password</title>

        <script>
            function noChangeEmailWarning() {
                alert('You cannot change the email for your account on this screen. If you would like to use a different email address, please contact the site administrator, <?php echo $jt_settings->contact_name . ' (' . $jt_settings->contact_email . ').'; ?>');
            }
            function submitForm() {
                var ff = ["username","name","email","password1","password2"];
                for (var i=0; i<ff.length; i++) {
                    var f=ff[i];
                    if (document.getElementById(f).value == '') {
                        alert("A necessary field is blank.");
                        document.getElementById(f).setCustomValidity("Required field.");
                        return;
                    }
                }
                if (document.getElementById("password1").value != document.getElementById("password2").value) {
                    alert("passwords do not match");
                    document.getElementById("password1").setCustomValidity("Do not match.");
                    document.getElementById("password2").setCustomValidity("Do not match.");
                    return;
                }
                document.getElementById("accountform").submit();
            }
        </script>
        
    </head>
    <body>
        <h1><?php echo $jt_settings->journal_name ?></h1>
        <fieldset>
            <legend>Typesetting framework account/password creation</legend>
            <form method="post" action="accountmaintenance.php" id="accountform">
                <table>
                    <tbody>
                        <tr>
                            <td>
                                <input type="text" required value="<?php echo $invite_data->username; ?>"  id="username" name="username" />
                            </td>
                            <td>
                                <label for="username" >username/login id</label>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <input type="text" required value="<?php echo $invite_data->name; ?>"  id="name" name="name" />
                            </td>
                            <td>
                                <label for="name" >real/preferred full name</label>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <input type="email" required value="<?php echo $invite_data->email; ?>" onclick="noChangeEmailWarning();" readonly id="email" name="email" />
                            </td>
                            <td>
                                <label for="email" onclick="noChangeEmailWarning();">email</label>
                            </td>
                            <td>
                        </tr>
                        <tr>
                            <td>
                                <input type="password" required  id="password1" name="password1" autocomplete="new-password" />
                            </td>
                            <td>
                                <label for="password1" >choose a password</label>
                            </td>
                            <td>
                        </tr>
                        <tr>
                            <td>
                                <input type="password" required  id="password2" name="password2" autocomplete="new-password" />
                            </td>
                            <td>
                                <label for="password2" >repeat password</label>
                            </td>
                            <td>
                        </tr>
                        <tr>
                            <td colspan="2">
                                <input type="hidden" value="<?php echo $tokenkey; ?>" name="token" id="token" readonly />
                                <button type="button" onclick="submitForm()">submit</button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </form>
        </fieldset>
    </body>
</html>