// LICENSE: GNU GPL v3 You should have received a copy of the GNU General
// Public License along with this program. If not, see
// https://www.gnu.org/licenses/.

// File: jsonhandler.mjs
// responds to json requests from the browser depending on request

export default async function jsonhandler(reqbody) {
  if (!reqbody?.postcmd) return {
    error: true,
    errMsg: 'No command given.'
  }
  if (!reqbody?.project) return {
    error: true,
    errMsg: 'No project specified.'
  }
  try {
    const imported = await import('./handlers/' + reqbody.postcmd + '.mjs');
    const handler = imported.default;
    const handlerres = await handler(reqbody);
    return handlerres;
  } catch(err) {
    console.error(err.stack);
    return {
      error: true,
      errMsg: err.toString()
    }
  }
  return null;
}
