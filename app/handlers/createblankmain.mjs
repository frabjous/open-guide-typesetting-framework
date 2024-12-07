// LICENSE: GNU GPL v3 You should have received a copy of the GNU General
// Public License along with this program. If not, see
// https://www.gnu.org/licenses/.

// File: createblankmain.mjs
// handler that responds to request to create blank main file

import {verifyJSONRequest} from '../libauthentication.mjs';
import {createOgeSettings, getAssignmentDir} from '../libassignments.mjs';
import fs from '../fs.mjs';
import path from 'node:path';

export default async function createblankmain(reqbody) {
  const verify = verifyJSONRequest(reqbody);
  if (verify?.error) return verify;
  const {project, assignmentId, assignmentType} = reqbody;
  if (!assignmentId || !assignmentType) return {
    error: true, errMsg: 'Insufficient information provided to create file.'
  }
  const assigndir = getAssignmentDir(project, assignmentType, assignmentId);
  if (!assigndir) return {
    error: true,
    errMsg: 'Unable to find or create directory for document. ' +
      'Contact your site administrator.'
  }
  const mainfile = path.join(assigndir, 'main.md');
  if (fs.isfile(mainfile)) return {
    success: true,
    error: false
  }
  if (!fs.savefile(mainfile, '')) return {
    error: true,
    errMsg: 'Could not create blank file. Please contact your ' +
      'site administrator.'
  }
  const ogeSave = createOgeSettings(project, assignmentType, assignmentId);
  if (!ogeSave) return {
    error: true,
    errMsg: 'Could not create settings for hte open guide editor. ' +
      'Please contact your site administrator.'
  }
  return {success: true, error: false};
}
