
# Open Guide Typesetting Framework Documentation

# Regular Usage (by typesetters and editors)

## Logging in

Typically a user is added to the system by another user.
If email is enabled, the new user is sent a link allowing them to choose a password for the site.
If a new user has not visited the site yet, or has not chosen to be remembered between visits, they will need to log in (and possibly choose what project to log in to) when first visiting the page.
This is done through a typical username/login form.
There is an option that allows a user to be remembered by the site on a given device.
Forgotten passwords can also be reset for existing users.

## Top Navigation and User Maintenance

After logging in, there are six buttons near the top of the page which navigate between main parts of the framework.
The "current" and "archived" buttons will show lists of the documents currently being worked on, and those that have been archived, respectively.
Clicking the name of an assignment will open its assignment page, and there are buttons for (un)archiving each assignment.

The "assignment" and "bibliography" top buttons will return the user to the assignment workflow or bibliography page for whatever assignment was most recently open during this visit to the site.
If no assignment has been visited yet, these redirect back to the list of current assignments.

The "my details" button allows each site user to change their name or email, or request a change in their password.
Hopefully it is self-explanatory how to do these things.
The "users" button can be used to add users to or remove users from the site.
Any one with access to the framework can invite other users or remove them, so be judicious about who is given access to the framework itself.

There is also a toggle in the top banner for switching between dark and light mode.
This is a purely aesthetic choice.

## Typesetting Workflow

The "current" page shows a listing of typesetting assignments or documents, organized by type.
(For more on configuring document types, see the [configuration](./configuration.md) documentation.)
By default, these lists do not show assignments assigned to other users, but there is a check box to see all assignments.
A new document assignment can be created by clicking the appropriate “add new …” button for the type.
Creating a new document. or clicking the title/document id of a preexisting item, takes you to the main page for that assignment.

With a new assignment, the first step is to choose a document id to fill the field in the top left.
This can be anything unique to the document, a number, identifier, or even a short title, so long as it consists only of letters and digits, and is not already in use.
A document id must be assigned before any other work on the individual document/typesetting assignment can be saved.
The document id also typically forms the basis for the production filenames created at the end of the process, so should be chosen appropriately.
This also assigns the document to the active user, but it can be assigned to someone else using the widget that will appear in the upper right.

The main page for an assignment is organized to match the typical workflow.
There are six collapsible/expandable parts: "**Metadata**", "**Uploads**", "**Bibliography**", "**Edit document**", "**Proofs**", and "**Publication**".
Most typesetting jobs would go through these in the sequence in which they are listed.
Each part is discussed in more detail below.

## Metadata

Opening the metadata block allows the various metadata fields used by the project for the type of document to be filled in.
These typically include things such as the title, author, abstract, and more.

Exactly which fields are included and how they work depend on the project configuration; see the [configuration documentation](./configuration.md).
Typically these fields will include all those used within the pandoc template used by the configuration.

Fields left empty are ignored, and usually not all fields are required for every document.

Some fields (such as “author”) may allow multiple values.
Values may be added or removed using the plus and minus buttons below the fields.
Markdown syntax such as asterisks and underscores may be used in metadata fields.

Click the “save metadata” button at the bottom of the block to save the metadata.
This will create (or modify) files named `metadata.json` and `metadata.yaml` in the document’s directory.
The latter is of the right form to be used with pandoc’s `--metadata-file` option.

Saving the metadata will usually close the block but it can be reopened if need be.
Changes to the metadata can be made later, and once saved, the changes should apply to any new processing of the document if everything is configured in a typical way.

## Uploads

The uploads section has two file upload fields, one for the main document, and another one for auxiliary files (such as images for figures and the like).

The main uploaded file can be any file format pandoc can convert to its own markdown format, including `.docx` (MS Word/OnlyOffice), `.epub`, `.html` (web page), `.md` (markdown), `.odt` (LibreOffice/OpenOffice), `.rtf` (Rich Text/WordPad), and `.tex` (LaTeX) formats.
This covers most use cases, but other formats (such as the pre-2003 MS Word `.doc` format) should be converted to one of these before upload.

Upon upload, the file is automatically converted into the markdown format used by the typesetting framework, and certain changes are applied as specified by the project configuration.
An attempt is also made to identify a free-form bibliography or reference section if one exists, and the extracted references made available to the next step.

