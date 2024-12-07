// LICENSE: GNU GPL v3 You should have received a copy of the GNU General
// Public License along with this program. If not, see
// https://www.gnu.org/licenses/.

// File: login.mjs
// handler that processes login attempts

import {newAccessKey, verifyByPassword} from '../libauthentication.mjs';

export default async function login(reqbody) {
  let {project, ogtfname, ogtfpwd, ogtfremember} = reqbody;
  if (!ogtfname) return {
    success: false,
    nosuchuser: true,
    loginErrMsg: 'Login name not provided'
  }
  if (!ogtfpwd) return {
    success: false,
    wrongpassword: true,
    loginErrMsg: 'No password provided.'
  }
  // remove rest of emai address, make lowercase,
  // remove non-alphabetical
  ogtfname = ogtfname.replace(/@.*/, '').toLowerCase()
    .replace(/[^a-z0-9]/g, '')
  const pwdResult = verifyByPassword(project, ogtfname, ogtfpwd);
  if (pwdResult == 'nosuchuser') return {
    success: false,
    nosuchuser: true,
    loginErrMsg: `User with name ${ogtfname} does not exist.`
  }
  if (!pwdResult) return {
    success: false,
    wrongpassword: true,
    loginErrMsg: 'Incorrect password provided.'
  }
  return {
    success: true,
    wrongpassword: false,
    nosuchuser: false,
    loginErrMsg: '',
    loggedinuser: ogtfname,
    project,
    loginaccesskey: newAccessKey(project, ogtfname),
    remember: !!ogtfremember
  }
}