// LICENSE: GNU GPL v3 You should have received a copy of the GNU General
// Public License along with this program. If not, see
// https://www.gnu.org/licenses/.

// File: proofs.mjs
// Sets up and controls the proofs page

import downloadFile from './download.mjs';
import postData from './fetch.mjs';

// initial setup
const w = window;
w.nummarkers = 0;
w.anychangesmade = false;
for (const id of [
  'toppanel',
  'errormessage',
  'htmlproofs',
  'pdfparent',
  'pdfpages'
]) {
  w[id] = document.getElementById(id);
}

function clearError() {
  w.errormessage.classList.remove('okmsg');
  w.errormessage.style.display = 'none';
}

function reportError(msg) {
  changeMode('instructions');
  w.errormessage.classList.remove('okmsg');
  w.errormessage.style.display = 'block';
  w.errormessage.innerHTML =
    '<span>Error when interacting with server.</span> (' + msg +
    ') Check your internet connection. If the problem persists, ' +
    ' consult your ' + ((w.iseditor) ? 'site administrator' :
    'editor') + '.';
  w.errormessage.scrollIntoView();
}

function okmessage(msg) {
  changeMode('instructions');
  w.errormessage.style.display = 'block';
  w.errormessage.classList.add('okmsg');
  w.errormessage.innerHTML = msg;
  w.errormessage.scrollIntoView();
}

async function jsonrequest(req) {
  clearError();
  const url = `/${w.urlbase}/json`;
  req.postcmd = 'processcomment';
  req.accesskey = w.accesskey;
  req.project = w.projectname;
  const resp = await postData(url, req);
  if ((!("respObj" in resp)) || resp.respObj?.error || resp.error) {
    let msg = ((resp?.errMsg) ? resp.errMsg + ' ' : '') +
      ((resp?.respObj?.errMsg) ? resp.respObj.errMsg : '');
    if (msg == '') { msg = 'unknown error'; }
    reportError(msg);
    return false;
  }
  return resp.respObj;
}

// general function for adding elements
function addelem(opts) {
  if (!('tag' in opts)) { return; }
  const elem = document.createElement(opts.tag);
  if ('parent' in opts) {
    opts.parent.appendChild(elem);
  }
  if ('classes' in opts) {
    for (const cl of opts.classes) {
      elem.classList.add(cl);
    }
  }
  for (const opt in opts) {
    if (opt == 'tag' || opt == 'parent' || opt == 'classes') {
      continue;
    }
    elem[opt] = opts[opt];
  }
  return elem;
}

// function for changing between modes
function changeMode(which) {
  document.body.classList.remove('pdf','html','instructions');
  document.body.classList.add(which);
}

// two functions for changing the zoom level
function changeZoom(inc) {
  if (inc === 'fitwidth') {
    w.pdfzoom = (document.body.clientWidth - 36);
  } else {
    w.pdfzoom = w.pdfzoom + inc;
  }
  // don't let it disappear completely
  if (w.pdfzoom <= 100) {
    w.pdfzoom = 100;
  }
  w.pdfparent.style.width = w.pdfzoom.toString() + 'px';
}

function zoomInOut(inout = true) {
  let inc = 200;
  if (w.pdfzoom < 800) { inc = 100; }
  if (w.pdfzoom > 1800) { inc = 400; }
  if (w.pdfzoom > 2800) { inc = 800; }
  if (!inout) { inc = (0 - inc); }
  changeZoom(inc);
}

// functions for comment elements

function purgeTraces() {
  const id = this.id;
  if (this?.mywidget?.insertionpoint) {
    const ip = this.mywidget.insertionpoint;
    ip.parentNode.removeChild(ip);
  }
  const tt = w.htmld.getElementsByClassName(id + '-change');
  while (tt.length > 0) {
    const t = tt[tt.length - 1];
    let rep = w.htmld.createTextNode(t.innerText);
    t.parentNode.insertBefore(rep,t);
    t.parentNode.removeChild(t);
  }
}

async function deleteComment() {
  // remove from DOM
  if (this?.mywidget?.mymarker) {
    const m = this.mywidget.mymarker;
    m.parentNode.removeChild(m);
  }
  if (this.ishtml) {
    this.purgeTraces();
  }
  // remove off server
  if (this.eversaved) {
    const req = {
      requesttype: 'deletecomment',
      commentid: this.id
    };
    if (this.ishtml) {
      req.bodyhtml = w.htmld.body.innerHTML;
    }
    const resp = await jsonrequest(req);
    if (!resp) return;
  }
  w.submitbutton.updateMe();
}

function isBadParent(e) {
  const tN = e.tagName.toLowerCase();
  return (tN == 'body' || tN == 'main' || tN == 'article');
}

