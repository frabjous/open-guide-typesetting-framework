// LICENSE: GNU GPL v3 You should have received a copy of the GNU General
// Public License along with this program. If not, see
// https://www.gnu.org/licenses/.

//////////////////////// bibitems.mjs ///////////////////////////////////
// a variety of functions for handling bibliographic items in o.g.s.t  //
/////////////////////////////////////////////////////////////////////////

// main function for adding a bibitem
export function addbibitems(itemarray, arenew = false) {
    // should be attached to card item
    const card = this;
    // sanity check
    console.log(itemarray);
    return;
    if (!card?.bibcontentsitems) { return false; }
    for (const item of itemarray) {
        const itemelem = addelem({
            tag: 'details',
            parent: card.bibcontentsitems,
            classes: ['bibitem']
        });
        itemelem.info = item;
        itemelem.hdr = addelem({
            tag: 'summary',
            classes: ['bibitemhdr'],
            parent: itemelem
        });
        itemelem.hdr.left = addelem({
            tag: 'div',
            classes: ['bibitemhdrleft'],
            parent: itemelem.hdr
        });
        itemelem.hdr.right = addelem({
            tag: 'div',
            classes: ['bibitemhdrright'],
            parent: itemelem.hdr
        });
        itemelem.hdr.display = addelem({
            tag: 'span',
            classes: ['bibitemhdrdisplay'],
            parent: itemelem.hdr
        });
        itemelem.details = addelem({
            tag: 'div',
            classes: ['bibitemdetails'],
            parent: itemelem
        });
    }
}

// generic function for adding elements
export function addelem(opts) {
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

