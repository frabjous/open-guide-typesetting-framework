<?php
// LICENSE: GNU GPL v3 You should have received a copy of the GNU General
// Public License along with this program. If not, see
// https://www.gnu.org/licenses/.

///////////////////////// logout.php ////////////////////////////////////
// handler that deals with logout requests                             //
/////////////////////////////////////////////////////////////////////////

$rv->error = false;

// nothing to do if no username
if (!isset($username)) {
    jsend();
}

// nothing to do if no accesskey
if (!isset($accesskey)) {
    jsend();
}

// load authentication and setting libraries
require_once(dirname(__FILE__) . '/../readsettings.php');
require_once(dirname(__FILE__) . '/../libauthentication.php');

$rv->success = remove_access_key($project, $username, $accesskey);

// take away oge access
remove_oge_access($project);

// remove cookie
if (isset($_COOKIE['open-guide-typesetting-framework-saved-login'])) {
    // unset it server-side
    unset($_COOKIE['open-guide-typesetting-framework-saved-login']);
    // set to time in past to make expire browser side
    setcookie(
        'open-guide-typesetting-framework-saved-login',
        'invalid',
        array(
            'expires' => time() - 34473600,
            'path' => '/',
            'SameSite' => 'Strict'
        )
    );
}

jsend();
