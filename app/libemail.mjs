// LICENSE: GNU GPL v3 You should have received a copy of the GNU General
// Public License along with this program. If not, see
// https://www.gnu.org/licenses/.

// File: libemail.mjs
// defines the functions for sending email used by the framework

// Note: this looks for a custom email handler in the project directory
// customemail.mjs, which should export a default function with four arguments
// to address, subject, htmlcontents, from address
// should return true or false

import {getProjectDir, getProjectSettings} from './projects.mjs';
import fs from './fs.mjs';
import path from 'node:path';

export default async function sendEmail(project, emailto, subject, contents) {
  const projsettings = getProjectSettings(project);
  if (!projsettings) return false;
  const contact = `${projsettings?.contactname ?? ''} ` +
    `<${projsettings?.contactemail ?? ''}>`;
  const projectdir = getProjectDir(project);
  const custommailer = path.join(projectdir, 'customemail.mjs');
  let sendsucc = false;
  try {
    const imported = await import(custommailer);
    const customMailer = imported.default;
    sendsucc = customMailer(emailto, subject, contents, contact);
  } catch(err) {
    console.error('email no go', err);
  }
  const ts = (new Date()).toLocaleString().replace(/[^0-9a-z]/gi,'-');
  const logcontents = `To: ${emailto}\n` +
    `Subject: ${subject}\n` +
    `From: ${contact}\n` +
    `Sent?: ${(sendsucc) ? 'yes' : 'no'}\n\n` +
    contents;
  const logfiledir = path.join(projectdir, 'emaillogs');
  if (!fs.ensuredir(logfiledir)) return false;
  const logfile = path.join(logfiledir, 'email-' + ts + '.log');
  return fs.savefile(logfile, logcontents);
}