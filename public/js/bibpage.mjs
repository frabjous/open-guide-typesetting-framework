// LICENSE: GNU GPL v3 You should have received a copy of the GNU General
// Public License along with this program. If not, see
// https://www.gnu.org/licenses/.

// File: bibpage.mjs
// functions particular to the bibliography page for each assignment

import uploadFiles from './file-upload.mjs';
import {addelem, addbibitems, getAllBibData} from './bibitems.mjs';

// opts: ogtf, parent
// ogtf should have clearmessage, reporterror, editorquery, editorupload methods
export async function bibpage(opts) {
  const {
    assignmentId,
    assignmentInfo,
    assignmentType,
    ogtf,
    parent
  } = opts;
  if (
    !ogtf || !parent || !assignmentType ||
    !assignmentId || !assignmentInfo
  ) return null;
  //
  // Bibliography block
  //
  const bibcardhdr = addelem({
    parent: parent,
    tag: 'h2',
    innerHTML: `Bibliography: ${assignmentId}`
  });
  const card = addelem({
    parent: parent,
    tag: 'div',
    classes: ['bibliographycard'],
    assignmentId: assignmentId,
    reporterror: ogtf.reporterror.bind(ogtf),
    clearmessage: ogtf.clearmessage.bind(ogtf),
    assignmentType: assignmentType
  });
  card.contents = addelem({
    parent: card,
    tag: 'div'
  })
  // top of bibliography area, with buttons
  card.bibtop = addelem({
    tag: 'div',
    parent: card.contents,
    classes: ['grid']
  });
  // areas for buttons at top of bibliography
  card.bibtopleft = addelem({
    tag: 'div',
    parent: card.bibtop
  });
  card.bibtopright = addelem({
    tag: 'div',
    parent: card.bibtop
  });

  //TODO
  card.bibextractbutton = addelem({
    tag: 'button',
    type: 'button',
    innerHTML: 'extract from main file',
    parent: card.bibtopleft,
    mycard: card,
    myogtf: ogtf,
    onclick: async function() {
      if (!this?.mycard?.assignmentId) return;
      if (this.mycard?.biblastextracted &&
          (this.mycard.biblastextracted > 0))
      {
        extractedAlreadyDialog(this.mycard);
        return;
      }
      this.innerHTML = 'getting extracted items …';
      this.setAttribute('aria-busy', 'true');
      const req = {
        postcmd: 'extractbibitems',
        assignmentId: this.mycard.assignmentId,
        assignmentType: this.mycard.assignmentType
      }
      const resp = await ogtf.editorquery(req);
      this.removeAttribute('aria-busy');
      this.innerHTML = 'extract from main file';
      if (!resp) return;
      if (resp?.noextractedbib) {
        this.myogtf.okmessage('No bibliographic items to extract. Either the main file has not been uploaded, or the bibliography could not be found.')
        this.innerHTML = 'extract from main file';
        return;
      }
      if (!resp?.bibitems) {
        this.mycard.reporterror('Response from server did not ' +
          'contain bibliographic material.');
        return;
      }
      const additions = resp?.additions ?? [];
      this.mycard.biblastextracted = Date.now();
      if (additions.length > 0) {
        this.mycard.addbibitems(additions);
        this.mycard.biblastchanged = Date.now();
        this.innerHTML = 'extracted successfully';
        setTimeout(() => {
          this.innerHTML = 'extract from main file'
        }, 2000);
      }
    }
  });
  card.bibuploadlabel = addelem({
    tag: 'span',
    innerHTML: 'import .bib, .json, .ris, .xml, .yaml',
    parent: card.bibtopright
  });
  card.bibuploadloading = addelem({
    tag: 'div',
    parent: card.bibtopright,
    classes: ['uploadindicator'],
    innerHMTL: 'uploading …'
  });
  card.bibuploadloading.setAttribute('aria-busy', 'true');
  card.bibuploadloading.style.display = 'none';
  card.bibuploadinput = addelem({
    tag: 'input',
    type: 'file',
    accept: '.bib, .json, .ris, .xml, .yaml',
    mycard: card,
    parent: card.bibtopright,
    onchange: async function() {
      this.style.display = 'none';
      this.mycard.bibuploadloading.style.display = 'block';
      const req = {
        uploadtype: 'bibimport',
        assignmentId: this?.mycard?.assignmentId ?? 'none',
        assignmentType: this?.mycard?.assignmentType ?? 'none',
      }
      const resp = await ogtf.editorupload(this, req);
      if (!resp?.success || !resp?.additions) {
        this.mycard.bibuploadloading.style.display = 'none';
        this.style.display = 'inline';
        return;
      }
      if (resp.additions.length > 0) {
        this.mycard.addbibitems(resp.additions);
        this.mycard.biblastchanged = Date.now();
        this.mycard.bibuploadloading.removeAttribute('aria-busy');
        this.mycard.bibuploadloading.innerHTML = '<span class="successconfirm">' +
          '<span class="material-symbols-outlined">check</span> import successful</span>'
        this.mycard.bibuploadloading.style.display = 'block';
        setTimeout(() => {
          this.mycard.bibuploadloading.setAttribute('aria-busy', 'true');
          this.mycard.bibuploadloading.innerHTML = 'uploading …'
          this.mycard.bibuploadloading.style.display = 'none';
          this.style.display = 'inline';
        }, 3000);
      }
    }
  });
  card.bibcontents = addelem({
    tag: 'div',
    parent: card.contents
  });
  card.bibcontentshdr = addelem({
    tag: 'h3',
    classes: ['bibitemlabel'],
    parent: card.bibcontents,
    innerHTML: 'Items'
  });
  card.bibcontentsitems = addelem({
    tag: 'article',
    mycard: card,
    parent: card.bibcontents
  });
  card.bibcontentbuttons = addelem({
    tag: 'div',
    classes: ['grid','stickybuttons'],
    parent: card.bibcontents
  });
  card.addentrybtn = addelem({
    tag: 'a',
    href: '#',
    onmousedown: function(e) {
      e.preventDefault();
    },
    parent: card.bibcontentbuttons,
    classes: ['outline'],
    mycard: card,
    innerHTML: 'add new item',
    onclick: function(e) {
      e.preventDefault();
      this.mycard.addbibitems([{}]);
      const bibi = document.getElementsByClassName("bibitem");
      if (!bibi || bibi.length == 0) return;
      bibi[bibi.length - 1].scrollIntoView();
    }
  });
  card.addentrybtn.setAttribute('role', 'button');

  card.bibsavebutton = addelem({
    tag: 'button',
    type: 'button',
    parent: card.bibcontentbuttons,
    innerHTML: 'save bibiography',
    mycard: card,
    onclick: async function() {
      this.mycard.clearmessage();
      if (!this?.mycard?.assignmentId) {
        this.mycard.reporterror('You cannot save the ' +
          'bibliography until a document ID is set.');
        return false;
      }
      const bibdata = getAllBibData(this.mycard.bibcontentsitems);
      if (!bibdata) { return false; }
      this.innerHTML = 'saving';
      this.setAttribute('aria-busy', 'true');
      const req = {
        postcmd: 'savebibliography',
        bibdata: bibdata,
        assignmentId: this.mycard.assignmentId,
        assignmentType: this.mycard.assignmentType
      }
      const resp = await ogtf.editorquery(req);
      this.removeAttribute('aria-busy');
      if (!resp) return false;
      if (resp.success) {
        this.innerHTML = 'saved successfully';
        setTimeout(() => {
          this.innerHTML = 'save bibliography';
        }, 2000);
        this.mycard.biblastsaved = Date.now();
      } else {
        this.innerHTML = 'save bibliography';
        return false;
      }
      return true;
    }
  });
  card.bibsavebutton.setAttribute('role', 'button');

  card.bibapplybutton = addelem({
    tag: 'button',
    warned: false,
    type: 'button',
    mycard: card,
    myogtf: ogtf,
    parent: card.bibcontentbuttons,
    innerHTML: 'apply to document',
    classes: ['secondary'],
    onclick: async function() {
      const card = this.mycard;
      card.clearmessage();
      if (!card?.assignmentId) {
        card.reporterror('Cannot apply a bibliography without ' +
         'a document id.');
        return;
      }
      if (!card.biblastsaved || card.biblastsaved == -1) {
        return;
      }
      this.innerHTML = 'applying …';
      this.setAttribute('aria-busy', 'true');
      if (!this.warned) {
        applyWarnDialog(card);
        return;
      }
      // ensure saved
      if (card.biblastchanged > card.biblastsaved) {
        if (!card?.bibsavebutton) return;
        await card.bibsavebutton.onclick();
      }
      const req = {
        postcmd: 'applybibliography',
        assignmentId: card.assignmentId,
        assignmentType: card.assignmentType
      }
      const resp = await ogtf.editorquery(req);
      this.removeAttribute('aria-busy');
      if (!resp) { return; }
      if (resp?.nomainfile) {
        this.innerHTML = 'apply to document';
        this.myogtf.okmessage(`Unable to locate main document. ` +
          `Perhaps one has not been uploaded yet?`);
        return;
      }
      if (resp?.success && ("biblastapplied" in resp)) {
        card.biblastapplied = resp.biblastapplied;
        this.innerHTML = 'applied successfully';
        setTimeout(() => {
          this.innerHTML = 'apply to document';
        }, 2000);
      } else {
        this.innerHTML = 'apply to document';
      }
    }
  });
  card.bibapplybutton.setAttribute('role', 'button');
  //card.bibsavebutton.disabled = true;
  card.biblastextracted = (assignmentInfo?.biblastextracted ?? -1);
  card.biblastapplied = (assignmentInfo?.biblastapplied ?? -1);
  card.biblastchanged = (assignmentInfo?.biblastchanged ?? -1);
  card.biblastsaved = (assignmentInfo?.biblastsaved ?? -1);
  card.extractbibmtime = (assignmentInfo?.extractbibmtime ?? -1);

  card.addbibitems = addbibitems;
  // restore bibliography
  if ("bibdata" in assignmentInfo) {
    card.addbibitems(Object.values(assignmentInfo.bibdata));
  }
}

