// LICENSE: GNU GPL v3 You should have received a copy of the GNU General
// Public License along with this program. If not, see
// https://www.gnu.org/licenses/.

// File: libpp.php
// functions for interacting with the philpapers database

import {execSync} from 'node:child_process';

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
  const url = 'https://philpapers.org/formats/item.bib?id=' + id;
  let bibtext = null;
  try {
    const response = await fetch(url);
    bibtext = await response.text();
  } catch(err) {
    return null;
  }
  return bibFix(bibtext);
}