async function saveComment() {
  const req = {
    requesttype: 'savecomment',
    commentinfo: {
      id: this.id,
      commenttype: this.mytype
    }
  };
  for (const x of ['del', 'ins', 'comment', 'response']) {
    const inp = this[x + 'input'];
    if (inp && inp.value != '') {
      req.commentinfo[x] = inp.value;
    }
  }
  if (this?.mywidget?.insertionpoint) {
    this.mywidget.insertionpoint.innerHTML =
      this.insinput.value;
  }
  if (this.addressedcb && this.addressedcb.checked) {
    req.commentinfo.hasbeenaddressed = true;
  }
  if (this?.mywidget?.mymarker) {
    const marker = this.mywidget.mymarker;
    if (marker?.mypage) {
      req.commentinfo.page = marker?.mypage.id;
    }
    if (marker?.anchorPP) {
      req.commentinfo.anchorPP = marker.anchorPP;
    }
    if (marker?.wanderPP) {
      req.commentinfo.wanderPP = marker.wanderPP;
    }
  }
  if (this.ishtml) {
    req.bodyhtml = w.htmld.body.innerHTML;
  }
  let wherefound = this;
  while (wherefound && (!isBadParent(wherefound.parentNode))) {
    wherefound = wherefound.parentNode;
  }
  // try to find good determination of position in document
  const bodychildren = {};
  let mainParent = w.htmld.body;
  const artart = mainParent.getElementsByTagName("article");
  if (artart && (artart.length > 0)) {
    mainParent = artart[0];
  }
  for (const child of mainParent.childNodes) {
    if (child.tagName) {
      const tagname = child.tagName.toLowerCase();
      if (!(tagname in bodychildren)) {
        bodychildren[tagname] = 0;
      }
      if (tagname == 'h1') {
        for (const tname in bodychildren) {
          if (tname != 'h1') {
            bodychildren[tname] = 0;
          }
        }
      }
      bodychildren[tagname] = bodychildren[tagname] + 1;
      if (child == wherefound) {
        req.commentinfo.topleveltag = tagname;
        req.commentinfo.position = bodychildren[tagname];
        req.commentinfo.section = (bodychildren['h1'] ?? 0);
        break;
      }
    }
  }
  this.makeSaving();
  const resp = await jsonrequest(req);
  if (!resp) {
    this.makeUnsaved();
    return;
  }
  w.anychangesmade = true;
  this.makeSaved();
  w.submitbutton.updateMe();
}

function makeSaved() {
  this.eversaved = true;
  this.classList.remove('unsaved', 'saving');
  this.savebutton.innerHTML = '(saved)';
  this.savebutton.classList.add('disabled');
  this.minimize(true);
}

function makeSaving() {
  this.classList.add('saving');
  this.savebutton.innerHTML = 'saving <span class="material-symbols-outlined' +
    ' rotating">sync</span>';
}

function makeUnsaved() {
  this.classList.remove('saving');
  this.classList.add('unsaved');
  this.savebutton.innerHTML = this.savebutton.origHTML;
  this.savebutton.classList.remove('disabled');
  if (w?.submitbutton?.updateMe) {
    w.submitbutton.updateMe();
  }
}

function minimize(b) {
  if (!this?.mywidget) return;
  const widg = this.mywidget;
  // minimize
  if (b) {
    widg.classList.add('minimized');
    if (widg?.mymarker?.innermarker) {
      const innermarker = widg.mymarker.innermarker;
      innermarker.onclick = (e) => {
        this.minimize(false);
        e.stopPropagation();
        e.preventDefault();
      }
      innermarker.onpointerdown = (e) => {
        e.stopPropagation();
        e.preventDefault();
      }
      innermarker.onpointerup = (e) => {
        e.stopPropagation();
        e.preventDefault();
      }
      innermarker.style.cursor = 'pointer';
    }
    if (this.ishtml) {
      let chch = w.htmld.getElementsByClassName(this.id + '-change');
      if (chch) {
        for (const ch of chch) {
          if (!(ch.classList.contains('ogtfchange'))) {
            continue;
          }
          ch.onclick = ((e) => {
            this.minimize(false);
            e.preventDefault();
            e.stopPropagation();
          });
          ch.onpointerdown = ((e) => {
            e.preventDefault();
            e.stopPropagation();
          });
          ch.onpointerup = ((e) => {
            e.preventDefault();
            e.stopPropagation();
          });
          ch.style.cursor = 'pointer';
        }
      }
    }
    if (!("minimizemarker" in widg)) {
      widg.minimizemarker = addelem({
        tag: 'span',
        parent: widg,
        mywidget: widg,
        classes: ['minimizemarker'],
        innerHTML:
          '<span class="material-symbols-outlined">comment</span>' +
          '<span class="material-symbols-outlined">expand_less</span>',
        onclick: function () {
          this.mywidget.commentform.minimize(false);
        }
      });
    }
    return;
  }
  // unminimize
  widg.classList.remove('minimized');
  if (widg?.mymarker?.innermarker) {
    const innermarker = widg.mymarker.innermarker;
    innermarker.onclick = (e) => (true);
    innermarker,onpointerdown = (e) => (true);
    innermarker,onpointerup = (e) => (true);
    innermarker.style.cursor = 'default';
  }
  if (this.ishtml) {
    let chch = w.htmld.getElementsByClassName(this.id + '-change');
    if (chch) {
      for (const ch of chch) {
        if (!(ch.classList.contains("ogtfchange"))) { continue; }
        chch.onclick = ((e) => (true));
        chch.onpointerdown = ((e) => (true));
        chch.onpointerup = ((e) => (true));
        ch.style.cursor = 'default';
      }
    }
  }
}

