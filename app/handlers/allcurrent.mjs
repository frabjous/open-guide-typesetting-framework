// LICENSE: GNU GPL v3 You should have received a copy of the GNU General
// Public License along with this program. If not, see
// https://www.gnu.org/licenses/.

// File: allcurrent.mjs
// handler that responds to requests for all current assignments by
// calling getassignments.mjs with archive set to false

import getassignments from './getassignments.mjs';

export default async function allcurrent(reqbody) {
  return await getassignments(reqbody, false);
}
