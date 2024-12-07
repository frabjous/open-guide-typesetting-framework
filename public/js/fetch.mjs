// LICENSE: GNU GPL v3 You should have received a copy of the GNU General
// Public License along with this program. If not, see 
// https://www.gnu.org/licenses/.

// File: fetch.mjs
// A simple function for making json post requests to the server and
// handling the results

export default async function postData(url = "", data = {}) {
  const rv = {};
  try {
    const response = await fetch(url, {
      method: "POST",
      cache: "no-cache",
      credentials: "same-origin",
      headers: {
        "Content-Type": "application/json",
      },
      redirect: "follow",
      referrerPolicy: "no-referrer",
      body: JSON.stringify(data),
    });
    rv.respObj = await response.json();
    rv.error = false;
  } catch(err) {
    rv.error = true;
    rv.errMsg = 'Unable to fetch data from server. ' +
      err.toString();
  }
  return rv;
}
