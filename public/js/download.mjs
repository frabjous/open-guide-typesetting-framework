// LICENSE: GNU GPL v3 You should have received a copy of the GNU General
// Public License along with this program. If not, see
// https://www.gnu.org/licenses/.

// download.mjs
// function downloadFile that creates a download link and clicks it for
// the user to initiate a file download

export default function downloadFile(url, filename = false) {
  // should be in browser context with a document
  if (!document) { return false; }
  // if no filename given, get from url
  if (filename === false) {
    const urlsplit = url.split('/');
    const fnpart = urlsplit[url.split.length - 1];
    filename = fnpart.split('?')[0];
  }
  // create link element with appropriate properties
  const link = document.createElement('a');
  link.href = url;
  link.download = filename;
  // add it to body, click it, then remove it
  document.body.appendChild(link);
  link.click();
  document.body.removeChild(link);
    return true;
}
