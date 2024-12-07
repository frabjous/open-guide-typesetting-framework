// LICENSE: GNU GPL v3 You should have received a copy of the GNU General
// Public License along with this program. If not, see
// https://www.gnu.org/licenses/.

// File: ogeRedirect.mjs
// Checks credentials, then creates an OGE session and returns the link to it

import {verifyByAccesskey} from './libauthentication.mjs';
import {getAssignmentDir} from './libassignments.mjs';
import path from 'node:path';

export default async function(query) {
  const { project, username, accesskey,
    assignmentType, assignmentId, filename } = query;
  if (!project || !username || !accesskey || !assignmentType ||
      !assignmentId || !filename) return null;
  if (!verifyByAccesskey(project, username, accesskey)) return null;
  if (!process.__ogedirname) return null;
  const ogeSessionLibrary = path.join(
    process.__ogedirname, 'app', 'sessions.mjs'
  );
  const assigndir = getAssignmentDir(project, assignmentType, assignmentId);
  try {
    const imported = await import(ogeSessionLibrary);
    const makeSession = imported.makeSession;
    const sess = makeSession({
      dir: assigndir,
      files: [filename],
      user: username
    });
    if (!sess?.sessionid) return null;
    return `/oge/${sess.sessionid}`;
  } catch(err) {
    return null;
  }
  return null;
}