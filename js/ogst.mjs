// LICENSE: GNU GPL v3 You should have received a copy of the GNU General
// Public License along with this program. If not, see
// https://www.gnu.org/licenses/.

////////////////////// ogst.mjs /////////////////////////////////////////
// defines the main open guide typesetting framework functions         //
/////////////////////////////////////////////////////////////////////////

const ogst = {};

import getformfields from '../open-guide-editor/open-guide-misc/formreader.mjs';
import postData from '../open-guide-editor/open-guide-misc/fetch.mjs';

function addelem(opts) {
    if (!("tag" in opts)) { return false; }
    const elem = document.createElement(opts.tag);
    for (const opt in opts) {
        if (opt == "tag") { continue; }
        if (opt == "parent") {
            opts[opt].appendChild(elem);
            continue;
        }
        if (opt == "classes") {
            for (const cln of opts[opt]) {
                elem.classList.add(cln);
            }
            continue;
        }
        elem[opt] = opts[opt];
    }
    return elem;
}

function byid(id) {
    return document.getElementById(id);
}

ogst.activebutton = function(h) {
    const mainnav = byid('projectmainnav');
    const bb = mainnav.getElementsByTagName('a');
    for (const b of bb) {
        b.classList.remove('outline','contrast');
    }
    const btn = byid(h + 'button');
    if (!btn) { return; }
    btn.classList.add('outline','contrast');
}

