// LICENSE: GNU GPL v3 You should have received a copy of the GNU General
// Public License along with this program. If not, see
// https://www.gnu.org/licenses/.

////////////////////// ogst.mjs /////////////////////////////////////////
// defines the main open guide typesetting framework functions         //
/////////////////////////////////////////////////////////////////////////

const ogst = {};

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
        window.projectname = projects[projectname].title;
        ogst.updatenav();
    }
    if (window.isloggedin) {
    } else {
        ogst.showview('login');
    }
    // TODO: got to either project or login
}

ogst.login = function() {
    let form = byid('login').getElementsByTagName('form')[0];

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
        spsp[0].innerHTML = window.projectname;
        spsp[1].style.display = 'inline';
    }
}

ogst.showview = function(id) {
    const vv = document.getElementsByClassName("ogstview");
    for (const v of vv) {
        v.style.display='none';
    };
    byid(id).style.display = 'block';
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
