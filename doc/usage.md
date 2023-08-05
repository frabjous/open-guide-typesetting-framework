
# Open Guide Typesetting Framework Documentation

# Regular Usage (by typesetters and editors)

## Logging in

Typically a user is added to the system by another user.
The new user is sent a link allowing them to choose a password for the site.
If a new user has not visited the site yet, or has not chosen to be remembered between visits, they will need to login in (and possibly choose what project to log in to) when first visiting the page.
This is done though a typical username/login form.
There is now a button that allows a user to be remembered indefinitely by the site on a given device.
Forgotten passwords can also be reset for existing users.

## Top Navigation and User Maintenance

After logging in, there are four buttons near the top of the page which navigate between main parts of the framework.
The "current" and "archived" buttons will show the documents currently being worked on, and those that have been archived, respectively.
More on using those below.

The "my details" button allows each site user (typesetter or editor) to change their name or email, or request a change in their password.
These should be self-explanatory.
The "users" button can be used to add or remove users from the site.
Any one with access to the framework can invite other users or remove them, so be judicious about who is given access to the framework itself.

There is also a toggle in the top banner for switching between dark and light mode.
This is a purely aesthetic choice.

## Typesetting Workflow

The "current" page shows a listing of typesetting assignments or documents.

A new document listing can be created by clicking the appropriate “add new …” button for the type of document/assignment.
(For more on configuring document types, see the [configuration](https://github.com/frabjous/open-guide-typesetting-framework/blob/main/doc/configuration.md) documentation.)

The first step after clicking this button is to fill in the document id field in the top left of the new listing.
This can be anything unique to the document, a number, identifier, or even a short title so long as it consists only of letters and digits.
A document id must be assigned before any other work on the individual document/typesetting assignment can be saved.

Each listing consists of six collapsible/expandable parts: "**Metadata**", "**Uploads**", "**Bibliography**", "**Edit document**", "**Proofs**", "**Publication**".
A typical typesetting job would go through each of these parts in the sequence in which they are listed.
Each part is discussed in more detail below.

## Metadata

Opening the metadata block allows the various metadata fields used by the project for the type of document to be filled in.
These typically include things such as the title, author, abstract and more.

Exactly which fields are included and how they work depend on the project configuration; see the [configuration documentation](https://github.com/frabjous/open-guide-typesetting-framework/blob/main/doc/configuration.md). 
Typically these fields will include all those used within the pandoc template used by the configuration.

Fields left empty are ignored, and usually not all fields are required for every document.

Some fields (such as “author”) may allow multiple values.
Values may be added or removed using the plus and minus buttons below the fields.
Markdown syntax such as asterisks and underscores may be used in metadata fields.

Click the “save metadata” button at the bottom of the block to save the metadata.
This will create (or edit) files named `metadata.json` and `metadata.yaml` in the document’s directory.
The latter is of the right form to be used with pandoc’s `--metadata` option.

Saving the metadata will usually close the block but it can be reopened if need be.
Changes to the metadata can be made later, and once saved, should be applied to any new processing of the document if everything is configured in a typical way.


## Uploads

The uploads section has two file upload fields, one for the main document, and another one for auxiliary files (such as images for figures and the like).

The main uploaded file can be any file format pandoc can convert into its own markdown format, including `.docx` (MS Word/OnlyOffice), `.epub`, `.html` (web page), `.md` (markdown), `.odt` (LibreOffice/OpenOffice), `.rtf` (Rich Text/WordPad), and `.tex` (LaTeX) formats.
This covers most use cases, but other formats (such as the ancient pre-2003 MS Word `.doc` format) should be converted to one of these before upload.

Upon upload, the file is automatically converted into the markdown format used by the typesetting framework, and certain changes are applied as specified by the project configuration.
An attempt is also made to identify a free-form bibliography or reference section if one exists, and the extracted references made available to the next step.

Once uploaded, the main file can be re-downloaded if need be.
It can also be replaced, which will regenerate the main markdown document, but the previous versions will still be available and should appear in the ancillary file list with the prefix `previous-` along with a timestamp.

## Bibliography

There are two buttons near the top of the Bibliography section.
The typical workflow would involve using one or the other to get started.
If a free-form bibliography was found in the main document, the "extract from main file" button will be enabled.
More details on what it does can be found below.
The other is an upload button for a separate structured bibliographic file such as a BibTeX `.bib` file, though other formats supported by pandoc's conversion methods are also allowed (`.ris`, Endnote `.xml` files, and CSL `.yaml` or `.json` files).
These files will be converted into the format used by the framework and their entries will be added to the bibliographic listing below.

The "extract" button takes each entry from the converted free-form bibliography and uses that text to search an online database.
Currently only PhilPapers is supported but this may change.
The process is deliberately made slow, so as not to overwhelm the database server it interacts with, or make it suspect a denial of service attack.
PhilPapers uses IDs which are typically 5 or 6 uppercase letters, and are somewhat random, though the first three letters often match the author's name, and the following letters often have something to do with the title, but this is not consistent.
These five or six digits are often followed by a hyphen and a number.
These IDs can be found at the end of URL when visiting a certain work's page on the PhilPapers site.
The extraction process finds up to the top five matching PhilPapers IDs for each entry, and from them selects one (usually the first) and downloads a bibliographic record for it and uses it to populate a listing in the Bibliography block.
This process is currently very error prone, though I hope to make improvements.

After extraction, each entry should be checked carefully.
There is a drop-down list of the other PhilPapers ID found in the search in the upper left of each converted listing, as well as an input field for inputting a new one, and a button for redoing the import.
It is recommended that you have PhilPapers open in another window or tab when working on the bibliography.
If possible, it's best to make changes and additions on PhilPapers itself, and then redo the import, if information is missing or incorrect, as this benefits everyone in the profession.
However, entries can also be manually edited in the framework, and new ones inserted using the button at the bottom.

The format of the bibliographic entries is based on what is supported by [Citation Style Language](https://citationstyles.org/) (CSL). 
When the bibliography is saved, it is saved as a [CSL JSON](https://citeproc-js.readthedocs.io/en/latest/csl-json/markup.html) file (`bibliography.json`) which is supported by pandoc's implementation of the [citeproc](https://github.com/jgm/citeproc) citation processor library.
CSL supports many different types of bibliographic entries, and the different types of entries support many different kinds of fields.
When the "type" of entry is chosen, the most common fields for the most common types of entries used in philosophy (at the top of the list of choices) are added to the listing, though other types of entries can also be used with more manual effort, and additional fields can be added with the drop-down at the bottom of the entry if needed.
Empty fields will be ignored.
Not all CSL styles, however, support all types and all fields.
The most common fields that might need to be added manually include things like "`original-date`" or "`translator`".

Note that when entries are added by conversion or extraction, the fields are often sorted in an usual way, and may change after saving and reloading.
The order of the fields in each entry does not matter, nor does the order of the entries in the list.
However, it is important to pay attention to which is which.
There is usually no need to worry about about the case of titles, as citeproc will put titles into title case automatically for styles that use it.

The "id" field is what is used for citations in the markdown document. See the [citations section in Pandoc User's guide](https://pandoc.org/MANUAL.html#citations) for more information on its citation methods.

The optional (and usually unused) "abbreviation" field is not a CSL field but a place to indicate that the work in question is referred to in the text with an abbreviation rather than by means of the usual author-date citations. When the bibliography is applied, an attempt will be made to link any occurrences of the abbreviation in the document to the appropriate bibliography entry.

When all items in the list have been checked and any missing or erroneous information supplied or fixed, click the "save bibliography" button at the bottom of the block.
In addition to saving the bibliography on the server, this will also enable another button labelled "apply to document".
Applying the bibliography to the document involves a search of the converted main document for what appear to be author-date citations (or abbreviations) to the entries in the bibliography, and an automatic replacement of them with pandoc-style citations.
This process of automatic identification is far from perfect, however, and the citations should be checked individually when the document is edited.
It is usually best to "apply" the bibliography once before the main document is edited, which is why the "Bibliography" block appears before the "Edit document" block.

The Bibliography section can be reopened later and entries added or changed, and such changes should be applied to the document when next processed.
However, "re-applying" the bibliography to an already-edited main file may have unexpected results and is probably best avoided except in exceptional circumstances.

## Edit document

After the bibliography is saved and applied, the main document may be edited.
The "Edit document" section will have a button for opening the document editor used by the framework in another tab.

The section also has a listing of other plain-text format files in the document directory, and a link to edit them in the editor as well.
Most of these are files generated by other blocks, and editing them directly is usually a bad idea.
For them, there will be a visible warning about this.
However, it is possible to make use of plain-text based auxiliary files, such as svg images or stylesheets or LaTeX packages, and there are circumstances in which it makes sense to edit these directly.

The main document is a markdown file using pandoc's academic-oriented flavor of markdown.
Editors or typesetters using the editor should be intimately familiar with pandoc's version of markdown. Pandoc's website provides a fairly [comprehensive overview of its markdown](https://pandoc.org/MANUAL.html#pandocs-markdown) in its [User's Guide](https://pandoc.org/MANUAL.html).

Those new to the typesetting framework should also consult the [Basic Usage, Buttons and Keybindings](https://github.com/frabjous/open-guide-editor/blob/main/doc/basic-usage.md) section of the documentation for the [Open Guide Editor](https://github.com/frabjous/open-guide-editor/) for information on how to use the editor, including the live-updating preview mechanisms, citation auto-completion, and more.

## Proofs

When the main document has been edited and is in good enough shape for page proofs to be shared with the author or authors, the Proofs block should be opened and the "create new proof set" button clicked.

This button will make use of the current state of the main document to create all the different file formats specified in the "output" section of the project configuration for the document type.
(See the [configuration](https://github.com/frabjous/open-guide-typesetting-framework/blob/main/doc/configuration.md) documentation.)

The new proof set will be added to a list of all the proof sets that have been created for the document.
The proof sets will be listed by date created, and each will have downloads for the various file formats of the proofs that the project is configured to use.
There will also be two links, an "editor link" and an "author link".
Typically, the editor creating the proofs should first visit the proofs page with the editor link and add any "query"-type comments to the proofs.
They should then send the "author link" (right click the link to copy the link url) to the authors to invite them to view the proofs.
The authors can add comments and corrections to the proofs, and then submit them to the editor

## Archiving and Unarchiving Documents

Documents no longer being worked on can be archived by clicking the archive button on the top right of each listing.
They will still be available under the "archived" and can be "unarchived" in a similar manner.
Proofs can only be viewed for current documents, but even old proof sets will become available again if a project is unarchived.
The main purpose of archiving is simply to make it easier to find the currently relevant documents.

## Other Documentation

See also the other documentation files concerning [installation](https://github.com/frabjous/open-guide-typesetting-framework/blob/main/doc/installation.md) and [project configuration](https://github.com/frabjous/open-guide-typesetting-framework/blob/main/doc/configuration.md).

## License

Copyright 2023 © Kevin C. Klement.
This is free software, which can be redistributed and/or modified under the terms of the [GNU General Public License (GPL), version 3](https://www.gnu.org/licenses/gpl.html).
