
# Open Guide Typesetting Framework Documentation

# Project Configuration and Settings

Follow these instructions after completing the [installation instructions](https://github.com/frabjous/open-guide-typesetting-framework/blob/main/doc/installation.md) for the framework.

## Projects, Project Directories and Settings File

Each site installation of the typesetting framework must host one or more "projects". A "project" could be a journal, or an anthology, an online guide, or similar. Each login account and typesetting assignment will be attached to project. A first or additional project can be created by running the `newproject.php` script in the `php` subdirectory of the cloned git repo from the command line.

```sh
php php/newproject.php
```

Each project has its own subdirectory of the site's data directory (using its "short" name).
Projects can also be created by simply copying an existing project subdirectory to a directory with a new short name, and editing its settings.

Inside the project's subdirectory is a file, "`project-settings.json`", which is where the settings for each project are configured, and customizations should be made.
If the project was created with the `newproject.php` script, this file will begin as a near-clone of the `sample-project-settings-journal.json` file found in the main directory of the git repo. 
These settings are appropriate for a journal.
For any other kind of project, and most likely even for a journal, the `project-settings.json` file should be edited and customized for the actual project.
Most of the below describes what it is in this file and how it can be tweaked.

Consult the file itself (or the sample file) to see its full structure, as this documentation will only show some parts at a time.

## Title, Contact Name and Contact Email

For example:

```json
{
    "title": "Journal of Something",
    "contactname": "Sydney B. Someone",
    "contactemail": "sydney@someone.net"
}

```

These settings specify the full title of the project, the main contact person for the project's name and email. The full project name appears at the top of the typesetting framework's pages and in various other places. The contact name and email are used in headers of emails sent (unless a custom script is used) and in certain error messages in the typesetting framework itsef.

These options are set by the `newproject.php` script if used, but may be changed at any time.

## Import Replacements

For example:

```json
{
    "importreplacements": {
        " -- ": "---",
        "\\. \\. \\.": "…",
        "\\[\\.\\.\\.\\]": "…",
        "\\[…\\]": "…",
        "\\b([A-Z])\\. ?([A-Z])\\. ?([A-Z])\\.": "\\1. \\2. \\3.",
        "\\b([A-Z])\\. ?([A-Z])\\.": "\\1. \\2."
    }
}
```

This setting consists of key--value pairs, and are used during the document conversion process. Each key is a regular expression search term, and each value is a regular expression replacement term. Each pair is applied as the first two arguments to PHP's `mb_ereg_replace(…)` (see [here](https://www.php.net/manual/en/function.mb-ereg-replace.php)) function on the markdown document that results after converting the main upload.

Note, however, that json requires that backslashes must be escaped as double backslashes. Since backslashes occur often in regular expressions, they must all occur as double backslashes. For more on regular expression syntax, see [here](https://github.com/geoffgarside/oniguruma/blob/master/Syntax.txt).

In the above, for example, the key `"\\. \\. \\."` is really the regular expression `"\. \. \."`, which is the syntax for three periods separated by spaces, i.e., "`. . .`". The value "…" is the single unicode ellipsis symbol. This entry under "`importreplacements`" option specifies that whenever three periods separated by spaces are found in the document, they should be replaced by a single unicode ellipsis.

In a monospaced font, the point of an entry like `"\\b([A-Z])\\. ?([A-Z])\\.": "\\1. \\2."` may be unclear. This looks for upper-case initials as occur in  "`G. E. Moore`". On the right side, in between the `\1.` and `\2.` (the first and second initial), the space is a narrow space rather than a full space, so "G. E. Moore" is condensed in the output to "G. E. Moore", which I like better aesthetically.

These replacements can be removed if unwanted, and more can be added if desired.

## Assignment Types

The remainder of the settings fall under the "`assignmentTypes`" option. Each key found under "`assignmentType`" specifies a *type* of document or typesetting assignment used by the project.

For example, he default `project-settings.json` file specifies two assignment types: `article` and `review`, which might be appropriate for a journal that publishes both articles and reviews.
A third, say, `discussion`, could be added.

An anthology might use a `chapter` or `contribution` assignment type instead. 
The framework does not place any limitation on the names of the assignment types, but it is best to use a single word, and in particular, a singular noun that can be pluralized by adding an "s".

Each project should have at least one assignment type, but needs to have no more than that.
A project could have any number of assignment types otherwise.

Typesetting assignments/documents in the typesetting framework are sorted by assignment type.
Each assignment type is given it own "add new …" button.
The files for a documents of a given assignment type can be found in a subdirectory of the project directory named after the assignment type with an `-s` at the end, e.g., `articles` (for the `article` assignment type).

Each assignment type key under `assignmentTypes` in the `project-settings.json` file must have various suboptions (with the appropriate keys) as described below.
These keys may occur in any order, and most likely will not occur in the order in which they discussed below.

### `display` (assignment type option)

For example:

```json
{
    "assignmentTypes": {
        "article": {
            "display": "[VOLUME.NUMBER] “<em>TITLE</em>”<br>by AUTHOR"
        }
    }
}
```

This should be a HTML string that includes certain metadata fields written in all caps (in this example, "`VOLUME`", "`NUMBER`", "`TITLE`" and "`AUTHOR`").
This is only used internally in the framework and specifies how the header for a listing for a document of a given type should appear inside the framework in the list of current assignments or archived assignments. 

### `metadata` (assignment type option)

This, often lengthy, option specifies what metadata fields appear in the metadata block for a given document of the assignment type in question.

The metadata fields may have any name. A typical element specification could looks like this:

```json
{
    "assignmentTypes": {
        "article": {
            "metadata": {
                "title": {
                    "required": true,
                    "label": "Title",
                    "inputtype": "text",
                    "pandoc": "yaml"
                }
            }
        }
    }
}
```

This specifies that there should be a metadata item called "title". A typical metadata item specifier has four attributes: (1) `required`, a boolean (true/false) that specifies whether the field is required, (2) `label`, a string used to label the item in the metadata block, (3) `inputtype`, which specifies what type of HTML input element should be used (typically "`text`" or "`number`" or possibly "`email`"), and (4) `pandoc`, a string specifying how the metadata should be passed to pandoc in the metadata files.

For `pandoc`, there are four possibilities, `yaml`, `yamlarray`, `yamlblock` and `subelement`. A typical metadata field will use `yaml`, which will simply insert the value into the `metadata.yaml` file using its item name followed by a colon followed by the value. `yamlblock` should be used instead if the value may consist of multiple lines. This will cause the item to be inserted using the a `|` after the metadata item name and colon with the value of the field following in indented lines. This is used for things like abstracts. `yamlarray` will split comma separated values into comma-separated array values in the entry, which is useful for things like keywords, where many values are entered into one input field. The `subelement` option should only be used for metadata items with subcategories, discussed below.



## Other Documentation

See also the other documentation files concerning [installation](https://github.com/frabjous/open-guide-typesetting-framework/blob/main/doc/installation.md) and [regular usage (by editors and typesetters)](https://github.com/frabjous/open-guide-typesetting-framework/blob/main/doc/usage.md).

## License

Copyright 2023 © Kevin C. Klement. This is free software, which can be redistributed and/or modified under the terms of the [GNU General Public License (GPL), version 3](https://www.gnu.org/licenses/gpl.html).