<?php
// LICENSE: GNU GPL v3 You should have received a copy of the GNU General
// Public License along with this program. If not, see
// https://www.gnu.org/licenses/.

//////////////// libauthentication.php /////////////////////////////////
// functions that verify logins, etc., for the typesetting framework  //
////////////////////////////////////////////////////////////////////////

function create_new_user($project, $username, $name, $email) {
    $users = load_users($project);
    if (isset($users->{$username})) { return 'userexists'; }
    $users->{$username} = new StdClass();
    $users->{$username}->name = $name;
    $users->{$username}->email = $email;
    return save_users($project, $users);
}

function grant_oge_access($project) {
    $projectdir = get_projectdir($project);
    if (!isset($_SESSION["open-guide-editor-access"])) {
        $_SESSION["open-guide-editor-access"] = array();
    }
    if (!in_array($projectdir, $_SESSION["open-guide-editor-access"])) {
        array_push($_SESSION["open-guide-editor-access"], $projectdir);
    }
}

function load_users($project) {
    $projectdir = get_projectdir($project);
    if (!$projectdir) { return (new StdClass()); }
    $usersfile = "$projectdir/users.json";
    if (!file_exists($usersfile)) { return (new StdClass()); }
    $json = file_get_contents($usersfile);
    if (!$json) { return (new StdClass()); }
    $users = json_decode($json);
    if (!$users) { return (new StdClass()); }
    // clear old pwd links
    $changed = false;
    foreach($users as $username => $userdata) {
        $userchanged = false;
        if (!isset($userdata->newpwdlinks)) { continue; }
        $newlinkarray = array();
        foreach($userdata->newpwdlinks as $linkobj) {
            if (time() > $linkobj->expires) {
                $userchanged = true;
            } else {
                array_push($newlinkarray, $linkobj);
            }
        }
        if ($userchanged) {
            $users->{$username}->newpwdlinks = $newlinkarray;
            $changed = true;
        }
    }
    if ($changed) { save_users($project, $users); }
    return $users;
}

function new_access_key($project, $user) {
    $users = load_users($project);
    if (!isset($users->{$user})) {
        $users->{$user} = new StdClass();
    }
    if (!isset($users->{$user}->keylist)) {
        $users->{$user}->keylist = array();
    }
    $accesskey = random_string(32);
    array_push(
        $users->{$user}->keylist,
        password_hash($accesskey, PASSWORD_DEFAULT)
    );
    save_users($project, $users);
    return $accesskey;
}

function new_set_pwd_link($project, $user) {
    $users = load_users($project);
    // fails if user does not exist
    if (!isset($users->{$user})) {
        return false;
    }
    if (!isset($users->{$user}->newpwdlinks)) {
        $users->{$user}->newpwdlinks = array();
    }
    $newpwdlink = random_string(48);
    $pwdobject = new StdClass();
    $pwdobject->hash = password_hash($newpwdlink, PASSWORD_DEFAULT);
    $pwdobject->expires = (time() + 2678400);
    array_push($users->{$user}->newpwdlinks, $pwdobject);
    save_users($project, $users);
    return $newpwdlink;
}

function random_string($length = 32) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $clength = strlen($characters);
    $str = '';
    for ($i = 0; $i < $length; $i++) {
        $str .= $characters[random_int(0, $clength - 1)];
    }
    return $str;
}

function remove_access_key($project, $user, $accesskey) {
    $users = load_users($project);
    $dosave = false;
    if (isset($users->{$user}->keylist)) {
        $newkeylist = array();
        foreach ($users->{$user}->keylist as $keyhash) {
            if (!password_verify($accesskey, $keyhash)) {
                array_push($newkeylist, $keyhash);
            } else {
                $dosave = true;
            }
        }
        $users->{$user}->keylist = $newkeylist;
    }
    if ($dosave) {
        return save_users($project, $users);
    }
    return true;
}

function remove_oge_access($project) {
    // remove access to open guide editor
    $projectdir = get_projectdir($project);
    if (isset($_SESSION["open-guide-editor-access"])) {
        $new_oge_access = array();
        foreach ($_SESSION["open-guide-editor-access"] as $accessdir) {
            if ($accessdir != $projectdir) {
                array_push($new_oge_access, $accessdir);
            }
        }
        if (count($new_oge_access) > 0) {
            $_SESSION["open-guide-editor-access"] = $new_oge_access;
        } else {
            unset($_SESSION["open-guide-editor-access"]);
        }
    }
}

function remove_user($project, $username) {
    $users = load_users($project);
    if (!isset($users->{$username})) { return false; }
    unset($users->{$username});
    return save_users($project, $users);
}

function save_users($project, $users) {
    $projectdir = get_projectdir($project);
    if (!$projectdir) { return false; }
    $usersfile = "$projectdir/users.json";
    return (file_put_contents(
        $usersfile,
        json_encode($users, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE)
    ) > 0);
}

function set_new_password($project, $user, $pwd) {
    $users = load_users($project);
    if (!isset($users->{$user})) {
        return 'User with name ' . $user . ' not found.';
    }
    // set new password
    $users->{$user}->passwordhash =
        password_hash($pwd, PASSWORD_DEFAULT);
    // remove any password change links
    $users->{$user}->newpwdlinks = array();
    // save changes
    return save_users($project, $users);
}

function set_user_details($project, $user, $name, $email) {
    $users = load_users($project);
    if (!isset($users->{$user})) { return false; }
    $users->{$user}->name = $name;
    $users->{$user}->email = $email;
    return save_users($project, $users);
}

function verify_by_accesskey($project, $user, $accesskey) {
    $users = load_users($project);
    if (!isset($users->{$user}->keylist)) { return false; }
    foreach  ($users->{$user}->keylist as $keyhash) {
        if (password_verify($accesskey, $keyhash)) {
            return true;
        }
    }
    return false;
}

function verify_by_password($project, $user, $password) {
    $users = load_users($project);
    if (!isset($users->{$user}->passwordhash)) { return 'nosuchuser'; }
    return password_verify($password, $users->{$user}->passwordhash);
}

function verify_newpwd_link($project, $user, $pwdlink) {
    // note that loading the users removes expired links
    // so that doesn't need to be checked here;
    // also, we do not remove things here because it is checked
    // by index.php before the actual request and needs to be
    // checked again
    $users = load_users($project);
    if (!isset($users->{$user}->newpwdlinks)) { return false; }
    foreach($users->{$user}->newpwdlinks as $pwdobj) {
        if (password_verify($pwdlink, $pwdobj->hash)) {
            return true;
        }
    }
    return false;
}
