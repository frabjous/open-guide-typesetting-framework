// LICENSE: GNU GPL v3 You should have received a copy of the GNU General
// Public License along with this program. If not, see
// https://www.gnu.org/licenses/.

// File: projects.mjs
// Exports functions for dealing with projects and their settings

import path from 'node:path';
import fs from './fs.mjs';
import randomString from './randomString.mjs';

const datadir = process?.env?.OGTFDATALOCATION ??
  path.join(process.env.HOME, 'data', 'ogtf');

if (!fs.ensuredir(datadir)) {
  console.error('Could not find or create ogtf data directory.');
  process.exit(1);
}

process.ogtfdatadir = datadir;
export {datadir};

export function getProjects() {
  const projects = {};
  const dirs = fs.subdirs(datadir);
  for (const dir of dirs) {
    const projsettingsfile = path.join(dir, 'project-settings.json');
    const projname = path.basename(dir);
    const settings = fs.loadjson(projsettingsfile);
    if (!settings) continue;
    projects[projname] = settings;
  }
  return projects;
}

export function getProjectSettings(project) {
  const dir = getProjectDir(project);
  const settingsfile = path.join(dir, 'project-settings.json');
  return fs.loadjson(settingsfile);
}

export function getProjectDir(project) {
  return path.join(datadir, project);
}

// determine project list for injecting into HTML
const startProjs = getProjects();
let projectlisthtml = '';
for (const projname in startProjs) {
  const project = startProjs[projname];
  const title = project.title;
  projectlisthtml += `<li><a href="javascript:ogtf.chooseproject('${projname}');">${title}</a></li>`;
}
export {projectlisthtml};

// determine cookiesecret
const cookiefile = path.join(datadir, '.cookiesecret');
if (!fs.isfile(cookiefile)) {
  fs.savefile(cookiefile, randomString(16));
}
const cookiesecret = fs.readfile(cookiefile);
export {cookiesecret};