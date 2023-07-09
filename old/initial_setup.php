<?php

chdir(dirname(__FILE__));

$sapi_name = php_sapi_name();

if ($sapi_name != 'cli') {
    exit("ERROR. This script must be run from the command line on the server.");
}

if (file_exists('jtsettings.json')) {
    echo "WARNING: The settings file already exists. Overwrite?" . PHP_EOL;
    $answer = '';
    while ((strtolower(substr($answer,0,1)) != 'n') && (strtolower(substr($answer,0,1)) != 'y')) {
        $answer = readline('[yes/no]: ');
    }
    if (strtolower(substr($answer,0,1)) == 'n') {
        exit("Exiting." . PHP_EOL);
    }
}


echo PHP_EOL . PHP_EOL;
echo "Welcome to the Journal Tools set up script." . PHP_EOL;
echo PHP_EOL;
echo "Please answer each question, or press enter for the default option [in brackets]." . PHP_EOL;
echo PHP_EOL;


// get root folder of web server
$cwd = getcwd();
$rootdir = '';
while (empty($rootdir)) {
    echo "Please enter the document root directory of the websever.". PHP_EOL;
    $defaultrootdir = dirname($cwd);
    $rootdir = readline("[" . $defaultrootdir . "]: ");
    if (empty($rootdir)) {
        $rootdir = $defaultrootdir;
    }
    if (substr($rootdir, -1, 1) == '/') {
        $rootdir == substr($rootdir, 0, -1);
    }
    if (!is_dir($rootdir)) {
        echo "The specified folder does not exist. Please try again." . PHP_EOL;
        $rootdir = '';
    }
}
chdir($rootdir);

function ask_to_install($reponame) {
    echo PHP_EOL;
    echo 'Required library: ' . $reponame . ' is not installed. Install with git?' . PHP_EOL;
    $answer = '';
    while ((strtolower(substr($answer,0,1)) != 'n') && (strtolower(substr($answer,0,1)) != 'y')) {
        $answer = readline('[yes]: ');
        if (empty($answer)) {
            $answer = 'yes';
        }
    }
    if (strtolower(substr($answer,0,1)) == 'y') {
        $cmdstr = 'git clone https://bitbucket.org/frabjous/' . $reponame . '.git';
        system($cmdstr, $rv);
        if ($rv != 0) {
            exit("ERROR. Could not install library . ' $reponame . ' with git. Exiting.". PHP_EOL);
        }
        if ($reponame == 'ke') {
            chdir('ke');
            $cmdstr = 'git clone https://github.com/codemirror/CodeMirror.git';
            system($cmdstr, $rv);
            if ($rv != 0) {
                exit("ERROR. Could not install codemirror. Exiting." . PHP_EOL);
            }
            rename('CodeMirror', 'codemirror');
            chdir('codemirror');
            system('npm install', $rv);
            if ($rv != 0) {
                exit("ERROR. Could not set up CodeMirror. Exiting. (Is npm installed?)." . PHP_EOL);
            }
        }

    } else {
        echo 'For proper functionality, this library should be installed before journal tools is used.' . PHP_EOL;
    }
}

if (!is_dir('icons')) {
    ask_to_install('icons');
}

if (!is_dir('kcklib')) {
    ask_to_install('kcklib');
}

if (!is_dir('ke')) {
    ask_to_install('ke');
}

chdir($cwd);

$datadir = '';
while (empty($datadir)) {
    echo PHP_EOL;
    echo 'Where do you wish to store data and files for the framework? (Preferably, this should not be under the webserver’s path, but still writeable by the server user.)' . PHP_EOL;
    $defaultdatadir = dirname($rootdir) . '/data/jt';
    $datadir = readline('[' . $defaultdatadir . ']: ');
    if (empty($datadir)) {
        $datadir = $defaultdatadir;
    }
    if (!is_dir($datadir)) {
        $result = mkdir($datadir, 0755, true);
        if ($result === false) {
            echo("ERROR: Could not create that folder. Specify another location." . PHP_EOL);
            $datadir = '';
        }
    }
}

$journal_name = '';
while (empty($journal_name)) {
    echo PHP_EOL;
    echo 'What is the name of your journal?' . PHP_EOL;
    $defaultjournalname = 'Journal for the History of Analytical Philosophy';
    $journal_name = readline('[' . $defaultjournalname . ']: ');
    if (empty($journal_name)) {
        $journal_name = $defaultjournalname;
    }
}