ogst.changedetails = async function() {
    const f = byid('changedetailsform');
    if (!f) { return; }
    const forminfo = getformfields(f);
    if (forminfo.anyinvalid) { return; }
    forminfo.postcmd = 'changedetails';
    // mark processing
    const b = byid('changedetailsbutton');
    b.innerHTML = 'changing';
    b.setAttribute('aria-busy', 'true');
    document.body.cursor = 'wait';
    // get info from server
    const respObj = await ogst.editorquery(forminfo);
    // mark no longer processing
    b.setAttribute('aria-busy', 'false');
    b.innerHTML = 'change';
    document.body.cursor = 'default';
    if (respObj?.success) {
        ogst.okmessage('New details saved.');
    }
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

ogst.clearmain = function() {
    const main = byid('projectmain');
    if (!window.isloggedin) {
        main.innerHTML = 'You do not have access to this ' +
            'when not logged in.';
        return;
    }
    if (!main?.hasbuttons) {
        main.innerHTML = '';
        const d = addelem({
            tag: 'div',
            parent: main,
            id: 'projectmainnav',
            classes: ['mainnav']
        });
        const currentbutton = addelem({
            parent: d,
            tag: 'a',
            id: 'currentbutton',
            href: '#current',
            innerHTML: 'current'
        });
        const archivebutton = addelem({
            parent: d,
            tag: 'a',
            id: 'archivedbutton',
            href: '#archived',
            innerHTML: 'archived'
        });
        const mydetailsbutton = addelem({
            parent: d,
            tag: 'a',
            id: 'mydetailsbutton',
            href: '#mydetails',
            innerHTML: 'my details'
        });
        const usersbutton = addelem({
            parent: d,
            tag: 'a',
            id: 'usersbutton',
            href: '#users',
            innerHTML: 'users'
        });
        for (const b of d.getElementsByTagName('a')) {
            b.setAttribute('role','button');
            b.classList.add('secondary');
        }
        main.msgdiv = addelem({
            tag: 'div',
            id: 'mainmsg',
            parent: main
        });
        main.msgdiv.style.display = 'none';
        main.loadingcard = addelem({
            tag: 'article',
            innerHTML: 'loading',
            parent: main
        });
        main.loadingcard.setAttribute('aria-busy', true);
        main.contents = addelem({
            parent: main,
            tag: 'section',
            id: 'projectcontents'
        });
        main.loading = function(l) {
            if (l) {
                main.loadingcard.style.display = 'block';
            } else {
                main.loadingcard.style.display = 'none';
            }
        }
        main.hasbuttons = true;
    }
    main.loading(true);
    main.contents.innerHTML = '';
    ogst.clearmessage();
    byid('projecttitle').scrollIntoView();
}

ogst.clearmessage = function() {
    const main = byid('projectmain');
    if (main?.msgdiv) {
        main.msgdiv.style.display = 'none';
        main.msgdiv.innerHTML = '';
        main.msgdiv.classList.remove('okmsg');
    }
}

ogst.editorquery = async function(req) {
    // always set postcmd in req
    if (!("postcmd" in req)) {
        console.error("Editor query made without postcmd.");
        return false;
    }
    req.username = window.username;
    req.accesskey = window.loginaccesskey;
    req.project = window.projectname;
    const resp = await postData('php/jsonhandler.php', req);
    if (resp?.error || (!("respObj" in resp)) ||
        resp?.respObj?.error) {
        ogst.reporterror('Error getting data from server. ' +
        (resp?.errMsg ?? '') + ' ' + (resp?.respObj?.errMsg ?? ''));
        return false;
    }
    return resp.respObj;
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

ogst.invitenewuser = async function() {
    const f = byid('newuserform');
    if (!f) { return; }
    const forminfo = getformfields(f);
    if (forminfo.anyinvalid) { return; }
    forminfo.postcmd = 'newuser';
    // mark processing
    const b = byid('newuserbutton');
    b.innerHTML = 'creating and inviting';
    b.setAttribute('aria-busy', 'true');
    document.body.cursor = 'wait';
    // set request to server
    const respObj = await ogst.editorquery(forminfo);
    // reload from scratch
    // save bad info
    const mainmsg = byid("mainmsg").innerHTML;
    ogst.clearmain();
    ogst.showusers(false);
    if (respObj?.success) {
        ogst.okmessage('Invitation sent.');
    } else {
        ogst.reporterror(mainmsg);
    }
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
    } 
    if (!window.isloggedin) {
        if (hash == "#forgotpwd") {
            ogst.showview("forgotpwd");
            return;
        }
        ogst.showview('login');
        return;
    }
    // can always get new password screen if logged in
    if (hash == '#newpwd') {
        ogst.showview('newpwd');
        return;
    }
    // everything else requires adding to the main element
    ogst.loadprojectmain();
    if (hash == '#mydetails') {
        ogst.showmydetails();
        return;
    }
    if (hash == '#users') {
        ogst.showusers();
        return;
    }
}

ogst.loadprojectmain = function() {
    ogst.clearmain();
    ogst.showview("projectmain");
    const main = byid("projectmain");
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
    ogst.clearmain();
    // unmark button as processing
    logoutbtn.innerHTML = 'log out';
    logoutbtn.setAttribute("aria-busy", "false");
    // update navigation panel
    ogst.updatenav();
    ogst.showview('login');
    byid("loginmsg").style.display = "block";
    byid("loginmsg").innerHTML = "You have been logged out."
}

ogst.okmessage = function(okmsg) {
    const main = byid('projectmain');
    if (main?.msgdiv) {
        main.msgdiv.style.display = 'block';
        main.msgdiv.innerHTML = okmsg;
        main.msgdiv.classList.add('okmsg');
        byid('projecttitle').scrollIntoView();
    } else {
        console.log('Message without main message: ' + okmsg);
    }
}

ogst.removeuser = function(usertodie) {
    const main = byid('projectmain');
    const dialog = addelem({
        tag: 'dialog',
        id: 'killuserdialog',
        parent: main.contents
    });
    dialog.setAttribute('open','true');
    const artcl = addelem({
        tag: 'article',
        parent: dialog
    });
    const hdr = addelem({
        tag: 'h4',
        innerHTML: 'Do you really want to remove the user ' +
            usertodie + '?',
        parent: artcl
    });
    const ftr = addelem({
        tag: 'footer',
        parent: artcl
    });
    const cancelbtn = addelem({
        tag: 'a',
        classes: ['secondary'],
        innerHTML: 'cancel',
        href: '',
        parent: ftr,
        mymdl: dialog,
        onclick: function(e) {
            e.preventDefault();
            this.mymdl.parentNode.removeChild(this.mymdl);
        }
    });
    const killbtn = addelem({
        tag: 'a',
        innerHTML: 'confirm',
        myusertodie: usertodie,
        href: '',
        mymdl: dialog,
        parent: ftr,
        onclick: async function(e) {
            e.preventDefault();
            const usertodie = this.myusertodie;
            const req = {
                postcmd: 'killuser',
                usertodie: usertodie
            }
            // set processing
            this.innerHTML = 'removing';
            this.setAttribute('aria-busy', 'true');
            document.body.cursor = 'wait';
            // send request to sever
            const respObj = await ogst.editorquery(req);
            // no longer processing
            document.body.cursor = 'default';
            // remove modal
            this.mymdl.parentNode.removeChild(this.mymdl);
            if (!respObj.success) { return; }
            // remove user from table
            const tbltbl = document.getElementsByClassName("userstable");
            if (!tbltbl) { return; }
            const tbl = tbltbl[0];
            const trtr = tbl.getElementsByTagName("tr");
            for (const trow of trtr) {
                const tdtd = trow.getElementsByTagName("td");
                if (!tdtd) { continue; }
                if (tdtd[0].innerHTML == usertodie) {
                    trow.parentNode.removeChild(trow);
                    ogst.okmessage('user removed');
                    break;
                }
            }
        }
    });
    cancelbtn.setAttribute('role','button');
    killbtn.setAttribute('role','button');
}

ogst.reporterror = function(errMsg) {
    const main = byid('projectmain');
    if (main?.msgdiv) {
        main.msgdiv.style.display = 'block';
        main.msgdiv.innerHTML = errMsg;
        main.msgdiv.classList.remove('okmsg');
        byid('projecttitle').scrollIntoView();
    } else {
        console.error(errMsg);
    }
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
    history.pushState('',document.title,'./');
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
        },
        4000);
    }
}