function applyWarnDialog(card) {

  const dialog = addelem({
    tag: 'dialog',
    parent: document.body
  });
  const artcl = addelem({ parent: dialog, tag: 'article'});
  const p = addelem({
    parent: artcl,
    tag: 'p',
    innerHTML: '<strong>Warning:</strong> Applying the bibliography ' +
      'changes the main document by attempting to autodetect and ' +
      'fix citations. It is best done after the bulk of the work ' +
      'has been done on the bibliography, but before doing ' +
      'serious work on editing the main document. Be sure the main ' +
      'document is not open or unsaved in another tab or window, or work ' +
      'may get overwritten.'
  });
  const ftr = addelem({
    parent: artcl,
    tag: 'footer'
  });
  const cancelbtn = addelem({
    parent: ftr,
    tag: 'button',
    type: 'button',
    innerHTML: 'cancel',
    classes: ['secondary'],
    onclick: () => {
      dialog.removeAttribute('open');
      card.bibapplybutton.removeAttribute('aria-busy');
      card.bibapplybutton.innerHTML = 'apply to document';
      return;
    }
  });
  const doanywaybtn = addelem({
    parent: ftr,
    tag: 'button',
    type: 'button',
    innerHTML: 'apply now',
    onclick: () => {
      dialog.removeAttribute('open');
      card.bibapplybutton.warned = true;
      card.bibapplybutton.onclick()
    }
  });
  dialog.setAttribute('open', 'true');
}

function extractedAlreadyDialog(card) {

  const dialog = addelem({
    tag: 'dialog',
    parent: document.body
  });
  const artcl = addelem({ parent: dialog, tag: 'article'});
  const p = addelem({
    parent: artcl,
    tag: 'p',
    innerHTML: 'Bibliographic data has already been extracted for this assignment. ' +
      'Extracting again may create duplicate items.'
  });
  const ftr = addelem({
    parent: artcl,
    tag: 'footer'
  });
  const cancelbtn = addelem({
    parent: ftr,
    tag: 'button',
    type: 'button',
    innerHTML: 'cancel',
    classes: ['secondary'],
    onclick: () => {
      dialog.removeAttribute('open');
      card.bibextractbutton.removeAttribute('aria-busy');
      card.bibextractbutton.innerHTML = 'extract from main file';
      return;
    }
  });
  const doanywaybtn = addelem({
    parent: ftr,
    tag: 'button',
    type: 'button',
    innerHTML: 'extract anyway',
    onclick: () => {
      dialog.removeAttribute('open');
      card.biblastextracted = -1;
      card.bibextractbutton.onclick()
    }
  });
  dialog.setAttribute('open', 'true');
}