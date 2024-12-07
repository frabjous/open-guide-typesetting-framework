// LICENSE: GNU GPL v3 You should have received a copy of the GNU General
// Public License along with this program. If not, see
// https://www.gnu.org/licenses/.

// File: archive.mjs
// handles request to archive or unarchive a typesetting assignment

import {verifyJSONRequest} from '../libauthentication.mjs';
import {getAssignmentDir} from '../libassignments.mjs';
import path from 'node:path';
import fs from '../fs.mjs';

export default async function archive(reqbody) {
  const verify = verifyJSONRequest(reqbody);
  if (verify?.error) return verify;
  for (const fld of ["makearchived", "assignmentId", "assignmentType"]) {
    if (!(fld in reqbody)) return {
      error: true,
      errMsg: 'Inadequate information supplied to handle archiving request'
    }
  }
  const {makearchived, assignmentId, assignmentType, project} = reqbody;
  const assigndir = getAssignmentDir(
    project, assignmentType, assignmentId, false
  );
  if (!assigndir) return {
    error: true,
    errMsg: 'Could not find the document/assignment directory.'
  }
  const archiveFile = path.join(assigndir, 'archived');
  const archivedNow = fs.isfile(archiveFile);
  if (makearchived && !archivedNow) {
    if (!fs.savefile(archiveFile,'')) return {
      error: true,
      errMsg: 'Could not make archived. Please inform your site administrator.'
    }
  }
  if (!makearchived && archivedNow) {
    if (!fs.rm(archiveFile)) return {
      error: true,
      errMsg: 'Could not unarchive. Please inform your site administrator.'
    }
  }
  return {error: false, success: true}
}
