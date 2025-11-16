// LICENSE: GNU GPL v3 You should have received a copy of the GNU General
// Public License along with this program. If not, see
// https://www.gnu.org/licenses/.

// File: savebibliography.mjs
// handler that responds to request to save bibliography for document

import {verifyJSONRequest} from '../libauthentication.mjs';
import {getAssignmentDir} from '../libassignments.mjs';
import fs from '../fs.mjs';
import path from 'node:path';

export default async function savebibliography(reqbody) {
  const verify = verifyJSONRequest(reqbody);
  if (verify?.error) return verify;
  const {assignmentId, assignmentType, bibdata, project} = reqbody;
  if (!assignmentId || !assignmentType || !bibdata) return {
    error: true,
    erMsg: 'Insufficient information provided to identify assignment.'
  }
  const assigndir = getAssignmentDir(
    project, assignmentType, assignmentId, true
  );
  if (!assigndir) return {
    error: true,
    errMsg: 'Unable to find or create directory for document.'
  }
  const fullBibInfoFile = path.join(assigndir, 'all-bibinfo.json');
  if (!fs.savejson(fullBibInfoFile, bibdata)) return {
    error: true,
    errMsg: 'Unable to save bibliography data. Please contact ' +
      'your site administrator.'
  }
  const cslarray = [];
  for (const id in bibdata) {
    const bibentry = bibdata[id];
    for (const goner of [
      'abbreviation',
      'possibilities',
      'philpapersid',
      'extractedfrom',
      'collapsed'
    ]) {
      delete bibentry[goner];
    }
    cslarray.push(bibentry);
  }
  const cslbibfile = path.join(assigndir, 'bibliography.json');
  if (!fs.savejson(cslbibfile, cslarray)) return {
    error: true,
    errMsg: 'Unable to save bibliography. Please contact your ' +
      'site administrator.'
  }
  return {
    biblastsaved: Date.now(),
    success: true,
    error: false
  }
}