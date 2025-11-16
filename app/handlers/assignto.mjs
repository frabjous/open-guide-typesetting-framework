// LICENSE: GNU GPL v3 You should have received a copy of the GNU General
// Public License along with this program. If not, see
// https://www.gnu.org/licenses/.

// File: archive.mjs
// handles request to archive or unarchive a typesetting assignment

import {verifyJSONRequest} from '../libauthentication.mjs';
import {getAssignmentDir} from '../libassignments.mjs';
import path from 'node:path';
import fs from '../fs.mjs';

export default async function assignto(reqbody) {
  const verify = verifyJSONRequest(reqbody);
  if (verify?.error) return verify;
  const {project, assignmentType, assignmentId, towhom} = reqbody;
  if (!project || !assignmentType || !assignmentId || !towhom) return {
    error: true,
    errMsg: 'Inadequate information supplied to handle reassignment.'
  }
  const assigndir = getAssignmentDir(
    project, assignmentType, assignmentId, true
  );
  const assignedfile = path.join(assigndir, 'assignedto.txt');
  if (!fs.savefile(assignedfile, towhom)) return {
    error: true,
    errMsg: 'Unable to save assignment file.'
  }
  return {success: true}
}
