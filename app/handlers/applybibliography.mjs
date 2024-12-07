// LICENSE: GNU GPL v3 You should have received a copy of the GNU General
// Public License along with this program. If not, see
// https://www.gnu.org/licenses/.

// File: applybibliography.mjs
// handler that responds to request to apply a bibliography to a file,
// which means trying to identify and fix its citations

import {verifyJSONRequest} from '../libauthentication.mjs';
import {getAssignmentDir} from '../libassignments.mjs';
import {applyAllBibdata} from '../libdocument.mjs'
import path from 'node:path';
import fs from '../fs.mjs';

export default async function applybibliography(reqbody) {
  const verify = verifyJSONRequest(reqbody);
  if (verify?.error) return verify;
  const {assignmentId, assignmentType, project} = reqbody;
  if (!assignmentId || !assignmentType) return {
    error: true,
    errMsg: 'Insufficient information provided to identify assignment.'
  }
  const assigndir = getAssignmentDir(
    project, assignmentType, assignmentId, true
  );
  if (!assigndir) return {
    error: true,
    errMsg: 'Unable to find or create directory for document.'
  }
  const fullBiblioInfoFile = path.join(assigndir, 'all-bibinfo.json');
  const bibdata = fs.loadjson(fullBiblioInfoFile);
  if (!bibdata) return {
    error: true,
    errMsg: 'Unable to read bibliography info file.'
  }
  const mainfile = path.join(assigndir, 'main.md');
  let mainmd = fs.readfile(mainfile);
  if (!mainmd) return {
    error: true,
    errMsg: 'Unable to read main file.'
  }
  mainmd = applyAllBibdata(mainmd, bibdata);
  if (!fs.savefile(mainfile, mainmd)) return {
    error: true,
    errMsg: 'Unable to save modified document.'
  }
  const biblastappliedfile = path.join(assigndir, 'biblastapplied');
  fs.savefile(biblastappliedfile, '');
  return {
    biblastapplied: Date.now(),
    success: true,
    error: false
  }
}
