// LICENSE: GNU GPL v3 You should have received a copy of the GNU General
// Public License along with this program. If not, see
// https://www.gnu.org/licenses/.

// File: logout.mjs
// handler that deals with logout requests;
// the router itself handles the session cookie changes

import {removeAccessKey} from '../libauthentication.mjs';

export default async function logout(reqbody) {
  const {username, project, accesskey} = reqbody;
  if (!username || !project || !accesskey) return true;
  return removeAccessKey(project, username, accesskey);
}
