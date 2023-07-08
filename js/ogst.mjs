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

ogst.changetheme = function(mode = 'toggle') {
    const themetoggleicon = byid('themetoggleicon');
    if (mode === 'toggle') {
        mode = ((themetoggleicon.innerHTML.includes('light_mode')) ?
            'dark' : 'light');
    }
    document.documentElement.dataset.theme = mode;
    themetoggleicon.innerHTML = mode + '_mode</span>';

}

// determine which mode to start in
const wantsDark = window.matchMedia('(prefers-color-scheme: dark)').matches;

ogst.changetheme((wantsDark) ? 'dark' : 'light');

export default ogst;
