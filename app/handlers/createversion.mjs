// LICENSE: GNU GPL v3 You should have received a copy of the GNU General
// Public License along with this program. If not, see
// https://www.gnu.org/licenses/.

// File: createversion.mjs
// handler that responds to request to create new edition/version for
// publication

import {verifyJSONRequest} from '../libauthentication.mjs';
import {getAssignmentDir} from '../libassignments.mjs';
import {getProjectDir, getProjectSettings} from '../projects.mjs';
import path from 'node:path';
import {execSync} from 'node:child_process';
import fs from '../fs.mjs';

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

export default async function createversion(reqbody) {
  const verify = verifyJSONRequest(reqbody);
  if (verify?.error) return verify;

  const {assignmentId, assignmentType, project, version} = reqbody;
  if (!assignmentId || !assignmentType || !version) return {
    error: true,
    errMsg: 'Insufficient information provided to create publication version.'
  }

  const assigndir = getAssignmentDir(
    project, assignmentType, assignmentId, false
  );
  if (!assigndir) return {
    error: true,
    errMsg: 'Unable to find document directory.'
  }

  const editionsdir = path.join(assigndir, 'editions');
  const versiondir = path.join(editionsdir, version);

  if (fs.isdir(versiondir)) return {
    error: true,
    errMsg: 'An edition with that version number already exists.'
  }
  if (!fs.ensuredir(versiondir)) return {
    error: true,
    errMsg: 'Unable to create directory for publication version. ' +
      'Please contact your site administrator.'
  }
  const projectDir = getProjectDir(project);
  const projSettings = getProjectSettings(project);
  const createInstructions = projSettings?.assignmentTypes?.[assignmentType]
    ?.createEdition;
  if (!createInstructions) return {
    error: true,
    errMsg: 'Project is not configured to create editions. ' +
      'Please check your project-settings.json file.'
  }
  const versioninfo = {
    files: []
  }
  for (const instruction of createInstructions) {
    let command = "true";
    if (instruction?.command) {
      command = instruction.command
        .replaceAll('%projectdir%', `"${projectDir}"`)
        .replaceAll('%documentid%', assignmentId)
        .replaceAll('%version%', version)
    }
    const ires = runcmd(command, assigndir);
    if (ires.returnvalue !== 0) return {
      error: true,
      errMsg: 'Error in version creation: ' +
        ((instruction?.outputfile)
        ? `(${instruction.outputfile})`
        : '') + ': ' + ires.stderr
    }
    if (instruction?.outputfile) {
      const outputfile = instruction.outputfile
        .replaceAll('%projectdir%', `"${projectDir}"`)
        .replaceAll('%documentid%', assignmentId)
        .replaceAll('%version%', version);
      const fulloutputfile = path.join(assigndir, outputfile);
      if (fs.isfile(fulloutputfile)) {
        const versionfile = path.join(versiondir, outputfile);
        if (!fs.mv(fulloutputfile, versionfile)) return {
          error: true,
          errMsg: 'Could not move an output file into the ' +
            'version directory. Please contact your site administrator.'
        }
        versioninfo.files.push(outputfile)
      }
    }
  }
  const ts = Date.now();
  versioninfo.creationtime = ts;
  return {
    error: false,
    success: true,
    versioninfo
  }
}
