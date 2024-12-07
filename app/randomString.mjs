// LICENSE: GNU GPL v3 You should have received a copy of the GNU General
// Public License along with this program. If not, see
// https://www.gnu.org/licenses/.

// File: randomString.mjs
// Function for creating random strings, with various options

const uppercase = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
const lowercase = 'abcdefghijklmnopqrstuvwxyz';
const digits = '0123456789';
const punctchars = '~!@#$%^&*_+=?,.:;"';

function strhas(str, charset) {
  for (const ch of charset) {
    if (str.includes(ch)) { return true; }
  }
  return false;
}

function randomFrom(charlist, n = 16, usePunct = false) {
  let rv = '';
  while (rv.length < n) {
    rv+=charlist[ Math.floor(Math.random() * charlist.length) ];
  }
  return rv;
}

function passesTests(str, usePunct) {
  if (!(
    strhas(str, uppercase) &&
    strhas(str, lowercase) &&
    strhas(str, digits)
  )) return false;
  return ((usePunct) ? strhas(str,punctchars) : true);
}

export default function randomString(n = 16, usePunct = false) {
  let rv = '';
  const charsToUse = lowercase + uppercase + digits +
    ((usePunct) ? punctchars : '');
  do {
    rv = randomFrom(charsToUse, n, usePunct);
  } while (!passesTests(rv, usePunct));
  return rv;
}

