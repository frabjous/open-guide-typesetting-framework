// LICENSE: GNU GPL v3 You should have received a copy of the GNU General
// Public License along with this program. If not, see
// https://www.gnu.org/licenses/.

// File: allusers.mjs
// handler that responds to request to see names and addresses of users

import {loadUsers, verifyJSONRequest} from '../libauthentication.mjs';

export default async function allusers(reqbody) {
  const verify = verifyJSONRequest(reqbody);
  if (verify?.error) return verify;
  const project = reqbody.project;
  const users = loadUsers(project);
  for (const username in users) {
    for (const k of ['passwordhash', 'keylist', 'newpwdlinks']) {
      delete users[username][k];
    }
  }
  return {usersinfo: users}
}

