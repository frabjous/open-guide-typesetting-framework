// LICENSE: GNU GPL v3 You should have received a copy of the GNU General
// Public License along with this program. If not, see
// https://www.gnu.org/licenses/.

// File: processcomment.mjs
// responds to requests on proofs to save, delete or submit comments

import {datadir, getProjectSettings} from '../projects.mjs';
import {proofsaccess} from '../proofsaccess.mjs';
import {loadUsers} from '../libauthentication.mjs';
import path from 'node:path';
import fs from '../fs.mjs';
import sendEmail from '../libemail.mjs';

function htmlesc(s) {
  return s.replaceAll('&','&amp;')
    .replaceAll('<','&lt')
    .replaceAll('>','&gt');
}

function newComments() {
  return {
    pdf: {}, html: {}
  }
}

function nicetime(ts) {
  const dateObj = new Date(parseInt(ts));
  if (isNaN(dateObj)) return '(unknown time)';
  return dateObj.toLocaleString();
}

function readComments(commentsfile) {
  return fs.loadjson(commentsfile) ?? newComments();
}

export default async function processcomment(reqbody) {
  const accesskey = reqbody?.accesskey;
  if (!accesskey) return {
    error: true, errMsg: 'No access key provided.'
  }
  const proofinfo = proofsaccess(accesskey);
  if (!proofinfo?.access) return {
    error: true,
    errMsg: 'Access to save proofs denied. ' + (proofinfo?.reason ?? '')
  }
  const {
    assigndir,
    assignmentId,
    assignmentType,
    project,
    proofdir,
    proofset,
    username
  } = proofinfo;
  const commentsfile = path.join(proofdir, 'saved-comments.json');
  const savedhtmlfile = path.join(proofdir, 'saved-with-comments.html');
  const orightmlfile = path.join(proofdir, `${assignmentId}.html`);
  const bodyhtml = reqbody?.bodyhtml;
  if (bodyhtml) {
    const orightml = fs.readfile(orightmlfile);
    if (!orightml || orightml == '') return {
      error: true,
      errMsg: 'Original html file is missing or blank.'
    }
    const htmlparts = orightml.split('<body');
    let remainder = htmlparts.slice(1).join('<body');
    let header = orightml;
    if (htmlparts.length > 1) {
      header = htmlparts[0] + '<body';
      let remparts = remainder.split('>');
      header += remparts[0] + '>';
    }
    let bottom = '</body>';
    let bottomparts = orightml.split('</body>');
    if (bottomparts.length > 1) {
      bottom += bottomparts.slice(1).join('</body>');
    } else {
      bottom += '\n</html>';
    }
    const newwhole = header + bodyhtml + bottom;
    if (!fs.savefile(savedhtmlfile, newwhole)) return {
      error: true,
      errMsg: 'Could not save modified html document.'
    }
  }

  if (reqbody?.requesttype == 'savecomment') {
    const commentinfo = reqbody?.commentinfo;
    if (!commentinfo?.id) return {
      error: true,
      errMsg: 'Succificient information on comment to save not provided.'
    }
    const comments = readComments(commentsfile);
    if (commentinfo?.page) {
      comments.pdf[commentinfo.id] = commentinfo;
    }
    if (bodyhtml) {
      comments.html[commentinfo.id] = commentinfo;
    }
    if (!fs.savejson(commentsfile, comments)) return {
      error: true,
      errMsg: 'Unable to save comments.'
    }
  }

  if (reqbody?.requesttype == 'deletecomment') {
    const commentid = reqbody?.commentid;
    if (!commentid) return {
      error: true,
      errMsg: 'ID of comment to delete not specified.'
    }
    const comments = readComments(commentsfile);
    if (comments?.pdf?.[commentid]) {
      delete comments.pdf[commentid];
    }
    if (comments?.html?.[commentid]) {
      delete comments.html[commentid];
    }
    if (!fs.savejson(commentsfile, comments)) return {
      error: true,
      errMsg: 'Unable to save modified comments.'
    }
  }

  if (reqbody?.requesttype == 'submit') {
    const users = loadUsers(project);
    if (!users?.[username]) return {
      error: true,
      errMsg: 'Unable to find information about editor who created ' +
        'these proofs.'
    }
    const userdetails = users[username];
    if (!userdetails?.email) return {
      error: true,
      errMsg: 'Could not find editor’s email address.'
    }
    const email = userdetails.email;
    const emailto = (userdetails?.name) ?
      `${userdetails.name} <${email}>` : email;
    const metadatafile = path.join(assigndir, 'metadata.json');
    const metadata = fs.loadjson(metadatafile) ?? {};
    const keyfile = path.join(datadir, 'proofkeys.json');
    const keys = fs.loadjson(keyfile) ?? {};
    let editorkey = null;
    for (const posskey in keys) {
      const posskeyinfo = keys[posskey];
      if (
        posskeyinfo?.project == project &&
        posskeyinfo?.username == username &&
        posskeyinfo?.assignmentType == assignmentType &&
        posskeyinfo?.assignmentId == assignmentId &&
        posskeyinfo?.proofset == proofset &&
        posskeyinfo?.editor
      ) {
        editorkey = posskey;
        break;
      }
    }
    if (!editorkey) return {
      error: true,
      errMsg: 'Could not find corresponding key for editor.'
    }
    const comments = readComments(commentsfile);
    let emailcontents = '\r\n';
    const projSettings = getProjectSettings(project);
    const subject = 'Proof comments submitted on the ' +
      (projSettings?.title ?? 'Open Guide') + ' typesetting framework ' +
      `(${assignmentId})`;
    if (userdetails?.name) {
      emailcontents += `<p>Dear ${userdetails.name},</p>\r\n`;
    }
    emailcontents += '<p>Author comments and/or corrections have been \r\n' +
      `submitted on the <em>\r\n${projSettings?.title ?? 'Open Guide'}</em> \r\n` +
      `typesetting framework for the proof set created on \r\n` +
      `${nicetime(proofset)} for document id <strong>${assignmentId}</strong>\r\n`;
    if (metadata?.title) {
      emailcontents += ` – “` + metadata.title.replaceAll('&', '&amp;') + '”\r\n';
    }
    if (metadata?.author) {
      emailcontents += ' by ';
      for (let i = 0; i < metadata.author.length; i++) {
        const authordetails = metadata.author[i];
        emailcontents += (i > 0)
          ? ((i < (metadata.author.length - 1)) ? ', ' : ' and ')
          : '';
        emailcontents += authordetails?.name ?? '';
      }
    }
    emailcontents += '.</p>\r\n';
    const reqUrl = reqbody.reqUrl;
    const newUrl = reqUrl.replace(/\/json.*/,'/proofs') +
      '?key=' + encodeURIComponent(editorkey);
    emailcontents += '<p>To view the proofs with the saved comments, \r\n' +
      'please visit this URL:</p>\r\n' +
      `<p><a href="${newUrl}" target="_blank">\r\n${newUrl}</a></p>\r\n` +
      '<p>A summary of the comments left is below.</p>\r\n';
    for (const prooftype of ['html', 'pdf']) {
      if (!comments?.[prooftype]) continue;
      let liststarted = false;
      for (const commentid in comments[prooftype]) {
        const commentinfo = comments[prooftype][commentid];
        if (!liststarted) {
          emailcontents += '<p>Comments on ' + prooftype +
            ' proofs.</p>\r\n<ol>\r\n';
          liststarted = true;
        }
        emailcontents += '<li>';
        if (commentinfo?.page) {
          emailcontents += '<strong>(' +
            commentinfo.page.replaceAll('page','page ') +
            ')</strong><br>\r\n'
        }
        if (commentinfo?.topleveltag) {
          emailcontents += '<strong>(';
          if (commentinfo?.section && commentinfo.section > 0) {
            emailcontents += 'section ' + commentinfo.section.toString();
          }
          if (commentinfo.topleveltag == 'h1') {
            emailcontents += ' heading ';
          } else {
            if (commentinfo.topleveltag == 'p') {
              emailcontents += ' paragraph ';
            } else if (commentinfo.topleveltag == 'h2') {
              emailcontents += ' subsection heading '
            } else {
              emailcontents += ` ${commentinfo.topleveltag} `;
            }
            if (commentinfo?.position) {
              emailcontents += commentinfo.position.toString()
            }
          }
          emailcontents += ')</strong>\r\n';
        }
        if (commentinfo?.del) {
          emailcontents += `Deleted text: ${htmlesc(commentinfo.del)}` +
            '<br>\r\n';
        }
        if (commentinfo?.ins) {
          emailcontents += `Inserted text: ${htmlesc(commentinfo.ins)}` +
            '<br>\r\n';
        }
        if (commentinfo?.comment) {
          let label = 'Comment: ';
          if (commentinfo?.commenttype == 'query') label = 'Query: ';
          emailcontents += label + htmlesc(commentinfo.comment) +
            '<br>\r\n';
        }
        if (commentinfo?.response) {
          emailcontents += 'Response: ' + htmlesc(commentinfo.response) +
            '<br>\r\n';
        }
        emailcontents += '</li>\r\n';
      }
      if (liststarted) {
        emailcontents += '</ol>\r\n';
      }
    }
    const emailResult = await sendEmail(
      project,
      emailto,
      subject,
      emailcontents
    );
    if (!emailResult) return {
      error: true,
      errMsg: 'Unable to send email to editor. Please contact them directly.'
    }
  }
  return {success: true, error: false}
}
