# open-guide-typesetting-framework

## Introduction

Academic typesetting web-based framework for open access guides, open access journals, etc.

Although it can be configured to use other tools, it is primarily designed to typeset academic work using [pandoc](https://pandoc.org/) and other open source tools like LaTeX.

This is a sequel to [journal-tools](https://bitbucket.org/frabjous/journal-tools) used by the [*Journal for the History of Analytical Philosophy*](https://jhaponline.org) and will also be used by the forthcoming *Open Guide to Bertrand Russell’s Philosophy*, which I am editing.

The project is in early development. More documentation forthcoming.

<!--
# Journal Tools

## The JHAP Typesetting framework

This code provides a web-based framework for typesetting academic journal articles and book reviews using LaTeX. Editors can log in, create projects, convert other file formats to LaTeX, edit LaTeX files, and natbib-based bibliographies, create page proofs and leave comments on them, share proofs with authors, allow authors to leave comments and answer queries on these proofs, and create web-optimized PDFs for online publication.

The framework was designed for [*The Journal for the History of Analytical Philosophy*](https://jhaponline.org) but may be adapted for use by other journals, or others in general.

## Requirements and Installation

You will need:

* A PHP-enabled web-server; I recommend running it on a Linux/Unix based system (but haven't tested other operating systems)
* Other helper libraries created by me: [KCKlib](https://bitbucket.org/frabjous/kcklib); [KCK Icons](https://bitbucket.org/frabjous/icons) and [K(ev)E(dit)](https://bitbucket.org/frabjous/ke); these are expected be installed in immediate subfolders of the web server root directory `kcklib/`, `icons/` and `ke/`; as well as CodeMirror in a `ke/codemirror/` subfolder. These can be optionally installed through the `initial_setup.php` script.
* A number of other programs used either by K(ev)E(dit) or Journal Tools for performing conversions, typesetting, optimization, etc. These include [TeXlive](https://www.tug.org/texlive/) (or another TeX distribution, for processing LaTeX files), [rubber](https://launchpad.net/rubber/) (for processing LaTeX errors), [LibreOffice](https://www.libreoffice.org/) (for convering Word Processor files to markup files),  [pandoc](https://pandoc.org/) (for converting markup formats to LaTeX), [ghostscript](https://www.ghostscript.com/) (for output PDF files), [qpdf](https://qpdf.sourceforge.io/) (for optimizing pdfs), the mupdf project’s [mutool](https://mupdf.com/index.html) (for converting PDF pages to images that can be displayed in a browser), [flite](http://www.festvox.org/flite/) and [lame](http://lame.sourceforge.net/) (for K(ev)E(dit)'s text-to-speech features). The executables for these programs should be found in the `$PATH` for the webserver user.

For installation and setup:

* Clone this repository into your webserver document root; rename the output folder if you wish.
* From within the subfolder created, from the command line execute the `initial_setup.php` script with php.
* The two steps above, for example might be (from a terminal):

```bash
cd /home/web/public_html # or whatever the webserver root folder is
git clone https://bitbucket.org/frabjous/journal-tools.git
mv journal-tools myjournal
cd myjournal
php initial_setup.php
```
* The setup script will ask for the name of the journal, contact information, etc., and in the process will create the first user of the framework and provide a password.
* The script may also be used to install KCKlib, KCK Icons and K(ev)E(dit)/Codemirror if not installed already. This functionality requires that [git](https://git-scm.com/) and [npm](https://www.npmjs.com/) are installed on the server.
* Login to the framework (e.g. `https://myserver.com/myjournal/`) through your browser to ensure that the created user and password work as expected. That user can create others users (who will be sent invitations via email).

## Usage

Here is a rough summary of usage. Fuller instructions for how the system is used for JHAP can be found with the [JHAP Typesetting Guide](https://bitbucket.org/frabjous/jhap-cls/src/master/jhap_typesetting_guide.md):

1. After logging in, the user will see a list of projects. There is a field to create a new one.
2. Each project is given a unique document number; this is meant to match the number given in an [OJS](https://openjournalsystems.com/) or similar system (and future versions of this project may allow for automatic integration with OJS).
3. This will bring up a page to enter metadata, and choose between articles and review. Fill in a click save.
4. Back at the project listing, there is now a box for the new project, and a link to upload a file for conversion; all file formats which can be imported into LibreOffice and/or Pandoc are allowed, including Word, etc.
5. The next step is to edit the bibliography by clicking the "edit bibliography" link; when the bibliography is finalized, you can move to the next step.
6. Click the "create LaTeX file" link to convert the uploaded file to LaTeX format; you will be automatically redirected to a page where the LaTeX file can be edited. This uses K(ev)E(dit); see [its documentation](https://bitbucket.org/frabjous/ke/) for more information. Use the "play button" icon to create a PDF at least once.
7. When the LaTeX file is in good shape, return to the main page for the framework (by clicking back in your browser, or renavigating to its page). Create a set of proofs with "create new proof set".
8. This will bring up the editor's version of viewing the proofs; queries can be added by drawing boxes on the page, and the toolbar at the top can be used for navigating between pages.
9. Click back to return the menu, and there are two links for the proof set, an editor link and author link. The author link should be provided to the author, who can use it to add comments, corrections and respond to queries. This link provides further instructions when first visited.
10. When author corrections are submitted, the journal contact is emailed. Changes may be made to the LaTeX file with "edit LaTeX file", and additional proof sets created as needed.
11. When all corrections are made, the "create optimized PDF" link will create a smaller, and web-optimized PDF, which should be the published (e.g. as "galleys" in OJS).

## Customization

A different document class other than `jhap.cls` can be chosen at setup when running the `initial_setup.php` script, or changed by directly editing the file `jtsettings.json` created by that script.

A different bibliography style can be used by creating a javascript file called `custombibstyle.js` which defines a function:

```javascript
function bblEntryFor(bibkey, bibdata, elem) {
    // ...
}
```

This function takes three arguments: a citation key, used, e.g., in LaTeX `\cite` commands, bibliographical data, as an object, and the form element on the page where such data is entered. This function should output a string used as the LaTeX `thebibliography` listing item for the entry. For more details, it would be best to compare to the `bblEntryFor` function defined in the file `editbib.php`, which is the version of the function used by JHAP. The output should be natbib-compatible.

Note that this script is used *instead* of BibTeX, which is not used at all, though BibTeX files may be imported.

Finally, by default PHP’s `mail(...)` function is used for sending email. However, you
can substitute a custom defined function by creating a file named `customemail.php`
and place it in the folder chosen during setup for storing data and files for
the framework. This PHP file should define a function `jt_custom_email` that
takes three arguments, `$to` (which will be the email address of the recipient),
`$subject` (which will be the subject of the email), and `$message`, which is
expected to be an HTML string representing the body of the email. The return value
of the function should be `true` on success and `false` on failure. For example:

```php
<?php

function jt_custom_email($to, $subject, $message) {
    $success = ...
    return $success;
}
```
The file may of course load other packages, such as [PHPMailer](https://github.com/PHPMailer/PHPMailer), etc., in order to define the function. If this file is not created or this function is not defined, the framework will use PHP’s default `mail(...)` function.
-->
## License

© 2018–2023 [Kevin C. Klement](https://people.umass.edu/klement). This is free software, which can be redistributed and/or modified under the terms of the [GNU General Public License (GPL), version 3](https://www.gnu.org/licenses/gpl.html).
