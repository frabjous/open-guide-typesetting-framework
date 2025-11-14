// LICENSE: GNU GPL v3 You should have received a copy of the GNU General
// Public License along with this program. If not, see
// https://www.gnu.org/licenses/.

// File: libpp.php
// functions for interacting with the philpapers database

import {execSync} from 'node:child_process';
import fs from './fs.mjs';
import {datadir} from './projects.mjs';
import path from 'node:path';

const goodUA = 'Mozilla/5.0 (X11; Linux x86_64; rv:144.0) Gecko/20100101 Firefox/144.0';

const ppapifile = path.join(datadir, 'ppapi.json');
let ppapiinfo = {};
if (fs.isfile(ppapifile)) {
  ppapiinfo = fs.loadjson(ppapifile);
}

// fix philpapers' annoying habit of sticking location inside
// and other small niceties
function bibFix(bib) {
  return bib.replace(
    /publisher = {([^}:]*): /,
    "address = {$1},\n    publisher = {"
  ).replace(/\n\s\s*/g, '\n    ')
  .replaceAll('?s', "'s")
}

function bibToJson(bib) {
  try {
    return execSync(
      'pandoc -f bibtex -t csljson',
      {
        encoding: 'utf-8',
        input: bib
      }
    );
  } catch(err) {
    return null;
  }
}

function bibToObj(bib) {
  try {
    return JSON.parse(bibToJson(bib)) ?? [];
  } catch(err) {
    return [];
  }
}

export async function idToObj(id) {
  const bib = await idToBib(id);
  if (!bib) return {};
  return bibToObj(bib);
}

async function idToBib(id) {
  let url = `https://philpapers.org/rec/${id}?format=bib`;
  if (ppapiinfo?.apiid && ppapiinfo?.apikey) {
    url += `&apiId=${ppapiinfo.apiid}&apiKey=${ppapiinfo.apikey}`
  }
  let cmd = `curl --silent -A "${goodUA}" "${url}"`
  let bibtext = null;
  try {
    bibtext = execSync(cmd, {encoding: 'utf-8'});
  } catch(err) {
    console.err(err, url)
    return null;
  }
  return bibFix(bibtext);
}