function makeCommentForm(widg, ctype, id) {
  const commentform = addelem({
    tag: 'span',
    parent: widg,
    id: id,
    mytype: ctype,
    mywidget: widg,
    onpointerdown: function(e) {
      e.preventDefault();
      e.stopPropagation();
    },
    onpointerup: function(e) {
      e.preventDefault();
      e.stopPropagation();
    },
    onkeydown: function(e) {
      e.forcethrough = true;
    },
    classes: ['commentform', ctype]
  });
  commentform.dellabel = addelem({
    tag: 'label',
    parent: commentform,
    classes: ['del'],
    innerHTML: 'text to remove'
  });
  commentform.delinput = addelem({
    tag: 'textarea',
    classes: ['del'],
    parent: commentform
  });
  commentform.inslabel = addelem({
    tag: 'label',
    parent: commentform,
    classes: ['ins'],
    innerHTML: 'text to insert'
  });
  commentform.insinput = addelem({
    tag: 'textarea',
    classes: ['ins'],
    parent: commentform,
    mycommentform: commentform,
    oninput: function(e) {
      const commentform = this.mycommentform;
      if (commentform.makeUnsaved) {
        commentform.makeUnsaved();
      }
      // fill in insertion block
      if (commentform?.mywidget?.insertionpoint) {
        commentform.mywidget.insertionpoint.innerHTML = this.value;
      }
    }
  });
  commentform.commentlabel = addelem({
    tag: 'label',
    parent: commentform,
    classes: ['comment'],
    innerHTML: ((ctype == 'query') ? 'query' : 'comment')
  });
  commentform.commentinput = addelem({
    tag: 'textarea',
    classes: ['comment'],
    parent: commentform
  });
  if (ctype == 'query' && !w.iseditor) {
    commentform.commentinput.readOnly = true;
  }
  commentform.responselabel = addelem({
    tag: 'label',
    classes: ['response'],
    parent: commentform,
    innerHTML: 'response'
  });
  commentform.responseinput = addelem({
    tag: 'textarea',
    classes: ['response'],
    parent: commentform
  });
  for (const x of ['del','comment','response']) {
    commentform[x+'input'].oninput = () => {
      if (commentform.makeUnsaved) { commentform.makeUnsaved(); }
    }
  }
  commentform.addressedarea = addelem({
    tag: 'span',
    parent: commentform,
    classes: ['commentformaddressedarea']
  });
  commentform.addressedcb = addelem({
    tag: 'input',
    type: 'checkbox',
    id: commentform.id + 'addressed',
    mycommentform: commentform,
    parent: commentform.addressedarea,
    onchange: function() {
      const commentform = this.mycommentform;
      if (this.checked) {
        commentform.saveComment();
      } else {
        commentform.makeUnsaved();
      }
    }
  });
  commentform.addressedlabel = addelem({
    tag: 'label',
    htmlFor: commentform.id + 'addressed',
    parent: commentform.addressedarea,
    classes: ['commentformaddressedlabel'],
    innerHTML: 'has been addressed'
  });
  commentform.buttons = addelem({
    tag: 'span',
    parent: commentform,
    classes: ['commentformbuttons']
  });
  commentform.rightbuttons = addelem({
    tag: 'span',
    parent: commentform.buttons,
    classes: ['commentformrightbuttons']
  });
  commentform.leftbuttons = addelem({
    tag: 'span',
    parent: commentform.buttons,
    classes: ['commentformleftbuttons']
  });
  commentform.removebutton = addelem({
    tag: 'span',
    parent: commentform.leftbuttons,
    title: 'delete this comment',
    classes: ['commentformbutton', 'removebutton'],
    mycommentform: commentform,
    innerHTML: '<span class="material-symbols-outlined">' +
      'delete_forever</span>',
    onclick: function() {
      this.mycommentform.deleteComment();
    }
  });
  commentform.savebutton = addelem({
    tag: 'span',
    title: 'save this comment',
    parent: commentform.rightbuttons,
    mycommentform: commentform,
    classes: ['commentformbutton','savebutton'],
    innerHTML: 'save <span class="material-symbols-outlined">' +
      'save</span>',
    onclick: function() {
      if (this.classList.contains('disabled')) { return; }
      if (this.mycommentform.classList.contains('saving')) {
        return;
      }
      this.mycommentform.saveComment();
    }
  });
  commentform.savebutton.origHTML = commentform.savebutton.innerHTML;
  commentform.minimizebutton = addelem({
    tag: 'span',
    title: 'minimize',
    mycommentform: commentform,
    parent: commentform.rightbuttons,
    classes: ['commentformbutton', 'minimize'],
    innerHTML: '<span class="material-symbols-outlined">' +
      'expand_more</span>',
    onclick: function() {
      this.mycommentform.minimize(true);
    }
  });
  commentform.clearer = addelem({
    tag: 'br',
    parent: commentform.buttons
  });
  commentform.onclick = function(e) {
    e.stopPropagation();
  }
  commentform.onpointerdown = function(e) {
    e.stopPropagation();
  }
  commentform.setAttribute('contenteditable',false);
  commentform.deleteComment = deleteComment;
  commentform.saveComment = saveComment;
  commentform.makeSaved = makeSaved;
  commentform.makeUnsaved = makeUnsaved;
  commentform.makeSaving = makeSaving;
  commentform.purgeTraces = purgeTraces;
  commentform.minimize = minimize;
  commentform.eversaved = false;
  commentform.makeUnsaved();
  return commentform;
}

function makeCommentTypeSelector(parnode) {

  const commentselector = addelem({
    parent: parnode,
    tag: 'div',
    classes: ['commentselector'],
    mywidget: parnode,
    onpointerdown: function(e) {
      e.preventDefault();
      e.stopPropagation();
    },
    onpointerup: function(e) {
      e.preventDefault();
      e.stopPropagation();
    }
  });
  commentselector.setAttribute('contenteditable',false);

  const adddel = addelem({
    parent: commentselector,
    tag: 'div',
    classes: ['commenttype','del'],
    title: 'mark selection for deletion',
    innerHTML: 'deletion',
    mywidget: parnode,
    onmousedown: function (e) { e.preventDefault(); },
    onpointerdown: function (e) {
      e.preventDefault();
      e.stopPropagation();
    },
    onclick: function (e) {
      e.preventDefault();
      this.mywidget.makeType('deletion');
    }
  });

  const addins = addelem({
    parent: commentselector,
    tag: 'div',
    classes: ['commenttype','ins'],
    title: 'mark spot for insertion',
    innerHTML: 'insertion',
    mywidget: parnode,
    onmousedown: function (e) { e.preventDefault(); },
    onpointerdown: function (e) {
      e.preventDefault();
      e.stopPropagation();
    },
    onclick: function (e) { this.mywidget.makeType('insertion'); }
  });

  const addcomm = addelem({
    parent: commentselector,
    tag: 'div',
    classes: ['commenttype','comment'],
    title: 'add a comment',
    innerHTML: 'comment',
    mywidget: parnode,
    onmousedown: function (e) {
      e.preventDefault();
    },
    onpointerdown: function (e) {
      e.preventDefault();
      e.stopPropagation();
    },
    onclick: function (e) { this.mywidget.makeType('comment'); }
  });

  if (w.iseditor) {
    const addquery = addelem({
      parent: commentselector,
      tag: 'div',
      classes: ['commenttype','query'],
      title: 'add a query',
      innerHTML: 'query',
      mywidget: parnode,
      onmousedown: function (e) {
        e.preventDefault();
      },
      onpointerdown: function (e) {
        e.preventDefault();
        e.stopPropagation();
      },
      onclick: function (e) {
        this.mywidget.makeType('query');
      }
    });
  }

  const cancx = addelem({
    parent: commentselector,
    tag: 'div',
    classes: ['commentselectorcancel'],
    title: 'cancel',
    innerHTML: '<span class="material-symbols-outlined">close</span>',
    mywidget: parnode,
    onpointerdown: function(e) {
      e.preventDefault();
      e.stopPropagation();
    },
    onclick: function(e) {
      const m = this.mywidget?.mymarker;
      if (m) {
        m.mypage.isdrawing = false;
        if (m.mypage.drawingmarker) {
          delete (m.mypage.drawingmarker);
        }
        m.parentNode.removeChild(m);
        return;
      }
      if (this.mywidget.classList.contains('commentselectorholder')) {
        this.mywidget.style.display = 'none';
      }
    }
  });

  return commentselector;
}

