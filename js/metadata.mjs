// LICENSE: GNU GPL v3 You should have received a copy of the GNU General
// Public License along with this program. If not, see
// https://www.gnu.org/licenses/.

////////////////////// metadata.mjs ////////////////////////////////////
// Various functions having to do with metafields in assignment cards //
////////////////////////////////////////////////////////////////////////


function createMetaElement(key, projspec, saved) {
    // two types of arrays: those with multiple fields
    // and those with a separator
    if (Array.isArray(projspec)) {
        // data actually in first element of array
        const subspec = projspec[0];
        if ("separator" in subspec) {
            
        }
    }
}
