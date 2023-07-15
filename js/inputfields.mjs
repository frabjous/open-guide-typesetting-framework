// LICENSE: GNU GPL v3 You should have received a copy of the GNU General
// Public License along with this program. If not, see
// https://www.gnu.org/licenses/.

////////////////////// inputfields.mjs //////////////////////////////////
// Various functions having to do with creating input fields, used for //
// both metadata
/////////////////////////////////////////////////////////////////////////

// metadata elements should at the top level have getValue, setValue,
// and getDisplay methods, even if they are not directly input fields,
// but various things holding them

// returns a div with a list of fields giving rise to an array of values,
// where buttons can be used to add or subtract values/inputs
// but each subvalue itself has multiple fields
function complexFieldList(key, lbltxt, subspecs) {
        const d = document.createElement("div");
        // create top label
        d.mainlabel = document.createElement("label");
        d.appendChild(d.mainlabel);
        d.mainlabel.innerHTML = lbltxt;
        // buttons for adding, removing fields
        d.buttondiv = document.createElement("div");
        d.buttondiv.classList.add("fieldlistbuttondiv");
        d.appendChild(d.buttondiv);
        d.addButton = document.createElement("a");
        d.addButton.iconname = 'add';
        d.remButton = document.createElement("a");
        d.remButton.iconname = 'remove';
        d.remButton.title = 'remove';
        d.addButton.title = 'add';
        for (const b of [d.remButton, d.addButton]) {
            b.setAttribute("role","button");
            b.innerHTML = '<span class="material-symbols-outlined">' +
                b.iconname + '</span>';
            b.href = '';
            b.onmousedown = function(e) { e.preventDefault(); };
            b.mydiv = d;
            d.buttondiv.appendChild(b);
        }
        // attach values from spec so they can be read by methods
        d.mykey = key;
        d.subspecs = subspecs;
        // add a field
        d.addField = function() {
            const subdiv = document.createElement("div");
            subdiv.classList.add("grid");
            subdiv.classList.add("complexfieldsubentry");
            this.insertBefore(subdiv, this.buttondiv);
            const thisisfirst =
                (this.getElementsByClassName("grid").length == 1);
            // subdiv contains multiple inputs, one for each subspec
            for (const subkey in this.subspecs) {
                const subfield = document.createElement("input");
                subfield.type = this.subspecs[subkey].inputtype;
                subfield.required = (thisisfirst &&
                    this.subspecs[subkey].required);
                subfield.mysubkey = subkey;
                subfield.placeholder = this.subspecs[subkey].label;
                subdiv.appendChild(subfield);
            }
            // function to get the complex value, i.e., an object
            // (rv) with attributes for each subkey
            subdiv.getSubValue = function() {
                const ii = this.getElementsByTagName("input");
                if (!ii || ii.length == 0) { return {}; }
                const rv = {};
                for (const i of ii) {
                    if (!i?.mysubkey) { continue; }
                    rv[i.mysubkey] = i.value.trim();
                    if (i.type == "number") {
                        rv[i.mysubkey] == ((i.value == '') ? '' :
                            parseFloat(i.value));
                    }
                }
                return rv;
            }
            // function to set the complex subvalue using an object
            // with attributes for eacj subkey
            subdiv.setSubValue = function(v) {
                const ii = this.getElementsByTagName("input");
                if (!ii || ii.length == 0) { return; }
                for (const vkey in v) {
                    for (const i of ii) {
                        if (i.mysubkey == vkey) {
                            if (i.type == "number") {
                                i.value = v[vkey].toString();
                                break;
                            }
                            i.value = v[vkey];
                            break;
                        }
                    }
                }
            }
            return subdiv;
        }
        d.addButton.onclick = function(e) {
            e.preventDefault();
            this.mydiv.addField();
        }
        // remove a field
        d.remButton.onclick = function(e) {
            e.preventDefault();
            const ss = this.mydiv.getElementsByClassName("complexfieldsubentry");
            if (!ss) { return; }
            const rems = ss[ss.length -1];
            if (!rems) { return; }
            rems.parentNode.removeChild(rems);
        }
        // value is array of all the inputs' values
        d.getValue = function() {
            const ss = this.getElementsByClassName("complexfieldsubentry");
            const rv = [];
            if (!ss) { return rv; }
            for (const s of ss) {
                rv.push(s.getSubValue());
            }
            return rv;
        }
        // setting the value requires creating at least one subdiv
        // or more if more are saved
        d.setValue = function(v) {
            if ((v=='') || v.length == 0) {
                this.addField();
                return;
            }
            for (const thisv of v) {
                const l = this.addField();
                l.setSubValue(thisv);
            }
        }
        return d;
}