ogst.showmydetails = async function() {
    if (!window.isloggedin) { return; }
    const detresp = await ogst.editorquery({ postcmd: 'mydetails' });
    const main = byid('projectmain');
    main.loading(false);
    if (!detresp || !detresp?.mydetails) { return; }
    const mydetails = detresp.mydetails;
    ogst.clearmessage();
    ogst.activebutton('mydetails');
    const hdr = addelem({
        tag: 'h2',
        parent: main.contents,
        innerHTML: 'Editor details'
    });
    const form = addelem({
        tag: 'form',
        id: 'changedetailsform',
        parent: main.contents
    });
    form.onsubmit = function(e) { e.preventDefault(); };
    const namelbl = addelem({
        tag: 'label',
        parent: form,
        innerHTML: 'Name'
    });
    const nameinp = addelem({
        id: 'detailsname',
        name: 'detailsname',
        type: 'text',
        tag: 'input',
        required: 'true',
        value: mydetails.name,
        parent: namelbl
    });
    const emaillbl = addelem({
        tag: 'label',
        parent: form,
        innerHTML: 'Email'
    });
    const emailinp = addelem({
        id: 'detailsemail',
        name: 'detailsemail',
        type: 'email',
        tag: 'input',
        required: 'true',
        value: mydetails.email,
        parent: emaillbl
    });
    const changeButton = addelem({
        id: 'changedetailsbutton',
        tag: 'button',
        type: 'button',
        innerHTML: 'change',
        parent: form,
        onclick: function(e) {
            ogst.changedetails();
        }
    });
    const pwdchangelink = addelem({
        id: 'changepwdlink',
        innerHTML: 'change password',
        tag: 'a',
        parent: form,
        href: '#newpwd'
    });
}


ogst.showusers = async function(cm = true) {
    if (!window.isloggedin) { return; }
    const usersresp = await ogst.editorquery({
        postcmd: 'allusers'
    });
    const main = byid('projectmain');
    main.loading(false);
    if (!usersresp || !usersresp?.usersinfo) { return; }
    const usersinfo = usersresp.usersinfo;
    if (cm) { ogst.clearmessage(); }
    ogst.activebutton('users');
    const hdr = addelem({
        tag: 'h2',
        parent: main.contents,
        innerHTML: 'Site users'
    });
    // table of current users
    const tbl = addelem({
        tag: 'table',
        classes: ['userstable'],
        parent: main.contents
    });
    tbl.setAttribute("role","grid");
    const tbody = addelem({
        tag: 'tbody',
        parent: tbl
    });
    for (const usrname in usersinfo) {
        const trow = addelem({
            tag: 'tr',
            parent: tbody
        });
        const unamecell = addelem({
            tag: 'td',
            parent: trow,
            innerHTML: usrname
        });
        const necell = addelem({
            tag: 'td',
            parent: trow,
            innerHTML: '<span>' + usersinfo[usrname].name + '</span>' +
                '<br><span>(<a href="mailto:' + usersinfo[usrname].email +
                '">' + usersinfo[usrname].email + '</a>)</span>',
            classes: ['userinfocell']
        });
        const killcell = addelem({
            tag: 'td',
            parent: trow,
            title: 'remove user',
            innerHTML: '<span class="material-symbols-outlined">' +
                'delete_forever</span>',
            myusername: usrname,
            onclick: function() {
                ogst.removeuser(this.myusername);
            }
        });
    }

    // new user form
    const subhdr = addelem({
        tag: 'h3',
        parent: main.contents,
        innerHTML: 'Invite a new user'
    });
    const form = addelem({
        tag: 'form',
        id: 'newuserform',
        parent: main.contents
    });
    form.onsubmit = function(e) { e.preventDefault(); };
    const namelbl = addelem({
        tag: 'label',
        parent: form,
        innerHTML: 'Name'
    });
    const nameinp = addelem({
        id: 'newname',
        name: 'newname',
        type: 'text',
        tag: 'input',
        required: 'true',
        placeholder: 'full name',
        parent: namelbl
    });
    const emaillbl = addelem({
        tag: 'label',
        parent: form,
        innerHTML: 'Email'
    });
    const emailinp = addelem({
        id: 'newemail',
        name: 'newemail',
        type: 'email',
        tag: 'input',
        required: 'true',
        placeholder: 'email address',
        onchange: function() {
            this.myusernameinput.value =
                this.value.replace(/@.*/,'')
                .replace(/[^A-Za-z0-9]/g,'').toLowerCase();
        },
        parent: emaillbl
    });
    const usernamelbl = addelem({
        tag: 'label',
        parent: form,
        innerHTML: 'Username (short log in name)'
    });
    const usernameinp = addelem({
        id: 'newusername',
        name: 'newusername',
        type: 'text',
        tag: 'input',
        required: 'true',
        placeholder: 'username',
        parent: usernamelbl
    });
    emailinp.myusernameinput = usernameinp;
    usernameinp.setAttribute('pattern','^[a-zA-Z0-9]*$');
    const newuserButton = addelem({
        id: 'newuserbutton',
        tag: 'button',
        type: 'button',
        innerHTML: 'invite new user',
        parent: form,
        onclick: function(e) {
            ogst.invitenewuser();
        }
    });
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
