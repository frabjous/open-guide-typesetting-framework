// LICENSE: GNU GPL v3 You should have received a copy of the GNU General
// Public License along with this program. If not, see
// https://www.gnu.org/licenses/.

// File: ogtfRouter.mjs
// exports a function for creating the router that can be mounted
// on an ExpressJS server, with certain options

import {cookiesecret, datadir, getProjects, projectlisthtml} from './app/projects.mjs';
import {verifyByAccesskey} from './app/libauthentication.mjs';
import express from 'express';
import cookieSession from 'cookie-session';
import fileUpload from 'express-fileupload';
import {fileURLToPath} from 'node:url';
import fs from './app/fs.mjs';
import path from 'node:path';
import jsonhandler from './app/jsonhandler.mjs';
import filehandler from './app/filehandler.mjs';
import downloadRequest from './app/downloadRequest.mjs';
import ogeRedirect from './app/ogeRedirect.mjs';
import {proofsaccess, proofspage} from './app/proofsaccess.mjs';
import nocache from 'nocache';

// get script directory
const __ogtffilename = fileURLToPath(import.meta.url);
const __ogtfdirname = path.dirname(__ogtffilename);
process.__ogtfdirname = __ogtfdirname;

function fillvars(html, swaps, urlbase) {
  for (const svar in swaps) {
    html = html.replaceAll(
      '⟨' + svar + '⟩',
      JSON.stringify(swaps[svar])
    );
  }
  html = html.replaceAll('⟨projectitems⟩', projectlisthtml);
  html = html.replaceAll('⟨urlbase⟩', process.ogtfbaseurl)
  return html;
}

function queryString(url) {
  const urlparts = url.split('?');
  if (urlparts.length < 2) return '';
  return '?' + urlparts[1];
}