// creates a given metadata element based on the specification
// in the project settings, and restores a saved value, if any
export function createMetaElement(key, projspec, saved = '') {
    // two types of arrays: those with multiple fields
    // and those with a separator
    if (Array.isArray(projspec)) {
        // data actually in first element of array
        const subspec = projspec[0];
        // those with separator are a single input but need to split
        // and join values
        if ("separator" in subspec) {
            const lbl = separatorInpLbl(
                key,
                subspec.label,
                subspec.inputtype,
                subspec.required,
                subspec.label,
                subspec.separator
            );
            lbl.getDisplay = function() {
                const currval = this.getValue();
                return currval.join(this.separator);
            }
            if ((saved != '') && (saved.length > 0)) {
                lbl.setValue(saved);
            }
            lbl.classList.add("metaelement");
            return lbl;
        }
        // no separator, so we have a simple field list
        const labeltext = subspec?.label ?? '';
        const inputtype = subspec?.inputtype ?? 'text';
        const required = subspec?.required ?? false;
        const placeholder = subspec?.placeholder ?? '';
        const d = simpleFieldList(key, labeltext, inputtype, required,
            placeholder);
        d.getDisplay = function() {
            const currval = this.getValue();
            if (currval.length == 0) { return ''; }
            if (currval.length == 1) { return currval[0]; };
            return currval.slice(0,-1).join(', ') + ' and ' +
                currval[ currval.length - 1];
        }
        d.setValue(saved);
        d.classList.add("metaelement");
        return d;
    }
    // got here, then the projspec is some kind of object, not array
    // there are two possibilities, simple and those with subcategories
    if (projspec?.subcategories) {
        const labeltext = projspec.label;
        const subspecs = {};
        for (const subspec in projspec) {
            // the subcategories and label attributes are not
            // genuine subspecs
            if (subspec == 'subcategories' || subspec == 'label') {
                continue;
            }
            subspecs[subspec] = projspec[subspec];
        }
        const d = complexFieldList(key, labeltext, subspecs);
        // the display will just be the "first" subvalues joined
        // for authors, etc. this should be "name"
        d.getDisplay = function() {
            const currval = this.getValue();
            const firstvals = [];
            for (const subval of currval) {
                for (const subkey in subval) {
                    firstvals.push(subval[subkey]);
                    break;
                }
            }
            if (firstvals.length == 0) { return ''; }
            if (firstvals.length == 1) { return firstvals[0]; }
            return firstvals.slice(0,-1).join(', ') + ' and ' +
                firstvals[ firstvals.length - 1];
        }
        d.setValue(saved);
        d.classList.add("metaelement");
        return d;
    }
    // otherwise we are left with a relatively simple thingy
    const lbl = labelWithInput(
        key,
        projspec?.label ?? '',
        projspec?.inputtype ?? 'text',
        projspec?.required ?? false,
        projspec?.selectoptions ?? [],
        projspec?.placeholder ?? (projspec?.label ?? '')
    );
    lbl.getValue = lbl.getInputValue;
    // for such simple things, getting the display is just getting
    // its value and ensuring it's a string
    lbl.getDisplay = function() {
        return this.getInputValue().toString();
    }
    lbl.setValue = lbl.setInputValue;
    lbl.setValue(saved);
    lbl.classList.add("metaelement");
    return lbl;
}

// function when applied to a thing with an inputfield attribute
// (usually a label with an input inside), gets its value
// the label will be "this" in actual use
function getInputValue() {
    let v = this?.inputfield?.value ?? '';
    v = v.trim();
    if (this?.inputfield?.type == 'number') {
        if (v == '') { return ''; }
        return parseFloat(v);
    }
    return v;
}

