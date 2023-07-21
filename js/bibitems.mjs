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
                    bibitem.fields[key] = val;
                    if (key != 'extractedfrom') {
                        bibitem.info[key] = val;
                    }
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
                bibitem.fields.extractedfrom = addelem({
                    tag: 'textarea',
                    parent: valtd,
                    readOnly: true,
                    value: val
                });
                bibitem.fields.extractedfrom.getVal = function() {
                    return this.value;
                }
                if (val == '') {
                    tr.style.display = 'none';
                }
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
                    if (common == val) { o.selected = true; }
                }
                for (other of csl.types) {
                    const o = addelem({
                        tag: 'option',
                        innerHTML: other,
                        value: other,
                        parent: othergroup
                    });
                    if (other == val) { o.selected = true; }
                }
                bibitem.fields.type.mybibitem = bibitem;
                // create appropriate fields
                bibitem.fields.type.onchange = function() {
                    const bibitem = this.mybibitem;
                    bibitem.info.type = this.value;
                    // populate with common fields if common
                    if (this.value in csl.common) {
                        const expected = csl.common[this.value];
                        for (const key of expected) {
                            bibitem.addinfo(key, '');
                        }
                    }
                }
                bibitem.fields.type.getVal = function() {
                    return this.value;
                }
                return;
            }
            let ftype
            if (key == 'id') {
                bibitem.fields.id = addelem({
                    tag: 'input',
                    type: 'text',
                    parent: valtd,
                    mybibitem: bibitem,
                    oninput: function() {
                        this.removeAttribute('aria-invalid');
                    },
                    onchange: function() {
                        if (!/^[A-Za-z0-9_-]*$/.test(this.value)) {
                            this.setAttribute('aria-invalid','true');
                            return;
                        }
                        this.mybibitem.info.id = this.value;
                        this.mybibitem.updateLabel();
                    },
                    getVal: function() {
                        return this.value;
                    },
                    value: val
                });
                return;
            }
            if (key == 'abbreviation') {
                bibitem.fields.abbreviation = addelem({
                    type: text,
                    parent: valtd,
                    mybibitem: bibitem,
                    oninput: function() {
                        this.removeAttribute('aria-invalid');
                    },
                    onchange: function() {
                        if (!/^[A-Za-z0-9_-]*$/.test(this.value)) {
                            this.setAttribute('aria-invalid','true');
                            return;
                        }
                        this.mybibitem.info.abbreviation = this.value;
                        this.mybibitem.updateLabel();
                    },
                    getVal: function() {
                        return this.value;
                    },
                    value: val
                });
                return;
            }

            // only add real csl entries
            if (!(key in csl.properties)) { return; }
            const proptype = csl.properties[key];
            // special kinds of inputs
            if (proptype == 'date' || proptype == 'dateparts' || proptype == 'names') {
                bibitem.fields[key] = addelem({
                    tag: 'div',
                    parent: valtd,
                    mybibitem: bibitem,
                    mykey: key,
                    updateInfo: function() {
                        this.mybibitem.info[this.mykey] = this.getVal();
                    }
                });
                const div = bibitem.fields[key];
                if (proptype == 'date') {
                    div.classList.add('grid');
                    div.setAttribute('role','grid');
                    div.dsi = addelem({
                        tag: 'input',
                        type: 'number',
                        parent: div,
                        mydiv: div,
                        placeholder: 'year (start)',
                        onchange: function() {
                            this.mydiv.updateInfo();
                        }
                    });
                    div.dei = addelem({
                        tag: 'input',
                        type: 'number',
                        parent: div,
                        mydiv: div,
                        placeholder: 'year (end)',
                        onchange: function() {
                            this.mydiv.updateInfo();
                        }
                    });
                    // set value
                    if (val && val !== '' && ("date-parts" in val)) {
                        div.dsi.value = val["date-parts"][0][0].toString();
                        if (val["date-parts"].length > 1) {
                            div.dei.value = val["date-parts"][1][0].toString();
                        }
                    }
                    // get value
                    div.getVal = function() {
                        const div = this;
                        const rv = '';
                        if (div.dsi.value == '' && div.dei.value == '') {
                            return rv;
                        }
                        rv = {};
                        rv["date-parts"] = [];
                        if (div.dsi.value != '') {
                            rv["date-parts"].push([parseInt(div.dsi.value)]);
                        } else {
                            rv["date-parts"].push([]);
                        }
                        if (div.dei.value != '') {
                            rv["date-parts"].push([parseInt(div.dei.value)]);
                        }
                        return rv;
                    }
                }
                if (proptype == 'dateparts') {
                    div.classList.add('grid');
                    div.setAttribute('role','grid');
                    div.dyear = addelem({
                        tag: 'input',
                        type: 'number',
                        parent: div,
                        mydiv: div,
                        placeholder: 'year',
                        onchange: function() {
                            this.mydiv.updateInfo();
                        }
                    });
                    div.dmonth = addelem({
                        tag: 'input',
                        type: 'number',
                        parent: div,
                        mydiv: div,
                        placeholder: 'month',
                        onchange: function() {
                            this.mydiv.updateInfo();
                        }
                    });
                    div.dday = addelem({
                        tag: 'input',
                        type: 'number',
                        parent: div,
                        mydiv: div,
                        placeholder: 'day',
                        onchange: function() {
                            this.mydiv.updateInfo();
                        }
                    });
                    // set value
                    if (val && val !== '' && ("date-parts" in val)) {
                        if (val["date-parts"].length > 0) {
                            const prts = val["date-parts"][0];
                            if (prts.length > 0) {
                                div.dyear.value = prts[0].toString();
                            }
                            if (prts.length > 1) {
                                div.dmonth.value = prts[1].toString();
                            }
                            if (prts.length > 2) {
                                div.dday.value = prts[2].toString();
                            }
                        }
                    }
                    // get value
                    div.getVal = function() {
                        const div = this;
                        const rv = '';
                        if (div.dyear.value == '') {
                            return rv;
                        }
                        rv = {};
                        rv["date-parts"] = [[]];
                        rv["date-parts"][0].push(parseInt(div.dyear.value));
                        if (div.dmonth.value != '') {
                            rv["date-parts"][0].push(parseInt(div.dmonth.value));
                        }
                        if (div.dday.value != '') {
                            rv["date-parts"][0].push(parseInt(div.dday.value));
                        }
                        return rv;
                    }
                }
                if (proptype == 'names') {
                    div.addname = function() {
                        const nf = addelem({
                            tag: 'div',
                            parent: this,
                            classes: ['bibnamefields']
                        });
                        this.insertBefore(nf, this.buttons);
                        nf.family = addelem({
                            tag: 'input',
                            type: 'text',
                            parent: nf,
                            mydiv: div,
                            placeholder: 'family',
                            onchange: function() { this.mydiv.updateInfo(); }
                        });
                        nf.given = addelem({
                            tag: 'input',
                            type: 'text',
                            parent: nf,
                            mydiv: div,
                            placeholder: 'given',
                            onchange: function() { this.mydiv.updateInfo(); }
                        });
                        return nf;
                    }
                    div.removename = function() {
                        const ff = this.getElementsByClassName("bibnamefields");
                        if (!ff || ff.length == 0) { return; }
                        ff[0].parentNode.removeChild(ff[0]);
                        this.updateInfo();
                    }
                    div.buttons = addelem({
                        tag: 'div',
                        parent: div,
                        classes: ['bibnamefieldbuttons']
                    });
                    div.subtrbutton = addelem({
                        mydiv: div,
                        tag: 'button',
                        type: 'button',
                        parent: div.buttons,
                        innerHTML: '<span class="material-symbols-outlined">' +
                            'remove</span>',
                        title: 'remove',
                        onclick: function() {
                            this.mydiv.removename();
                        }
                    });
                    div.addbtn = addelem({
                        mydiv: div,
                        tag: 'button',
                        type: 'button',
                        parent: div.buttons,
                        innerHTML: '<span class="material-symbols-outlined">' +
                            'add</span>',
                        title: 'add',
                        onclick: function() {
                            this.mydiv.addname();
                        }
                    });
                    // set value
                    if (val != '' && (val.length > 0)) {
                        for (const nameobj of val) {
                            const nf = div.addname();
                            nf.given.value = nameobj?.given ?? '';
                            let family = nameobj?.family ?? '';
                            if ("non-dropping-particle" in nameobj) {
                                family = namobj["non-dropping-particle"] +
                                    ' ' + family;
                            }
                            nf.family.value = family;
                        }
                    } else {
                        // put in empty name otherwise
                        const nf = div.addname();
                    }
                    // get value
                    div.getVal = function() {
                        const nfnf = this.getElementsByClassName("bibnamefields");
                        if (!nfnf || nfnf.length == 0) { return ''; }
                        const rv = [];
                        for (const nf of nfnf) {
                            let family = nf.family.value;
                            if (family == '') { continue; }
                            const nameobj = {};
                            if (nf.given.value != '') {
                                nameobj.given = nf.given.value;
                            }
                            for (const prtcl of ['von ', 'van ', 'de ', 'del ', 'der ', 'du ']) {
                                if (family.substr(0, prtcl.length) == prtcl) {
                                    nameobj["non-dropping-particle"] = prtcl.trim();
                                    family = family.substr(prtcl.length);
                                }
                            }
                            nameobj.family = family;
                            rv.push(nameobj);
                        }
                        return rv;
                    }
                }


                return;
            }

            // fell through here for string and number (and array for categories)
            bibitem.fields[key] = addelem({
                tag: 'input',
                parent: valtd,
                type: 'text',
                mybibitem: bibitem,
                mykey: mykey,
                placeholder: key + ((proptype == "number") ? ' (number)' : ''),
                getVal: function() { return this.value; },
                onchange: function() {
                    this.mybibitem.info[this.mykey] = this.getVal();
                }
            });
            if (key == 'categories') {
                bibitem.fields[key].value = val.join(', ');
            } else {
                if (val != '') {
                    bibitem.fields[key].value = val;
                }
            }
            // categories really an array; here we separate by commas
            if (key == 'categories') {
                bibitem.fields[key].getVal = function() {
                    if (this.value == '') { return ''; }
                    return this.value.split(',').map((f) = (f.trim()));
                }
            }
        }
        bibitem.addinfo('extractedfrom', bibitem.extractedfrom);
        bibitem.addinfo('id', (bibitem?.info?.id ?? ''));
        bibitem.addinfo('type', (bibitem?.info?.type ?? ''));
        bibitem.addinfo('abbreviation',
            (bibitem?.info?.abbreviation ?? ''));

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

