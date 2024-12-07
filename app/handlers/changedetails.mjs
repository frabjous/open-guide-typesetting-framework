// LICENSE: GNU GPL v3 You should have received a copy of the GNU General
// Public License along with this program. If not, see
// https://www.gnu.org/licenses/.

// File: changedetails.mjs
// handler that responds to request to change details for user

import {setUserDetails, verifyJSONRequest} from '../libauthentication.mjs';

export default async function changedetails(reqbody) {
  const verify = verifyJSONRequest(reqbody);
  if (verify?.error) return verify;
  let {detailsemail, detailsname, project, username} = reqbody;
  if (!detailsemail || !detailsname || !username) return {
    error: true, errMsg: 'No new email, name or username given.'
  }
  detailsemail = detailsemail.toLowerCase();
  return {
    success: setUserDetails(project, username, detailsname, detailsemail)
  }
}