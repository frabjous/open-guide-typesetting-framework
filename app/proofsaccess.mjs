// LICENSE: GNU GPL v3 You should have received a copy of the GNU General
// Public License along with this program. If not, see
// https://www.gnu.org/licenses/.

// File: proofsaccess.php
// determines whether or not access to something involving page proofs
// is authenticated, and sends an error message if not
// also includes script for filling in proofs html page

import path from 'node:path';
import fs from './fs.mjs';
import {datadir, getProjectSettings} from './projects.mjs';
import {getAssignmentDir} from './libassignments.mjs';

const keyfile = path.join(datadir, 'proofkeys.json');

function htmlesc(s) {
  return s.replaceAll('&','&amp;')
    .replaceAll('<','&lt')
    .replaceAll('>','&gt');
}

export function proofsaccess(key) {
  if (!key) return {
    access: false,
    reason: 'No access key provided.'
  }
  const keys = fs.loadjson(keyfile);
  if (!keys) return {
    access: false,
    reason: 'No access keys readable on server.'
  }
  if (!keys?.[key]) return {
    access: false,
    reason: 'Invalid access key.'
  }
  const accessinfo = keys[key];
  const {project, username, assignmentId, assignmentType,
    proofset, editor} = accessinfo;
  const assigndir = getAssignmentDir(
    project, assignmentType, assignmentId, false
  );
  if (!assigndir) return {
    access: false,
    reason: 'Cannot find document directory.'
  }
  if (fs.isfile(path.join(assigndir, 'archived'))) return {
    access: false,
    reason: 'Document has been archived. Proofs can no longer ' +
      'be accessed or changed.'
  }
  const proofdir = path.join(assigndir, 'proofs', proofset);
  if (!fs.isdir(proofdir)) return {
    access: false,
    reason: 'Directory for proof set not found.'
  }
  return {
    access: true,
    key, project, username, assignmentId, assignmentType,
    proofset, proofdir, editor, assigndir
  }
}