export default async function ogtfRouter(opts) {
  if (!opts) opts = {};
  if (!opts?.ogepath) {
    opts.ogepath = path.join(__ogtfdirname, 'open-guide-editor');
  }
  if (!opts?.baseurl) opts.baseurl = 'ogtf';

  const {baseurl, ogepath} = opts;
  process.ogtfbaseurl = baseurl;
  const router = express.Router();

  try {
    const imported = await import(path.join(opts.ogepath, 'ogeRouter.mjs'));
    const ogeRouter = imported.default;
    router.use(ogeRouter);
  } catch(err) {
    console.error(err.stack);
    console.error('Could not load OGE router for OGTF.');
    process.exit(1);
  }

  router.use(`/${baseurl}/reverse`, async function(req, res) {
    const s = req?.query?.s;
    if (!s || s.length > 120) {
      res.status(401).type('txt').send('Error 401 Invalid Request');
      return;
    }
    const r = s.split('').reverse().join('');
    res.type('txt').send(r);
  });

  router.use(`/${baseurl}/public`, express.static(
    path.join(__ogtfdirname, 'public')
  ));

  router.use(`/${baseurl}/proofs/serve`, nocache());

  router.use(`/${baseurl}/proofs/serve`, async function(req, res) {
    const key = req?.query?.key;
    const praccess = proofsaccess(key);
    if (!praccess.access) {
      res.status(404).type('txt').send('Error 404 Not Found');
      return;
    }
    const proofdir = praccess?.proofdir;
    const assignmentId = praccess?.assignmentId;
    if (!proofdir || !assignmentId) {
      res.status(404).type('txt').send('Error 404 Not Found');
      return;
    }
    const download = (req?.query?.download == 'true');
    let filename = '';
    if (req?.query?.pdfpage) {
      let pagestr = req.query.pdfpage;
      while (pagestr.length < 2) pagestr = '0' + pagestr;
      filename = path.join(proofdir, 'pages', `page${pagestr}.svg`);
    }
    if ((req?.query?.html == 'true') && filename == '') {
      const savedname = path.join(proofdir, 'saved-with-comments.html');
      const regname = path.join(proofdir, `${assignmentId}.html`);
      filename = (fs.isfile(savedname)) ? savedname : regname;
    }
    if (req?.query?.ext && filename == '') {
      const ext = req.query.ext;
      filename = path.join(proofdir, `${assignmentId}.${ext}`);
    }
    if (!fs.isfile(filename)) {
      res.status(404).type('txt').send('Error 404 Not Found');
      return;
    }
    if (download) {
      res.download(filename);
      return;
    }
    res.sendFile(filename);
  });

  router.use(`/${baseurl}/proofs`, async function(req, res) {
    const key = req?.query?.key;
    const praccess = proofsaccess(key);
    if (!praccess.access) {
      res.status(401).type('txt').send('Error 401 Invalid Request: '
        + (praccess?.reason ?? ''));
      return;
    }
    const starton = req?.query?.starton;
    praccess.starton =
      (starton == 'pdf' || starton == 'html' || starton == 'instructions')
      ? starton : null;
    const page = proofspage(praccess);
    if (!page) {
      res.status(404).type('txt').send('Error 404 Not Found.');
      return;
    }
    res.type('html').send(page);
  });

  router.use(cookieSession({
    secret: cookiesecret,
    sameSite: 'Strict'
  }));

  // see cookie-session documentation
  router.use(function (req, res, next) {
    req.sessionOptions.maxAge =
      (req.session.maxAge || req.sessionOptions.maxAge);
    next();
  });

  router.get(`/${baseurl}/editor`, async function(req, res) {
    const newurl = await ogeRedirect(req.query);
    if (!newurl) {
      res.status(401).type('txt').send('Error 401 Invalid Request');
      return;
    }
    res.redirect(newurl);
  });

  router.use(`/${baseurl}/upload`, fileUpload({
    createParentPath: true
  }));

  router.post(`/${baseurl}/upload`, async function(req, res) {
    const uploadreq = JSON.parse(req?.body?.requestjson ?? '') ?? {};
    const handleRes = await filehandler(uploadreq, req?.files ?? {});
    res.json(handleRes);
  });

  router.use(`/${baseurl}/download`, nocache());

  router.get(`/${baseurl}/download`, async function(req, res) {
    const fullfilename = downloadRequest(req?.query ?? {});
    if (!fullfilename) {
      res.status(401).type('txt').send('Error 401 Invalid Request');
      return;
    }
    res.download(fullfilename);
  });

  router.use(`/${baseurl}/json`, express.json({limit: '10mb'}));

  router.post(`/${baseurl}/json`, async function(req, res) {
    if (!req?.body) {
      res.json({error: true, errMsg: 'Invalid request.'});
      return;
    }
    req.body.reqUrl = req.protocol + '://' + req.get('host') +
      req.originalUrl;
    const handlerres = await jsonhandler(req.body);
    // process login
    if (req?.body?.postcmd == 'login' &&
      handlerres?.success &&
      handlerres?.project &&
      handlerres?.loggedinuser &&
      handlerres?.loginaccesskey) {
      req.session.maxAge = (handlerres?.remember) ? 3162240000 : undefined;
      req.session.ogtfuser = handlerres.loggedinuser;
      req.session.ogtfaccesskey = handlerres.loginaccesskey;
      req.session.ogtfproject = handlerres.project;
    }
    if (req?.body?.postcmd == 'logout') {
      req.session.maxAge = undefined;
      req.session.ogtfuser = undefined;
      req.session.ogtfaccesskey = undefined;
      req.session.ogtfproject = undefined;
    }
    res.json(handlerres);
  });

  router.use(`/${baseurl}/index.html`, async function(req, res) {
    res.redirect(`/${baseurl}` + queryString(req.originalUrl));
  });

  router.use(`/${baseurl}/index.php`, async function(req, res) {
    res.redirect(`/${baseurl}` + queryString(req.originalUrl));
  });

  // check old access key
  router.use(`/${baseurl}`, async function(req, res, next) {
    if (!req?.session?.ogtfuser ||
      !req?.session?.ogtfaccesskey ||
      !req?.session?.ogtfproject) {
      next();
      return;
    }
    if (!verifyByAccesskey(
      req.session.ogtfproject,
      req.session.ogtfuser,
      req.session.ogtfaccesskey)) {
        req.session.ogtfproject = null;
        req.session.ogtfuser = null;
        req.session.ogtfaccesskey = null;
    }
    next();
  });

  router.get(`/${baseurl}`, async function(req, res) {
    let page = fs.readfile(
      path.join(__ogtfdirname, 'views', 'index.html')
    );
    const ogtfprojects = getProjects();
    page = fillvars(page, {
      isloggedin: !!req?.session?.ogtfuser,
      username: req?.session?.ogtfuser ?? req?.query?.user ?? '',
      accesskey: req?.session?.ogtfaccesskey ?? '',
      project: req?.session?.ogtfproject ?? req?.query?.project ?? '',
      projects: ogtfprojects,
      newpwdlink: req?.query?.newpwd ?? '',
      datadir: datadir
    });
    res.send(page);
  });

  return router;
}
