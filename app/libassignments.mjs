// LICENSE: GNU GPL v3 You should have received a copy of the GNU General
// Public License along with this program. If not, see
// https://www.gnu.org/licenses/.

// File: libassignments.mjs
// general functions for working with assignments in projects

import path from 'node:path';
import fs from './fs.mjs';
import {getProjectDir, getProjectSettings} from './projects.mjs';

export function getAssignmentDir(project, assignmentType, assignmentId, ensure = true) {
  const parentDir = getAssignmentTypeDir(project, assignmentType, ensure);
  if (!parentDir) return null;
  const dir = path.join(parentDir, assignmentId);
  if (!fs.isdir(dir)) {
    if (!ensure) return null;
    if (!fs.ensuredir(dir)) return null;
  }
  return dir;
}

export function getAssignmentTypeDir(project, assignmentType, ensure = true) {
  const projdir = getProjectDir(project);
  const dir = path.join(projdir, assignmentType + 's');
  if (!fs.isdir(dir)) {
    if (!ensure) return null;
    if (!fs.ensuredir(dir)) return null;
  }
  return dir;
}

export function createOgeSettings(project, assignmentType, assignmentId) {
  const projectdir = getProjectDir(project);
  const projectsettings = getProjectSettings(project);
  const assignmentDir =
    getAssignmentDir(project, assignmentType, assignmentId, true);
  if (!assignmentDir) return false;
  const ogesettings = {};
  ogesettings.rootdocument = 'main.md';
  ogesettings.bibliographies = ['bibliography.json'];
  ogesettings.routines = {md: {}};
  const outputinfo = projectsettings?.assignmentTypes?.[assignmentType]?.output
    ?? {};
  for (const outputext in outputinfo) {
    const extinfo = outputinfo[outputext];
    if (extinfo?.editorcommand) {
      ogesettings.routines.md[outputext] = {};
      let command = extinfo.editorcommand;
      command = command.replaceAll('%projectdir%',
        `"${projectdir}"`);
      ogesettings.routines.md[outputext].command = command;
    }
  }
  const ogesettingsfile = path.join(assignmentDir, 'oge-settings.json');
  return fs.savejson(ogesettingsfile, ogesettings);
}
