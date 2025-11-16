// LICENSE: GNU GPL v3 You should have received a copy of the GNU General
// Public License along with this program. If not, see
// https://www.gnu.org/licenses/.

// File: assignmentinfo.mjs
// retrieves data about a particular assignment

import {verifyJSONRequest} from '../libauthentication.mjs';
import {getAssignment} from './getassignments.mjs'

export default async function getAssignmentInfo(reqbody, archive = false) {
  const verify = verifyJSONRequest(reqbody);
  if (verify?.error) return verify;
  const {project, assignmentId, assignmentType} = reqbody;
  if (!project || !assignmentId || !assignmentType) return {
    error: true,
    errMsg: 'Insufficient information provided to get assignment info.'
  }
  return getAssignment(project, assignmentType, assignmentId);
}