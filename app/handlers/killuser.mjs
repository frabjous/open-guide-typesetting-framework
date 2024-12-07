// LICENSE: GNU GPL v3 You should have received a copy of the GNU General
// Public License along with this program. If not, see
// https://www.gnu.org/licenses/.

// File: killuser.mjs
// handler that responds to request to remove a user from the site

import {removeUser, verifyJSONRequest} from '../libauthentication.mjs';

export default async function killuser(reqbody) {
  const verify = verifyJSONRequest(reqbody);
  if (verify?.error) return verify;
  const project = reqbody.project;
  const usertodie = reqbody?.usertodie;
  if (!usertodie) return {
    error: true,
    errMsg: 'User to be removed not specified.'
  }
  return {
    success: removeUser(project, usertodie)
  }
}