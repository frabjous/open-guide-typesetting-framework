// LICENSE: GNU GPL v3 You should have received a copy of the GNU General
// Public License along with this program. If not, see 
// https://www.gnu.org/licenses/.

// File: file-upload.mjs
// A simple function for making fetch requests for a file upload
// along with json-able data

export default async function uploadFiles(inputelem, url = "", data = {}) {
  const fD = new FormData();
  let ctr=0;
  for (const file of inputelem.files) {
    fD.append('files' + ctr.toString(), file);
    ctr++;
  }
  fD.append('requestjson', JSON.stringify(data));
  const rv = {};
  try {
    const response = await fetch(url, {
      method: "POST",
      cache: "no-cache",
      credentials: "same-origin",
      redirect: "follow",
      referrerPolicy: "no-referrer",
      body: fD
    });
    rv.respObj = await response.json();
    rv.error = false;
  } catch(err) {
    rv.error = true;
    rv.errMsg = 'Unable to process file upload. ' +
      err.toString();
  }
  return rv;
}
window.uploadFiles = uploadFiles;

