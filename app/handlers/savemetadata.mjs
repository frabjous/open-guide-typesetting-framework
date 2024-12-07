// LICENSE: GNU GPL v3 You should have received a copy of the GNU General
// Public License along with this program. If not, see
// https://www.gnu.org/licenses/.

/// File: savemetadata.mjs
// handler that responds to request to save metadata

import {verifyJSONRequest} from '../libauthentication.mjs';
import {getAssignmentDir} from '../libassignments.mjs';
import {getProjectSettings} from '../projects.mjs';
import path from 'node:path';
import fs from '../fs.mjs';

export default async function savemetadata(reqbody) {
  const verify = verifyJSONRequest(reqbody);
  if (verify?.error) return verify;

  const {assignmentId, assignmentType, metadata, project} = reqbody;
  if (!assignmentId || !metadata || !assignmentType) return {
    error: true,
    errMsg: 'Insufficient information provided to save metadata.'
  }
  const assigndir = getAssignmentDir(
    project, assignmentType, assignmentId, true
  );
  if (!assigndir) return {
    error: true,
    errMsg: 'Unable to find or create directory for document. ' +
      'Contact your site administrator.'
  }
  const metadataFile = path.join(assigndir, 'metadata.json');
  if (!fs.savejson(metadataFile, metadata)) return {
    error: true,
    errMsg: 'Unable to save metadata. Contact your site adminsitrator.'
  }
  const projSettings = getProjectSettings(project);
  const typeOpts = projSettings?.assignmentTypes?.[assignmentType];
  if (!typeOpts) return {
    error: true,
    errMsg: 'Metadata saved, but server cannot find information about ' +
      'assignment type to create yaml metadata file for ' +
      'inclusion in output documents. Check your site settings.'
  }
  const metadataSpec = typeOpts?.metadata ?? {};
  let yaml = '';
  // title for reviews
  if (!metadata?.title && metadata?.reviewedtitle) {
    yaml += `title: 'Review of ${metadata.reviewedtitle.replaceAll("'","''")}`;
    if (metadata?.reviewedsubtitle) {
      yaml += ': ' + metadata.reviewedsubtitle.replaceAll("'","''");
    }
    if (metadata?.reviewedauthor) {
      yaml+= ' by ';
      for (let j=0; j<metadata.reviewedauthor.length; j++) {
        yaml += (j == 0) ? '' :
          ((j < metadata.reviewedauthor.length - 1) ? ', ' : ' and ');
        yaml += metadata.reviewedauthor[j].replaceAll("'","''");
      }
    } else if (metadata.reviewededitor) {
      yaml+= ' edited by ';
      for (let j=0; j<metadata.reviewededitor.length; j++) {
        yaml += (j == 0) ? '' :
          ((j < metadata.reviewededitor.length - 1) ? ', ' : ' and ');
        yaml += metadata.reviewededitor[j].replaceAll("'","''");
      }
    }
    yaml += `'\n`
  }
  // most metadata keys
  for (const mkey in metadata) {
    let mval = metadata[mkey];
    if (!metadataSpec?.[mkey]) continue;
    const mspec = metadataSpec[mkey];
    let spec = mspec;
    if (Array.isArray(mspec)) {
      spec = mspec[0];
    }
    if (spec?.subcategories) {
      let subyaml = `${mkey}:\n`;
      let usesubyaml = false;
      for (const subval of mval) {
        let hashyphen = false;
        for (const subkey in subval) {
          const ssubval = subval[subkey];
          if (!spec?.[subkey]?.pandoc) {
            continue;
          }
          if (spec[subkey].pandoc != 'subelement') {
            continue;
          }
          if (hashyphen) {
            subyaml += '  ';
          } else {
            subyaml += '- ';
            hashyphen = true;
            usesubyaml = true;
          }
          subyaml += `${subkey}: ${ssubval}\n`
        }
      }
      if (usesubyaml) yaml += subyaml;
      continue;
    }
    if (!spec?.pandoc) continue;
    // yaml arrays for keywords
    if (spec.pandoc == 'yamlarray') {
      // skip empty arrays
      if (!Array.isArray(mval) || mval.length == 0) continue;
      yaml += `${mkey}: [${mval.join(', ')}]\n`;
      continue;
    }
    //name lists
    if (spec.pandoc == 'yamllist') {
      if (!Array.isArray(mval) || mval.length == 0) continue;
      yaml += mkey + ': ';
      if (mval.length == 1) {
        yaml += mval[0] + '\n';
        continue;
      }
      yaml += mval.slice(0, -1).join(', ') + ' and ' +
        mval[mval.length - 1] + '\n';
      continue;
    }
    // mval should be a string; floats with 2 dec places
    mval = mval.toString().trim();
    if (/\.[0-9]$/.test(mval)) mval += '0';
    // blocks of text set off, like abstracts
    if (spec.pandoc == 'yamlblock') {
      yaml += `${mkey}: |\n`;
      yaml += '  ' + mval.split('\n').join('\n  ') + '\n';
      continue;
    }
    // regular yaml entry, quotes to allow semicolons, etc.
    // with singlequotes escaped
    if (spec.pandoc == 'yaml') {
      yaml += `${mkey}: '${mval.replaceAll("'", "''")}'\n`;
    }
  }
  // epub specific metadata
  if (metadata?.author && !metadata?.creator) {
    yaml += 'creator:\n';
    for (const author of metadata.author) {
      yaml += '- role: author\n';
      yaml += '  text: ';
      if (typeof author == 'string') {
        yaml += author + '\n';
        continue;
      }
      if (author?.name) {
        yaml += author.name + '\n';
      }
    }
  }
  if (projSettings?.title && !metadata?.publisher) {
    yaml += `publisher: '${projSettings.title.replaceAll("'","''")}'\n`;
  }
  // universal nocite and save if non-empty
  if (yaml != '') {
    yaml += 'nocite: |\n  @*\n';
    yaml += 'link-citations: true\n';
    const yamlfile = path.join(assigndir, 'metadata.yaml');
    if (!fs.savefile(yamlfile, yaml)) return {
      error: true,
      errMsg: 'Unable to save metadata yaml file. ' +
        'Contact your site administrator.'
    }
  }
  return {success: true, error: false}
}