$contact_name = '';
while (empty($contact_name)) {
    echo PHP_EOL;
    echo 'What is the name of the principal contact for the journal or typesetting site?' . PHP_EOL;
    $defaultcontactname = 'Journal Contact';
    $contact_name = readline('[' . $defaultcontactname . ']: ');
    if (empty($contact_name)) {
        $contact_name = $defaultcontactname;
    }
}

$contact_email = '';
while (empty($contact_email)) {
    echo PHP_EOL;
    echo 'What is the contact’s email address?' . PHP_EOL;
    $defaultcontactemail = 'journalcontact@journalsite.net';
    $contact_email = readline('[' . $defaultcontactemail . ']: ');
    if (empty($contact_email)) {
        $contact_email = $defaultcontactemail;
    }
}

$document_class = '';
while (empty($document_class)) {
    echo PHP_EOL;
    echo 'What LaTeX document class is default for the journal?' . PHP_EOL;
    $defaultdc = 'article';
    $document_class = readline('[' . $defaultdc . ']: ');
    if (empty($document_class)) {
        $document_class = $defaultdc;
    }
    if (substr($document_class, -4, 4) == '.cls') {
        $document_class = substr($document_class,0,-4);
    }
}

$timezone = '';
while (empty($timezone)) {
    echo PHP_EOL;
    echo 'What is the Region/Timezone of the journal?' . PHP_EOL;
    $timezone = readline('[America/New_York]: ');
    if (empty($timezone)) {
        $timezone = 'America/New_York';
    }
}

$settings = new StdClass();
$settings->journal_name = $journal_name;
$settings->contact_name = $contact_name;
$settings->contact_email = $contact_email;
$settings->datafolder = $datadir;
$settings->document_class = $document_class;
$settings->timezone = $timezone;

$result = file_put_contents('jtsettings.json', json_encode($settings, JSON_PRETTY_PRINT));
if ($result === false) {
    exit("Could not save settings file. Exiting.". PHP_EOL);
}
echo PHP_EOL;
echo 'Settings file jtsettings.json created; modifications may be made there if need be.';
echo PHP_EOL;

chdir($datadir);
if (file_exists('users.json')) {
    echo 'Users file already exists. Not creating a new user.' . PHP_EOL;
} else {

    $users = new StdClass();

    $firstusername = '';
    while (empty($firstusername)) {
        echo PHP_EOL;
        echo "Enter a username for " . $contact_name . "." . PHP_EOL;
        $default_user_name = explode('@', $contact_email)[0];
        $firstusername = readline("[" . $default_user_name . "]: ");
        if (empty($firstusername)) {
            $firstusername = $default_user_name;
        }
    }

    require_once 'libjt.php';
    $newpassword = generate_password(12);
    $newuser = new StdClass();
    $newuser->name = $contact_name;
    $newuser->email = $contact_email;
    $newuser->passwordhash =  password_hash($newpassword, PASSWORD_DEFAULT);
    $users->{$firstusername} = $newuser;

    $result = file_put_contents('users.json', json_encode($users, JSON_PRETTY_PRINT));
    if ($result === false) {
        exit("Could not create users file in data folder. Exiting.". PHP_EOL);
    }
    echo PHP_EOL;
    echo 'An initial user file was created:' . PHP_EOL;
    echo PHP_EOL;
    echo 'Username: ' . $firstusername . PHP_EOL;
    echo 'Name: ' . $contact_name . PHP_EOL;
    echo 'Email: ' . $contact_email . PHP_EOL;
    echo 'Password: ' . $newpassword . PHP_EOL;
    echo PHP_EOL;
    echo 'This user should now be able to log in to the framework, change their password, invite others, etc.' . PHP_EOL;
    echo PHP_EOL;
}
echo 'Delete this script for security?' . PHP_EOL;
$answer = '';
while ((strtolower(substr($answer,0,1)) != 'n') && (strtolower(substr($answer,0,1)) != 'y')) {
    $answer = readline('[yes/no]: ');
}
if (strtolower(substr($answer,0,1)) == 'y') {
    unlink(__FILE__);
}

exit(0);