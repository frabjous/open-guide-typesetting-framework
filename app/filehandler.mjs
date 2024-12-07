// LICENSE: GNU GPL v3 You should have received a copy of the GNU General
// Public License along with this program. If not, see
// https://www.gnu.org/licenses/.

// filehandler.mjs
// responds to file upload requests from the browser depending on request

import {verifyByAccesskey} from './libauthentication.mjs';
import {createOgeSettings, getAssignmentDir} from './libassignments.mjs';
import {extractBibliography, fixMarkdown} from './libdocument.mjs';
import {getProjectSettings} from './projects.mjs';
import path from 'node:path';
import {execSync} from 'node:child_process';
import fs from './fs.mjs';

function mvPromise(fileinfo, destination) {
  return new Promise((resolve) => {
    fileinfo.mv(destination, function(err) {
      if (err) resolve(false);
      resolve(true);
    });
  });
}

export default async function filehandler(reqObj, files) {
  const {uploadtype, project, assignmentType, assignmentId,
    accesskey, username} = reqObj;
  if (!uploadtype || !project || !assignmentType || !assignmentId ||
    !accesskey || !username) return {
    error: true,
    errMsg: 'Insufficient information provided to process upload.'
  }
  if (!verifyByAccesskey(project, username, accesskey)) return {
    error: true,
    errMsg: 'Invalid upload request access key.'
  }
  let assignmentDir = 'none';
  if (!(uploadtype == 'bibimport' && assignmentId == 'none')) {
    assignmentDir = getAssignmentDir(project, assignmentType, assignmentId);
    if (!assignmentDir) return {
      error: true,
      errMsg: 'Could not find or create document/assignment directory.'
    }
  }
  const rv = {};

  // main file upload
  if (uploadtype == 'mainfile') {
    const fileinfo = Object.values?.(files)?.[0];
    if (!fileinfo) return {
      error: true,
      errMsg: 'Could not find appropriate file in upload.'
    }
    const origfilename = fileinfo.name;
    const extension = path.extname(origfilename).substring(1).toLowerCase();
    if (!['docx', 'tex', 'md', 'markdown', 'ltx', 'htm', 'html', 'xhtml',
      'epub', 'latex', 'rtf', 'odt'].includes(extension)) return {
      error: true,
      errMsg: 'File with inappropriate extension for main file uploaded.'
    }
    rv.extension = extension;
    const dirfiles = fs.filesin(assignmentDir);
    // rename existing mainfile
    for (const dirfile of dirfiles) {
      const basename = path.basename(dirfile);
      if (basename.startsWith('mainupload.')) {
        const newname = path.join(assignmentDir,
          'previous-' + (fs.mtime(dirfile)).toString() + '-' + basename);
        fs.mv(dirfile, newname);
      }
    }
    const mainfilename = path.join(assignmentDir, 'mainupload.' + extension);
    const mvsuccess = await mvPromise(fileinfo, mainfilename);
    if (!mvsuccess) return {
      error: true,
      errMsg: 'Unable to move main file to appropriate directory. ' +
        'Please inform your site adminsitrator.'
    }
    const projSettings = getProjectSettings(project);
    const mainbase = 'mainupload.' + extension;
    // Convert to markdown
    const convCommand = projSettings?.assignmentTypes?.[assignmentType]
      ?.convert?.replaceAll('%upload%', `"${mainbase}"`);
    let markdown = '';
    try {
      markdown = execSync(convCommand, {
        cwd: assignmentDir,
        encoding: 'utf-8'
      });
    } catch(err) {
      return {
        error: true,
        errMsg: 'Could not convert to markdown. ' +
          (err?.stderr ?? '')
      }
    }
    // extract bibliography
    const ebRes = extractBibliography(markdown);
    markdown = ebRes[0];
    const bibcontents = ebRes[1];
    // Read metadata if it exists. Useful for fixing main file
    const metadatafile = path.join(assignmentDir, 'metadata.json');
    const metadata = fs.loadjson(metadatafile) ?? {};
    const splitsentences =
      !!(projSettings?.assignmentTypes?.[assignmentType]?.splitsentences);
    const importreplacements = projSettings?.importreplacements ?? {};
    markdown = fixMarkdown(
      markdown, metadata, importreplacements, splitsentences
    );
    // save main file
    const markdownFile = path.join(assignmentDir, 'main.md');
    // back up existing
    if (fs.isfile(markdownFile)) {
      const newname = path.join(assignmentDir, 'previous-' +
        (fs.mtime(markdownFile)).toString() + '-main.md');
      fs.mv(markdownFile, newname);
    }
    if (!fs.savefile(markdownFile, markdown)) return {
      error: true,
      errMsg: 'Could not save markdown file.'
    }
    // save bibliography
    if (bibcontents != '') {
      const bibfilename = path.join(assignmentDir, 'extracted-bib.txt');
      if (!fs.savefile(bibfilename, bibcontents)) return {
        error: true,
        errMsg: 'Could not save extracted bibliography.'
      }
      rv.extractedbib = true;
    }
    // create oge settings file
    const ogesave = createOgeSettings(project, assignmentType, assignmentId);
    if (!ogesave) return {
      error: true,
      errMsg: 'Could not save settings file for the open guide editor. ' +
        'Contact your site administrator.'
    }
    rv.success = true;
    rv.error = false;
    return rv;
  }

  // AUX file upload(s)
  if (uploadtype == 'auxfiles') {
    for (const fileinfo of (Object.values?.(files) ?? [])) {
      const basename = fileinfo.name;
      const ffn = path.join(assignmentDir, basename);
      if (fs.isfile(ffn)) {
        const renameto = path.join(assignmentDir,
          'previous-' + (fs.mtime(ffn)).toString() + '-' + basename);
        fs.mv(ffn, renamteto);
      }
      const mvsuccess = await mvPromise(fileinfo, ffn);
      if (!mvsuccess) return {
        error: true,
        errMsg: 'Could not save uploaded file.'
      }
    }
    rv.error = false;
    rv.success = true;
    return rv;
  }

  // Import bibliography upload
  if (uploadtype == 'bibimport') {
    const fileinfo = Object.values?.(files)?.[0];
    if (!fileinfo) return {
      error: true,
      errMsg: 'Could not find appropriate file in upload.'
    }
    const origfilename = fileinfo.name;
    const extension = path.extname(origfilename).substring(1).toLowerCase();
    if (!['bib', 'json', 'ris', 'xml', 'yaml', 'yml']
      .includes(extension)) return {
      error: true,
      errMsg: 'File with inappropriate extension for ' +
        'bibliography import uploaded.'
    }
    const filecontents = fileinfo.data.toString();
    let json = '';
    if (extension == 'json') {
      json = filecontents;
    } else {
      let cmd = 'pandoc -t csljson ';
      if (extension == 'bib') {
        cmd += '-f bibtex ';
      }
      if (extension == 'ris') {
        cmd += '-f ris ';
      }
      if (extension == 'xml') {
        cmd += '-f endnotexml '
      }
      try {
        json = execSync(cmd, {encoding: 'utf-8', input: filecontents});
      } catch(err) {
        return {
          error: true,
          errMsg: 'Could not convert bibliography file. ' +
            (err?.stderr ?? '')
        }
      }
    }
    rv.additions = JSON.parse(json);
    if (!Array.isArray(rv?.additions)) return {
      error: true,
      errMsg: 'Wrong kind of json produced as result.'
    }
    if (!rv?.additions || rv.additions.length == 0) return {
      error: true,
      errMsg: 'Unable to extract bibliographic items from file.'
    }
    rv.success = true;
    rv.error = false;
    return rv;
  }

  return {
    error: true,
    errMsg: 'Unrecognized upload type.'
  }
}