function makeHtmlType(ctype, id = false) {
  if (!id) {
    id = 'comment' + ((new Date()).getTime().toString());
  }
  const selection = this?.myselection;
  let deltext = '';
  let marker = {};
  if (selection) {
    if (selection.toString() != '') {
      deltext = selection.toString();
    }
    const position = selection.anchorNode.compareDocumentPosition(
      selection.focusNode);
    let anchorfirst;
    let onlyoneselected = false;
    if (position && Node.DOCUMENT_POSITION_FOLLOWING) {
      anchorfirst = true;
    } else if (position && Node.DOCUMENT_POSITION_PRECEDING) {
      anchorfirst = false;
    } else {
      onlyoneselected = true;
    }
    let firstnodeoffset = selection.anchorOffset;
    let firstnode = selection.anchorNode;
    let endnode = selection.focusNode;
    let endnodeoffset = selection.focusOffset;
    if (!anchorfirst) {
      firstnode = selection.focusNode;
      firstnodeoffset = selection.focusOffset;
      endnode = selection.anchorNode;
      endnodeoffset = selection.anchorOffset;
    }
    let ctypetagtype = 'span';
    let ctypeclasses = [ id + '-change', 'ogtfchange' ];
    if (ctype == 'deletion') {
      ctypetagtype = 'del';
    }
    if (ctype == 'query') {
      ctypeclasses.push('ogtfquery');
    }
    if (ctype == 'comment') {
      ctypeclasses.push('ogtfcomment');
    }
    // handle other nodes
    let tt = getTextNodes(w.htmld.body);
    tt = tt.filter((t) => (selection.containsNode(t)));
    tt = tt.filter((t) => (t != firstnode && t != endnode));
    for (const t of tt) {
      const tTC = t.textContent;
      const repNode = addelem({
        tag: ctypetagtype,
        classes: ctypeclasses,
        innerHTML: tTC
      });
      const tparNode = t.parentNode;
      tparNode.insertBefore(repNode, t);
      tparNode.removeChild(t);
    }

    // handle first node
    const fnTC = firstnode.textContent;
    let fnPre = fnTC.substring(0, firstnodeoffset);
    let fnMid = '';
    let fnPost = fnTC.substring(firstnodeoffset);
    if (onlyoneselected) {
      const minoffset = Math.min(firstnodeoffset, endnodeoffset);
      const maxoffset = Math.max(firstnodeoffset, endnodeoffset);
      fnPre = fnTC.substring(0, minoffset);
      fnMid = fnTC.substring(minoffset,maxoffset);
      fnPost = fnTC.substring(maxoffset);
    }
    const parNode = firstnode.parentNode;
    if (fnPre != '') {
      const preNode = addelem({
        tag: 'span',
        innerHTML: fnPre,
        classes: [id + '-change']
      });
      parNode.insertBefore(preNode, firstnode);
    }
    marker = addelem({
      tag: 'span',
      classes: ['htmlcommentmarker','proofsetaddition',
                id+'-marker'],
    });
    marker.setAttribute('commenteditable',false);
    parNode.insertBefore(marker, firstnode);
    marker.commentwidget = widgify(marker, {});
    if (this.classList.contains('noselection')) {
      marker.visiblemarker = addelem({
        tag: 'span',
        classes: ['visiblemarker', ctype, id+'-visiblemarker'],
        parent: marker.innermarker
      });
    }
    const yloc = marker.offsetTop;
    if ((yloc > 0) && (yloc < 350)) {
      marker.commentwidget.classList.add("underneath");
    }
    const xloc = marker.offsetLeft;
    const sw = w.htmlw.innerWidth;
    if (((xloc+375) > sw) && (xloc > (sw/2))) {
      marker.commentwidget.classList.add("pushleft");
    }
    marker.commentwidget.classList.remove('selecting');
    marker.commentwidget.makeType = function() {};
    if (fnMid != '') {
      const midNode = addelem({
        tag: ctypetagtype,
        classes: ctypeclasses,
        innerHTML: fnMid,
      });
      parNode.insertBefore(midNode, firstnode);
    }
    if (firstnode == endnode) {
      marker.commentwidget.insertionpoint = addelem({
        tag: 'ins',
        id: id+'-insertionpoint',
        classes: ctypeclasses
      });
      parNode.insertBefore(marker.commentwidget.insertionpoint,
                           firstnode);
    }
    if (fnPost != '') {
      let posttagt = 'span';
      let postclasses = [id + '-change'];
      if (firstnode != endnode) {
        posttagt = ctypetagtype;
        postclasses = ctypeclasses;
      }
      const postNode = addelem({
        tag: posttagt,
        classes: postclasses,
        innerHTML: fnPost
      });
      parNode.insertBefore(postNode, firstnode);
    }
    parNode.removeChild(firstnode);
    // handle last node
    if (firstnode != endnode) {
      const enTC = endnode.textContent;
      const enPre = enTC.substring(0, endnodeoffset);
      const enPost = enTC.substring(endnodeoffset);
      const eparNode = endnode.parentNode;
      if (enPre != '') {
        const epreNode = addelem({
          tag: ctypetagtype,
          innerHTML: enPre,
          classes: ctypeclasses
        });
        eparNode.insertBefore(epreNode, endnode);
      }
      marker.commentwidget.insertionpoint = addelem({
        tag: 'ins',
        id: id+'-insertionpoint',
        classes: ctypeclasses
      });
      eparNode.insertBefore(marker.commentwidget.insertionpoint,
                            endnode);
      if (enPost != '') {
        const epostNode = addelem({
          classes: [id +'-change'],
          tag: 'span',
          innerHTML: enPost
        });
        eparNode.insertBefore(epostNode, endnode);
      }
      eparNode.removeChild(endnode);
    }
    selection.collapse(null);
    if (w.htmlw.commentselectorholder) {
      w.htmlw.commentselectorholder.style.display = 'none'
    }
    marker.classList.add(ctype);
    if (marker?.commentwidget) {
      marker.commentwidget.classList.add(ctype);
      marker.commentwidget.commentform = makeCommentForm(
        marker.commentwidget, ctype, id);
      if (ctype == 'deletion' && deltext != '') {
        const delinput = marker.commentwidget.commentform.delinput;
        delinput.value = deltext;
        delinput.readOnly = true;
      }
      marker.commentwidget.commentform.ishtml = true;
    }
  } else {
    marker = this;
    marker.setAttribute("commenteditable", false);
    marker.commentwidget = widgify(marker, {});
    if (this.hasvm) {
      marker.visiblemarker = addelem({
        tag: 'span',
        classes: ['visiblemarker', ctype, id + '-visiblemarker'],
        parent: marker.innermarker
      });
    }
    const yloc = marker.offsetTop;
    if ((yloc > 0) && (yloc < 350)) {
      marker.commentwidget.classList.add("underneath");
    }
    const xloc = marker.offsetLeft;
    const sw = w.htmlw.innerWidth;
    if ((xloc+375) > sw && (xloc > (sw/2))) {
      marker.commentwidget.classList.add("pushleft");
    }
    marker.commentwidget.classList.remove('selecting');
    marker.commentwidget.makeType = function() {};
    marker.classList.add(ctype);
    if (marker?.commentwidget) {
      marker.commentwidget.classList.add(ctype);
      marker.commentwidget.commentform = makeCommentForm(
        marker.commentwidget, ctype, id);
      marker.commentwidget.commentform.ishtml = true;
    }
  }
}

