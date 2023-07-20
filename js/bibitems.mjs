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
        // set key values from item
        bibitem.info = {};
        if ("data" in item) {
            bibitem.info = item.data;
        }
        bibitem.possibilities = [];
        if ("possibilities" in item) {
            bibitem.possibilities = item.possibilities;
        }
        bibitem.philpapersid = '';
        if ("philpapersid" in item) {
            bibitem.philpapersid = item.philpapersid;
        }
        bibitem.extractedfrom = '';
        if ("extractedfrom" in item) {
            bibitem.extractedfrom = item.extractedfrom;
        }
        bibitem.inner = addelem({
            tag: 'div',
            parent: bibitem
        });
        // label at the top; should change when id changes
        bibitem.itemlabel = addelem({
            tag: 'div',
            classes: ['bibitemlabel'],
            parent: bibitem.inner
        });
        // buttons near top
        bibitem.widgets = addelem({
            tag: 'div',
            parent: bibitem.inner,
            classes: ['grid','bibitemwidgets']
        });
        // selector for different pp id
        bibitem.ppselect = addelem({
            tag: 'select',
            parent: bibitem.widgets
        });
        // function to fill selector
        bibitem.setppselectopts = function() {
            const ss = bibitem.ppselect.getElementsByTagName("option");
            // remove existing options
            while (ss.length > 0) {
                ss[ss.length-1].parentNode.removeChild(ss[ss.length-1]);
            }
            if (bibitem.possibilities.length == 0) {
                bibitem.ppselect.innerHTML =
                    '<option value="" disabed selected">' +
                    'none to select</option>';
                return;
            }
            for (const ppposs of bibitem.possibilities) {
                const optelem = addelem({
                    tag: 'option',
                    value: ppposs,
                    innerHTML: ppposs
                });
            }
        }
        bibitem.setppselectopts();
        // input to add arbitrary new phil papers id
        bibitem.newppinput = addelem({
            tag: 'input',
            parent: bibitem.widgets,
            placeholder: 'new PhilPapers ID'
        });
        // delete the entry altogether
        bibitem.delbutton = addelem({
            tag: 'button',
            type: 'button',
            title: 'remove this bibliography item',
            innerHTML: '<span class="material-symbols-outlined">' +
                'delete_forever</span> remove',
            mybibitem: bibitem,
            parent: bibitem.widgets,
            onclick: function() {
                this.mybibitem.parentNode.removeChild(this.mybibitem);
            }
        });
        // function to update the label
        bibitem.updateLabel = function() {
            this.itemlabel.innerHTML = '@' + (this.info?.id ?? '');
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

