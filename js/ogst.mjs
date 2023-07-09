// LICENSE: GNU GPL v3 You should have received a copy of the GNU General
// Public License along with this program. If not, see
// https://www.gnu.org/licenses/.

////////////////////// ogst.mjs /////////////////////////////////////////
// defines the main open guide typesetting framework functions         //
/////////////////////////////////////////////////////////////////////////

const ogst = {};

import getformfields from '../open-guide-editor/open-guide-misc/formreader.mjs';
import postData from '../open-guide-editor/open-guide-misc/fetch.mjs';

function byid(id) { 
    return document.getElementById(id);
}

// function to switch between light/dark themes
ogst.changetheme = function(mode = 'toggle') {
    const themetoggle = byid('themetoggle');
    const themetoggleicon = byid('themetoggleicon');
    if (mode === 'toggle') {
        mode = ((themetoggleicon.innerHTML.includes('light_mode')) ?
            'dark' : 'light');
    }
    document.documentElement.dataset.theme = mode;
    themetoggleicon.innerHTML = mode + '_mode</span>';
    themetoggle.blur();
}

// function to choose a project
ogst.chooseproject = function(projectname) {
    if (projectname != '') {
        window.projectname = projectname;
        ogst.updatenav();
    }
    if (window.isloggedin) {
    } else {
        ogst.showview('login');
    }
    // TODO: got to either project or login
}

ogst.establishuser = function(respObj) {
    // reset login fields
    byid('ogstname').removeAttribute("aria-invalid");
    byid('ogstname').value='';
    byid('ogstpwd').removeAttribute("aria-invalid");
    byid('ogstpwd').value='';
    byid('ogstremember').checked = false;
    byid('loginmsg').style.display = "none";
    window.isloggedin = true;
    window.username = respObj.loggedinuser;
    window.loginaccesskey = respObj.loginaccesskey;
    ogst.updatenav();
    // TODO: save if remember
    ogst.loadprojectmain();
}

ogst.loadprojectmain = function() {
    ogst.showview("projectmain");
}

ogst.login = async function() {
    // get the important elements
    const form = byid('login').getElementsByTagName('form')[0];
    const btn = byid('login').getElementsByTagName('button')[0];
    // get the info from the login form
    const forminfo =getformfields(form);
    if (forminfo.anyinvalid) { return; }
    forminfo.project = window.projectname;
    forminfo.postcmd = 'login';
    // mark as processing
    btn.innerHTML = 'logging in';
    btn.setAttribute('aria-busy','true');
    document.body.style.cursor = 'wait';
    // wait for result
    const loginresult = await postData('php/jsonhandler.php', forminfo);
    // change button back to normal
    btn.innerHTML = 'log in';
    btn.setAttribute('aria-busy','false');
    document.body.style.cursor = 'default';
    // report fetch errors
    if (loginresult?.error || !loginresult?.respObj ||
        loginresult?.respObj?.error) {
        byid("loginmsg").style.display = "block";
        byid("loginmsg").innerHTML = 'Login error. ' +
            (loginresult?.errMsg ?? '') +
            (loginresult?.respObj?.errMsg ?? '');
        byid("login").getElementsByTagName("h2")[0].scrollIntoView();
        return;
    }
    // report login errors
    const respObj = loginresult.respObj;
    if (!respObj?.success) {
        byid("loginmsg").style.display = "block";
        byid("loginmsg").innerHTML = 'Login error. ' +
            (respObj?.loginErrMsg ?? '');
        if (respObj?.nosuchuser) {
            byid('ogstname').setAttribute("aria-invalid", "true");
            byid('ogstname').addEventListener('change', function() {
                this.removeAttribute("aria-invalid");
            });
        }
        if (respObj?.wrongpassword) {
            byid('ogstpwd').setAttribute("aria-invalid", "true");
            byid('ogstpwd').addEventListener('change', function() {
                this.removeAttribute("aria-invalid");
            });
        }
        byid("login").getElementsByTagName("h2")[0].scrollIntoView();
        return;
    }
    ogst.establishuser(respObj);
}

ogst.logout = async function() {
    // mark button as processing
    const logoutbtn = byid("logoutbutton");
    logoutbtn.innerHTML = '';
    logoutbtn.setAttribute("aria-busy", "true");
    const request = {
        username: window.username,
        project: window.projectname,
        accesskey: window.loginaccesskey
    }
    // handle things browser-side
    window.username = '';
    window.loggedin = false;
    window.loginaccesskey = '';
    byid("projectmain").innerHTML = '';
    // unmark button as processing
    logoutbtn.innerHTML = 'log out';
    logoutbtn.setAttribute("aria-busy", "false");
    // update navigation panel
    ogst.updatenav();
    ogst.showview('login')
}

// function to update the top navigation
ogst.updatenav = function() {
    if (window.isloggedin) {
        byid('logoutbutton').parentNode.style.display = 'inline-block';
    } else {
       byid('logoutbutton').parentNode.style.display = 'none';
    }
    if (window.projectname != '') {
        const spsp = byid('projecttitle').getElementsByTagName("span");
        if (!spsp) { return; }
        spsp[0].innerHTML = window.projects[window.projectname].title;
        spsp[1].style.display = 'inline';
        document.title = window.projectname + ' Typesetting Framework';
    }
}

ogst.showview = function(id) {
    const vv = document.getElementsByClassName("ogstview");
    for (const v of vv) {
        v.style.display='none';
    };
    byid(id).style.display = 'block';
    byid('projecttitle').scrollIntoView();
}

//
// Things to do at load
//

// determine which color theme to start in
const wantsDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
ogst.changetheme((wantsDark) ? 'dark' : 'light');
ogst.chooseproject(window.projectname);
ogst.showview('chooseproject');

ogst.updatenav();

export default ogst;
