// LICENSE: GNU GPL v3 You should have received a copy of the GNU General
// Public License along with this program. If not, see
// https://www.gnu.org/licenses/.

//////////////////////// bibitems.mjs ///////////////////////////////////
// a variety of functions for handling bibliographic items in o.g.s.t  //
/////////////////////////////////////////////////////////////////////////

// data, possibilities, philpapersid, extractedfrom

// main function for adding a bibitem
export function addbibitems(itemarray, arenew = false) {
    // should be attached to card item
    const card = this;
    // sanity check
    if (!card?.bibcontentsitems) { return false; }
    for (const item of itemarray) {
        const bibitem = addelem({
            tag: 'div',
            classes: ['bibitem'],
            parent: card.bibcontentsitems,
        });
        bibitem.inner = addelem({
            tag: 'div'
            parent: bibitem
        });
        bibitem.itemlabel = addelem({
            tag: 'div',
            parent: bibitem.inner
        });

        bibitem.updateLabel = function() {
            this.itemlabel.innerHTML = '@' + this.info.id;
        }
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

