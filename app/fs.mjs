// LICENSE: GNU GPL v3 You should have received a copy of the GNU General
// Public License along with this program. If not, see
// https://www.gnu.org/licenses/.

// File: fs.mjs
// A wrapper around node's fs module with added convenience functions

import fs from 'node:fs';
import path from 'node:path';

const prettytabsize = 2;

// copies a file

fs.cp = function(src, dest) {
  try {
    fs.copyFileSync(src, dest);
  } catch(err) {
    return false;
  }
  return true;
}

// checks if a directory exists and creates it otherwise
// returns directory name/path on success, or else false
fs.ensuredir = function(dir) {
  let stats;
  if (fs.isdir(dir)) return dir;
  try {
    fs.mkdirSync(dir, { recursive: true, mode: 0o755 } );
  } catch(err) {
    return false;
  }
  return dir;
}

// list of files (not subdirectories) in a directory
fs.filesin = function filesin(dir, includeHidden = false) {
  let contents;
  try {
    contents = fs.readdirSync(dir)
      .filter((item => (includeHidden || item[0] != '.')))
      .map((item) => path.join(dir, item))
      .filter((item) =>  (fs.isfile(item)))
  } catch(err) {
    return null;
  }
  return contents;
}

fs.filesize = function(filename) {
  let rv;
  try {
    const s = fs.statSync(filename);
    rv = s.size;
  } catch(err) {
    return 0;
  }
  return rv;
}

// returns boolean: dir exists (and is a dir)
fs.isdir = function(dir) {
  var stats;
  try {
    stats = fs.statSync(dir);
  } catch (err) {
    return false;
  }
  return stats?.isDirectory?.();
}

// returns boolean: file exists (and is a file)
fs.isfile = function(file) {
  var stats;
  try {
    stats = fs.statSync(file);
  } catch (err) {
    return false;
  }
  return stats?.isFile?.();
}

// loads a json file and decodes into object
fs.loadjson = function(filename) {
  const json = fs.readfile(filename);
  if (!json) return null;
  try {
    const res = JSON.parse(json);
    return (res) ? res : null;
  } catch(err) {
    return null;
  }
  return null;
}

// returns last modification fime of file as timestamp
fs.mtime = function(filename) {
  let rv;
  try {
    const s = fs.statSync(filename);
    rv = s.mtimeMs;
  } catch(err) {
    return 0;
  }
  return rv;
}

fs.mv = function(oldpath, newpath) {
  const newdir = path.dirname(newpath);
  if (!fs.ensuredir(newdir)) return false;
  try {
    fs.renameSync(oldpath, newpath);
  } catch(err) {
    return false;
  }
  return true;
}

fs.readfile = function(filename) {
  try {
    return fs.readFileSync(filename, { encoding: 'utf8' });
  } catch(err) {
    return null;
  }
}

fs.rm = function(filename) {
  try {
    fs.unlinkSync(filename);
  } catch(err) {
    return false;
  }
  return true;
}

// saves contents into a file
fs.savefile = function(filename, contents) {
  let stats;
  // ensure directory exists
  let dir = fs.ensuredir(path.dirname(filename));
  if (!dir) { return false; }
  try {
    fs.writeFileSync(filename, contents,
                     { encoding: 'utf8', mode: 0o644 });
  } catch(err) {
    return false;
  }
  return true;
}

// saves serializable object as json file
fs.savejson = function(filename, obj, pretty = false){
  const json = JSON?.stringify?.(obj, null, (pretty) ? prettytabsize : null);
  if (!json) return false;
  return fs.savefile(filename, json);
}

fs.stdin = function() {
  return fs.readfile(process.stdin.fd);
}

// gets immediate subdirs of a given dir
fs.subdirs = function(dir, includeHidden = false) {
  let contents;
  try {
    contents = fs.readdirSync(dir)
      .filter((item => (includeHidden || item[0] != '.')))
      .map((item) => path.join(dir, item))
      .filter((item) =>  (fs.isdir(item)))
  } catch(err) {
    return null;
  }
  return contents;
}

// async versions
fs.async = {};

