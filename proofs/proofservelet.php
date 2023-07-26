<?php
// LICENSE: GNU GPL v3 You should have received a copy of the GNU General
// Public License along with this program. If not, see
// https://www.gnu.org/licenses/.

////////////////// proofservelet.php //////////////////////////////////
// serves html files, pdf files, etc. to those with access to proofs //
///////////////////////////////////////////////////////////////////////

session_start();

require_once(dirname(__FILE__) . '/proofsaccess.php');

require_once(dirname(__FILE__) . '/../php/readsettings.php');

$proofdir = "$datadir/$project/$assignment_type" . 's' .
    "/$assignment_id/proofs/$proofset";

require_once(dirname(__FILE__) . '/../open-guide-editor/open-guide-misc/libservelet.php');

$download = (isset($_GET["download"]) && ($_GET["download"] == "true"));

$pdfpage = ((isset($_GET["pdfpage"])) ? intval($_GET["pdfpage"]) : 0);

// get pdf page
if ($pdfpage != 0) {
    $padded = (($pdfpage<10) ? '0' : '') . strval($pdfpage);
    $file = "$proofdir/pages/page" . $padded . ".svg";
    if (!file_exists($file)) { itsanogo('requested pdf page not found'); }
    servelet_send(array(
        "download" => $download,
        "filename" => $file,
        "mimetype" => "image/svg+xml"
    ));
    exit(0);
}

// get html file, either saved or original
if (isset($_GET["html"]) && ($_GET["html"] == "true")) {
    if (file_exists("$proofdir/saved-with-comments.html") && !$download) {
        servelet_send(array(
            "download" => false,
            "filename" => "$proofdir/saved-with-comments.html",
            "mimetype" => "text/html"
        ));
        exit(0);
    }
    if (file_exists("$proofdir/$assignment_id.html")) {
        servelet_send(array(
            "download" => $download,
            "filename" => "$proofdir/$assignment_id.html",
            "mimetype" => "text/html"
        ));
        exit(0);
    }
}

$ext = false;
if (isset($_GET["ext"])) {
    $ext = $_GET["ext"];
}
if (file_exists("$proofdir/$assignment_id.$ext")) {
    servelet_send(array(
        "download" => true,
        "filename" => "$proofdir/$assignment_id.$ext"
    ));
    exit(0);
}

itsanogo('Invalid request without a request for an existing file.');