function makePdfType(ctype, id = false) {
  const alltypes = ['drawing','deletion','query','comment','insertion'];
  for (const thistype of alltypes) {
    if (this?.mymarker) {
      this.mymarker.classList.remove(thistype);
    }
    this.classList.remove(thistype);
  }
  if (this?.mymarker) {
    this.mymarker.classList.add(ctype);
    // detach it as the drawing marker so it is not overwritten
    if (this.mymarker?.mypage?.drawingmarker) {
      delete(this.mymarker.mypage.drawingmarker);
    }
  }
  this.classList.remove('selecting');
  this.classList.add(ctype);
  if (this?.myselector) {
    this.myselector.parentNode.removeChild(this.myselector);
    delete(this.myselector);
  }
  if (!id) {
    id = 'comment' + ((new Date()).getTime().toString());
  }
  this.commentform = makeCommentForm(this, ctype, id);
}

// Functions for drawing boxes

function updatePosition(pp) {
  if (!this?.anchorPP) { return; }
  if (!pp?.x || !pp?.y) { return; }
  const anchorx = this.anchorPP.x;
  const anchory = this.anchorPP.y;
  const minx = Math.min(anchorx, pp.x);
  const maxx = Math.max(anchorx, pp.x);
  const miny = Math.min(anchory, pp.y);
  const maxy = Math.max(anchory, pp.y);
  this.style.left = minx.toString() + '%';
  this.style.right = (100 - maxx).toString() + '%';
  this.style.top = miny.toString() + '%';
  this.style.bottom = (100 - maxy).toString() + '%';
  this.wanderPP = pp;
}

function pointerPerc(elem, evnt) {
  const bcr = elem.getBoundingClientRect();
  const w = bcr.right - bcr.left;
  const h = bcr.bottom - bcr.top;
  const rx = evnt.clientX - bcr.left;
  const ry = evnt.clientY - bcr.top;
  const xp = (rx/w) * 100;
  const yp = (ry/h) * 100;
  return { x: xp, y: yp };
}

function createPdfCommentMarker(elem) {
  const marker = addelem({
    tag: 'div',
    classes: ['pdfcommentmarker'],
    mypage: elem,
    parent: elem
  });
  marker.style.position = 'absolute';
  marker.style.display = 'inline-block';
  w.nummarkers = w.nummarkers + 1;
  marker.updatePosition = updatePosition;
  return marker;
}

function startdraw(elem, evnt) {
  if (elem.isdrawing) { return; }
  elem.isdrawing = true;
  if (elem.drawingmarker) {
    canceldraw(elem, evnt);
  }
  elem.drawingmarker = createPdfCommentMarker(elem);
  const marker = elem.drawingmarker;
  marker.classList.add('drawing');
  marker.anchorPP = pointerPerc(elem, evnt);
  marker.updatePosition(marker.anchorPP);
  elem.isdrawing = true;
}