fs.async.cp = async function(src, dest) {
  try {
    await fs.promises.copyFile(src, dest);
  } catch(err) {
    return false;
  }
  return true;
}

fs.async.ensuredir = async function(dir) {
  let stats;
  if (await fs.async.isdir(dir)) return dir;
  try {
    await fs.promises
      .mkdir(dir, { recursive: true, mode: 0o755 } );
  } catch(err) {
    return false;
  }
  return dir;
}

fs.async.filesin = async function(dir, includeHidden = false) {
  let rv = [], resList, filtered;
  try {
    let contents = await fs.promises.readdir(dir);
    filtered = contents.filter((item) =>
      (includeHidden || item[0] != '.'))
      .map((item) => path.join(dir, item));
    const promises = filtered.map(
      async (item) => {
        const x= await fs.async.isfile(item);
        return x
      }
    );
    resList = await Promise.all(promises);
  } catch(err) {
    return [];
  }
  for (let i=0 ; i<filtered.length; i++) {
    if (resList[i]) rv.push(filtered[i]);
  }
  return rv;
}

fs.async.filesize = async function(filename) {
  let rv;
  try {
    const s = await fs.promises.stat(filename);
    rv = s.size;
  } catch(err) {
    return 0;
  }
  return rv;
}


fs.async.isdir = async function(dir) {
  var stats;
  try {
    stats = await fs.promises.stat(dir);
  } catch (err) {
    return false;
  }
  return stats?.isDirectory?.();
}

fs.async.isfile = async function(file) {
  var stats;
  try {
    stats = await fs.promises.stat(file);
  } catch (err) {
    return false;
  }
  return stats?.isFile?.();
}


fs.async.loadjson = async function(filename) {
  const json = await fs.async.readfile(filename);
  if (!json) return null;
  let res = null;
  try {
    res = JSON.parse(json);
  } catch(err) {
    return null;
  }
  return res;
}

fs.async.mtime = async function(filename) {
  let rv;
  try {
    const s = await fs.promises.stat(filename);
    rv = s.mtimeMs;
  } catch(err) {
    return 0;
  }
  return rv;
}

fs.async.mv = async function(oldpath, newpath) {
  const newdir = path.dirname(newpath);
  const ensure = await fs.async.ensuredir(newdir);
  if (!ensure) return false;
  try {
    await fs.promises.rename(oldpath, newpath);
  } catch(err) {
    return false;
  }
  return true;
}


fs.async.readfile = async function(filename) {
  try {
    return await fs.promises.readFile(filename, { encoding: 'utf-8' });
  } catch(err) {
    return null;
  }
}


fs.async.rm = async function(filename) {
  try {
    await fs.promises.unlink(filename);
  } catch(err) {
    return false;
  }
  return true;
}


fs.async.savefile = async function(filename, contents) {
  let stats;
  // ensure directory exists
  let dir = await fs.async.ensuredir(path.dirname(filename));
  if (!dir) { return false; }
  try {
    await fs.promises.writeFile(filename, contents,
                                { encoding: 'utf8', mode: 0o644 });
  } catch(err) {
    return false;
  }
  return true;
}

fs.async.savejson = async function(filename, obj, pretty = false){
  const json = JSON?.stringify?.(obj, null, (pretty) ? prettytabsize : null);
  if (!json) return false;
  return await fs.async.savefile(filename, json);
}

fs.async.stdin = async function() {
  return await fs.async.readfile(process.stdin.fd);
}

fs.async.subdirs = async function(dir, includeHidden = false) {
  let rv = [], resList, filtered;
  try {
    let contents = await fs.promises.readdir(dir);
    filtered = contents.filter((item) =>
      (includeHidden || item[0] != '.'))
      .map((item) => path.join(dir, item));
    const promises = filtered.map(
      async (item) => {
        const x= await fs.async.isdir(item);
        return x
      }
    );
    resList = await Promise.all(promises);
  } catch(err) {
    return [];
  }
  for (let i=0 ; i<filtered.length; i++) {
    if (resList[i]) rv.push(filtered[i]);
  }
  return rv;
}

export default fs;
