// LICENSE: GNU GPL v3 You should have received a copy of the GNU General
// Public License along with this program. If not, see
// https://www.gnu.org/licenses/.

// ppdata.mjs
// handler that responds to request get PhilPapers data for an id

import {verifyJSONRequest} from '../libauthentication.mjs';
import {idToObj} from '../libpp.mjs';

export default async function savebibliography(reqbody) {
  const verify = verifyJSONRequest(reqbody);
  if (verify?.error) return verify;
  const {philpapersid} = reqbody;
  if (!philpapersid) return {
    error: true,
    errMsg: 'PhilPapers ID not specified.'
  }
  const bibObj = await idToObj(philpapersid);
  return {
    success: true,
    error: false,
    data: bibObj
  }
}

