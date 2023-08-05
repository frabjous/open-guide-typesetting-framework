
# Open Guide Typesetting Framework Documentation

# Project Configuration and Settings

Follow these instructions after completing the [installation instructions](https://github.com/frabjous/open-guide-typesetting-framework/blob/main/doc/installation.md) for the framework.

## Projects, Project Directories and Settings File

Each site installation of the typesetting framework must host one or more "projects". A "project" could be a journal, or an anthology, an online guide, or similar. Each login account and typesetting assignment will be attached to project. A first or additional project can be created by running the `newproject.php` script in the `php` subdirectory of the cloned git repo from the command line.

```sh
php php/newproject.php
```

Answer the prompts in the terminal to complete the process.

Each project has its own subdirectory of the site's data directory (using its "short name").
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
Any set of fields, with any names, can be used, but it makes sense for the metadata items to match those used in the project's pandoc template for the assignment type.

A typical metadata item specification could look like this:

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

This specifies that there should be a metadata item called "title" with the given attributes.
A typical metadata item specifier has four attributes:
(1) `required`, a boolean (true/false) that specifies whether the field is required,
(2) `label`, a string used to label the item in the metadata block,
(3) `inputtype`, which specifies what type of HTML input element should be used (typically "`text`" or "`number`" or possibly "`email`"), and
(4) `pandoc`, a string specifying how the metadata should be passed to pandoc in the metadata files.

Optionally a `"placeholder"` may also be specified, whose value will appear in the input field until a value is actually supplied.
If not specified, the label will also be used for this purpose.

For `"pandoc"`, there are five possibilities, `"yaml"`, `"yamlblock"`, `"yamlarray"`, `"yamllist"` and `"subelement"`.
A simple metadata field will use `"yaml"`, which will simply insert the value into the `metadata.yaml` file using its item name followed by a colon followed by the value. 

`"yamlblock"` should be used instead if the value may consist of multiple lines.
This will cause the item to be inserted using the a `|` after the metadata item name and colon with the value of the field following on indented lines.
This can be used for things like an abstract.

The `"yamlarray"`, `"yamllist"` and `"subelement"` options are used for those items allowing multiple values.
If the specifier for a metadata item is wrapped in (json array) brackets, this means that the metadata item allows multiple values, for example:

```json
{
    "assignmentTypes": {
        "article": {
            "metadata": {
                "reviewedauthor": [{
                    "required": false,
                    "inputtype": "text",
                    "label": "Author(s) of reviewed work",
                    "placeholder": "Editor name",
                    "pandoc": "yamllist"
                }]
            }
        }
    }
}
```

This would allow more than one `reviewedauthor` values to be specified.
In the metadata block, such entries will have plus and minus buttons for adding or removing additional values.
The `"yamllist"` option for pandoc will create, in the yaml metadata file, a list of the values, one per line, preceded with hyphens.

If `"yamlarray"` is used instead, there will be a single input field, but individual values will be identified as separated by the value of `"separator"` in the input field, e.g., `"separator": ","`, for comma-separated values.
The separated values will be made into comma-separated array values in the yaml file. This is useful for things like keywords.

Finally, it is possible to create metadata items that allow for multiple complex values, each of which has multiple subfields.
This is done by using the `"subcategories": true` option in the metadata specifier.
Besides it and `"label"`, every other key–value pair will be interpreted as representing a subfield, which has its own specifier.
These should use the value `"subelement"` for the `"pandoc"` option.
Here is an example:

```json
{
    "assignmentTypes": {
        "article": {
            "metadata": {
                "author": {
                    "subcategories": true,
                    "label": "Author(s)",
                    "name": {
                        "required": true,
                        "inputtype": "text",
                        "label": "Name",
                        "pandoc": "subelement"
                    },
                    "affiliation": {
                        "required": false,
                        "inputtype": "text",
                        "label": "Affiliation",
                        "pandoc": "subelement"
                    },
                    "email": {
                        "required": false,
                        "inputtype": "email",
                        "label": "Email",
                        "pandoc": "subelement"
                    }
                }
            }
        }
    }
}
```

This defines a metadata item for "author" that can have multiple values, each of which itself has subfields for "name", "affiliation" and "email".
These multiple values and their subfields will be added to the yaml metadata field as a complex yaml structure in the appropriate way.

Note, however, that the default pandoc template is not set up to use subfields for the "author" metadata item.
However, non-default pandoc templates can be set up to make use of complex values like these, as described in the [pandoc documentation](https://pandoc.org/MANUAL.html#metadata-blocks).

For those items listed as `required` which allow multiple values, only the first value of the multiple allowed values will be treated as required.

### `convert` (assignment type option)

This option is a string that specifies the command that will be used to convert the uploaded file of the main document into the markdown file used as the main editing and source document used by the framework.

For example:

```json
{
    "assignmentTypes": {
        "article": {
            "convert": "pandoc %upload% -t markdown --quiet --reference-location=block --wrap=none"
        }
    }
}
```

The placeholder `%upload%` will be replaced in actual use by the name of the uploaded file.

Since the framework is built around a pandoc-based workflow, it is unlikely that you will want to customize this much, except perhaps to add custom pandoc filters and the like.

The option `-o main.md` will be added to the command to write to a file named `main.md` which is expected to be used for the main markdown file for the document until proofs or publication versions are created.

### `splitsentences` (assignment type option)

```json
{
    "assignmentTypes": {
        "article": {
            "splitsentences": true
        }
    }
}
```

This is a simple boolean option (`true`/`false`).
If set to `true`, paragraphs of text in the markdown document will be split so as to have one sentence per line.
The sentence-break detection algorithm is not perfect, and it may miss some, especially when a sentence ends with an uppercase letter or unusual punctuation. 
In these cases, multiple sentences will appear on the same line, but this is usually harmless.
Paragraph breaks are indicated in markdown with blank lines between lines of text, which should not be affected by this option.
This option exists simply for the convenience of the editor in locating individual sentences in a paragraph when editing.
This splitting is not done by pandoc itself, and will work best if the `convert` option discussed above includes `--wrap=none` to start with, so that each sentence is not already split across lines.

The option can simply be set to false if this behavior is undesired, in which case the `--wrap` option in the `convert` option will determine the layout of the paragraphs in the markdown.

## Other Documentation

See also the other documentation files concerning [installation](https://github.com/frabjous/open-guide-typesetting-framework/blob/main/doc/installation.md) and [regular usage (by editors and typesetters)](https://github.com/frabjous/open-guide-typesetting-framework/blob/main/doc/usage.md).

## License

Copyright 2023 © Kevin C. Klement. This is free software, which can be redistributed and/or modified under the terms of the [GNU General Public License (GPL), version 3](https://www.gnu.org/licenses/gpl.html).