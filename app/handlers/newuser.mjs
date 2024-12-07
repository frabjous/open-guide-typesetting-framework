// LICENSE: GNU GPL v3 You should have received a copy of the GNU General
// Public License along with this program. If not, see
// https://www.gnu.org/licenses/.

// File: newuser.mjs
// handler that responds to request to invite a new user to the site

import {
  createNewUser,
  newSetPwdLink,
  verifyJSONRequest
} from '../libauthentication.mjs';
import sendEmail from '../libemail.mjs';
import {getProjectSettings} from '../projects.mjs';

export default async function newuser(reqbody) {
  const verify = verifyJSONRequest(reqbody);
  if (verify?.error) return verify;

  let {newname, newemail, newusername, project} = reqbody;
  if (!newname || !newemail || !newusername) return {
    error: true,
    errMsg: 'Insufficient information provided to create user.'
  }

  newusername = newusername.toLowerCase();
  newemail = newemail.toLowerCase();
  const cnu = createNewUser(project, newusername, newname, newemail);

  if (cnu === 'userexists') return {
    error: true,
    errMsg: 'A user with that username already exists.'
  }

  if (!cnu) return {
    error: true,
    errMsg: 'Unable to create new user.'
  }

  const nspl = newSetPwdLink(project, newusername);
  if (nspl === false) return {
    error: true,
    errMsg: 'User created, but unable to create link to set password.'
  }

  const projInfo = getProjectSettings(project);
  const projectTitle = projInfo?.title ?? 'Open Guide';
  const projectContact = projInfo?.contactname ?? 'Unknown';
  const projectEmail = projInfo?.contactemail ?? 'unknown';
  const url = (reqbody?.reqUrl ?? 'unknown').replace(/\/json.*/,'') +
    '?project=' + encodeURIComponent(project);
  const changeurl = url + '&newpwd=' + encodeURIComponent(nspl) +
    '&user=' + encodeURIComponent(newusername);
  const emailto = `${newname} <${newemail}>`;
  const mailcontents =
    `\r\n<p>${newname},</p>\r\n` +
    `<p>An account has been created for you on \r\n` +
    `<a href="${url}">the typesetting \r\n` +
    `framework for the ${projectTitle}</a>.</p>\r\n` +
    `<p>To make use of it, you will need to set a password \r\n` +
    `by visiting this link:</p>\r\n` +
    `<p><a href="${changeurl}">` +
    changeurl.replaceAll('&','&amp;') + `</a></p>\r\n` +
    `<p>Your username is: ${newusername}</p>\r\n` +
    `<p>If you believe this account was created in error, please \r\n` +
    `inform the project contact person, \r\n` + projectContact +
    ` (<a href="mailto:${projectEmail}">${projectEmail}` +
    `</a>),\r\n to let them know.</p>\r\n`;
  sendEmail(project, emailto,
    `Account created on the ${projectTitle} typesetting framework`,
    mailcontents
  );
  return {success: true}
}
