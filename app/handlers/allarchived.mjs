// LICENSE: GNU GPL v3 You should have received a copy of the GNU General
// Public License along with this program. If not, see
// https://www.gnu.org/licenses/.

// File: allarchived.mjs
// handler that responds to requests for all archived assignments by
// calling getassignments.php with archive set to true

import getassignments from './getassignments.mjs';

export default async function allarchived(reqbody) {
  return await getassignments(reqbody, true);
}
