<?php
// LICENSE: GNU GPL v3 You should have received a copy of the GNU General
// Public License along with this program. If not, see
// https://www.gnu.org/licenses/.

///////////////////// libemail.php ////////////////////////////////////
// defines the functions for sending email used by the framework     //
///////////////////////////////////////////////////////////////////////

// Note: this uses the expected global $project for the project name
// and looks for custom email handler in the projectdir, 'customemail.php'
// for defining a function custom_send_email; if it isn’t found, it
// uses the php native mail(...) function

// Note: this script expects readsettings.php is already required by
// the caller; and expects global $project and get_projectdir() to
// be available

function send_email($to, $subject, $htmlcontents) {
    global $project;
    $projectdir = get_projectdir($project);
    $customscript = "$projectdir/customemail.php";
    if (file_exists($customscript)) {
        require_once($customscript);
    }
    if (function_exists('custom_send_email') {
        return custom_send_email($to, $subject, $htmlcontents);
    }
    // determine sender; hopefully overwriting default nonexistent gmail
    // address
    $project_settings = get_project_settings($project);
    if (!is_object($project_settings)) { return false; }
    $email_from = 'Open Guide Typesetting Framework <noreply@gmail.com>';
    if (isset($project_settings->contactname) &&
        isset($project_settings->contactemail)) {
        $email_from = $project_settings->contactname . ' <' .
            $project_settings->contactemail . '>';
    }
    $hdrs = "MIME-Version: 1.0\r\n" .
        "Content-type:text/html;charset=UTF-8\r\n" .
        "From: " . $email_from . "\r\n";
    return mail($to, $subject, $htmlcontents, $hdrs);
}
