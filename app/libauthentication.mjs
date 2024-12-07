// LICENSE: GNU GPL v3 You should have received a copy of the GNU General
// Public License along with this program. If not, see
// https://www.gnu.org/licenses/.

// File: libauthentication.mjs
// functions that verify logins, etc., for the typesetting framework

import bcrypt from 'bcryptjs';
import fs from './fs.mjs';
import path from 'node:path';
import randomString from './randomString.mjs';
import {getProjectDir} from './projects.mjs';

export function createNewUser(project, username, name, email) {
  const users = loadUsers(project);
  if (username in users) return 'userexists';
  users[username] = {name, email};
  return saveUsers(project, users);
}

export function loadUsers(project) {
  const projectdir = getProjectDir(project);
  if (!projectdir) return {};
  const usersfile = path.join(projectdir, 'users.json');
  const users = fs.loadjson(usersfile);
  if (!users) return {};
  // clear old pwd links
  let changed = false;
  for (const username in users) {
    const userdata = users[username];
    let userchanged = false;
    if (!("newpwdlinks" in userdata)) continue;
    const nowts = Date.now();
    const prelinknum = userdata.newpwdlinks.length;
    userdata.newpwdlinks = userdata.newpwdlinks.filter(
      (l) => (l.expires >= nowts)
    );
    const postlinknum = userdata.newpwdlinks.length;
    if (postlinknum != prelinknum) changed = true;
  }
  if (changed) saveUsers(project, users);
  return users;
}

export function newAccessKey(project, username) {
  const users = loadUsers(project);
  if (!(username in users)) users[username] = {};
  if (!("keylist" in users[username])) users[username].keylist = [];
  const accesskey = randomString(32);
  users[username].keylist.push(passwordHash(accesskey));
  saveUsers(project, users);
  return accesskey;
}

export function newSetPwdLink(project, username) {
  const users = loadUsers(project);
  // fails if user does not exist
  if (!(username in users)) return false;
  if (!("newpwdlinks" in users[username])) {
    users[username].newpwdlinks = [];
  }
  const newpwdlink = randomString(48);
  const pwdobject = {
    hash: passwordHash(newpwdlink),
    expires: Date.now() + 2678400000
  }
  users[username].newpwdlinks.push(pwdobject);
  saveUsers(project, users);
  return newpwdlink;
}

export function passwordHash(pwd) {
  return bcrypt.hashSync(pwd, 10);
}

function passwordVerify(password, hash) {
  return bcrypt.compareSync(password, hash);
}

export function removeAccessKey(project, username, accesskey) {
  const users = loadUsers(project);
  let dosave = false;
  if (!("keylist" in users[username])) return true;
  const prelength = users[username].keylist.length;
  users[username].keylist = users[username].keylist.filter(
    (keyhash) => (!passwordVerify(accesskey, keyhash))
  );
  const postlength = users[username].keylist.length;
  if (prelength != postlength) {
    return saveUsers(project, users);
  }
  return true;
}

export function removeUser(project, username) {
  const users = loadUsers(project);
  if (!(username in users)) return false;
  delete users[username];
  return saveUsers(project, users);
}

function saveUsers(project, users) {
  const projectdir = getProjectDir(project);
  if (!projectdir) return false;
  const usersfile = path.join(projectdir, 'users.json');
  return fs.savejson(usersfile, users);
}

export function setNewPassword(project, username, pwd) {
  const users = loadUsers(project);
  if (!(username in users)) {
    return `User with name ${username} not found.`;
  }
  users[username].passwordhash = passwordHash(pwd);
  users[username].newpwdlinks = [];
  return saveUsers(project, users);
}

export function setUserDetails(project, username, name, email) {
  const users = loadUsers(project);
  if (!(username in users)) return false;
  users[username].name = name;
  users[username].email = email;
  return saveUsers(project, users);
}

export function verifyByAccesskey(project, username, accesskey) {
  const users = loadUsers(project);
  if (!users?.[username]?.keylist) return false;
  for (const keyhash of users[username].keylist) {
    if (passwordVerify(accesskey, keyhash)) {
      return true;
    }
  }
  return false;
}

export function verifyByPassword(project, username, password) {
  const users = loadUsers(project);
  if (!users?.[username]?.passwordhash) return 'nosuchuser';
  return passwordVerify(password, users[username].passwordhash);
}

export function verifyJSONRequest(request) {
  const {project, username, accesskey} = request;
  if (!project || !username || !accesskey) return {
    error: true,
    errMsg: 'Insufficient information provided to validate request.'
  }
  const akVerify = verifyByAccesskey(project, username, accesskey);
  if (akVerify) return {error: false};
  return {
    error: true,
    errMsg: 'Invalid access key provided.'
  }
}

export function verifyNewpwdLink(project, username, pwdlink) {
  // note that loading the users removes expired links
  // so that doesn't need to be checked here;
  // also, we do not remove things here because it is checked
  // by the site before the actual request and needs to be
  // checked again
  const users = loadUsers(project);
  if (!users?.[username]?.newpwdlinks) return false;
  for (const pwdobj of users[username].newpwdlinks) {
    if (passwordVerify(pwdlink, pwdobj.hash)) return true;
  }
  return false;
}
