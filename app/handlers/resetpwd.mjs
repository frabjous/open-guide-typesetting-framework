// LICENSE: GNU GPL v3 You should have received a copy of the GNU General
// Public License along with this program. If not, see
// https://www.gnu.org/licenses/.

// File: resetpwd.mjs
// handler that processes requests to reset password

import {loadUsers, newSetPwdLink} from '../libauthentication.mjs';
import {getProjectSettings} from '../projects.mjs';
import sendEmail from '../libemail.mjs';

export default async function resetpwd(reqbody) {
  if (!reqbody?.email) return {
    error: true,
    success: false,
    resetErrMsg: 'No email specified for reset.'
  }
  const email = reqbody.email.toLowerCase();
  const project = reqbody.project;
  const users = loadUsers(project);
  let user = null;
  for (const username in users) {
    const userdata = users[username];
    if (userdata.email == email) {
      user = username;
      break;
    }
  }
  if (!user) return {
    success: false,
    error: true,
    resetErrMsg: 'No user found with that email address. ' +
      'Contact your project leader about (re)gaining access.'
  }
  const nspl = newSetPwdLink(project, user);

  const emailto = `${users[user].name} <${users[user].email}>`;
  const projInfo = getProjectSettings(project);
  const projectTitle = projInfo?.title ?? 'Open Guide';
  const projectContact = projInfo?.contactname ?? 'Unknown';
  const projectEmail = projInfo?.contactemail ?? 'unknown';

  const url = (reqbody?.reqUrl ?? 'unknown').replace(/\/json.*/,'') +
    '?project=' + encodeURIComponent(project);
  const changeurl = url + '&newpwd=' + encodeURIComponent(nspl) +
    '&user=' + encodeURIComponent(user);
  const mailcontents =
    `\r\n<p>A password reset request was made for you on the typesetting \r\n` +
    `framework for the ${projectTitle}. To reset your password, \r\n` +
    `use the link below:</p>\r\n` +
    `<p><a href="${changeurl}">` + changeurl.replaceAll('&', '&amp;') +
    `</a></p>\r\n` +
    `<p>Your username is: ${user}</p>\r\n` +
    `<p>If you did not request this password reset, or the \r\n` +
    `request was made in error, please \r\n` +
    `inform the project contact person, \r\n` + projectContact +
    ` (<a href="mailto:${projectEmail}">${projectEmail}</a>),\r\n ` +
    `to let them know.</p>\r\n`;

  sendEmail(
    project,
    emailto,
    `Reset password for the ${projectTitle} typesetting framework`,
    mailcontents
  );

  return {success: true}
}