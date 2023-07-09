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
    ogst.loadhash((window?.location?.hash ?? ''));
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
    ogst.loadhash((window?.location?.hash ?? ''));
}

ogst.loadhash = function(hash) {
    // absolute top preference is to load newpwd if set by
    // url parameters
    if (("newpwdlink" in window) && window.newpwdlink != '') {
        ogst.showview('newpwd');
        return;
    }
    // choosing a project and logging in always
    // takes precedent
    if (window.projectname == '') {
        ogst.showview('chooseproject');
        return;
    } else {
        if (!window.isloggedin) {
            if (hash == "#forgotpwd") {
                ogst.showview("forgotpwd");
                return;
            }
            ogst.showview('login');
            return;
        }
    }
    if (hash == '') {
        ogst.loadprojectmain();
    }
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
    // do request
    const request = {
        postcmd: 'logout',
        username: window.username,
        project: window.projectname,
        accesskey: window.loginaccesskey
    }
    const logoutResp = await postData('php/jsonhandler.php', request);
    // handle things browser-side
    window.username = '';
    window.isloggedin = false;
    window.loginaccesskey = '';
    byid("projectmain").innerHTML = '';
    // unmark button as processing
    logoutbtn.innerHTML = 'log out';
    logoutbtn.setAttribute("aria-busy", "false");
    // update navigation panel
    ogst.updatenav();
    ogst.showview('login');
    byid("loginmsg").style.display = "block";
    byid("loginmsg").innerHTML = "You have been logged out."
}

ogst.resetpwd = async function() {
    // read form
    const form = byid('forgotpwd').getElementsByTagName('form')[0];
    const forminfo = getformfields(form);
    if (forminfo.anyinvalid) { return; }
    // mark as processing
    const btn = byid('pwdresetbutton');
    btn.innerHTML = 'sending a password reset link';
    btn.setAttribute('aria-busy','true');
    document.body.style.cursor = 'wait';
    // request link
    const request = {
        postcmd: 'resetpwd',
        project: window.projectname,
        email: forminfo.ogstpwdreset
    }
    const response = await postData('php/jsonhandler.php', request);
    // no longer waiting
    btn.setAttribute('aria-busy','false');
    btn.innerHTML = 'email a password reset link';
    // we show msg in any case
    const msg = byid('resetmsg');
    msg.style.display = 'block';

    // check for error
    document.body.style.cursor = 'default';
    if (response?.error || !("respObj" in response) ||
        response?.respObj?.error) {
        msg.innerHTML = 'Error requesting password reset. ' +
            (response?.errMsg ?? '') + ' ' +
            (response?.respObj?.errMsg ?? '');
        byid('forgotpwd').getElementsByTagName('h2')[0].scrollIntoView();
        return;
    }
    // check for lack of success
    const respObj = response.respObj;
    if (!respObj.success) {
        msg.innerHTML = 'Error with password request. ' +
            (respObj?.resetErrMsg ?? '');
        byid('forgotpwd').getElementsByTagName('h2')[0].scrollIntoView();
        return;
    }
    // it was successful, destroy the form
    form.innerHTML = '';
    msg.classList.add('okmsg');
    msg.innerHTML = 'Check your email in a minute or two ' +
        'for the reset link. You should close this tab now.';
}

ogst.setnewpwd = async function() {
    const form = byid('newpwd').getElementsByTagName('form')[0];
    const forminfo = getformfields(form);
    if (forminfo.anyinvalid) { return; }
    if (forminfo.ogstnewpwd1 != forminfo.ogstnewpwd2) {
        const msg = byid("newpwdmsg");
        msg.style.display = "block";
        msg.innerHTML = "Passwords do not match.";
        console.log('dnm',(new Date()).getTime());
        return;
    }
    // mark as processing
    const btn = byid('newpwdbutton');
    btn.innerHTML = 'setting new password';
    btn.setAttribute('aria-busy', 'true');
    document.body.cursor = 'wait';
    // add to request
    forminfo.postcmd = 'newpwd';
    forminfo.newpwdlink = (window.newpwdlink ?? '');
    forminfo.accesskey = (window.loginaccesskey ?? '');
    forminfo.project = window.projectname;
    forminfo.username = window.username;
    forminfo.wasloggedin = window.isloggedin;
    //  make request
    const response = await postData('php/jsonhandler.php', forminfo);
    // mark no longer waiting
    document.body.cursor = 'default';
    btn.innerHTML = 'set new password';
    btn.setAttribute('aria-busy', 'false');
    // always show message afterwards
    const msg = byid("newpwdmsg");
    msg.style.display = "block";
    // check for errors
    if (response?.error || !("respObj" in response) ||
        response?.respObj?.error) {
        msg.innerHTML = "Error requesting new password. " +
            (response?.errMsg ?? '') +
            (response?.respObj?.errMsg ?? '');
        return;
    }
    // check for problems with request
    const respObj = response.respObj;
    if (!respObj?.success) {
        msg.innerHTML = 'Error setting new password. ' +
            (respObj?.pwdChangeErrMsg ?? '');
        return;
    }
    // success; destroy form
    form.innerHTML = '';
    msg.classList.add('okmsg');
    msg.innerHTML = 'Password changed. You should get an email confirmation.';
    window.newpwdlink = '';
    // in four seconds, redirect back to login form and reset form
    if (!window.isloggedin) {
        setTimeout(function() {
            ogst.showview('login');
            byid('ogstname').removeAttribute("aria-invalid");
            byid('ogstname').value='';
            byid('ogstpwd').removeAttribute("aria-invalid");
            byid('ogstpwd').value='';
            byid('ogstremember').checked = false;
            byid('loginmsg').style.display = "none";
            window.location.search = '?project=' .
                encodeURIComponent(window.project);
        },
        4000);
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
        document.title = window.projects[window.projectname].title + ' Typesetting Framework';
    }
}

//
// Things to do at load
//
// attach load change listener
window.onhashchange = function(e) {
    ogst.loadhash(window?.location?.hash ?? '');
}

// make project title go to main project page or login, etc.
byid("projecttitle").onclick = function(e) {
    if (window.location.hash != '') {
        window.location.hash = '';
        return;
    }
    ogst.loadhash('');
}

// determine which color theme to start in
const wantsDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
ogst.changetheme((wantsDark) ? 'dark' : 'light');

ogst.loadhash(window?.location?.hash ?? '');

// set the nav and title appropriately
ogst.updatenav();

export default ogst;
