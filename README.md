# Open Guide Typesetting Framework

## Introduction

Web-based academic typesetting framework for open access guides, open access journals, etc.

![ogtf](https://github.com/frabjous/open-guide-typesetting-framework/assets/305948/f66dcc1f-59f1-4ed9-a7e8-91e65bb5a157)

Although it can be configured to use other tools (additionally), it is primarily designed for typesetting academic works using [pandoc](https://pandoc.org/), and other open source tools like LaTeX.
The framework makes use of the flexibility of pandoc's academic-oriented flavor of markdown in its source documents.
This can in turn be used to create high-quality output production files in many different file formats.
Output formats can include typographically sophisticated pdfs, as well as more accessible formats such as html/web pages, and others such as ePub, for the distribution of academic publications in many forms and modalities.

The framework employs the [Open Guide Editor](https://github.com/frabjous/open-guide-editor) to be used in the typesetting process.
This is a web-based text editor that provides live-updating previews of html- or pdf-based output formats (or both) for the source file being edited.
(A video showing its capabilities can be found on its GitHub page through the link just given.)

The framework was created to be used by the [*Journal for the History of Analytical Philosophy*](https://jhaponline.org), as well as the forthcoming *Open Guide to Bertrand Russell’s Philosophy*, which I am editing.
It may be useful to others undertaking similar projects.
Thus, the framework is being made available open source and free for others to use, modify, and benefit from.

## Major Version Change

As of version 0.2.0, the Open Guide Typesetting Framework makes use of a router for [ExpressJS](https://expressjs.com) (or compatible) web servers, running on a javascript runtime such as [NodeJS](https://nodejs.org), for its server back-end. Previous versions used php instead. The php code is still available in this repository in the php branch. See [its corresponding documentation](https://github.com/frabjous/open-guide-typesetting-framework/blob/php/README.md) for more information.

## Features

The Open Guide Typesetting Framework provides the following features:

* Remote log in and user maintenance for editors and typesetters attached to a given project.

* A means for editing and maintaining the metadata for a given typesetting assignment, using customizable fields appropriate to the document types of the works published by the project.

* A mechanism for uploading submitted documents, automatically converting them to the markdown format used by the framework, and doing additional processing.

* Automatic extraction of bibliographic data from uploaded files, or from separate bibliography files. Text of extracted entries can be used to search online bibliographic databases for automatic download of structured bibliographic data. Bibliographies can be created in a format compatible with [Citation Style Language](https://citationstyles.org/), and the citeproc citation processor used by pandoc.

* Automatic conversion of citations in imported documents to pandoc-style citations consistent with the bibliographies created.

* The ability to copy-edit and typeset markdown files, with the ability to preview multiple output files created from the same source file.

* A mechanism for producing page proofs and sharing them with authors, including the ability to add queries, comments, and corrections on the proofs, whether in html or pdf format. These corrections and comments can be submitted back to the editors for purposes of creating finalized versions.

* Final production of a typeset document in any number of output formats, including optimizations and post-processing. Multiple editions or versions of the same work can be produced over time, using a version-numbering scheme.

## Additional Documentation

This repository contains additional documentation broken down into three additional files, on the following topics.

* [Installation instructions](./doc/installation.md) (for system administrators)

* [Project configuration and settings](./doc/configuration.md) (for project leaders and/or their technical support helpers)

* [Regular usage information](./doc/usage.md) (for editors/typesetters)

Hopefully answers to most questions can be found in the documents linked to above. If not, feel free to contact me.

## TODO / Roadmap

The framework is mostly feature-complete at this point, at least for my own use cases.
However, more beta testing and bug fixing may be needed.
Pull requests for expansions for other use cases are welcome.
There are also areas for improvement, especially with regard to bibliographic management and automated interaction with bibliographic databases.
Feature requests and bug reports can be posted here on GitHub.

## Trivia

This is a sequel to an earlier project entitled [journal-tools](https://bitbucket.org/frabjous/journal-tools), which was also used by the [*Journal for the History of Analytical Philosophy*](https://jhaponline.org) for many years.

## License

Copyright 2023–2025 © [Kevin C. Klement](https://people.umass.edu/klement). This is free software, which can be redistributed and/or modified under the terms of the [GNU General Public License (GPL), version 3](https://www.gnu.org/licenses/gpl.html).

The favicon is based on [JS_Icon_Edit_White.svg](https://commons.wikimedia.org/wiki/File:JS_Icon_Edit_White.svg) by [Jstalins](https://commons.wikimedia.org/wiki/User:Stalinsunnykvj), licensed under a [Creative Commons Attribution Share-Alike 4.0 International](https://creativecommons.org/licenses/by-sa/4.0/deed.en) license.
