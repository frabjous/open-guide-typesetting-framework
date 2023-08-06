# Open Guide Typesetting Framework

## Introduction

Web-based academic typesetting framework for open access guides, open access journals, etc.

Although it can be configured to use other tools additionally, it is primarily designed for typesetting academic works using [pandoc](https://pandoc.org/), and other open source tools like LaTeX.
The framework makes use of the flexibility of pandoc's academic-oriented flavor of markdown in its source documents.
These can in turn be used to create high-quality output production files in many different file formats.
Output formats can include typographically sophisticated pdfs, as well as more accessible formats such as html/web pages, and others such as ePub, for the distribution of academic publications in many forms and modalities.

The framework includes the [Open Guide Editor](https://github.com/frabjous/open-guide-editor) as a submodule, to be used in the typesetting process. This is a web-based text editor that provides live-updating previews of html- or pdf-based output formats (or both) for the source file being edited. (A video showing its capabilities can be found on its github page through the link just given.)

The framework was created to be used by the [*Journal for the History of Analytical Philosophy*](https://jhaponline.org), as well as the forthcoming *Open Guide to Bertrand Russell’s Philosophy*, which I am editing, but may be useful to others undertaking similar projects. Thus, the framework is being made available open source and free for others to use, modify, and benefit from.

## Features

The Open Guide Typesetting Framework provides the following features:

* Remote log in and user maintenance for editors and typesetters attached to a given project.

* A means for editing and maintaining the metadata for a given typesetting assignment, using customizable fields appropriate to the document types of the works published by the project.

* A mechanism for uploading submitted documents, automatically converting them to the markdown format used by the framework, and doing additional processing.

* Automatically extraction of bibliographic data from uploaded files, or from separate bibliography files. Text of extracted entries can be used to search online bibliographic databases for automatic download of structured bibliographic data. Bibliographies can created in a format compatible with [Citation Style Language](https://citationstyles.org/), and the citeproc citation processor used by pandoc.

* Automatic conversion of citations in imported documents to pandoc-style style citations consistent with the bibliographies created.

* The ability to copy-edit and typeset markdown files, with the ability to preview multiple output files created from the same source file.

* A mechanism for producing page proofs and sharing them with authors, including the ability to add queries, comments, and corrections on the proofs, whether in html or pdf format. These corrections and comments can be submitted back to the editors for purposes of creating finalized versions.

* Final production of a typeset document in any number of output formats, including optimizations and post-processing. Multiple editions or versions of the same work can be produced over time, using a version-numbering scheme.

## Additional Documentation

This repository contains additional documentation broken down into three additional files, on the following topics.

* [Installation instructions](https://github.com/frabjous/open-guide-typesetting-framework/blob/main/doc/installation.md) (for system administrators)

* [Project configuration and settings](https://github.com/frabjous/open-guide-typesetting-framework/blob/main/doc/configuration.md) (for project leaders and/or their technical support helpers)

* [Regular usage information](https://github.com/frabjous/open-guide-typesetting-framework/blob/main/doc/usage.md) (for editors/typesetters)

Hopefully answers to most questions can be found in the documents above. If not, feel free to contact me.

## TODO / Roadmap

The framework is mostly feature-complete at this point, at least for my own use cases.
However, more beta testing and bug fixing may be needed at this stage.
Pull requests for expansions for other use cases are welcome.
There are also areas for improvement, especially with regard to bibliographic management and automated interaction with bibliographic databases.
Improvements will hopefully be implemented at some point.
Feature requests and bug reports can be posted here on GitHub.

## Trivia

This is a sequel to an earlier project entitled [journal-tools](https://bitbucket.org/frabjous/journal-tools), which was also used by the [*Journal for the History of Analytical Philosophy*](https://jhaponline.org) for many years.

<!--
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

Copyright 2023 © [Kevin C. Klement](https://people.umass.edu/klement). This is free software, which can be redistributed and/or modified under the terms of the [GNU General Public License (GPL), version 3](https://www.gnu.org/licenses/gpl.html).

The favicon is based on [JS_Icon_Edit_White.svg](https://commons.wikimedia.org/wiki/File:JS_Icon_Edit_White.svg) by [Jstalins](https://commons.wikimedia.org/wiki/User:Stalinsunnykvj), licensed under a [Creative Commons Attribution Share-Alike 4.0 International](https://creativecommons.org/licenses/by-sa/4.0/deed.en) license.
