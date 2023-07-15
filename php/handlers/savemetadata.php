<?php
// LICENSE: GNU GPL v3 You should have received a copy of the GNU General
// Public License along with this program. If not, see
// https://www.gnu.org/licenses/.

///////////////// savemetadata.php /////////////////////////////////////
// handler that responds to request to save metadata                  //
////////////////////////////////////////////////////////////////////////

if (!isset($username) || !isset($accesskey)) {
    jquit('No username or access key provided.');
}

if (!isset($metadata) || !isset($assignmentId) ||
    !isset($assignmentType)) {
    jquit('Insufficient information provided to identify assignment.');
}

// load authentication and setting libraries
require_once(dirname(__FILE__) . '/../readsettings.php');
require_once(dirname(__FILE__) . '/../libauthentication.php');

if (!verify_by_accesskey($project, $username, $accesskey)) {
    jquit('Invalid accesskey provided.');
}
require_once(dirname(__FILE__) . '/../libassignments.php');

$assigndir = get_assignment_dir($assignmentType, $assignmentId);

if (!$assigndir) {
    jquit('Unable to find or create directory for document.' .
    ' Contact your site administrator.');
}

// create json file with metadata
$metadata_file = "$assigndir/metadata.json";
$json_save = file_put_contents($metadata_file,
    json_encode($metadata, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE));
if (!$json_save || $json_save == 0) {
    jquit('Unable to save metadata. Contact your site administrator.');
}
//
////// create yaml file with metadata for pandoc
//
// get info on spec from project settings
if (!isset($project_settings->assignmentTypes->{$assignmentType})) {
    jquit('Metadata saved, but server cannot find information about ' .
        'assignment type to create yaml metadata file for ' .
        'inclusion in output documents. Check your site settings.');
}
$assign_type_info = $project_settings->assignmentTypes->{$assignmentType};
// default to blank spec if nothing set
$metadata_spec = new StdClass();
if (isset($assign_type_info->metadata)) {
    $metadata_spec = $assign_type_info->metadata;
}
$yaml = '';
foreach($metadata as $mkey => $mval) {
    // subelement, yaml, yamlarray, yamllist, yamlblock
    if (!isset($metadata_spec->{$mkey})) { continue; }
    $mspec = $metadata_spec->{$mkey};
    $spec = $mspec;
    if (is_array($mspec)) {
        $spec = $mspec[0];
    }
    // metadata elements with subelements
    if (isset($spec->subcategories) && ($spec->subcategories)) {
        $subyaml = $mkey . ':' . PHP_EOL;
        $usesubyaml = false;
        foreach($mval as $subval) {
            $hashyphen = false;
            foreach ($subval as $subkey => $subval) {
                if (!isset($spec->{$subkey}->pandoc)) {
                    continue;
                }
                if ($spec->{$subkey}->pandoc != 'subelement') {
                    continue;
                }
                if ($hashyphen) {
                    $subyaml .= ' ';
                } else {
                    $subyaml .= '- ';
                    $hashyphen = true;
                    $usesubyaml = true;
                }
                $subyaml .= $subkey + ': ' + $subval + PHP_EOL;
            }
        }
        if ($usesubyaml) { $yaml .= $subyaml; }
        continue;
    }
    // if no pandoc setting, skip
    if (!isset($spec->pandoc)) {
        continue;
    }
    // yaml arrays as for keywords
    if ($spec->pandoc == 'yamlarray') {
        // skip empty arrays
        if (!is_array($mval) || (count($mval) == 0)) { continue; }
        $yaml .= $mkey . ': [' . implode(', ', $mkey) . ']' . PHP_EOL;
        continue;
    }
    if ($spec->pandoc == 'yamllist') {
        // skip empty arrays
        if (!is_array($mval) || (count($mval) == 0) { continue; }
        
    }
}

$rv->success = true;
$rv->error = false;

jsend();