// a relatively generic field with a label and input field
function labelWithInput(key, labeltxt, itype, req, seloptions = [],
    placeholder = '') {
    // determine tag type
    let tt = 'input';
    if (itype == 'select' || itype == 'textarea') {
        tt = itype;
    }
    // create label
    const lblelem = document.createElement('label');
    if (labeltxt !== '') {
        lblelem.innerHTML = labeltxt;
    }
    lblelem.inputfield = document.createElement(tt);
    lblelem.appendChild(lblelem.inputfield);
    if (tt == 'input') {
        lblelem.inputfield.type = itype;
    }
    lblelem.inputfield.required = req;
    if (placeholder != '') { lblelem.inputfield.placeholder = placeholder;
    }
    lblelem.getInputValue = getInputValue;
    lblelem.setInputValue = setInputValue;
    lblelem.mykey = key;
    // populate options for select fields
    if (itype == 'select') {
        for (const opt of seloptions) {
            const optelem = document.createElement("option");
            optelem.innerHTML = opt;
            optelem.value = opt;
            lblelem.inputfield.appendChild(optelem);
        }
    }
    return lblelem;
}

// a single input field that really returns an array of values which
// are the contents of the field split by a certain separator
function separatorInpLbl(key, labeltxt, inputtype, required,
    placeholder, separator) {
    const lbl = labelWithInput(key, labeltxt, inputtype, required,
        [], placeholder);
    lbl.separator = separator;
    // value is actually got by exploding the value, and
    // trimming surrounding whitespace
    lbl.getValue = function() {
        const str = lbl.getInputValue();
        return str.split(this.separator).map((s)=>(s.trim()));
    }
    lbl.setValue = function(arr) {
        const j = arr.join(this.separator);
        lbl.setInputValue(j);
    }
    lbl.mykey = key;
    return lbl;
}

// method applied to something like a label with an input inside
// (something with an inputfield attribute) to get the value of
// the internal input
function setInputValue(v) {
    if (!this?.inputfield) { return; }
    this.inputfield.value = v.toString();
}

// returns a div with a list of fields giving rise to an array of values
// where buttons can be used to add or subtract values/inputs
function simpleFieldList(key, lbltxt = '', inputtype = 'text',
    required = false, placeholder = '') {
        const d = document.createElement("div");
        // create top label
        d.mainlabel = document.createElement("label");
        d.appendChild(d.mainlabel);
        d.mainlabel.innerHTML = lbltxt;
        // buttons for adding, removing fields
        d.buttondiv = document.createElement("div");
        d.buttondiv.classList.add("fieldlistbuttondiv");
        d.appendChild(d.buttondiv);
        d.addButton = document.createElement("a");
        d.addButton.iconname = 'add';
        d.remButton = document.createElement("a");
        d.remButton.iconname = 'remove'; 
        d.addButton.title = 'add';
        d.remButton.title = 'remove';
        for (const b of [d.remButton, d.addButton]) {
            b.setAttribute("role","button");
            b.innerHTML = '<span class="material-symbols-outlined">' +
                b.iconname + '</span>';
            b.href = '';
            b.onmousedown = function(e) { e.preventDefault(); };
            b.mydiv = d;
            d.buttondiv.appendChild(b);
        }
        // attach values from spec so they can be read by methods
        d.label = lbltxt;
        d.inputtype = inputtype;
        d.mykey = key;
        d.required = required;
        d.placeholder = placeholder;
        // add a field
        d.addField = function() {
            // don't put text in label, just set as placeholder
            const shouldberequired = (
                this.getElementsByTagName("label").length == 0 &&
                this.required
            );
            // never give them labels? because we already have the main
            // one?
            const newl = labelWithInput(this.mykey, '',
                this.inputtype, shouldberequired, [], this.placeholder);
            this.insertBefore(newl, this.buttondiv);
            return newl;
        }
        d.addButton.onclick = function(e) {
            e.preventDefault();
            this.mydiv.addField();
        }
        // remove a field
        d.remButton.onclick = function(e) {
            e.preventDefault();
            const ll = this.mydiv.getElementsByTagName("label");
            if (!ll) { return; }
            const reml = ll[ll.length -1];
            reml.parentNode.removeChild(reml);
        }
        // value is array of all the inputs' values
        d.getValue = function() {
            const ll = this.getElementsByTagName("label");
            const rv = [];
            if (!ll) { return rv; }
            for (const l of ll) {
                if (!l.getInputValue) { continue; }
                rv.push(l.getInputValue());
            }
            return rv;
        }
        // setting the value requires creating at least one field
        // or more if more are saved
        d.setValue = function(v) {
            if ((v=='') || (v.length == 0)) {
                this.addField();
                return;
            }
            for (const thisv of v) {
                const l = this.addField();
                l.setInputValue(thisv);
            }
        }
        return d;
}
