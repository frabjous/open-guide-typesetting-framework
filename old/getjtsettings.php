<?php

$settings_file_location = __DIR__ . '/jtsettings.json';

if (file_exists($settings_file_location)) {
    $jt_settings = json_decode(file_get_contents($settings_file_location));
} else {
    $jt_settings = new StdClass();
    $jt_settings->journal_name = 'Your Journal Name Here';
    $jt_settings->contact_name = 'Journal Contact';
    $jt_settings->contact_email = 'journalcontact@journalsite.net';
    $jt_settings->timezone = 'America/New_York';
    $jt_settings->document_class = 'article';
    $poss_data_loc = $_SERVER["DOCUMENT_ROOT"] . '/../data/jt';
    if (is_dir($poss_data_loc)) {
        $jt_settings->datafolder = realpath($poss_data_loc);
    } else {
        mkdir('data',0755);
        $jt_settings->datafolder = realpath('data');
    }
    file_put_contents($settings_file_location, json_encode($jt_settings, JSON_PRETTY_PRINT+JSON_UNESCAPED_UNICODE));
}

date_default_timezone_set($jt_settings->timezone);

?>