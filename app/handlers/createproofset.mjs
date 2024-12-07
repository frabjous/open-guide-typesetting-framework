// LICENSE: GNU GPL v3 You should have received a copy of the GNU General
// Public License along with this program. If not, see
// https://www.gnu.org/licenses/.

// File: createproofset.mjs
// handler that responds to request to create a proof set for a document

import {verifyJSONRequest} from '../libauthentication.mjs';
import {getAssignmentDir} from '../libassignments.mjs';
import path from 'node:path';
import fs from '../fs.mjs';
import randomString from '../randomString.mjs';
import {execSync} from 'node:child_process';

function fillvariables(cmd, swaps) {
  for (const variablename in swaps) {
    if (!variablename.startsWith('%') || !variablename.endsWith('%')) continue;
    cmd = cmd.replaceAll(variablename, `"${swaps[variablename]}"`)
  }
  return cmd;
}

function runcmd(cmd, dir) {
  try {
    const stdout = execSync(cmd, {
      cwd: dir,
      encoding: 'utf-8'
    });
    return {
      returnvalue: 0,
      stderr: '',
      stdout
    }
  } catch(err) {
    return {
      returnvalue: err.status,
      stderr: err.stderr,
      stdout: err.stdout
    }
  }
}

export default async function createproofset(reqbody) {
  const verify = verifyJSONRequest(reqbody);
  if (verify?.error) return verify;

  const {assignmentId, assignmentType, username, project} = reqbody;
  if (!assignmentId || !assignmentType || !username) return {
    error: true,
    errMsg: 'Insufficient information provided to identify assignment.'
  }
  const assigndir = getAssignmentDir(
    project, assignmentType, assignmentId, false
  );
  if (!assigndir) return {
    error: true,
    errMsg: 'Unable to find directory for document.'
  }
  const proofsdir = path.join(assigndir, 'proofs');
  if (!fs.ensuredir(proofsdir)) return {
    error: true,
    errMsg: 'Unable to find or create directory for proofs.'
  }
  const ogesettingsFile = path.join(assigndir, 'oge-settings.json');
  const ogesettings = fs.loadjson(ogesettingsFile);
  if (!ogesettings) return {
    error: true,
    errMsg: 'Could not load settings for editor for processing files.'
  }
  if (!ogesettings?.routines?.md) return {
    error: true,
    errMsg: 'No routines set for creating proofs. Check your ' +
      'oge-settings.json file.'
  }
  const ts = Date.now();
  const proofdir = path.join(proofsdir, ts.toString());
  if (!fs.ensuredir(proofdir)) return {
    error: true,
    errMsg: 'Could not create directory for proof set.'
  }
  const proofset = {
    settime: ts,
    outputfiles: []
  }
  for (const outext in ogesettings.routines.md) {
    const routine = ogesettings.routines.md[outext];

    // run command
    if (!routine?.command) continue;
    const swaps = {
      '%rootdocument%': 'main.md',
      '%savedfile%': 'main.md',
      '%outputfile%': 'main.' + outext
    }
    const cmd = fillvariables(routine.command, swaps);
    const cmdres = runcmd(cmd, assigndir);
    if (cmdres.returnvalue !== 0) return {
      error: true,
      errMsg: 'Error when processing markdown to ' + outext + ': ' +
        cmdres.stderr
    }

    // move into place
    const fulloutputfile = path.join(assigndir, 'main.' + outext);
    const prooffilename = path.join(proofdir, assignmentId + '.' + outext);
    if (!fs.cp(fulloutputfile, prooffilename)) return {
      error: true,
      errMsg: 'Could not copy output file into proofs directory.'
    }
    // convert to PDF pages
    if (outext == 'pdf' && fs.isfile(prooffilename)) {
      const pagesdir = path.join(proofdir, 'pages');
      if (!fs.ensuredir(pagesdir)) return {
        error: true,
        errMsg: 'Could not create directory for pdf pages.'
      }
      const pagefilepattern = path.join(pagesdir, 'page%02d.svg');
      const convresult = runcmd(
        `mutool draw -o "${pagefilepattern}" "${prooffilename}"`,
        assigndir
      );
      if (convresult.returnvalue != 0) return {
        error: true,
        errMsg: 'Could not convert pdf pages to images: ' +
          convresult.stderr
      }
    }
    proofset.outputfiles.push(path.basename(prooffilename));
  }
  // copy main file
  const mainfile = path.join(assigndir, 'main.md');
  const maincopy = path.join(proofdir, 'main-' + ts.toString() + '.md');
  if (!fs.cp(mainfile, maincopy)) return {
    error: true,
    errMsg: 'Unable to make a copy of the main file.'
  }

  // save keys
  const keyfile = path.join(process.ogtfdatadir, 'proofkeys.json');
  const keys = fs.loadjson(keyfile) ?? {};
  let ekey = null;
  do {
    ekey = randomString(24);
  } while (keys?.[ekey]);
  proofset.ekey = ekey;
  keys[ekey] = {
    proofset: ts.toString(),
    editor: true,
    project, username, assignmentId, assignmentType
  }
  let akey = null;
  do {
    akey = randomString(24);
  } while (keys?.[akey]);
  proofset.akey = akey;
  keys[akey] = {
    proofset: ts.toString(),
    project, username, assignmentId, assignmentType
  }
  if (!fs.savejson(keyfile, keys)) return {
    error: true,
    errMsg: 'Unable to save access keys for proofs.'
  }
  return {
    success: true,
    error: false,
    proofset
  }
}
