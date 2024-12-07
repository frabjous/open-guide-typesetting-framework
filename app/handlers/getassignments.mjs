// LICENSE: GNU GPL v3 You should have received a copy of the GNU General
// Public License along with this program. If not, see
// https://www.gnu.org/licenses/.

// File: getassignments.mjs
// retrieves data about all assignments, either current or archived

import {verifyJSONRequest} from '../libauthentication.mjs';
import {datadir, getProjectSettings} from '../projects.mjs';
import {getAssignmentTypeDir} from '../libassignments.mjs';
import fs from '../fs.mjs';
import path from 'node:path';

export default async function getassignments(reqbody, archive = false) {
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
      const archiveFile = path.join(assignmentDir, 'archived');
      if (fs.isfile(archiveFile) !== archive) continue;
      const assignmentId = path.basename(assignmentDir);
      allassignments[assignmentType][assignmentId] = {};
      const metadataFile = path.join(assignmentDir, 'metadata.json');
      allassignments[assignmentType][assignmentId].metadata =
        fs.loadjson(metadataFile) ?? {};
      const filenames = fs.filesin(assignmentDir)
        .map((f) => (path.basename(f)));
      if (filenames.length > 0) {
        allassignments[assignmentType][assignmentId].filenames = filenames;
      }
      if (filenames.includes('biblastextracted')) {
        allassignments[assignmentType][assignmentId].biblastextracted =
          fs.mtime(path.join(assignmentDir, 'biblastextracted'));
      }
      if (filenames.includes('biblastapplied')) {
        allassignments[assignmentType][assignmentId].biblastapplied =
          fs.mtime(path.join(assignmentDir, 'biblastapplied'));
      }
      if (filenames.includes('extracted-bib.txt')) {
        allassignments[assignmentType][assignmentId].extractbibmtime =
          fs.mtime(path.join(assignmentDir, 'extracted-bib.txt'));
      }
      if (filenames.includes('bibliography.json')) {
        const mtime = fs.mtime(path.join(assignmentDir, 'bibliography.json'));
        allassignments[assignmentType][assignmentId].biblastchanged = mtime;
        allassignments[assignmentType][assignmentId].biblastsaved = mtime;
      }
      if (filenames.includes('main.md')) {
        allassignments[assignmentType][assignmentId].mainfilechanged =
          fs.mtime(path.join(assignmentDir, 'main.md'));
      }
      if (filenames.includes('all-bibinfo.json')) {
        allassignments[assignmentType][assignmentId].bibdata =
          fs.loadjson(path.join(assignmentDir, 'all-bibinfo.json')) ?? false;
      }
      const proofsdir = path.join(assignmentDir, 'proofs');
      if (!fs.isdir(proofsdir)) continue;
      allassignments[assignmentType][assignmentId].proofsets = [];
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
          allassignments[assignmentType][assignmentId].proofsets.push(newset);
        }
      }
      const editionsdir = path.join(assignmentDir, 'editions');
      if (!fs.isdir(editionsdir)) continue;
      const versions = fs.subdirs(editionsdir).map((f) => (path.basename(f)));
      for (const version of versions) {
        const versiondir = path.join(editionsdir, version);
        if (!allassignments?.[assignmentType]?.[assignmentId]?.editions) {
          allassignments[assignmentType][assignmentId].editions = {};
        }
        const versioninfo = {};
        versioninfo.creationtime = fs.mtime(versiondir);
        versioninfo.files = fs.filesin(versiondir)
          .map((f) => (path.basename(f)));
        allassignments[assignmentType][assignmentId].editions[version] =
          versioninfo;
      }
    }
  }
  return allassignments;
}
