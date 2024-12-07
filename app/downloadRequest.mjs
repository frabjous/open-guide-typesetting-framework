// LICENSE: GNU GPL v3 You should have received a copy of the GNU General
// Public License along with this program. If not, see
// https://www.gnu.org/licenses/.

// File: downloadRequest.mjs
// Validates a request for downloading a file and determines its full filename

import {verifyByAccesskey} from './libauthentication.mjs';
import path from 'node:path';
import {getAssignmentDir} from './libassignments.mjs';
import fs from './fs.mjs';

export default function downloadRequest(query) {
  const {
    accesskey,
    assignmentId,
    assignmentType,
    filename,
    project,
    username
  } = query;
  if (
    !accesskey ||
    !assignmentId ||
    !assignmentType ||
    !filename ||
    !project ||
    !username
  ) return null;
  if (!verifyByAccesskey(project, username, accesskey)) {
    return null;
  }
  const assigndir = getAssignmentDir(
    project, assignmentType, assignmentId, false
  );
  if (!assigndir) return null;
  const ffn = path.join(assigndir, filename);
  if (!fs.isfile(ffn)) return null;
  return ffn;
}