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
function complexFieldList(key, lbltxt = '', inputtype = 'text',
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
        d.remButton.iconname = 'remove'; 
        d.remButton = document.createElement("a");
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
            const reml = ll[ll.length -1]];
            reml.parentNode.removeChild(reml);
        }
        // value is array of all the inputs' values
        d.getValue = function() {
            const ll = this.getElementsByTagName("label");
            const rv = [];
            if (!ll) { return rv; }
            for (const l of ll) {
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

// creates a given metadata element based on the specification
// in the project settings, and restores a saved value, if any
function createMetaElement(key, projspec, saved) {
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
                subspec.label
            );
            lbl.getDisplay = function() {
                const currval = this.getValue();
                return currval.join(this.separator);
            }
            if ((saved != '') && (saved.length > 0)) {
                lbl.setValue(saved);
            }
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
            subspecs[subpec] = projspec[subspec];
        }
        const d = complexFieldList(key, labeltext, subspecs);
    }
}

// function when applied to a thing with an inputfield attribute
// (usually a label with an input inside), gets its value
// the label will be "this" in actual use
function getInputValue() {
    const v = this?.inputfield?.value; ?? '';
    if (this?.inputfield?.type == 'number') {
        return parseInt(v);
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
    const lblelem = document.createElement(label);
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
        for (const opt in seloptions) {
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
function separatorInpLbl(key, labeltxt, inputtype, required, placeholder) {
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
    return lbl;
}

// method applied to something like a label with an input inside
// (something with an inputfield attribute) to get the value of
// the internal input
function setInputValue = function(v) {
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
        d.remButton.iconname = 'remove'; 
        d.remButton = document.createElement("a");
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
            const reml = ll[ll.length -1]];
            reml.parentNode.removeChild(reml);
        }
        // value is array of all the inputs' values
        d.getValue = function() {
            const ll = this.getElementsByTagName("label");
            const rv = [];
            if (!ll) { return rv; }
            for (const l of ll) {
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
