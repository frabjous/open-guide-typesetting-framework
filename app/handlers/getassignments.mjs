// LICENSE: GNU GPL v3 You should have received a copy of the GNU General
// Public License along with this program. If not, see
// https://www.gnu.org/licenses/.

// File: getassignments.mjs
// retrieves data about all assignments, either current or archived

import {verifyJSONRequest, loadUsers} from '../libauthentication.mjs';
import {datadir, getProjectSettings} from '../projects.mjs';
import {getAssignmentDir, getAssignmentTypeDir} from '../libassignments.mjs';
import fs from '../fs.mjs';
import path from 'node:path';

export async function getAssignment(project, assignmentType, assignmentId) {
  const assignmentDir = getAssignmentDir(
    project, assignmentType, assignmentId, false
  );
  if (!assignmentDir) return {
    meinongian: true
  }
  const assignmentInfo = {};
  const metadataFile = path.join(assignmentDir, 'metadata.json');
  assignmentInfo.metadata = fs.loadjson(metadataFile) ?? {};
  const filenames = fs.filesin(assignmentDir).map((f) => (path.basename(f)));
  if (filenames.length > 0) assignmentInfo.filenames = filenames;
  assignmentInfo.isarchived = (filenames.includes('archived'));
  if (filenames.includes('biblastextracted')) {
    assignmentInfo.biblastextracted =
      fs.mtime(path.join(assignmentDir, 'biblastextracted'));
  }
  if (filenames.includes('biblastapplied')) {
    assignmentInfo.biblastapplied =
      fs.mtime(path.join(assignmentDir, 'biblastapplied'));
  }
  if (filenames.includes('extracted-bib.txt')) {
    assignmentInfo.extractbibmtime =
      fs.mtime(path.join(assignmentDir, 'extracted-bib.txt'));
  }
  if (filenames.includes('bibliography.json')) {
    const mtime = fs.mtime(path.join(assignmentDir, 'bibliography.json'));
    assignmentInfo.biblastchanged = mtime;
    assignmentInfo.biblastsaved = mtime;
  }
  if (filenames.includes('main.md')) {
    assignmentInfo.mainfilechanged =
      fs.mtime(path.join(assignmentDir, 'main.md'));
  }
  if (filenames.includes('all-bibinfo.json')) {
    assignmentInfo.bibdata =
      fs.loadjson(path.join(assignmentDir, 'all-bibinfo.json')) ?? false;
  }
  if (filenames.includes('assignedto.txt')) {
    const userid = fs.readfile(path.join(assignmentDir, 'assignedto.txt'))
      ?? null;
    const users = loadUsers(project);
    const username = users?.[userid]?.name;
    if (userid && username) {
      assignmentInfo.assignedTo = userid;
      assignmentInfo.assignedToName = username;
    }
  }
  const proofsdir = path.join(assignmentDir, 'proofs');
  if (!fs.isdir(proofsdir)) return assignmentInfo;
  assignmentInfo.proofsets = [];
  const proofsets = fs.subdirs(proofsdir).map((f) => (path.basename(f)));
  const keyfile = path.join(datadir, 'proofkeys.json');
  const proofkeys = fs.loadjson(keyfile) ?? {};
  for (const proofset of proofsets) {
    const ts = parseInt(proofset);
    if (isNaN(ts) || ts == 0) continue;
    const newset = {
      settime: ts,
      outputfiles: [],
      key: ''
    }
    const prooffiles = fs.filesin(path.join(proofsdir, proofset))
      .map((f) => (path.basename(f)));
    for (const pfile of prooffiles) {
      if (pfile.startsWith(assignmentId + '.')) {
        newset.outputfiles.push(pfile);
      }
    }
    for (const key in proofkeys) {
      const prdata = proofkeys[key];
      if (prdata?.project == project &&
        prdata?.assignmentId == assignmentId &&
        prdata?.assignmentType == assignmentType &&
        prdata?.proofset == proofset) {
        if (prdata?.editor) {
          newset.ekey = key;
        } else {
          newset.akey = key;
        }
        if (newset?.ekey && newset?.akey) break;
      }
    }
    if ((newset?.ekey && newset.ekey != '') &&
      (newset?.akey && newset.akey != '')) {
      assignmentInfo.proofsets.push(newset);
    }
  }
  const editionsdir = path.join(assignmentDir, 'editions');
  if (!fs.isdir(editionsdir)) return assignmentInfo;
  const versions = fs.subdirs(editionsdir).map((f) => (path.basename(f)));
  for (const version of versions) {
    const versiondir = path.join(editionsdir, version);
    if (!assignmentInfo?.editions) {
      assignmentInfo.editions = {};
    }
    const versioninfo = {};
    versioninfo.creationtime = fs.mtime(versiondir);
    versioninfo.files = fs.filesin(versiondir) .map((f) => (path.basename(f)));
    assignmentInfo.editions[version] = versioninfo;
  }
  return assignmentInfo;
}


export default async function getAssignments(reqbody, archive = false) {
  const verify = verifyJSONRequest(reqbody);
  if (verify?.error) return verify;

  const project = reqbody.project;
  const projsettings = getProjectSettings(project);
  if (!projsettings?.assignmentTypes) return {
    error: true,
    errMsg: 'Project settings do not have document/assignment types. ' +
      'Please check your site configuration.'
  }
  const allassignments = {};
  for (const assignmentType in projsettings.assignmentTypes) {
    const assignTypeSpec = projsettings.assignmentTypes[assignmentType];
    allassignments[assignmentType] = {};
    const typeDir = getAssignmentTypeDir(project, assignmentType);
    const subdirs = fs.subdirs(typeDir);
    for (const assignmentDir of subdirs) {
      const assignmentId = path.basename(assignmentDir);
      const archiveFile = path.join(assignmentDir, 'archived');
      if (fs.isfile(archiveFile) !== archive) continue;
      allassignments[assignmentType][assignmentId] =
        await getAssignment(project, assignmentType, assignmentId);
    }
  }
  return allassignments;
}