export function proofspage(accessinfo) {
  const {
    assigndir,
    assignmentId,
    assignmentType,
    editor,
    key,
    project,
    proofdir,
    proofset,
    starton,
    username
  } = accessinfo;
  const pdfparentstart = 1200;
  const editorclass = (editor) ? ' class="editormode"' : '';
  const projSettings = getProjectSettings(project);
  const projecttitle = projSettings?.title ?? 'Open Guide';
  const usehtml = (fs.isfile(path.join(proofdir, assignmentId + '.html')));
  const usepdf = (fs.isfile(path.join(proofdir, assignmentId + '.pdf')));
  const pdfpages = fs.filesin(path.join(proofdir, 'pages')).filter(
    (f) => (/page[0-9]+\.svg$/.test(f))
  ).length;
  const downloads = fs.filesin(proofdir).map(
    (f) => (path.basename(f))
  ).filter(
    (f) => (f.startsWith(assignmentId + '.'))
  );
  const commentsfile = path.join(proofdir, 'saved-comments.json');
  const commentsjson = fs.readfile(commentsfile) ?? 'null';
  const urlbase = process?.ogtfbaseurl ?? 'ogtf';

  const editoronlyinstructions = (editor)
  ? `<h2>Editor instructions</h2>
<p>
Usage of the proofs pages for editors is mostly the same as for authors (see below).
The main difference is that while visiting a proofs page using the editor link, all comments left will automatically be of the (green) “query” type.
The presumption is that the editor will add any queries they have about the document before sending the author link for the proofs to the author(s).
When the authors visit the proofs page, these comments will be open by default and the authors can fill in their responses to the queries.
The editor should revisit the page after the authors submit their comments and corrections (which the editor should be notified about via email).
Editors can click the “has been addressed” checkbox for each correction/comment to mark it as dealt with.
</p>

<p>
The submit button is also disabled in editor mode, as editors do not need to submit their comments to themselves.
</p>

<p>
If, for whatever reason, an editor wishes to add a different kind of comment/correction, e.g., an insertion or deletion, they should use the author link instead. Both should be listed in the document’s “proofs” listing on the typesetting framework current project page.
</p>` : '';

  const htmlonlyinstructions = (usehtml)
  ? `<h2>HTML page proofs</h2>
<p>
When you are ready to view the html proofs, click the “html proofs” button on the panel above.
Please read over the proofs carefully.
</p>

<p>
There are three kinds of comments or corrections authors can leave on proofs: (a) deletions (pink), which indicate certain text should be deleted, with the optional possibility of replacement text to be inserted, (b) insertions (blue), which indicate that text should be inserted (only), and (c) comments (yellow), which can be used for any other kind of comment or question.
</p>

<p>
To add a comment or correction, either select the relevant part of the document with the mouse, or, if inserting text without removing anything, click the point where text should be inserted.
A small box should pop up when you release the mouse button (or lift your finger on a touch screen) giving you an option of what kind of comment/correction to leave.
(Deletions require some text be selected; insertions without deletions require the opposite.
Comments can be left in either kind of case.)
Once the type is chosen, a small form should pop up wherein you can specify your requested changes or comments.
When this is filled out, click the “save” button on the lower right of the form.
</p>

<div><img src="/${urlbase}/public/images/deletion.gif" alt="[animation of deletion correction]"></div>

<p>
Once saved, the comment form will minimize, but can be brought back up by clicking the small comment icon next to the marker for the comment.
It can be re-minimized by clicking the small button to the right of the “(saved)” indicator.
A comment can be completely removed by clicking the trash can icon in the lower left of the form.
</p>

<p>
You may see green comment markers and forms already open when you examine the proofs.
These are queries about the document left by the editor(s).
You can fill in the “response” field, and then save the comment to answer their query.
</p>

<div><img src="/${urlbase}/public/images/editorcomm.gif" alt="[animation of editor query]"></div>

<p>
Once you have reviewed the entire document, and saved all your comments and corrections, you can ${(usepdf) ? 'move to the pdf proofs, or ' : ''}submit your comments to the editor(s).
</p>` : '';

  const pdfonlyinstructions = (usepdf)
  ? (`<h2>PDF page proofs</h2>` +
  ((usehtml) ? `
<p>
You do not need to leave duplicate comments on both the html and pdf proofs.
Generally, comments regarding changes to the text should be left on the html proofs.
Comments specific to the pdf, e.g., those involving page breaks or running headers, etc., can be left on the pdf proofs.
</p>` : '') + `
<p>
To view the pdf proofs, click the “pdf proofs” button on the panel above.
</p>

<p>
The proofs consist of a series of images converted from the pages of the pdf.
To see the actual pdf itself, you can download it using the pdf icon in the upper right.
However, comments and corrections to the pdf should be indicated here on this page.
</p>

<p>
To leave a comment or correction on a pdf page, click (or touch) part of the page, and while the mouse button is down (or your finger is touching the touch screen), draw a box by moving the mouse (or your finger), and then release.
This should trigger a pop-up menu, on which you can choose what kind of comment or correction to leave.
${(usehtml) ? 'As with html proofs, t' : 'T'}here are three types you can choose from: (a) deletions (pink), indicating that text should be deleted, with the possibility of suggesting replacement text, (b) insertions (blue), indicating that text should be inserted (only), and (c) general comments (yellow), which may contain any other kind of comment or question.
</p>

<p>
Once you choose the type, a small dialogue window should appear allowing you to fill in the details of your comment or correction.
When done, click the “save” button in the lower right of the pop-up.
</p>

<div><img src="/${urlbase}/public/images/pdfcomment.gif" alt="[animation of pdf comment]"></div>

<p>
The comment will minimize once saved, but you can unminimize it by clicking again on the marker.
You can re-minimize it by clicking on the small icon next to the “(saved)” indicator.
You can also wholly remove a comment by clicking on the red trash can icon in the lower left of the pop-up form.
</p>

<p>
You may also see green comment boxes.
These are queries left by the editor(s) about the document.
You can fill in the “response” field for each query and save the query.
</p>

<p>
If all individual comments are saved, you may close this page, and re-open it again using the same URL to continue your work. However, please do not have multiple instances of proofs open in multiple tabs, browsers, or devices at the same time, or changes in one may override those in another.
</p>

<p>
When you are done adding comments and corrections, and all of them have been saved, you may submit your comments and corrections.
</p>`) : '';

  const onboth = (usehtml && usepdf) ?  'on both the pdf and html proofs' : '';

  const contactinfo =
    ((projSettings?.contactname) ? (', ' + htmlesc(projSettings?.contactname)) : '') +
    ((projSettings?.contactemail) ? (
      ` (<a href="mailto:${projSettings.contactemail}">${projSettings.contactemail}</a>)`
    ) : '');

  const htmlsrc = (usehtml)
    ? (`src="/${urlbase}/proofs/serve?key=${encodeURIComponent(key)}&html=true"`)
    : '';

  let pdfpagedivs = '';
  if (pdfpages > 0) {
    pdfpagedivs += '\n';
    for (let i = 1; i <= pdfpages; i++) {
      pdfpagedivs += `<div class="pdfpage" id="page${i.toString()}">` +
        `<img src="/${urlbase}/proofs/serve?key=${encodeURIComponent(key)}` +
        `&pdfpage=${i.toString()}" alt="pdf page ${i.toString()}" ` +
        `draggable="false"></div>\n`;
    }
  }

  let html = fs.readfile(
    path.join(process.__ogtfdirname, 'views', 'proofs.html')
  );
  if (!html) return null;
  const variables =  {
    assignmentId,
    assignmentType,
    commentsjson,
    contactinfo,
    editorclass,
    editoronlyinstructions,
    htmlonlyinstructions,
    htmlsrc,
    key,
    onboth,
    pdfonlyinstructions,
    pdfpagedivs,
    project,
    proofset,
    urlbase,
    username
  }
  for (const variable in variables) {
    html = html.replaceAll(`⟨${variable}⟩`, variables[variable]);
  }
  const evariables =  {
    projecttitle
  }
  for (const evar in evariables) {
    html = html.replaceAll(`⟨${evar}⟩`, htmlesc(evariables[evar]));
  }
  const jvariables = {
    iseditor: !!editor,
    downloads,
    starton,
    usehtml,
    usepdf,
    pdfpages,
    pdfparentstart
  }
  for (const jvar in jvariables) {
    html = html.replaceAll(`⟨${jvar}⟩`, JSON.stringify(jvariables[jvar]));
  }
  return html;
}
