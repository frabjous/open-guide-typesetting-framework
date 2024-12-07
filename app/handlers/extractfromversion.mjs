// LICENSE: GNU GPL v3 You should have received a copy of the GNU General
// Public License along with this program. If not, see
// https://www.gnu.org/licenses/.

// File: extractfromversion.mjs
// handler that extracts certain info from text files saved for a version

import {verifyJSONRequest} from '../libauthentication.mjs';
import {getAssignmentDir} from '../libassignments.mjs';
import path from 'node:path';
import fs from '../fs.mjs';

export default async function extractfromversion(reqbody) {
  const verify = verifyJSONRequest(reqbody);
  if (verify?.error) return verify;
  const {assignmentId, assignmentType, project, version} = reqbody;
  if (!assignmentId || !assignmentType || !version) return {
    error: true,
    errMsg: 'Insufficient information provided to identify version.'
  }
  const assigndir = getAssignmentDir(
    project, assignmentType, assignmentId, false
  );
  if (!assigndir) return {
    error: true,
    errMsg: 'Unable to find directory for document.'
  }
  const editionsdir = path.join(assigndir, 'editions');
  const versiondir = path.join(editionsdir, version);
  if (!fs.isdir(versiondir)) return {
    error: true,
    errMsg: 'Unable to find version specified.'
  }
  const extractos = {};
  const files = fs.filesin(versiondir).filter(
    (f) => (path.extname(f) === '.txt')
  );
  for (const file of files) {
    const bn = path.basename(file, '.txt');
    extractos[bn] = fs.readfile(file);
  }
  return {
    success: true,
    error: false,
    extractos
  }
}