function continuedraw(elem, evnt) {
  if (!elem.isdrawing) return;
  const newPP = pointerPerc(elem, evnt);
  elem.drawingmarker.updatePosition(newPP);
}

function canceldraw(elem, evnt) {
  if (!elem.isdrawing) return;
  const marker = elem.drawingmarker;
  marker.parentNode.removeChild(marker);
  delete(elem.drawingmarker);
  elem.isdrawing = false;
}

function widgify(marker, elem) {
  marker.innermarker = addelem({
    parent: marker,
    classes: ['innermarker'],
    tag: 'span',
    onpointerdown: function(e) {
      e.stopPropagation();
    },
  });
  const commentwidget = addelem({
    parent: marker.innermarker,
    mymarker: marker,
    tag: 'span',
    classes: ['commentwidget','selecting'],
    onpointerdown: function(e) {
      e.stopPropagation();
    },
    makeType: makePdfType
  });
  const anchorPP = marker?.anchorPP ?? false;
  const newPP = marker?.wanderPP ?? false;
  if (anchorPP && newPP) {
    if (elem?.id == 'page1' & (anchorPP.y < 25 || newPP.y < 25)) {
      commentwidget.classList.add('underneath');
    }
    if (anchorPP.x > 80 || newPP.x > 80) {
      commentwidget.classList.add('pushleft');
    }
  }
  return commentwidget;
}

function enddraw(elem, evnt) {
  if (!elem.isdrawing) return;
  const newPP = pointerPerc(elem, evnt);
  const marker = elem.drawingmarker
  const anchorPP = marker.anchorPP;
  if (newPP.x == anchorPP.x || newPP.y == anchorPP.y) {
    canceldraw(elem, evnt);
    return;
  }
  marker.updatePosition(newPP);
  elem.isdrawing = false;
  marker.commentwidget = widgify(marker, elem);
  const commentwidget = marker.commentwidget;
  if (!w.iseditor) {
    commentwidget.myselector = makeCommentTypeSelector(commentwidget);
  } else {
    commentwidget.makeType('query');
  }
}

// Function for submitting changes
async function submitToEditors() {
  w.submitbutton.classList.remove('lookatme');
  w.submitbutton.classList.add('submitting');
  const req = {
    requesttype: 'submit'
  }
  w.submitbutton.innerHTML = '<span class="material-symbols-outlined ' +
    'rotating">sync</span> submitting …'
  const resp = await jsonrequest(req);
  w.submitbutton.classList.remove('submitting');
  w.submitbutton.innerHTML = 'submit';
  if (resp) {
    w.anychangesmade = false;
    w.submitbutton.updateMe();
  }
  if (!resp) {
    w.submitbutton.classList.add('lookatme');
    return;
  }
  okmessage('Thank you for your comments and corrections. They have ' +
    'been submitted to the editors. You may close this window now. ' +
    'If you need to make any additional changes, you may visit this ' +
    'page with the same URL, add more comments, and resubmit.');
}

//
// HTML functions
//
function getTextNodes(node) {
  let rv = [];
  for (node=node.firstChild; node; node=node.nextSibling) {
    if (node.nodeType == 3) {
      rv.push(node);
    } else {
      rv = rv.concat(getTextNodes(node));
    }
  }
  return rv;
}

function htmlSelectionChange(e) {
  const selection = w.htmlw.getSelection();
  // don't let it work outside of a text block
  if (selection.anchorNode?.tagName) return;
  if (!w.htmlw.commentselectorholder) {
    w.htmlw.commentselectorholder = addelem({
      tag: 'div',
      classes: ['commentselectorholder','proofsetaddition'],
      parent: w.htmld.body
    });
    w.htmlw.commentselectorholder.setAttribute('contenteditable',false);
  }
  if (!w.htmlw.commentselector) {
    htmlw.commentselector = makeCommentTypeSelector(
      w.htmlw.commentselectorholder
    );
  }
  w.htmlw.commentselectorholder.style.display = 'inline-block';
  w.htmlw.commentselectorholder.style.left =
    (e.layerX - 20).toString() + 'px';
  w.htmlw.commentselectorholder.style.top =
    (e.layerY - 65).toString() + 'px';
  if (selection.isCollapsed) {
    w.htmlw.commentselectorholder.classList.add("noselection");
  } else {
    w.htmlw.commentselectorholder.classList.remove("noselection");
  }
  w.htmlw.commentselectorholder.myselection = selection;
  w.htmlw.commentselectorholder.makeType = makeHtmlType;
}

//
// FILL IN THE PANEL
//
// we put what's on the right first to keep it up top
const rightbuttons = addelem({
  parent: w.toppanel,
  tag: 'div',
  id: 'rightbuttons'
});

const dllabel = addelem({
  parent: rightbuttons,
  tag: 'div',
  innerHTML: 'download:'
});

const exticons = {
  "md": "draft",
  "epub": "install_mobile",
  "html": "public",
  "pdf": "picture_as_pdf",
  "zip": "folder_zip"
}

for (const file of w.downloads) {
  const ext = file.split('.').reverse()[0];
  let icon = 'download';
  if (ext in exticons) {
    icon = exticons[ext];
  }
  const dlbtn = addelem({
    tag: 'div',
    parent: rightbuttons,
    title: 'download ' + ext + ' file',
    myext: ext,
    myfilename: file,
    classes: ['downloadbutton'],
    innerHTML: '<span class="material-symbols-outlined">' + icon +
      '</span>',
    onclick: function() {
      downloadFile(`/${w.urlbase}/proofs/serve?download=true&ext=` +
         encodeURIComponent(this.myext) + '&key=' +
         encodeURIComponent(w.accesskey),
         this.myfilename);
    }
  });
}

