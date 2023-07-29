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
    
}

$rv->error = false;
jsend();
