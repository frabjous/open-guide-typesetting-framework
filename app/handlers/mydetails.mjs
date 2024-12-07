// LICENSE: GNU GPL v3 You should have received a copy of the GNU General
// Public License along with this program. If not, see
// https://www.gnu.org/licenses/.

// File: mydetails.mjs
// handler that responds to request to see details for user

import {loadUsers, verifyJSONRequest} from '../libauthentication.mjs';

export default async function mydetails(reqbody) {
  const verify = verifyJSONRequest(reqbody);
  if (verify?.error) return verify;
  const users = loadUsers(reqbody.project);
  const username = reqbody?.username;
  if (!username) return {
    error: true, errMsg: 'No username provided.'
  }
  if (!(username in users)) return {
    error: true, errMsg: 'Specified username does not exist.'
  }
  return {
    mydetails: users[username]
  }
}
