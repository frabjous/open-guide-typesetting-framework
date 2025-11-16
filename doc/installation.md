
# Open Guide Typesetting Framework Documentation

# Installation Instructions (for system administrators)

## Note on Version Change and Underlying Platform

As of version 0.2.0, the Open Guide Typesetting Framework makes use of a router for [ExpressJS](https://expressjs.com) (or compatible) web servers, running on a javascript runtime such as [NodeJS](https://nodejs.org), for its server back-end. Previous versions used php instead. The php code is still available in this repository in the php branch. See [its corresponding documentation](https://github.com/frabjous/open-guide-typesetting-framework/blob/php/doc/installation.md) for information on installing the php version.

The instructions below cover the newer, server-side javascript version.

## Requirements

1. You will need a server on which to install the framework (or a local machine running server software). I have only tested it on Linux, but it may be compatible with other operating systems.

2. [NodeJS](https://nodejs.org) or compatible javascript runtime, as well as a package manager for it such as [npm](https://npmjs.com).

3. [Git](https://git-scm.com) to clone this repository (or another way of cloning it). There's a good chance you have this installed already.

4. You will need to install the programs used in the typesetting, including [pandoc](https://pandoc.org), a TeX distribution such as [texlive](https://tug.org/texlive/), and others. See the [instructions for setting up the Open Guide Editor](https://github.com/frabjous/open-guide-editor/blob/main/doc/installation.md) for the full list of programs it uses by default. The sample settings also make use of [ghostscript](https://www.ghostscript.com/), which often comes bundled with your TeX distribution for compressing pdfs, [jq](https://jqlang.github.io/jq/) for extracting abstracts from json files, [anystyle](https://anystyle.io) for parsing plain text bibliographies, and a zip implementation like [info-zip](https://infozip.sourceforge.net/Zip.html). However, the framework can be configured to use almost any software in place of these.

   All these programs can likely be installed with your linux distribution's package manager. Anystyle is a possible exception, but it can be installed as a ruby gem if the `rubygems` package is installed. (See [its page on the rubygems catalog](https://rubygems.org/gems/anystyle).

`gem install anystyle anystyle-cli erb`

The framework automatically adds gem binary directories matching `$HOME/.local/share/gem/ruby/*/bin` to its `$PATH`.)

## Installation Steps

Follow these instructions to install the framework. You do not need root access for any of these steps.

1. Clone this repository. First navigate to the directory where you want it located, then run:

   ```sh
   git clone --depth 1 \
     https://github.com/frabjous/open-guide-typesetting-framework.git
   ```

2. You will also need to clone the `open-guide-editor` repository. To install it as a subdirectory of the typesetting framework directory:

  ```
  cd open-guide-typesetting-framework
  git clone --depth 1 https://github.com/frabjous/open-guide-editor.git
  ```

3. Use npm or another package manager to install the node module dependencies needed by both the typesetting framework and editor. Also use the editor’s npm "build" script to bundle the [codemirror](https://codemirror.net) packages it needs.

  ```sh
  npm install
  cd open-guide-editor
  npm install
  npm run build
  cd ..
  ```

  See the [installation](https://github.com/frabjous/open-guide-editor/blob/main/doc/installation.md) and [settings configuration](https://github.com/frabjous/open-guide-editor/blob/main/doc/settings.md) documentation for the editor for more information about the editor.

4. Create at least one project by running the `newproject.mjs` script in the `cli/` subdirectory of the main repository to create your first typesetting project. (This script will also complete steps 2–3 above if not done already.)

  ```sh
  node cli/newproject.mjs
  ```

  This will prompt you for a short name as well as a full title for your project, as well as a site maintainer contact name and email. It will create an initial user of the site with the same name and email, and provide a password.

  This script can be run multiple times if you wish to use the same site/server for multiple projects.

  Project typesetting data is stored in `$HOME/data/ogtf` (in subdirectories using the short name of each project) by default. However, this can be changed by setting the environmental variable `OGTFDATALOCATION` to another path, e.g., `OGTFDATALOCATION=$HOME/.local/share/ogtfdata node cli/newproject.mjs`. The router, discussed below, also respects this variable.

  Projects can also be created manually by copying an existing project in the data directory and renaming it and changing its settings files.

5. Run the server back-end either by running the test server (step 6), or by adding the router to an existing ExpressJS server project (step 7).

6. You can run the test server with `npm run test` or `node test-server.mjs`. This will make the framework available at the url:

  ```
  https://localhost:14747/typesetting
  ```

  The port number can be changed by setting the OGETESTSERVERPORT environmental variable to another number.

7. To use the framework within another ExpressJS server app, you can import and mount the router.  This is done as follows:

  ```javascript
  import express from 'express';
  import ogtfRouter from './ogtfRouter.mjs';

  const app = express();

  // pre-ogtf routes here...

  // mount router
  app.use(await ogtfRouter());

  // post-ogtf routes here...

  ```

  See the ExpressJS documentation on [routing](https://expressjs.com/en/guide/routing.html) and [middleware](https://expressjs.com/en/guide/using-middleware.html) if necessary. The code in `test-server.mjs` gives a full example of how to use the router in an ExpressJS app, albeit one that does nothing else.

  The ogtfRouter function optionally takes an argument, with two possible options:

  ```javascript
  app.use(await ogtfRouter({
    baseurl: 'typesetting',
    ogepath: '/path/to/open-guide-editor'
  }))
  ```

  The `baseurl` option determines the url path on the server used by the framework. For example, if set to `"typesetting"` as above, the framework will be available at:

  ```
  https://yourdomain.net/typesetting
  ```

  If unset, the framework will use `ogtf` as its main route:

  ```
  https://yourdomain.net/ogtf
  ```

  All other paths used by the framework will begin with the base url, and it is a good idea to set it to something not used elsewhere on your server. Paths beginning  with `/oge` will also be used by the Open Guide editor's router, which the typesetting framework also mounts.

  The `ogepath` option tells the router where to look for the `open-guide-editor` repository if it was not cloned into a subdirectory of the `open-guide-typesetting-framework` repository. (If it was cloned there, this option need not be set.)

8. You should now be able to login to the framework at `http://localhost:14747/typesetting` or `https://yourdomain.net/ogtf` depending on exactly what was done in the previous two steps.

  If there are multiple projects, you will first need to choose which one to log in to. You can skip right to the login form for a project by adding `?project=shortname` (replacing "`shortname`" with the short name of the project configured in step 4 above) to the end of the url.

9. Follow the [usage instructions](./usage.md) for adding additional editors. The site maintainer can also be removed as an editor if desired.

10. You will likely want to finish configuring your project by editing the `project-settings.json` file in the project’s subdirectory of the data directory. Instructions for configuring projects can be found in the [configuration](./configuration.md) documentation.

## Usage on a Different Server

If you would like to integrate the open guide typesetting framework on another web server, one not using ExpressJS, you have two options.

You can use the php branch instead, which can be used with any web server supporting php.

Alternatively, you can run the framework’s test server, and configure your web server to re-route requests to it via a reverse proxy or similar. For example, with [nginx](https://nginx.org/), you might put something like this in your `nginx.conf` file:


```
server {
  listen 80;
  listen [::]:80;
  server_name yourdomain.net;
  location /oge* {
    proxy_pass http://localhost:14747;
  }
  location /ogtf* {
    proxy_pass http://localhost:14747;
  }
}
```

Experienced sysadmins can no doubt think of other solutions tailored to the individual use case.

## Email Configuration

It is very useful if the framework is able to send email, for example, to inform an editor that proof corrections have been submitted, or allow an editor to reset their password. It is outside the scope of the project to implement a method that will work universally. Unless a custom email function is added, the server will simply save emails to an `emaillog/` subdirectory of the project’s data directory.

However, if a javascript module named `customemail.mjs` exists in the project data directory, the framework will import the default (async) function defined therein, and call it when it would make sense to send an email. The script should take this basic form:

```javascript

// Filename: $HOME/data/ogtf/myproject/customemail.mjs

export default async function customemail(to, subject, html, from) {
  // ... implement your own script here
}

```

The default export should be an asynchronous function taking four arguments, (a) the recipient (to: header), (b) the subject of the email, (c) the html body of the email, and (d) the sender (from: header).

The script might, for example, be a wrapper around [nodemailer](https://www.nodemailer.com/):


```javascript
// Filename: $HOME/data/ogtf/myproject/customemail.mjs

import nodemailer from 'nodemailer';

const transporter = nodemailer.createTransport({
  host: "smtp.myprovider.com",
  port: 587,
  secure: false,
  auth: {
    user: 'myname@myprovider.com',
    pass: 'mysecretpassword'
  }
});

export default async function customemail(to, subject, html, from) {
  return await transporter.sendMail({
    to, from, subject, html
  });
}

```

Such a script is not necessary to run the framework, but without it no email will be sent.

## PhilPapers configuration

If the site administrator has a PhilPapers API id and key, a file can be placed in the main data directory for the OGE installation named `ppapi.json` (typically `~/data/ogtf/ppapi.json`).
Its contents should take the form:

```json
{
  "apiid": "99999",
  "apikey": "XXXXXXXXXXXXX"
}
```

Of course, replace "99999" and "XXXXXXXXXXXXX" with the actual PhilPapers ID and key.
If this file is in place when the server is started, the id and key will be used when the server attempts to retrieve bibliographical information.
Unfortunately, even with these supplied, this process is very unreliable due to PhilPapers’ draconian security measures.

## Other Documentation

See also the other documentation files concerning [project configuration](./configuration.md) and [regular usage (by editors and typesetters)](./usage.md).

## License

Copyright 2023–2025 © Kevin C. Klement.
This is free software, which can be redistributed and/or modified under the terms of the [GNU General Public License (GPL), version 3](https://www.gnu.org/licenses/gpl.html).
