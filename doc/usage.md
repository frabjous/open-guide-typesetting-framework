
# Open Guide Typesetting Framework Documentation

# Regular Usage (By Typesetters and Editors)

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

Exactly which fields are included and how they work depends on the project configuration; see the [configuration documentation](https://github.com/frabjous/open-guide-typesetting-framework/blob/main/doc/configuration.md). 
Typically these fields will include all those used within the pandoc template used by the configuration.

Fields left empty are ignored, and usually not all fields are required for every document.

Some fields (such as “author”) may allow multiple values. Values may be added or removed using the plus and minus buttons below the fields. Markdown syntax such as asterisks and underscores may be used in metadata fields.

Click the “save metadata” button at the bottom of the block to save the metadata. This will create (or edit) `metadata.json` and `metadata.yaml` files in the document’s directory. The latter is of the right form to be used with pandoc’s `--metadata` option.

Saving the metadata will usually close the block but it can be reopened if need be. Changes to the metadata can be made later, and once saved, should be applied to any new processing of the document if everything is properly and typically configured.


## Uploads

The uploads section has two file upload fields, one for the main document, and another one for ancillary files (such as images for figures and the like).

The main uploaded file can be any file format pandoc can convert into its own markdown format, including `.docx` (MS Word/OnlyOffice), `.epub`, `.html` (web page), `.md` (markdown), `.odt` (LibreOffice/OpenOffice), `.rtf` (Rich Text/WordPad), and `.tex` (LaTeX) formats.
This covers most use cases, but other formats (such as the ancient pre-2003 Word `.doc` format) should be converted to one of these before upload.

Upon upload, the file is automatically converted into the markdown format used by the typesetting framework, and certain changes are applied as specified by the project configuration.
An attempt is also made to identify any free-form bibliography or reference sections, and the extracted references made available to the next step.

Once uploaded, the main file can be re-downloaded if need be.
It can also be replaced, which will regenerate the main markdown document, but the previous versions will still be available and should appear in the ancillary file list with the prefix `previous-` along with a timestamp.

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
