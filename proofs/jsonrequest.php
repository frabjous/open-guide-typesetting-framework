<?php
// LICENSE: GNU GPL v3 You should have received a copy of the GNU General
// Public License along with this program. If not, see
// https://www.gnu.org/licenses/.

//////////////////// jsonrequest.php //////////////////////////////////////
// responds to requests on proofs like saving comments, submitting, etc. //
///////////////////////////////////////////////////////////////////////////

session_start();

require_once(dirname(__FILE__) . '/proofsaccess.php');

require_once(dirname(__FILE__) . '/../open-guide-editor/open-guide-misc/json-request.php');

if (!isset($requesttype)) {
    jquit('No request type specified.');
}

require_once(dirname(__FILE__) . '/../php/readsettings.php');

$rv = new StdClass();

$proofdir = "$datadir/$project/$assignment_type" . 's' .
    "/$assignment_id/proofs/$proofset";

$commentsfile = "$proofdir/saved-comments.json";

function nicetime($ts) {
    return date('d M Y H:i:s', $ts);
}

function new_comments() {
    $comments = new StdClass();
    $comments->pdf = new StdClass();
    $comments->html = new StdClass();
    return $comments;
}

function read_comments() {
    global $commentsfile;
    if (!file_exists($commentsfile)) {
        return new_comments();
    }
    $comments = json_decode(file_get_contents($commentsfile) ?? '');
    if (!$comments) { return new_comments(); }
    return $comments;
}

function save_comments($comments) {
    global $commentsfile;
    $saveresult = file_put_contents($commentsfile,
        json_encode($comments, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)
    );
    return (!!$saveresult);
}

if ($requesttype == 'savecomment') {
    if (!isset($commentinfo)) {
        jquit('Request to save comment received without info about comment.');
    }
    if (!isset($commentinfo->id)) {
        jquit('Request to save comment received without comment id.');
    }
    $comments = read_comments();
    if (isset($commentinfo->page)) {
        $comments->pdf->{$commentinfo->id} = $commentinfo;
    }
    $success = save_comments($comments);
    if (!$success) {
        jquit('Unable to save comments.', 500);
    }
}

if ($requesttype == 'deletecomment') {
    if (!isset($commentid)) {
        jquit('ID of comment to delete not specified.');
    }
    $comments = read_comments();
    if (isset($comments->pdf->{$commentid})) {
        unset($comments->pdf->{$commentid});
    }
    if (isset($comments->html->{$commentid})) {
        unset($comments->html->{$commentid});
    }
    $success = save_comments($comments);
    if (!$success) {
        jquit('Unable to save comments with this comment removed.', 500);
    }
}

if ($requesttype == 'submit') {
    require_once(dirname(__FILE__) . '/../php/libauthentication.php');
    $users = load_users($project);
    if (!isset($users->{$username})) {
        jquit('Could not find information about editor who created ' +
            'the proofs', 500);
    }
    $userdetails = $users->{$username};
    if (!isset($userdetails->email)) {
        jquit('Could not find editor’s email address.', 500);
    }
    $email = $userdetails->email;
    require_once(dirname(__FILE__) . '/../php/libemail.php');
    $comments = read_comments();
    $emailcontents = '';
    if (isset($userdetails->name)) {
        $emailcontents .= '<p>Dear ' . $userdetails->name . ',</p>' . "\r\n";
    }
    $emailcontents .= '<p>Comments have been saved on the ' .
        ((isset($project_settings->title)) ? $project_settings->title :
        'Open Guide') . ' typesetting framework for the proof set ' +
        'created at ' . nicetime(intval($proofset)) .

}

$rv->error = false;
jsend();
