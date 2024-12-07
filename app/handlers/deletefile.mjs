// LICENSE: GNU GPL v3 You should have received a copy of the GNU General
// Public License along with this program. If not, see
// https://www.gnu.org/licenses/.

// File: deletefile.mjs
// handles request to delete a file from a typesetting assignment

import {verifyJSONRequest} from '../libauthentication.mjs';
import {getAssignmentDir} from '../libassignments.mjs';
import path from 'node:path';
import fs from '../fs.mjs';

export default async function deletefile(reqbody) {
  const verify = verifyJSONRequest(reqbody);
  if (verify?.error) return verify;

  const {filetodelete, assignmentType, assignmentId, project} = reqbody;
  if (!filetodelete || !assignmentType || !assignmentId) return {
    error: true,
    errMsg: 'Inadequate information supplied to handle deletion request'
  }

  const assigndir = getAssignmentDir(
    project, assignmentType, assignmentId, false
  );
  if (!assigndir) return {
    error: true,
    errMsg: 'Cannot find document directory.'
  }

  const deletionFile = path.join(assigndir, filetodelete);
  if (fs.isfile(deletionFile)) {
    if (!fs.rm(deletionFile)) return {
      error: true,
      errMsg: 'Could not delete file. Please inform your ' +
        'site administrator.'
    }
  }

  return {error: false, success: true}
}