w.submitbutton = addelem({
  parent: rightbuttons,
  tag: 'div',
  innerHTML: 'submit',
  tooltip : 'submit changes to editor',
  title: ((w.iseditor) ? '(button for author use only)' :
          '(no changes to submit)'),
  id: 'submitbutton',
  classes: ['disabled'],
  updateMe: function() {
    // never change it in editor mode
    if (w.iseditor) return;
    let readytosubmit = w.anychangesmade;
    if (readytosubmit) {
      clearError();
      const uu = document.getElementsByClassName("unsaved");
      if (uu.length > 0) {
        readytosubmit = false
      }
      const huhu = w.htmld.getElementsByClassName("unsaved");
      if (huhu.length > 0) {
        readytosubmit = false;
      }
    }
    if (readytosubmit) {
      this.classList.remove('disabled');
      this.classList.add('lookatme');
      this.title = this.tooltip;
    } else {
      this.classList.add('disabled');
      this.classList.remove('lookatme');
      if (w.anychangesmade) {
        this.title = 'please save any open comments before ' +
          'submititng';
      } else {
        this.title = '(no changes to submit)'
      }
    }
  },
  onclick: function() {
    if (this.classList.contains('disabled')) return;
    if (this.classList.contains('submitting')) return;
    submitToEditors();
  }
});

// view selection choices
const viewselector = addelem({
  parent: w.toppanel,
  tag: 'div',
  id: 'viewselector'
});

const viewlabel = addelem({
  parent: viewselector,
  tag: 'div',
  innerHTML: 'view:'
});

const instructionselect = addelem({
  parent: viewselector,
  tag: 'div',
  innerHTML: 'instructions',
  title: 'view instructions',
  classes: ['viewoption', 'instructions'],
  onclick: function(e) {
    e.preventDefault();
    changeMode('instructions');
  }
});

if (w.usehtml) {
  const htmlselect = addelem({
    parent: viewselector,
    tag: 'div',
    innerHTML: 'html proofs',
    title: 'view html proofs',
    classes: ['viewoption','html'],
    onclick: function(e) {
      e.preventDefault();
      changeMode('html');
    }
  });
}

if (w.pdfpp > 0) {
  const pdfselect = addelem({
    parent: viewselector,
    tag: 'div',
    innerHTML: 'pdf proofs',
    title: 'view pdf proofs',
    classes: ['viewoption', 'pdf'],
    onclick: function(e) {
      e.preventDefault();
      changeMode('pdf');
    }
  });
}

if (w.pdfpp > 0) {
  const pdfbuttons = addelem({
    parent: w.toppanel,
    tag: 'div',
    classes: ['pdfbuttons', 'pdfonly']
  });
  const zoomout = addelem({
    parent: pdfbuttons,
    innerHTML: '<span class="material-symbols-outlined">zoom_out</span>',
    classes: ['pdfbutton'],
    tag: 'div',
    onclick: function() { zoomInOut(false); }
  });
  const fit = addelem({
    parent: pdfbuttons,
    innerHTML: '<span class="material-symbols-outlined">fit_width</span>',
    classes: ['pdfbutton'],
    tag: 'div',
    onclick: function() { changeZoom('fitwidth'); }
  });
  const zoomin = addelem({
    parent: pdfbuttons,
    innerHTML: '<span class="material-symbols-outlined">zoom_in</span>',
    classes: ['pdfbutton'],
    tag: 'div',
    onclick: function() { zoomInOut(true); }
  });
  const pagejump = addelem({
    parent: pdfbuttons,
    tag: 'input',
    type: 'number',
    min: 1,
    max: w.pdfpp,
    placeholder: 'goto page',
    id: 'pagejump',
    onchange: function() {
      const id = 'page' + this.value;
      const page = document.getElementById(id);
      if (page) {
        page.scrollIntoView();
      }
      // clear old value
      this.value = '';
    }
  });
  const oftotal = addelem({
    parent: pdfbuttons,
    tag: 'div',
    innerHTML: ' / ' + w.pdfpp.toString()
  });
}
//
// SET UP PDF LISTENERS
//
w.pdfpages.addEventListener('keydown', function(e) {
  const w = this.clientWidth;
  const h = this.clientHeight;
  const pageamount = (h/w.pdfpp)*0.6;
  const hamount = h/10;
  const wamount = w/10;
  //pageup, pagedown changes pages
  if (e.key == 'PageDown') {
    e.preventDefault();
    this.scrollTop = this.scrollTop + pageamount;
    return;
  }
  if (e.key == 'PageUp') {
    e.preventDefault();
    this.scrollTop = this.scrollTop - pageamount;
    return;
  }
  // arrow up, down, etc. scrolls
  if (e.key == 'ArrowUp') {
    e.preventDefault();
    this.scrollTop = this.scrollTop - hamount;
    return;
  }
  if (e.key == 'ArrowDown') {
    e.preventDefault();
    this.scrollTop = this.scrollTop + hamount;
    return;
  }
  if (e.key == 'ArrowRight') {
    e.preventDefault();
    this.scrollLeft = this.scrollLeft + wamount;
    return;
  }
  if (e.key == 'ArrowLeft') {
    e.preventDefault();
    this.scrollLeft = this.scrollLeft - wamount;
    return;
  }
});

// listener for creating pdf boxes
if (w.pdfpp > 0) {
  for (const page of w.pdfpages.getElementsByClassName("pdfpage")) {
    page.isdrawing = false;
    page.onpointerdown = function(e) {
      if (!this.isdrawing) {
        startdraw(this, e);
      }
    }
    page.onpointermove = function(e) {
      if (this.isdrawing) {
        continuedraw(this, e);
      }
    }
    page.onpointerup = function(e) {
      if (this.isdrawing) {
        enddraw(this, e);
      }
    }
    page.onpointercancel = function(e) {
      if (this.isdrawing) {
        canceldraw(this, e);
      }
    }
    page.onpointerleave = function(e) {
      if (this.isdrawing) {
        canceldraw(this, e);
      }
    }
  }
}

