<?php

session_start();
require_once 'getjtsettings.php';
require 'libjt.php';
require 'libjtbib.php';

function rage_quit($s = '') {
    echo "ERROR: $s.";
    exit(0);
}

if (!isset($_SESSION["_jt_user"])) {
    rage_quit("Not logged in");
}

if (!isset($_GET["doc"])) {
    rage_quit("No document number given");
}

$doc_num = $_GET["doc"];

$doc_folder = $jt_settings->datafolder . '/docs/' . $doc_num;

$doc_status_file = $doc_folder . '/status.json';

if (!file_exists($doc_status_file)) {
    rage_quit("Document specified, or its status file, does not exist");
} 

$doc_status = json_decode(file_get_contents($doc_status_file));

$bibdata_file = $doc_folder . '/bibdata.json';

if (!file_exists($bibdata_file)) {
    initialize_bibdata($doc_num);
}

?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8" />
        <meta name="description" content="<?php echo $jt_settings->journal_name; ?> typesetting site" />
        <meta name="author" content="<?php echo $jt_settings->contact_name; ?>" />
        <meta name="copyright" content="© <?php echo getdate()["year"] . ' ' . $jt_settings->contact_name; ?>" />
        <meta name="keywords" content="journal,typesetting" />
        <meta name="robots" content="noindex,nofollow" />  
        <meta name="viewport" content="width=device-width, initial-scale=1" />
        <meta name="apple-mobile-web-app-capable" content="yes" />
        <meta name="mobile-web-app-capable" content="yes" />
        <link rel="shortcut icon" href="favicon.ico" type="image/x-icon" />
        <script type="text/javascript" charset="utf-8" src="/kcklib/kckdialog.js"></script>
        <script type="text/javascript" charset="utf-8" src="/kcklib/ajax.js"></script>
        <link rel="stylesheet" type="text/css" href="/kcklib/kckdialog.css" /> 
        <title>Edit bibliography: document number <?php echo $doc_num; ?></title>
        <style>
            #logoutstrip {
                background-color: rgb(0,0,0,0.6);
                position: fixed;
                top: 3px;
                right: 3px;
                border-radius: 5px;
                display: inline-block;
                padding: 1ex;
                text-align: right;
            }
            #logoutstrip a, #logoutstrip a:link, #logoutstrip a:visited {
                color: #CCCCFF;
            }
            #bottombuttons {
                position: fixed;
                bottom: 2px;
                right: 3px;
                display: inline-block;
            }
            #bottombuttons span {
                padding: 1ex;
                text-decoration: underline;
                color: #CCCCFF;
                display: inline-block;
                background-color: rgb(0,0,0,0.6);
                border-radius: 5px;
                cursor: pointer;
            }
            .buttonsdiv {
                margin-bottom: 1ex;
            }
            fieldset span {
                display: block;
                white-space: nowrap;
                margin-bottom: 1ex;
            }
            input, button {
                margin-right: 0.5em;
            }
            fieldset {
                margin-bottom: 1ex;
            }
            input[type="number"] {
                width: 4em;
            }
            #biblist {
                list-style-type: none;
                margin-left: 0;
                padding-left: 0;
            }
            #biblist item {
                margin: 0;
            }
            .insertentry {
                background-color: gray;
                padding-top: 1ex;
                padding-bottom: 1ex;
                padding-left: 0.5em;
                padding-right: 0.5em;
                margin-bottom: 1ex;
            }
            fieldset textarea {
                display: block;
                width: 100%;
                font-size: 125%;
            }
            .nobreak {
                white-space: nowrap;
            }
            .radiogroup {
                margin-right: 1em;
                display: inline-block;
            }
            .fieldgroup {
                white-space: nowrap;
                margin-bottom: 1ex;
            }
            .fakelink {
                color: blue;
                cursor: pointer;
                text-decoration: underline;
            }
            .smaller {
                font-variant: small-caps;
                font-size: 80%;
            }
        </style>
        <script>
            
            window.docNum = '<?php echo $doc_num; ?>';
            window.initialBibdata = <?php readfile($doc_folder . '/bibdata.json'); ?>;
            window.newBibdata = {};
            window.entryTypes = ['article','book','incollection','misc'];
            window.radioCtr = 0;
            window.inpCtr = 0;
            window.uploadFD = {};
            window.allFields = ["key","authorArray","year","title","crossref","booktitle","edition","editorArray","journal","pages","address","publisher","volume","howpublished","url","accessed","note"];
            window.shownFields = {
                "article": ["key","authorArray","title","journal","volume","year","pages","note"],
                "incollection": ["key","authorArray","title","crossref","booktitle","edition","editorArray","address","publisher","year","pages","note"],
                "book": ["key","authorArray","title","edition","editorArray","address","publisher","year","note"],
                "misc": ["key","authorArray","title","year","howpublished","pages","url","accessed","note"]
            };
            
            function exne(ob, prop) {
                return ((ob.hasOwnProperty(prop)) && (ob[prop] != ''));
            }
            
            function fixpages(pp) {
                var ppparts = pp.split(/\s*[-–—]+\s*/);
                if (ppparts.length != 2) {
                    return pp;
                }
                if ((!/^[0-9]+$/.test(ppparts[0])) || (!/^[0-9]+$/.test(ppparts[1]))) {
                    return pp;
                }
                var sdigits = ppparts[0].length;
                var edigits = ppparts[1].length;
                var sto = ppparts[0];
                var eto = ppparts[1];
                if (eto.length < 2) {
                    if (sto.length > 1) {
                        eto = sto.charAt( sto.length - 2 ) + eto;
                    }
                }
                if (eto.length > 2) {
                    var eeto = eto;
                    var ssto = sto;
                    while (ssto.length < eeto.length) {
                        ssto = '0' + ssto;
                    }
                    if (ssto.length > eeto.length) {
                       eeto = ssto.substring(0, (ssto.length - eeto.length) ) + eeto;
                    }
                    var sctr = 0;
                    while ((sctr < (ssto.length - 2)) && ( ssto.charAt(sctr) == eeto.charAt(sctr)  )) {
                        sctr++;
                    }
                    eto = eeto.slice(sctr);
                }
                
                return sto + '--' + eto;
            }
            
            function nameArrayToCitenames(namearray) {
                var s = '';
                s += namearray[0].split(',',2)[0].trim();
                if (namearray.length < 2) {
                    return s;
                }
                for (var i=1; i<(namearray.length - 1); i++) {
                    s+=', ' + namearray[i].split(',',2)[0].trim();
                }
                s+=' and ' + namearray[ (namearray.length - 1) ].split(',',2)[0].trim();
                return s;
            }
            
            function citationNames(bibdata) {
                if ((bibdata.hasOwnProperty("authorArray")) && (bibdata.authorArray.length > 0)) {
                    return nameArrayToCitenames(bibdata.authorArray);
                }
                if ((bibdata.hasOwnProperty("editorArray")) && (bibdata.editorArray.length > 0)) {
                    return nameArrayToCitenames(bibdata.editorArray);
                }
                return 'Anonymous';
            }
            
            function nameReverse(name) {
                var nspl = name.split(',',2);
                if (nspl.length < 2) {
                    return name;
                }
                return nspl[1].trim() + ' ' + nspl[0].trim();
            }
            
            function nameArrayToText(namearray) {
                var s = namearray[0];
                if (namearray.length < 2) {
                    return s;
                }
                for (var i=1; i<(namearray.length - 1); i++) {
                    s += ', ' + nameReverse(namearray[i]);
                }
                s += ' and ' + nameReverse(namearray[ namearray.length - 1 ]);
                return s;
            }
            
            function editorList(namearray) {
                var s = nameReverse(namearray[0]);
                if (namearray.length < 2) {
                    return s;
                }
                for (var i=1; i<(namearray.length - 1); i++) {
                    s += ', ' + nameReverse(namearray[i]);
                }
                s += ' and ' + nameReverse(namearray[ namearray.length -1 ]);
                return s;
            }
            
            function aeBlockFor(bibdata) {
                if ((bibdata.hasOwnProperty("authorArray")) && (bibdata.authorArray.length > 0)) {
                    return nameArrayToText(bibdata.authorArray);
                }
                if ((bibdata.hasOwnProperty("editorArray")) && (bibdata.editorArray.length > 0)) {
                    return nameArrayToText(bibdata.editorArray);
                }
                return 'Anonymous';
            }
            
            <?php if (file_exists('custombibstyle.js')): 
                readfile('custombibstyle.js');
            else:
            ?>
            function bblEntryFor(bibkey, bibdata, elem) {
                
                var entrytype = 'none';
                if (exne(bibdata,'entrytype')) {
                    entrytype = bibdata.entrytype;
                }
                
                // author or editor block
                var repeatedAEBlock = false;
                var aeBlock = aeBlockFor(bibdata);
                if ((elem.previousSibling) && (typeof elem.previousSibling.gatherData === "function")) {
                    var prevData = elem.previousSibling.gatherData();
                    var prevAEBlock = aeBlockFor(prevData);
                    if (aeBlock == prevAEBlock) {
                        repeatedAEBlock = true;
                    }
                }
                var bbltext = '';
                var bibitemcmd = '\\bibitem[{' + citationNames(bibdata) + '(';
                if (repeatedAEBlock) {
                    bbltext += '\\rbibrule';
                } else {
                    bbltext += aeBlock;
                }
                
                // ed or eds if need be
                if ((!bibdata.hasOwnProperty("authorArray")) || (bibdata.authorArray.length == 0)) {
                    if (bibdata.hasOwnProperty("editorArray")) {
                        bbltext+= ', ed';
                        if (bibdata.editorArray.length > 1) {
                            bbltext += 's';
                        }
                        bbltext+= '.';
                    }
                }
                
                if (exne(bibdata, 'year')) {
                    var thisYear = bibdata.year;
                    var yearAdd = '';
                    bbltext+= ', ' + thisYear;
                    var sameBefore = 0;
                    if (repeatedAEBlock) {
                        var preElem = elem;
                        while ((preElem.previousSibling) && (typeof preElem.previousSibling.gatherData === "function")) {
                            preElem = preElem.previousSibling;
                            var peData = preElem.gatherData();
                            var peAEblock = aeBlockFor(peData);
                            var peYear = '';
                            if (exne(peData, 'year')) {
                                peYear = peData.year;
                            }
                            if ((peAEblock == aeBlock) && (peYear == thisYear)) {
                                sameBefore++;
                                preElem.updateMe();
                            } else {
                                break;
                            }
                        }
                    }
                    if (sameBefore != 0) {
                        var alphabet='abcdefghijklmnopqrstuvwxyz';
                        yearAdd = alphabet.charAt(sameBefore);
                        bbltext+= yearAdd;
                    } else {
                        if ((elem.nextSibling) && (typeof elem.nextSibling.gatherData === "function")) {
                            var neData = elem.nextSibling.gatherData();
                            var neAEblock = aeBlockFor(neData);
                            var neYear = '';
                            if (exne(neData, 'year')) {
                                neYear = neData.year;
                            }
                            if ((neAEblock == aeBlock) && (neYear == thisYear)) {
                                yearAdd = 'a';
                                bbltext+= yearAdd;
                            } 
                        }
                    }
                    bibitemcmd += thisYear + yearAdd + ')}]';
                } else {
                    bibitemcmd += 'yyyy)}]';
                }
                bbltext += '.';
                bibitemcmd += '{' + bibkey + '}';
                bbltext = bibitemcmd + "\r\n" + bbltext;
                
                if (exne(bibdata, 'title')) {
                    bbltext += ' ';
                    var needclose = false;
                    if ((entrytype == 'article') || (entrytype=='incollection')) {
                        needclose = true;
                        bbltext += '\\enquote{';
                    } else if (entrytype=='book') {
                        needclose = true;
                        bbltext += '\\booktitle{';
                    }
                    bbltext += bibdata.title;
                    if ((entrytype == 'article') || (entrytype=='incollection')) {
                        var lastchar = bbltext.slice(-1);
                        if ((lastchar != '.') && (lastchar != '?')) {
                            bbltext += '.';
                        }
                    }
                    if (needclose) {
                        bbltext += '}';
                    }
                    
                }
                
                if (entrytype == 'incollection') {
                    bbltext += ' In ';
                    if (exne(bibdata, 'crossref')) {
                        bbltext += '\\citet{' + bibdata.crossref + '}';
                        if (exne(bibdata, 'pages')) {
                            bbltext += ', p';
                            if (/[-–]/.test(bibdata.pages)) {
                                bbltext += 'p';
                            }
                            bbltext += '.~';
                            bbltext += fixpages(bibdata.pages);
                        }
                        bbltext += '.';
                        if (exne(bibdata, 'note')) {
                            bbltext += ' ' + bibdata.note;
                            if (bbltext.slice(-1) != '.') {
                                bbltext += '.';
                            }
                        }
                        return bbltext;
                    }
                    if (exne(bibdata, 'booktitle')) {
                            bbltext += '\\booktitle{' + bibdata.booktitle + '}';
                    }
                }
                
                if ((entrytype == 'incollection') || (entrytype=='book')) {
                    if (exne(bibdata, 'edition')) {
                        bbltext += ', ' + bibdata.edition;
                    }
                    if ((bibdata.hasOwnProperty("authorArray")) 
                        && (bibdata.authorArray.length > 0)
                        && (bibdata.hasOwnProperty("editorArray"))
                        && (bibdata.editorArray.length > 0)) {
                        bbltext+= ', edited by ';
                        bbltext+= editorList(bibdata.editorArray);
                    } 
                    if ((entrytype == 'incollection') && (exne(bibdata, 'pages'))) {
                        bbltext += ', p';
                        if (/[-–]/.test(bibdata.pages)) {
                            bbltext += 'p';
                        }
                        bbltext += '.~';
                        bbltext += fixpages(bibdata.pages);
                    }
                    if (bbltext.slice(-1) != '.') {
                        bbltext+='.';
                    }
                    if (exne(bibdata, 'address')) {
                        bbltext += ' ' + bibdata.address;
                        if (exne(bibdata, 'publisher')) {
                            bbltext += ':';
                        }
                    }
                    if (exne(bibdata, 'publisher')) {
                        bbltext += ' ' + bibdata.publisher;
                    }
                    if (bbltext.slice(-1) != '.') {
                        bbltext+='.';
                    }
                    
                }
                
                if (entrytype=='article') {
                    if (exne(bibdata, 'journal')) {
                        bbltext += ' \\booktitle{' + bibdata.journal + '}';
                    }
                    if (exne(bibdata, 'volume')) {
                        bbltext += ' ' + bibdata.volume;
                    }
                    if (exne(bibdata, 'pages')) {
                        bbltext += ': ' + fixpages(bibdata.pages);
                    }
                    if (bbltext.slice(-1) != '.') {
                        bbltext+='.';
                    }

                }
                
                if (entrytype=='misc') {
                    if (bbltext.slice(-1) != '.') {
                        bbltext += '.';
                    }
                    if (exne(bibdata, 'howpublished')) {
                        bbltext += ' ' + bibdata.howpublished;
                    }
                    if (exne(bibdata, 'url')) {
                        bbltext += ', \\url{' + bibdata.url + '}';
                    }
                    if (exne(bibdata, 'pages')) {
                        bbltext += ', p';
                        if (/[-–]/.test(bibdata.pages)) {
                            bbltext += 'p';
                        }
                        bbltext += '.~';
                        bbltext += fixpages(bibdata.pages);
                    }
                    if (exne(bibdata, 'accessed')) {
                        bbltext += ', accessed ' + bibdata.accessed;
                    }
                    if (bbltext.slice(-1) != '.') {
                        bbltext+='.';
                    }
                }
                
                if (exne(bibdata, 'note')) {
                    bbltext += ' ' + bibdata.note;
                    if (bbltext.slice(-1) != '.') {
                        bbltext += '.';
                    }
                }

                
                return bbltext;
            }
            <?php endif; ?>
                                 
            function newTextInputWithLabel(parNode, labeltext) {
                var d = document.createElement("div");
                parNode.appendChild(d);
                d.classList.add("fieldgroup");
                var inf = document.createElement("input");
                d.appendChild(inf);
                inf.type = "text";
                window.inpCtr++;
                inf.id = 'input' + window.inpCtr.toString();
                var l = document.createElement("label");
                d.appendChild(l);
                l.htmlFor = inf.id;
                l.innerHTML = labeltext;
                inf.onchange = function() {
                    var p = this.parentNode;
                    while ((p.tagName.toLowerCase() != 'li') && (p.tagName.toLowerCase() != 'body')) {
                        p = p.parentNode;
                    }
                    if (typeof p.updateMe === "function") {
                        p.updateMe();
                    }
                }
                return inf;
            }
            
            function newRadioWithLabel(parNode, labeltext, radiovalue) {
                var s = document.createElement("span");
                parNode.appendChild(s);
                s.classList.add("radiogroup");
                s.classList.add("nobreak");
                var rb = document.createElement("input");
                rb.type = 'radio';
                rb.name = 'radioset' + window.radioCtr.toString();
                s.appendChild(rb);
                rb.value = radiovalue;
                rb.id = 'radioset' + window.radioCtr.toString() + radiovalue;
                var l = document.createElement("label");
                s.appendChild(l);
                l.htmlFor = rb.id;
                l.innerHTML = labeltext;
                return rb;
            }
            
            function newFieldArrayBlock(parNode, fname) {
                var d = document.createElement("div");
                parNode.appendChild(d);
                d.addSpan = document.createElement("span");
                d.appendChild(d.addSpan);
                d.addSpan.classList.add("fakelink");
                d.addSpan.classList.add("smaller");
                d.addSpan.myBlock = d;
                d.addSpan.onclick = function() { this.myBlock.addField(); };
                d.shortName = fname.replace('Array','');
                d.addSpan.innerHTML = 'add ' + d.shortName;
                
                d.fieldCtr = 0;
                d.addField = function() {
                    this.fieldCtr++;
                    var ni = newTextInputWithLabel(this, this.shortName + ' ' + this.fieldCtr.toString());
                    this.insertBefore(ni.parentNode, this.addSpan);
                    return ni;
                }
                
                return d;
                
            }
            
            function insertEntryAtEnd() {
                var neww = newEntry('new',{});
                document.getElementById("biblist").appendChild(neww);
            }
            
            function newEntry(bibkey, bibprops) {
                
                var newentry = document.createElement("li");
                
                // button for inserting a new entry above
                var insertDiv = document.createElement("div");
                newentry.appendChild(insertDiv);
                insertDiv.classList.add("insertentry");
                var insertButton = document.createElement("button");
                insertButton.type = "button";
                insertDiv.appendChild(insertButton);
                insertButton.innerHTML = "insert entry";
                insertButton.myEntry = newentry;
                insertButton.onclick = function() {
                    var newe = newEntry('new',{});
                    this.myEntry.parentNode.insertBefore( 
                        newe,
                        this.myEntry
                    );
                }
                
                // buttons
                var tbDiv = document.createElement("div");
                tbDiv.classList.add("buttonsdiv");
                newentry.appendChild(tbDiv);
                var removeButton = document.createElement("button");
                removeButton.myEntry = newentry;
                removeButton.type = "button";
                removeButton.onclick = function() {
                    this.myEntry.parentNode.removeChild(this.myEntry);
                }
                removeButton.innerHTML = "remove entry";
                tbDiv.appendChild(removeButton);
                
                // original text display
                if (bibprops.hasOwnProperty("originalText")) {
                    var fs = document.createElement("fieldset");
                    newentry.appendChild(fs);
                    var leg = document.createElement("legend");
                    fs.appendChild(leg);
                    leg.innerHTML = "Imported text of entry";
                    newentry.origTextTextArea = document.createElement("textarea");
                    fs.appendChild(newentry.origTextTextArea);
                    newentry.origTextTextArea.readOnly = true;
                    newentry.origTextTextArea.value = bibprops.originalText;
                }
                
                // type of entry radio selection
                var fs = document.createElement("fieldset");
                newentry.appendChild(fs);
                var leg = document.createElement("legend");
                fs.appendChild(leg);
                leg.innerHTML = "Type of entry";
                newentry.typeRadios = [];
                window.radioCtr++;
                for (var i=0; i<window.entryTypes.length; i++) {
                    newentry.typeRadios.push(
                        newRadioWithLabel(fs, window.entryTypes[i], window.entryTypes[i])
                    )
                    
                }
                newentry.getEntryType = function() {
                    for (var i=0; i<this.typeRadios.length; i++) {
                        var rb = this.typeRadios[i];
                        if (rb.checked) {
                            return rb.value;
                        }
                    }
                    return 'none';
                }
                for (var i=0; i<newentry.typeRadios.length; i++) {
                    newentry.typeRadios[i].myEntry = newentry;
                    newentry.typeRadios[i].onchange = function() {
                        this.myEntry.showRelevantFields();
                    }
                    if ((bibprops.hasOwnProperty("entrytype")) && (bibprops.entrytype == newentry.typeRadios[i].value)) {
                            newentry.typeRadios[i].checked = true;
                    }
                }
                
                // entry fields
                var fs = document.createElement("fieldset");
                newentry.appendChild(fs);
                var leg = document.createElement("legend");
                fs.appendChild(leg);
                leg.innerHTML = "Entry fields";
                newentry.entryFields = {};
                for (var i=0; i<window.allFields.length; i++) {
                    var fname = window.allFields[i];
                    if (fname.slice(-5) == 'Array') {
                        newentry.entryFields[fname] = newFieldArrayBlock(fs, fname);
                        if ((bibprops.hasOwnProperty(fname)) && (bibprops[fname].length > 0)) {
                            for (var j=0; j<bibprops[fname].length; j++) {
                                var f = newentry.entryFields[fname].addField();
                                f.value = bibprops[fname][j];
                            }
                        } else {
                            newentry.entryFields[fname].addField();
                        }
                    } else {
                        newentry.entryFields[fname] = newTextInputWithLabel(fs, fname);
                        if (fname == 'key') {
                            if (bibkey != 'new') {
                                newentry.entryFields[fname].value = bibkey;
                            }
                        } else {
                            if (bibprops.hasOwnProperty(fname)) {
                                newentry.entryFields[fname].value = bibprops[fname];
                            }
                        }
                    }
                }
                
                // bbl listing
                var fs = document.createElement("fieldset");
                newentry.appendChild(fs);
                var leg = document.createElement("legend");
                fs.appendChild(leg);
                leg.innerHTML = "LaTeX bibliography item";
                var cbd = document.createElement("div");
                cbd.classList.add("nobreak");
                fs.appendChild(cbd);
                newentry.overrideCB = document.createElement("input");
                newentry.overrideCB.type = "checkbox";
                newentry.overrideCB.myEntry = newentry;
                cbd.appendChild(newentry.overrideCB);
                var l = document.createElement("label");
                l.innerHTML = "override";
                cbd.appendChild(l);
                window.inpCtr++;
                newentry.overrideCB.id = 'checkbox' + window.inpCtr.toString();
                l.htmlFor = newentry.overrideCB.id;
                newentry.bblTextTextArea = document.createElement("textarea");
                cbd.appendChild(newentry.bblTextTextArea);
                if (bibprops.hasOwnProperty("bblText")) {
                    newentry.bblTextTextArea.value = bibprops.bblText;
                }
                if ((bibprops.hasOwnProperty("override")) && (bibprops.override)) {
                    newentry.overrideCB.checked = true;
                } else {
                    newentry.bblTextTextArea.readOnly = true;
                }
                newentry.overrideCB.onchange = function() {
                    if (this.checked) {
                        this.myEntry.bblTextTextArea.readOnly = false;
                    } else {
                        this.myEntry.bblTextTextArea.readOnly = true;
                    }
                }
                
                // show the correct fields
                newentry.showRelevantFields = function() {
                    var etype = this.getEntryType();
                    for (var i=0; i<window.allFields.length; i++) {
                        var fname = window.allFields[i];
                        var elem = this.entryFields[fname];
                        if (elem.tagName.toLowerCase() == 'input') {
                            var elem = elem.parentNode;
                        }
                        if (window.shownFields[etype].indexOf(fname) != -1) {
                            elem.style.display = 'block';
                        } else {
                            elem.style.display = 'none';
                        }
                    }
                }
                
                newentry.gatherData = function() {
                    var rv = {};
                    if (this.hasOwnProperty("origTextTextArea")) {
                        rv.originalText = this.origTextTextArea.value;
                    }
                    rv.entrytype = this.getEntryType();
                    for (var i=0; i<window.allFields.length; i++) {
                        var fname = window.allFields[i];
                        if (fname == 'key') {
                            continue;
                        }
                        var elem = this.entryFields[fname];
                        if (elem.tagName.toLowerCase() == 'input') {
                            if (elem.value != '') {
                                rv[fname] = elem.value.trim();
                            }
                        } else {
                            var inin = elem.getElementsByTagName("input");
                            var a = [];
                            for (var j=0; j<inin.length; j++) {
                                var thisin = inin[j];
                                if (thisin.value != '') {
                                    a.push(thisin.value.trim());
                                }
                            }
                            if (a.length > 0) {
                                rv[fname] = a;
                            }
                        }
                    }
                    if (this.overrideCB.checked) {
                        this.override = true;
                    } else {
                        this.override = false;
                    }
                    rv.bblText = this.bblTextTextArea.value;
                    return rv;
                }
                
                newentry.updateMe = function() {
                    if (this.overrideCB.checked) {
                        return;
                    }
                    var bibkey = this.entryFields.key.value;
                    var bibdata = this.gatherData();
                    this.bblTextTextArea.value = bblEntryFor(bibkey,bibdata,this);
                }
                
                // if nothing selected, select article
                if (newentry.getEntryType() == 'none') {
                    newentry.typeRadios[0].checked = true;
                }
                newentry.showRelevantFields();
                
                return newentry;
                
            }
            
            function saveBibdata(finalize) {
                var allBibdata = {};
                var allEntries = document.getElementById("biblist").getElementsByTagName("li");
                for (var i=0; i<allEntries.length; i++) {
                    var entry = allEntries[i];
                    entry.updateMe();
                    if (entry.entryFields.key.value.trim() == '') {
                        kckErrAlert("One or more entries have empty keys. Please enter keys, or remove them before proceeding.");
                                    
                        return false;
                    }
                    var entrykey = entry.entryFields.key.value.trim();
                    if (allBibdata.hasOwnProperty(entrykey)) {
                        kckErrAlert("The key " + entrykey + " is used more than once. Please remove or change duplicate keys before proceeding.");
                        return false;
                    }
                    allBibdata[entrykey] = entry.gatherData();
                }
                kckWaitScreen();
                var bibjson = JSON.stringify(allBibdata);
                var fD = new FormData();
                fD.append("bibjson", bibjson);
                fD.append("finalize", finalize.toString());
                fD.append("docnum", window.docNum);
                AJAXPostRequest('savebibdata.php',fD, function(text) {
                    kckRemoveWait();
                    try {
                        var resObj = JSON.parse(text);
                    } catch(err) {
                        kckErrAlert("Could not parse information from server. " + err + ". " +
                                    text);
                        return;
                    }
                    if (!resObj.saved) {
                        kckErrAlert("Unable to save bibliography! There seems to be a problem on the server.");
                        return;
                    }
                    if (resObj.finalized) {
                        window.location.href = 'index.php';
                        return;
                    }
                    kckAlert("Changes saved.");
                });
            }
            
            function finalizeBib() {
                kckYesNoBox(
                    'Warning: Once the bibliography is finalized, changes can only be made by editing the “thebibliography” environment in the LaTeX file. Proceed?',
                    function() {
                        saveBibdata(true);
                    }
                );
            }
            
            function processUploadResponse(text) {
                try {
                    var resObj = JSON.parse(text);
                } catch(err) {
                    kckErrAlert("There was a problem with the response from the server. " + err + " -- " + text);
                    return;
                }
                if (resObj.error) {
                    kckErrAlert("There was a problem processing the uploaded file. " + resObj.errmsg);
                    return;
                }
                window.newBibdata = resObj.bibobj;
                Object.keys(window.newBibdata).forEach(function(bibkey,index) {
                    document.getElementById("biblist").appendChild( newEntry(bibkey,window.newBibdata[bibkey]) );
                });
                resort();
                
            }
            
            function doUpload() {
                AJAXPostRequest('importbib.php', window.uploadFD, 
                                function(text) {
                    kckRemoveWait();
                    processUploadResponse(text);
                }, 
                                function(text) {
                    kckRemoveWait();
                    kckErrAlert("There was a server error when attempting to upload the file.");
                });
            }
            
            function startUpload() {
                var ufInput = document.getElementById("uploadfile");
                if (!ufInput) {
                    kckErrAlert("Input not found.");
                    return;
                }
                if (ufInput.files.length < 1) {
                    kckErrAlert("No file chosen.");
                    return;
                }
                if (ufInput.files.length > 1) {
                    kckErrAlert("Too many files chosen.");
                    return;
                }
                window.uploadFD = new FormData();
                window.uploadFD.append("uploadfile", ufInput.files[0], ufInput.files[0].name);
                kckWaitScreen();
                setTimeout(
                    function() {
                        doUpload();
                    }
                    , 800);
            }
            
            function entryCompare(a, b) {
                var adata = a.gatherData();
                var bdata = b.gatherData();
                var acnames = citationNames(adata);
                var bcnames = citationNames(bdata);
                if (acnames != bcnames) {
                    return acnames.localeCompare(bcnames);
                }
                var ahasAuthor = ((adata.hasOwnProperty("authorArray")) && (adata.authorArray.length > 0));
                var bhasAuthor = ((bdata.hasOwnProperty("authorArray")) && (bdata.authorArray.length > 0));
                if ((ahasAuthor) && (!bhasAuthor)) {
                    return -1;
                }
                if ((bhasAuthor) && (!ahasAuthor)) {
                    return -1;
                }
                if (exne(adata, 'year') && exne(bdata, 'year')) {
                    var ayear = parseInt(adata.year);
                    var byear = parseInt(bdata.year);
                    if (ayear != byear) {
                        return ayear - byear;
                    }
                }
                if (exne(adata, 'title') && exne(bdata, 'title')) {
                    if (adata.title != bdata.title) {
                        return adata.title.localeCompare(bdata.title);
                    }
                }
                return 0;
            }
            
            function resort() {
                var blist = document.getElementById("biblist"); 
                var blis = blist.getElementsByTagName("li");
                var blArray = [];
                for (var i=0; i<blis.length; i++) {
                    blArray.push(blis[i]);
                }
                blArray.sort(entryCompare);
                
                for (var i=0; i<blArray.length; i++) {
                    blist.appendChild(blArray[i]);
                }
                
                for (var i=0; i<blis.length; i++) {
                    blis[i].updateMe();
                }

            }
            
            window.onload = function() {
                Object.keys(window.initialBibdata).forEach(function(bibkey,index) {
                    document.getElementById("biblist").appendChild( newEntry(bibkey,window.initialBibdata[bibkey]) );
                });
                
            }
            
        </script>

    </head>
    <body>
        <div id="logoutstrip"><a href="logout.php">log out</a></div>
        <h1>Edit bibliography: document number <?php echo $doc_num; ?></h1>
        <div id="topbuttons">
            <fieldset>
                <legend>Import BibTeX .bib file</legend>
                <input type="file" id="uploadfile" name="uploadfile" onchange="startUpload();" />
            </fieldset>
            <button type="button" onclick="resort()">re-sort</button>
        </div>
        
        <ul id="biblist">
        </ul>
        <div class="insertentry">
            <button type="button" onclick="insertEntryAtEnd()">insert entry</button>
        </div>
        <div id="bottombuttons">
            <span onclick="saveBibdata(false)">save</span>
            <span onclick="finalizeBib()">finalize</span>
        </div>

    </body>
</html>