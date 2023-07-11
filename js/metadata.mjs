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
        // those with separator are a single input but need to split
        // and join values
        if ("separator" in subspec) {
            const lbl = labelWithInput(
                key,
                subspec.label,
                subspec.inputtype,
                subspec.required, [], subspec.label
            );
            lbl.separator = subspec.separator;
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
            if ((saved != '') && (saved.length > 0)) {
                lbl.setValue(saved);
            }
            return lbl;
        }
        // no separator, we are dealing with an expandable thingy
        const d = document.createElement("div");
        // buttons for adding, removing fields
        d.buttondiv = document.createElement("div");
        d.buttondiv.classList.add("metabuttondiv");
        d.appendChild(d.buttondiv);
        d.addButton = document.createElement("a");
        d.addButton.iconname = 'add';
        d.remButton.iconname = 'remove'; 
        d.remButton = document.createElement("a");
        for (const b of [d.remButton, d.addButton]) {
            b.setAttribute("role","button");
            b.href = '';
            b.onmousedown = function(e) { e.preventDefault(); };
            b.mydiv = d;
            d.buttondiv.appendChild(b);
        }
        // attach values from spec so they can be read by methods
        d.label = subspec?.label ?? '';
        d.inputtype = subspec?.label ?? 'text';
        d.mykey = key;
        d.required = subspec?.required ?? false;
        d.placeholder = subspec?.placeholder ?? '';
        // add a field
        d.addField = function() {
            // don't put text in label, just set as placeholder
            const newl = labelWithInput(this.mykey, '',
                this.inputtype, this.required, [], this.placeholder);
            this.insertBefore(newl, this.buttondiv);
            return newl;
        }
        d.addButton.onclick = function(e) { this.mydiv.addField(); }
        // remove a field
        d.remButton.onclick = function(e) {
            const ll = this.mydiv.getElementsByTagName("label");
            if (!ll) { return; }
            const reml = ll[ll.length -1]];
            reml.parentNode.removeChild(reml);
        }
        // value is array of all the inputs' values
        d.getValue = function() {
            const ll = this.getElementsByTagName("label");
            const rv = [];
            for (const l of ll) {
                rv.push(l.getInputValue());
            }
            return;
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
        d.setValue(saved);
        return d;
    }
}

function getInputValue() {
    const v = this?.inputfield?.value; ?? '';
    if (this?.inputfield?.type == 'number') {
        return parseInt(v);
    }
    return v;
}

function labelWithInput(key, labeltxt, itype, req, seloptions = [],
    placeholder = '') {
    // determine tag type
    let tt = 'input';
    if (itype == 'select' || itype == 'textarea') {
        tt = itype;
    }
    // create label
    const lblelem = document.createElement(label);
    lblelem.innerHTML = labeltxt;
    lblelem.inputfield = document.createElement(tt);
    lblelem.appendChild(lblelem.inputfield);
    if (tt == 'input') {
        lblelem.inputfield.type = itype;
    }
    lblelem.inputfield.required = req;
    if (placeholder != '') {
        lblelem.inputfield.placeholder = placeholder;
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

function setInputValue = function(v) {
    if (!this?.inputfield) { return; }
    this.inputfield.value = v.toString();
}
