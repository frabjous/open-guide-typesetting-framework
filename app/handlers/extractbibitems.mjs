// LICENSE: GNU GPL v3 You should have received a copy of the GNU General
// Public License along with this program. If not, see
// https://www.gnu.org/licenses/.

// File: extractbibitems.php
// handler that responds to request to get extracted bib items

import {verifyJSONRequest} from '../libauthentication.mjs';
import {getAssignmentDir} from '../libassignments.mjs';
import {fixAnyStyle} from '../anystyle.mjs';
import path from 'node:path';
import fs from '../fs.mjs';
import {execSync} from 'node:child_process';

// add ruby gems to path if not there already, for anystyle
if (!process.env.PATH.includes('ruby')) {
  const rubydir = path.join(
    process.env.HOME, '.local', 'share', 'gem', 'ruby'
  );
  const rubysubdirs = fs.subdirs(rubydir);
  for (const rsd of rubysubdirs) {
    const bindir = path.join(rsd, 'bin');
    if (fs.isdir(bindir)) {
      process.env.PATH = process.env.PATH + ':' + bindir;
    }
  }
}

export default async function extractbibitems(reqbody) {
  const verify = verifyJSONRequest(reqbody);
  if (verify?.error) return verify;
  const {project, assignmentId, assignmentType} = reqbody;
  if (!assignmentId || !assignmentType) return {
    error: true,
    errMsg: 'Insufficient information provided to identify assignment.'
  }
  const assigndir = getAssignmentDir(
    project, assignmentType, assignmentId, false
  );
  if (!assigndir) return {
    error: true,
    errMsg: 'Unable to find directory of document.'
  }
  const lastextractedfile = path.join(assigndir, 'biblastextracted');
  fs.savefile(lastextractedfile, '');
  const extractedBibfile = path.join(assigndir, 'extracted-bib.txt');
  if (!fs.isfile(extractedBibfile)) return {
    bibitems: [],
    additions: [],
    success: true,
    error: false
  }
  const extBibfileContents = fs.readfile(extractedBibfile);
  const plainEntries = extBibfileContents.split('\n').filter(
    (e) => (/[a-z]/.test(e))
  );
  let anystyleitems = [];
  try {
    const anystylejson = execSync(
      "anystyle --stdout -f csl parse extracted-bib.txt",
      {
        encoding: 'utf-8',
        cwd: assigndir
      }
    );
    anystyleitems = JSON.parse(anystylejson);
  } catch(err) {
    return {
      bibitems: plainEntries,
      additions: [],
      success: true,
      error: false
    }
  }
  if (!Array.isArray(anystyleitems) ||
      anystyleitems.length == 0) return {
    bibitems: plainEntrees,
    additions: [],
    success: true,
    error: false
  }
  const anystyleAdditions = fixAnyStyle(anystyleitems);
  if (anystyleAdditions.length == plainEntries.length) {
    for (let i = 0; i < plainEntries.length; i++) {
      anystyleAdditions[i].extractedfrom = plainEntries[i];
    }
  }
  return {
    bibitems: plainEntries,
    success: true,
    error: false,
    additions: anystyleAdditions
  }
}
