<?php
// LICENSE: GNU GPL v3 You should have received a copy of the GNU General
// Public License along with this program. If not, see
// https://www.gnu.org/licenses/.

/////////////////// extractbib.php /////////////////////////////////////
// handler that responds to request to save metadata                  //
////////////////////////////////////////////////////////////////////////

if (!isset($username) || !isset($accesskey)) {
    jquit('No username or access key provided.');
}

if (!isset($assignmentId) || !isset($assignmentType)) {
    jquit('Insufficient information provided to identify assignment.');
}

// load authentication and setting libraries
require_once(dirname(__FILE__) . '/../readsettings.php');
require_once(dirname(__FILE__) . '/../libauthentication.php');

if (!verify_by_accesskey($project, $username, $accesskey)) {
    jquit('Invalid accesskey provided.');
}
require_once(dirname(__FILE__) . '/../libassignments.php');

$assigndir = get_assignment_dir($assignmentType, $assignmentId, false);

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
                    $subyaml .= '  ';
                } else {
                    $subyaml .= '- ';
                    $hashyphen = true;
                    $usesubyaml = true;
                }
                $subyaml .= $subkey . ': ' . $subval . PHP_EOL;
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
        $yaml .= $mkey . ': [' . implode(', ', $mval) . ']' . PHP_EOL;
        continue;
    }
    // lists of names separated by commas with "and" before the last one
    if ($spec->pandoc == 'yamllist') {
        // skip empty arrays
        if (!is_array($mval) || (count($mval) == 0)) { continue; }
        $yaml .= $mkey . ': ';
        if (count($mval) == 1) {
            $yaml .= $mval[0] . PHP_EOL;
            continue;
        }
        $yaml .= implode(', ', array_slice($mval, 0, -1)) . ' and ' .
            $mval[count($mval)-1] . PHP_EOL;
        continue;
    }
    // should be a string, let's correct it
    $mval = trim(strval($mval));
    if (mb_ereg_match('.*\.[0-9]$',$mval)) {
        $mval .= '0';
    }
    // a block of text set off separately, as for abstracts
    if ($spec->pandoc == 'yamlblock') {
        $yaml .= $mkey . ': |' . PHP_EOL;
        $yaml .= '  ' . implode(PHP_EOL . '  ', explode(PHP_EOL, $mval)) .
            PHP_EOL;
        continue;
    }
    // a regular yaml entry, quoted to allow for semicolons, etc.
    // with single quotes escaped
    if ($spec->pandoc == 'yaml') {
        $yaml .= $mkey . ': \'' . mb_ereg_replace("'","''", $mval) . '\'' .
            PHP_EOL;
    }
}
// save yaml
if ($yaml != '') {
    $yaml_file = "$assigndir/metadata.yaml";
    $yaml_save = file_put_contents($yaml_file, $yaml);
    if (!$yaml_save || $yaml_save == 0) {
        jquit('Unable to save metadata yaml file. Contact your site administrator.');
    }
}

// if we made it here, all was well
$rv->success = true;
$rv->error = false;

jsend();