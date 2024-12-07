#!/usr/bin/env node

// LICENSE: GNU GPL v3 You should have received a copy of the GNU General
// Public License along with this program. If not, see
// https://www.gnu.org/licenses/.

// File: newproject.mjs
// cli tool for setting up site and creating a first project

import {execSync} from 'node:child_process';
import readline from 'node:readline/promises';
import {fileURLToPath} from 'node:url';
import path from 'node:path';
import fs from '../app/fs.mjs';
import randomString from '../app/randomString.mjs';

// get script directory
const __scriptfilename = fileURLToPath(import.meta.url);
const __scriptdirname = path.dirname(__scriptfilename);
const maindir = path.dirname(__scriptdirname);
const ogedir = path.join(maindir, 'open-guide-editor');

const rl = readline.createInterface({
  input: process.stdin,
  output: process.stdout,
});

function ragequit(msg) {
  console.error(msg);
  process.exit(1);
}

async function runOrQuit(cmd, cwd) {
  let output = '';
  try {
    output = await execSync(cmd, {
      encoding: 'utf-8',
      cwd: cwd,
      input: ''
    });
  } catch(err) {
    ragequit(`Error running: ${cmd}\n${err.toString()}\n${err.stderr}`);
  }
  return output;
}

console.log(`Welcome to the typesetting framework new project set up script.
Note: git and npm should be installed before proceeding
or the script may malfunction.`);


// install oge
if (!fs.isfile(
  path.join('open-guide-editor', 'ogeRouter.mjs'))) {
  console.log('Installing open guide editor ...');
  await runOrQuit('git clone --depth 1 https://github.com/frabjous' +
    '/open-guide-editor.git', maindir);
  console.log('Installed.')
}

// install oge node packages
if (!fs.isdir(path.join(ogedir, "node_modules"))) {
  console.log('Installing open guide editor node packages ... (may take awhile)');
  await runOrQuit('npm install', ogedir);
  console.log('Installed.');
}

// bundle oge dependencies
if (!fs.isfile(path.join(ogedir, 'public', 'js', 'editor.mjs'))) {
  console.log('Building editor packages for browser ...');
  await runOrQuit('npm run build', ogedir);
  console.log('Done.')
}

// Installing own dependencies
if (!fs.isdir(path.join(maindir, 'node_modules'))) {
  console.log('Installing node dependencies ... (may take awhile)');
  await runOrQuit('npm install', maindir);
  console.log('Installed.');
}

const datadir = process?.env?.OGTFDATALOCATION ??
  path.join(process.env.HOME, 'data', 'ogtf');

if (!fs.ensuredir(datadir)) {
  console.error('Could not find or create ogtf data directory.');
  process.exit(1);
}

console.log(`\nData directory is: ${datadir}.\n`);

let project = '';
let projectnameok = false;
while (!projectnameok) {
  project = await rl.question('New (short) project name: ');
  projectnameok = (/^[0-9a-z]+$/i.test(project));
  if (!projectnameok) {
    console.log('Project names should have only letters and numerals.');
    continue;
  }
  projectnameok = (!fs.isdir(path.join(datadir, project)));
  if (!projectnameok) {
    console.log('Project with that name aleady exists.');
  }
}

let projecttitle = '';
while (projecttitle == '') {
  projecttitle = await rl.question('Full project title: ');
}

let contact = '';
while (contact == '') {
  contact = await rl.question('Full name of contact person for project: ');
}

let email = '';
while (!email.includes('@')) {
  email = await rl.question('Contact person email address: ');
}

const projectdir = path.join(datadir, project);

if (!fs.ensuredir(projectdir)) {
  ragequit('Could not create directory for project.');
}

const projSettings = fs.loadjson(
  path.join(maindir, 'sample', 'sample-project-settings-journal.json')
);

if (!projSettings) {
  ragequit('Could not find or read sample project settings.');
}

projSettings.title = projecttitle;
projSettings.contactname = contact;
projSettings.contactemail = email;

const projsettingsfile = path.join(projectdir, 'project-settings.json');

if (!fs.savejson(projsettingsfile, projSettings, true)) {
  ragequit('Unable to save settings for project.');
}

const password = randomString(12);
const users = {};
const username = email.replace(/@.*/, '')
  .toLowerCase().replace(/[^a-z0-9]/gi,'');

// note we load libauthenatication dynamically since its
// dependencies may not have already been installed

let passwordHash = null;

try {
  const imported = await import('../app/libauthentication.mjs');
  passwordHash = imported?.passwordHash;
} catch(err) {
  ragequit('Unable to read authentication library.');
}

if (!passwordHash) {
  rageQuit('Unable to set function for saving password hashes.');
}

users[username] = {
  name: contact,
  email: email,
  passwordhash: passwordHash(password)
}

const usersfile = path.join(projectdir, 'users.json');
if (!fs.savejson(usersfile, users)) {
  ragequit('Unable to save users file.');
}

console.log(`
Project ${project} created.

Username: ${username}
Password: ${password}

You should now be able to log in to the typesetting framework (once started),
with the account info given above.

The password may be changed on site.

The sample settings file has been copied into the project’s data directory, at
${projsettingsfile}
where it may be customized for the project.

Script complete.
`);

process.exit(0);
