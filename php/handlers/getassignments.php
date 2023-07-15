<?php
// LICENSE: GNU GPL v3 You should have received a copy of the GNU General
// Public License along with this program. If not, see
// https://www.gnu.org/licenses/.

//////////////////// getassignments.php ////////////////////////////////
// retrieves data about all assignments, either current or archived   //
// depending on value of $archive from caller                         //
////////////////////////////////////////////////////////////////////////

// authenticate request

if (!isset($username) || !isset($accesskey)) {
    jquit('No username or access key provided.');
}

// load authentication and setting libraries
require_once(dirname(__FILE__) . '/../readsettings.php');
require_once(dirname(__FILE__) . '/../libauthentication.php');

if (!verify_by_accesskey($project, $username, $accesskey)) {
    jquit('Invalid access key provided.');
}

require_once(dirname(__FILE__) . '/../libassignments.php');

if (!(isset($project_settings->assignmentTypes))) {
    
}


    // should have: title (header), metadata, files/upload, bibl, proofs, publication
    // (title): identify the work, and its id
    // maybe put archive button on right of title?
    // (metadata): custom for type; jhap has title, author name, email, affiliation
    // volume, number, special volume title, special volume editors
    // for reviews, also, title of reviewed work, author of reviewed work,
    // editor of reviewed work, publication details, = place, publisher,year,
    // pages, cost, hardcover/soft. ISBN
    // (files): main upload: download, replace
    // supplementary
    // edit LaTeX (now markdown
    // (bibliography):edit/complete
    // (proofs): list, each with editor link, author link
    // create new proofs button
    // (publication): optimized pdf creation/download,
    // extract abstract, extract references
    // (maybe put abstract in metadata)

    // Files: uploaded, random supplementary, bibliography.json,
    // main.md (better name), metadata.json, status.json, abstract?
    // + now, oge-setting.json
    // subfolder: proofs
    // subfolder: publication


$rv->error = false;
jsend();
