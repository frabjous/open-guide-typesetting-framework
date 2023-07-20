// LICENSE: GNU GPL v3 You should have received a copy of the GNU General
// Public License along with this program. If not, see
// https://www.gnu.org/licenses/.

//////////////////////// bibitems.mjs ///////////////////////////////////
// a variety of functions for handling bibliographic items in o.g.s.t  //
/////////////////////////////////////////////////////////////////////////

import csl from './csl.mjs';

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
                    'none</option>';
                if (this.reimportbtn) {
                    this.reimportbtn.innerHTML = 'import';
                }
                return;
            }
            for (const ppposs of bibitem.possibilities) {
                const optelem = addelem({
                    tag: 'option',
                    value: ppposs,
                    innerHTML: ppposs
                });
            }
            if (this.reimportbtn) {
                this.reimportbtn.innHTML = 'reimport';
            }
        }
        // input to add arbitrary new phil papers id
        bibitem.newppinput = addelem({
            tag: 'input',
            parent: bibitem.widgets,
            placeholder: 'new PhilPapers ID'
        });
        // button to do reimportation
        bibitem.reimportbtn = addelem({
            tag: 'button',
            type: 'button',
            classes: ['outline'],
            parent: bibitem.widgets,
            innerHTML: 'import'
        });
        bibitem.setppselectopts();

        // delete the entry altogether
        bibitem.delbutton = addelem({
            tag: 'button',
            type: 'button',
            classes: ['bibremovebtn','secondary','outline'],
            title: 'remove this bibliography item',
            innerHTML: '<span class="material-symbols-outlined">' +
                'delete_forever</span>',
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
        bibitem.infotable = addelem({
            tag: 'table',
            parent: bibitem.inner
        });
        bibitem.infotablebody = addelem({
            tag: 'tbody',
            parent: bibitem.infotable
        });
        bibitem.fields = {};
        bibitem.addinfo = function(key, val = '') {
            // no duplicates; but update value if non-blank
            if (key in bibitem.fields) {
                if (val != '') {
                    bibitems.fields[key] = val;
                }
                return;
            }
            const tr = addelem({
                tag: 'tr',
                parent: this.infotablebody,
                bibproperty: key
            });
            const keytd = addelem({
                tag: 'td',
                parent: tr,
                innerHTML: key
            })
            const valtd = addelem({
                tag: 'td',
                parent: tr
            });
            if (key == 'extractedfrom') {
                bibitem.fields[keys] = addelem({
                    tag: 'textarea';
                    parent: valtd,
                    readOnly: true,
                    value: val
                });
                return;
            }
            if (key == 'type') {
                bibitem.fields.type = addelem({
                    tag: 'select',
                    parent: valtd
                });
                const commgroup = addelem({
                    tag: 'optgroup',
                    parent: bibitem.fields.type,
                    label: 'Common types'
                });
                const othergroup = addelem({
                    tag: 'optgroup',
                    parent: bibitem.fields.type,
                    label: 'Other types'
                });
                for (common in csl.common) {
                    const o = addelem({
                        tag: 'option',
                        innerHTML: common,
                        value: common,
                        parent: commgroup
                    });
                }
                for (other of csl.types) {
                    const o = addelem({
                        tag: 'option',
                        innerHTML: other,
                        value: other,
                        parent: othergroup
                    });
                }
                bibitem.fields.type.mybibitem = bibitem;
                // create appropriate fields
                bibitem.fields.type.onchange = function() {
                    const bibitem = this.mybibitem;
                    if (this.value in csl.common) {
                        const expected = csl.common[this.value];
                        for (const key of expected) {
                            bibitem.addinfo(key, '');
                        }
                    }
                }

                return;
            }
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

