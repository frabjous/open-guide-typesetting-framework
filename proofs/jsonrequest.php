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

$savedhtmlfile = "$proofdir/saved-with-comments.html";

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
    // save html body
    

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
    // determine editor details
    require_once(dirname(__FILE__) . '/../php/libauthentication.php');
    $users = load_users($project);
    if (!isset($users->{$username})) {
        jquit('Could not find information about editor who created ' .
            'the proofs', 500);
    }
    $userdetails = $users->{$username};
    if (!isset($userdetails->email)) {
        jquit('Could not find editor’s email address.', 500);
    }
    $email = $userdetails->email;
    // try to read metadata
    $metadata = new StdClass();
    if (file_exists("$assigndir/metadata.json")) {
        $possmeta = json_decode(file_get_contents("$assigndir/metadata.json"));
        if ($possmeta) {
            $metadata = $possmeta;
        }
    }
    // try to find matching accesskey
    $editorkey = '';
    foreach ($keys as $posskey => $posskeyinfo) {
        if (($posskeyinfo->project == $project) &&
            ($posskeyinfo->username == $username) &&
            ($posskeyinfo->assignmentId == $assignment_id) &&
            ($posskeyinfo->assignmentType == $assignment_type) &&
            ($posskeyinfo->proofset == $proofset) &&
            (isset($posskeyinfo->editor) && $posskeyinfo->editor)) {
            $editorkey = $posskey;
            break;
        }
    }
    if ($editorkey == '') {
        jquit('Could not find corresponding access key for editor.');
    }
    require_once(dirname(__FILE__) . '/../php/libemail.php');
    $comments = read_comments();
    $emailcontents = '';
    $subject = 'Proof comments submitted on the ' . ((isset($project_settings->title)) ?
        $project_settings->title : 'Open Guide') . ' typesetting framework ' .
        '(' . $assignment_id . ')';
    if (isset($userdetails->name)) {
        $emailcontents .= '<p>Dear ' . $userdetails->name . ',</p>' . "\r\n";
    }
    $emailcontents .= '<p>Author comments and/corrections have been ' .
        'submitted on the <em>' . "\r\n" . ((isset($project_settings->title)) ?
        $project_settings->title : 'Open Guide') . '</em>' . "\r\n" .
        'typesetting framework for the proof set created on' . "\r\n" .
        nicetime(intval($proofset)) . ' for document id ' .
        '<strong>' . $assignment_id . '</strong>' . "\r\n";
    if (isset($metadata->title)) {
        $emailcontents .= ' — “' . str_replace('&', '&amp;',
            $metadata->title) . '”' . "\r\n";
    }
    if (isset($metadata->author)) {
        $emailcontents .= ' by ';
        foreach($metadata->author as $i => $authordetails) {
            if (isset($authordetails->name)) {
                if ($i > 0) {
                    if ($i < (count($metadata->author) - 1)) {
                        $emailcontents .= ', ';
                    } else {
                        $emailcontents .= ' and ';
                    }
                }
                $emailcontents .= $authordetails->name;
            }
        }
    }
    $emailcontents .= '.</p>'. "\r\n";
    $fp = full_path();
    $fp = str_replace('jsonrequest.php','',$fp);
    $editorurl = $fp . '?key=' . rawurlencode($editorkey);
    $emailcontents .= '<p>To view the proofs with the saved comments,' .
        "\r\n" . 'please visit this URL:</p>';
    $emailcontents .= '<p><a href="' . $editorurl . '" target="_blank" ' .
        "\r\n" . '>' . $editorurl . '</a></p>' . "\r\n";
    $emailcontents .= "<p>A summary of the comments left is below.</p>\r\n";
    foreach(array('html','pdf') as $prooftype) {
        if (!isset($comments->{$prooftype})) {
            continue;
        }
        $liststarted = false;
        foreach ($comments->{$prooftype} as $commentid => $commentinfo) {
            if (!$liststarted) {
                $emailcontents .= '<p>Comments on ' . $prooftype .
                    ' proofs</p>' . "\r\n" . '<ol>' . "\r\n";
                $liststarted = true;
            }
            $emailcontents .= '<li>';
            if (isset($commentinfo->page)) {
                $emailcontents .= '<strong>' . str_replace('page', 'page ',
                    $commentinfo->page) . '</strong><br>' . "\r\n";
            }
            //TODO: something htmlish
            if (isset($commentinfo->del)) {
                $emailcontents .= 'Deleted text: ' . htmlspecialchars(
                    $commentinfo->del) . '<br>' . "\r\n";
            }
            if (isset($commentinfo->ins)) {
                $emailcontents .= 'Inserted text: ' . htmlspecialchars(
                    $commentinfo->ins) . '<br>' . "\r\n";
            }
            if (isset($commentinfo->comment)) {
                $label = 'Comment: ';
                if (isset($commentinfo->commenttype) && 
                    $commentinfo->commenttype == 'query') {
                    $label = 'Query: ';
                }
                $emailcontents .= $label . htmlspecialchars(
                    $commentinfo->comment) . '<br> ' . "\r\n";
            }
            if (isset($commentinfo->response)) {
                $emailcontents .= 'Response: ' . htmlspecialchars(
                    $commentinfo->response) . '<br>' . "\r\n";
            }
            $emailcontents .= '</li>' . "\r\n";
        }
        if ($liststarted) {
            $emailcontents .= '</ol>' . "\r\n";
        }
    }
    $emailresult = send_email($email, $subject, $emailcontents);
    if (!$emailresult) {
        jquit('Unable to email editors. Please contact them directly.');
    }
}

$rv->error = false;
jsend();