Once uploaded, the main file can be re-downloaded if need be.
It can also be replaced, which will regenerate the main markdown document, but the previous versions will still be available and should appear in the auxiliary file list with the prefix `previous-` along with a timestamp.

## Bibliography

This block contains two links, each of which takes the user to a separate page where the work on the bibliography is done.
The only difference is that one opens it in a new tab, and the other in the current tab.

On the bibliography page, there are two buttons near the top.
The typical workflow would involve using one or the other to get started.
If a free-form bibliography was found in the main document, the "extract from main file" can be used.
More details on what it does can be found below.
The other is an upload button for a separate structured bibliographic file such as a BibTeX `.bib` file, though other formats supported by pandoc's conversion methods are also allowed (`.ris`, Endnote `.xml` files, and CSL `.yaml` or `.json` files).
These files will be converted into the format used by the framework and their entries will be added to the bibliographic listing below.

The "extract" button takes each entry from the free-form bibliography found in the main upload, and parses it using the [anystyle](https://anystyle.io) ruby gem if installed on the server.
This parser is far from perfect, and each parsed entry should be carefully checked for correctness and fixed if need be.

It was intended that entries could also be imported from PhilPapers.
This method, however, is very unreliable because of PhilPapers' draconian security measures.
PhilPapers uses IDs which are typically 5 or 6 uppercase letters, and are somewhat random, though the first three letters often match the author's name, and the following letters often have something to do with the title, but this is not consistent.
These five or six digits are often followed by a hyphen and a number.
These IDs can be found at the end of the URL when visiting a certain work's page on the PhilPapers site.
When things do work, you can enter the PhilPapers ID in the first field and then click "(re)import" to fill in the bibliographic data into the entry.
Again, unfortunately, this method is unreliable.
<!--It is recommended that you have PhilPapers open in another window or tab when working on the bibliography.
If PhilPapers is used, it's best to make changes and additions on PhilPapers itself before import if information is missing or incorrect, as this benefits everyone in the profession.
Currently only PhilPapers is supported for manual import but this may change.
-->

The format of the bibliographic entries is based on what is supported by [Citation Style Language](https://citationstyles.org/) (CSL).
When the bibliography is saved, it is saved as a [CSL JSON](https://citeproc-js.readthedocs.io/en/latest/csl-json/markup.html) file (`bibliography.json`) which is supported by pandoc's implementation of the [citeproc](https://github.com/jgm/citeproc) citation processor library.
CSL supports many different types of bibliographic entries, and the different types of entries support many different kinds of fields.
When the "type" of entry is chosen, the most common fields for the most common types of entries used in philosophy (at the top of the list of choices) are added to the listing, though other types of entries can also be used with more manual effort, and additional fields can be added with the drop-down at the bottom of the entry if needed.
Empty fields will be ignored.
Not all CSL styles, however, support all types and all fields.
The most common fields that might need to be added manually include things like "`original-date`" or "`translator`".

Note that when entries are added by conversion or extraction, the fields are often sorted in an usual way, and may change after saving and reloading.
The order of the fields in each entry does not matter, nor does the order of the entries in the list.
However, it is important to pay attention to which field is which.
There is usually no need to worry about the case of titles, as citeproc will put titles into title case automatically for styles that use it.

The "id" field is what is used for citations in the markdown document.
See the [citations section in the pandoc user's guide](https://pandoc.org/MANUAL.html#citations) for more information on its citation methods.

The optional (and usually unused) "abbreviation" field is not a CSL field but a place to indicate that the work in question is referred to in the text with an abbreviation rather than by means of the usual author-date citations.
When the bibliography is applied, an attempt will be made to link any occurrences of the abbreviation in the document to the corresponding bibliography entry.

When all items in the list have been checked and any missing or erroneous information supplied or fixed, click the "save bibliography" button at the bottom of the page.
Then the bibliography can be "applied" by clicking the "apply to document" button.
Applying the bibliography to the document involves a search of the converted main document for what appear to be author-date citations (or abbreviations) to the entries in the bibliography, and an automatic replacement of them with pandoc-style citations.
This process of automatic identification is far from perfect, however, and the citations should be checked individually when the document is edited.
It is usually best to "apply" the bibliography only once, when the bibliography is in good shape, but before the main document is edited.
This is why the "Bibliography" block appears before the "Edit document" block.

The bibliography page can be reopened later and entries added or changed, then saved.
Such changes should be applied to the document when next processed.
However, "re-applying" the bibliography to an already-edited main file may have unexpected results, and is probably best avoided except in exceptional circumstances.

## Edit document

After the bibliography is saved and applied, the main document may be edited.
The "Edit document" section will have a button for opening the document editor used by the framework in another tab.

The section also has a listing of other plain-text format files in the document directory, and a link to edit them in the editor as well.
Most of these are files generated by other blocks, and editing them directly is usually a bad idea.
For them, there will be a visible warning about this.
However, it is possible to make use of plain-text based auxiliary files, such as svg images or stylesheets or LaTeX packages, and there are circumstances in which it makes sense to edit these directly.

The main document is a markdown file using pandoc's academic-oriented flavor of markdown.
Editors or typesetters editing the document should be intimately familiar with pandoc's version of markdown. Pandoc's website provides a fairly [comprehensive overview of its markdown](https://pandoc.org/MANUAL.html#pandocs-markdown) in its [user's guide](https://pandoc.org/MANUAL.html).

Those new to the typesetting framework should also consult the [Basic Usage, Buttons and Keybindings](https://github.com/frabjous/open-guide-editor/blob/main/doc/basic-usage.md) section of the documentation for the [Open Guide Editor](https://github.com/frabjous/open-guide-editor/) for information on how to use the editor, including the live-updating preview mechanisms, citation auto-completion, and more.

## Proofs

When the main document has been edited and is in good enough shape for page proofs to be shared with the author or authors, the Proofs block should be opened and the "create new proof set" button clicked.

This button will make use of the current state of the main document to create all the different file formats specified in the "output" section of the project configuration for the document type.
(See the [configuration](./configuration.md) documentation.)

The new proof set will be added to a list of all the proof sets that have been created for the document.
These are listed by date created, and each has downloads for the various file formats of the proofs that the project is configured to use.
There will also be two links, an "editor link" and an "author link".
Typically, the editor creating the proofs should first visit the proofs page with the editor link, and add any "query"-type comments to the proofs.
They should then send the "author link" (right click the link to copy the link url) to the authors to invite them to view the proofs.
The authors can add comments and corrections to the proofs, and then submit them when completed.
If an email script is enabled, this will also trigger an email to be sent to the editor who created the proofs.
The editor should then revisit the proofs page with the editor link and view the comments and corrections.
They should then reopen the "Edit document" section and make any necessary changes.
Depending on circumstances, another proof set may need to be generated afterwards, or it may be time to move on to the next step.

The proofs page has its own instructions about how to add comments or corrections.
These instructions are visible at first by default when using the author link.
Editors can also view the instructions by clicking the button for viewing them on the panel.
It is typically one of the three viewing options on the panel, in addition to viewing the html or pdf versions of the proofs (unless the project is configured not to use one of these).

## Publication

The final block is labelled "Publication".
This block is to be used when it is time to create a finalized version for publication.
There are two buttons, one for creating a new minor version (which raises the version number by 0.1) and one for creating a new major version (which raises the version to the next whole number, e.g., 1.0).
Some typesetting projects may only target one published version; others may post new versions periodically, and it is a matter of individual policy what kind of revision should correspond to a minor or major version change.

What files are created during this process depends once again on the [configuration](./configuration.md).
Typically they will be the same file-types as were produced during the proofs stage, possibly with some post-processing done such as file-size or linearization optimizations.
The framework can be configured to create a `.zip` file containing all the production files together, which will be listed for download first if created.
If any of the produced files are plain-text `.txt` files, a link will be added to view/extract them directly in the publication block.
There they can be copied to the clipboard, which can be useful for things like extracting an abstract or references list.

## Archiving and Unarchiving Documents

Documents no longer being worked on can be archived by clicking the archive button on its listing in the current assignments page.
They will still be available under the "archived" section and can be "unarchived" in a similar manner.
The main purpose of archiving is to make it easier to find the currently relevant documents by shortening the list shown.

## Other Documentation

See also the other documentation files concerning [installation](./installation.md) and [project configuration](./configuration.md).

## License

Copyright 2023–2025 © Kevin C. Klement.
This is free software, which can be redistributed and/or modified under the terms of the [GNU General Public License (GPL), version 3](https://www.gnu.org/licenses/gpl.html).