// restore pdf comments
if ((w?.savedcomments) && ("pdf" in w.savedcomments)) {
  for (const commentid in w.savedcomments.pdf) {
    const commentinfo = w.savedcomments.pdf[commentid];
    if (!commentinfo?.page) { continue; }
    const page = document.getElementById(commentinfo.page);
    if (!page) { continue; }
    const marker = createPdfCommentMarker(page);
    marker.anchorPP = commentinfo.anchorPP ?? { x: 0, y: 0 };
    marker.updatePosition(commentinfo.wanderPP ?? { x: 10, y: 10 });
    marker.commentwidget = widgify(marker, page);
    if (!commentinfo?.commenttype) { continue; }
    marker.commentwidget.makeType(commentinfo.commenttype, commentid);
    if (!marker.commentwidget.commentform) { continue; }
    const commentform = marker.commentwidget.commentform;
    // restore input fields
    for (const x of ['comment','ins','del','response']) {
      if (x in commentinfo) {
        commentform[x + 'input'].value = commentinfo[x];
      }
    }
    // restore check box
    commentform.addressedcb.checked = (("hasbeenaddressed" in commentinfo)
      && (commentinfo.hasbeenaddressed));
    commentform.makeSaved();
    // even saved queries should start open for non-editors
    if (commentinfo?.commenttype == 'query' &&
        commentform.responseinput.value == '' &&
        (!w.iseditor)) {
      commentform.minimize(false);
    }
  }
}

// set pdf zoom level
if (document.body.clientWidth < 1200) {
  changeZoom('fitwidth');
}
//
// SET UP HTML PROOFS
//
w.htmlw = {}; w.htmld = {};

function setUpHtml() {
  if (w.htmlproofs.contentWindow) {
    w.htmlw = w.htmlproofs.contentWindow;
  }

  if (htmlproofs.contentDocument) {
    w.htmld = w.htmlproofs.contentDocument;
  }
  if (!w.htmld.body) { return; }

  // remove any old commentselector
  const hh = w.htmld.getElementsByClassName("commentselectorholder");
  if (hh) { for (const h of hh) { h.parentNode.removeChild(h); } };

  // fix old comments
  if ((w?.savedcomments) && ("html" in w.savedcomments)) {
    for (const commentid in w.savedcomments.html) {
      const commentinfo = w.savedcomments.html[commentid];
      const markers = w.htmld.getElementsByClassName(commentid + "-marker");
      if (!markers || markers.length == 0) { continue; }
      const marker = markers[0];
      const vv = marker.getElementsByClassName("visiblemarker");
      const hasvm = (vv && vv.length> 0);
      // clear it out
      marker.innerHTML = '';
      marker.hasvm = hasvm;
      marker.makeType = makeHtmlType;
      marker.makeType(commentinfo.commenttype, commentid);
      if (!marker.commentwidget.commentform) { continue; }
      const commentform = marker.commentwidget.commentform;
      // restore values
      for (const x of ['comment', 'ins', 'del', 'response']) {
        if (x in commentinfo) {
          commentform[x + 'input'].value = commentinfo[x];
        }
      }
      // make deletion deletioninput readOnly
      if (commentinfo.commenttype == 'deletion') {
        commentform.delinput.readyOnly = true;
      }
      // restore check box
      commentform.addressedcb.checked = (("hasbeenaddressed" in
        commentinfo) && (commentinfo.hasbeenaddressed));
      // restore insertion point
      const inspt = w.htmld.getElementById(commentid + '-insertionpoint');
      if (inspt) {
        marker.commentwidget.insertionpoint = inspt;
      }
      commentform.makeSaved();
      if (commentinfo?.commenttype == 'query' &&
          commentform.responseinput.value == '' &&
          (!w.iseditor)) {
        commentform.minimize(false);
      }
    }
  }

  // make editable
  w.htmld.body.setAttribute('contenteditable',true);
  w.htmld.body.setAttribute('spellcheck',false);
  // prevent actual editing?
  w.htmld.body.addEventListener('keydown', (e) => {
    if (e.forcethrough) { return true; }
    if (e.target.tagName.toLowerCase() == 'textarea') {
      return true;
    }
    if (
      ((!e.metaKey && !e.ctrlKey && !e.altKey) && (e.key.length == 1)) ||
      (e.key == 'Backspace' || e.key == 'Delete') ||
      (e.ctrlKey && (e.key == 'x' || e.key == 'X' || e.key == 'v' || e.key == 'V')) ||
      (e.shiftKey && e.key == 'Insert') ||
      (e.key == 'Enter') ||
      (e.keyCode > 128)
    ) {
      e.preventDefault();
    }
  });

  w.htmld.body.addEventListener('paste', (e) => {
    if (e.target.tagName.toLowerCase() == 'textarea') {
      return;
    }
    e.preventDefault();
  });

  w.htmld.body.addEventListener('cut', (e) => {
    if (e.target.tagName.toLowerCase() == 'textarea') {
      return;
    }
    e.preventDefault();
  });

  // context menus sometimes have their own way of deleting
  w.htmld.body.addEventListener('contextmenu', (e) => {
    e.preventDefault();
    return false;
  });

  // add listener for selection
  w.htmld.onpointerup = htmlSelectionChange;

  // apply editormode to html body
  if (document.body.classList.contains('editormode')) {
    w.htmld.body.classList.add('editormode');
  }

  // load CSS
  addelem({
    tag: 'link',
    rel: 'stylesheet',
    type: 'text/css',
    href: `/${w.urlbase}/public/shared.css`,
    parent: w.htmld.head
  });

}

setUpHtml();
w.htmlproofs.onload = setUpHtml;

// show one of the three main body elements
if (w?.starton) {
  changeMode(w.starton);
} else {
  if (w.iseditor) {
    if (w.usehtml) {
      changeMode('html');
    } else {
      changeMode('pdf');
    }
  } else {
    changeMode('instructions');
  }
}
