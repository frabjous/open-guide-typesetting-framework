// LICENSE: GNU GPL v3 You should have received a copy of the GNU General
// Public License along with this program. If not, see
// https://www.gnu.org/licenses/.

// File: newpwd.mjs
// handler that processes requests to set new password

import {
  loadUsers,
  setNewPassword,
  verifyJSONRequest,
  verifyNewpwdLink
} from '../libauthentication.mjs';
import sendEmail from '../libemail.mjs';
import {getProjectSettings} from '../projects.mjs';

export default async function newpwd(reqbody) {
  const {wasloggedin, newpwdlink, ogtfnewpwd1,
    ogtfnewpwd2, project, username} = reqbody;
  if (!ogtfnewpwd1 || !ogtfnewpwd2 || !username) return {
    error: true,
    pwdChangeErrMsg: 'Insufficient info provided for password change.'
  }
  if (wasloggedin) {
    const verify = verifyJSONRequest(reqbody);
    if (verify?.error) return verify;
  } else {
    if (!newpwdlink) return {
      error: true, pwdChangeErrMsg: 'No password change key given.'
    }
    const verify = verifyNewpwdLink(project, username, newpwdlink);
    if (!verify) return {
      error: true,
      pwdChangeErrMsg: 'Invalid or expired password change key given.'
    }
  }
  if (ogtfnewpwd1 != ogtfnewpwd2) return {
    error: true, pwdChangeErrMsg: 'Requested new passwords do not match.'
  }
  const setres = setNewPassword(project, username, ogtfnewpwd1);
  if (setres !== true) return {
    error: true,
    pwdChangeErrMsg: 'Error saving password hash on server.'
  }
  const users = loadUsers(project);
  const emailto = `${users[username].name} <${users[username].email}>`;
  const projInfo = getProjectSettings(project);
  const projectTitle = projInfo?.title ?? 'Open Guide';
  const projectContact = projInfo?.contactname ?? 'Unknown';
  const projectEmail = projInfo?.contactemail ?? 'unknown';
  const url = (reqbody?.reqUrl ?? 'unknown').replace(/\/json.*/,'') +
    '?project=' + encodeURIComponent(project);
  const mailcontents = `\r\n<p>Your password on \r\n` +
    `<a href="${url}">the typesetting framework for the \r\n` +
    `${projectTitle}</a> has been changed. \r\n` +
    `For security reasons, the new \r\n` +
    `password is not given here.</p>\r\n` +
    `<p>Your username is: ${username}</p>\r\n` +
    `<p>If you did not request this change, or \r\n` +
    `it was made in error, please \r\n` +
    `inform the project contact person, ${projectContact} \r\n` +
    `(<a href="mailto:${projectEmail}">${projectEmail}</a>), \r\n` +
    `to let them know.</p>\r\n`;
  sendEmail(
    project,
    emailto,
    `Password changed on the ${projectTitle} typesetting framework`,
    mailcontents
  );
  return {success: true}
}
