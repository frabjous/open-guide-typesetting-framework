
# Open Guide Typesetting Framework Documentation

# Project Configuration and Settings

It may be best to read and make use of this documentation after completing the [installation instructions](https://github.com/frabjous/open-guide-typesetting-framework/blob/main/doc/installation.md) for the framework.

## Projects, Project Directories, and Settings Files

Each installation site of the typesetting framework must host one or more "projects".
A "project" could be a journal, or an anthology, an online guide, or similar.
Each login account and typesetting assignment will be attached to a project.
A first (or additional) project can be created by running the `newproject.php` script in the `php` subdirectory of the cloned git repo from the command line.

```sh
php php/newproject.php
```

Answer the prompts in the terminal to complete the process.

Each project has its own subdirectory of the site's data directory (using its "short name").
Projects can also be created by simply copying an existing project subdirectory to create another subdirectory with a different name, and editing its settings.

Inside each project's subdirectory is a file, "`project-settings.json`", which is where the settings for each project are configured, and customizations should be made.
If the project was created with the `newproject.php` script, this file will begin as a near-clone of the `sample-project-settings-journal.json` file found in the main directory of the git repo. 
These settings are appropriate for a journal.

For any other kind of project, and most likely even for a journal, the `project-settings.json` file should be edited and customized for the actual project.
Most of the rest of this documentation file describes what it is in `project-settings.json` and how it can be tweaked.

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

These settings specify the full title of the project, the main contact person for the project's name and email. The full project name appears at the top of the typesetting framework's pages and in various other places. The contact name and email are used in headers of emails sent (unless a custom script is used) and in certain error messages in the typesetting framework itself.

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
        "\\b([A-Z])\\. ?([A-Z])\\. ?([A-Z])\\.": "\\1. \\2. \\3.",
        "\\b([A-Z])\\. ?([A-Z])\\.": "\\1. \\2."
    }
}
```

This setting consists of key--value pairs, and are used during the document conversion process.
Each key is a regular expression search term, and each value is a regular expression replacement term.
Each pair is applied as the first two arguments to php's `mb_ereg_replace(…)` (see [here](https://www.php.net/manual/en/function.mb-ereg-replace.php)) function, and applied to the markdown document that results after converting the main upload.

Note, however, that json requires that backslashes must be escaped as double backslashes.
Since backslashes occur often in regular expressions, they must all occur as double backslashes here.
For more on regular expression syntax, see [here](https://github.com/geoffgarside/oniguruma/blob/master/Syntax.txt).

In the above, for example, the key `"\\. \\. \\."` is really the regular expression `"\. \. \."`, which is the syntax for three periods separated by spaces, i.e., "`. . .`".
The value "…" is the single unicode ellipsis symbol.
This entry under the "`importreplacements`" option specifies that whenever three periods separated by spaces are found in the document, they should be replaced by a single unicode ellipsis when the document is converted.

In a monospaced font, the point of an entry like `"\\b([A-Z])\\. ?([A-Z])\\.": "\\1. \\2."` may be unclear.
This looks for two upper-case initials, such as those occurring in  "`G. E. Moore`".
On the right side, in between the `\1.` and `\2.` (representing the first and second initial), the space there is a narrow space rather than a full space, so "G. E. Moore" is condensed in the output to "G. E. Moore", which I like better aesthetically.

These replacements can be removed if unwanted, and more can be added if desired.

## Assignment Types

The remainder of the settings fall under the "`assignmentTypes`" option.
Each key found under "`assignmentType`" specifies a *type* of document or typesetting assignment used by the project.

For example, he default `project-settings.json` file specifies two assignment types: `article` and `review`, which might be appropriate for a journal that publishes both articles and reviews.
A third, say, `discussion`, could be added.

An anthology might use a `chapter` or `contribution` assignment type instead. 
The framework does not place any limitation on the names of the assignment types, but it is best to use a single word, and in particular, a singular noun that can be pluralized by adding an "s".

Each project should have at least one assignment type, but needs to have no more than that.
A project could have any number of assignment types otherwise.

Typesetting assignments/documents in the typesetting framework are sorted by assignment type.
Each assignment type is given its own "add new …" button.
The files for a documents of a given assignment type can be found in a subdirectory of the project directory named after the assignment type with an `s` at the end, e.g., `articles` (for the `article` assignment type).

Each assignment type key under `assignmentTypes` in the `project-settings.json` file must have various suboptions (with the appropriate keys) as described below.
These keys may occur in any order, and most likely will not occur in the order in which they are discussed below.

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

This should be an HTML string that includes certain metadata fields written in all caps (in this example, "`VOLUME`", "`NUMBER`", "`TITLE`" and "`AUTHOR`").
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
(1) `"required"`, a boolean (true/false) that specifies whether or not the field is required,
(2) `"label"`, a string used to label the item in the metadata block,
(3) `"inputtype"`, which specifies what type of HTML input element should be used (typically `"text"` or `"number"` or possibly `"email"`), and
(4) `"pandoc"`, a string specifying how the metadata should be passed to pandoc in the metadata files.

Optionally a `"placeholder"` may also be specified, whose value will appear in the input field until a value is actually supplied.
If not specified, the label will be used for this purpose.

For `"pandoc"`, there are five possibilities, `"yaml"`, `"yamlblock"`, `"yamlarray"`, `"yamllist"` and `"subelement"`.
A simple metadata field will use `"yaml"`, which will simply insert the value into the `metadata.yaml` file using its item name followed by a colon followed by the value. 

`"yamlblock"` should be used instead if the value may consist of multiple lines.
This will cause the item to be inserted using a "`|`" after the metadata item name and colon with the value of the field following with indented lines.
This can be used for things like an abstract.

The `"yamlarray"`, `"yamllist"` and `"subelement"` options are used for those items allowing multiple values.
If the specifier for a metadata item is wrapped in (json array) brackets, this means that the metadata item allows multiple values, for example:

```json
{
    "assignmentTypes": {
        "review": {
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
The `"yamllist"` option for pandoc will create, in the yaml metadata file, a list of the values, one per line, preceded by hyphens.

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

This option is a string that specifies the command that will be used to convert the uploaded file of the main document into the main markdown file employed as the edited source document used by the framework.

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
This splitting is not done by pandoc itself, but done after the markdown conversion, and will work best if the `convert` option discussed above includes `--wrap=none` to start with, so that each sentence is not already split across lines.

The option can be set to `false` if this behavior is undesired, in which case the `--wrap` option in the `convert` command will determine the layout of the paragraphs in the markdown.

### `output` (assignment type option)

Here is an example:

```json
{
    "assignmentTypes": {
        "article": {
            "output": {
                "pdf": {
                    "editorcommand": "pandoc --metadata-file metadata.yaml --number-sections --citeproc --bibliography bibliography.json --resource-path .:%projectdir% -t pdf -o %outputfile% %rootdocument%"
                },
                "html": {
                    "editorcommand": "pandoc --metadata-file metadata.yaml --wrap preserve --embed-resources --standalone --number-sections --citeproc --bibliography bibliography.json --resource-path .:%projectdir% -t html -o %outputfile% %rootdocument%"
                },
                "epub": {
                    "editorcommand": "pandoc --metadata-file metadata.yaml --wrap preserve --number-sections --citeproc --bibliography bibliography.json --resource-path .:%projectdir% -t epub -o %outputfile% %rootdocument%"
                }
            }
        }
    }
}
```

This complex option does two things.
Firstly, when a new document is imported, a settings file for the Open Guide Editor named `oge-settings.json` is created in its directory.
Such settings files can be used to set the main or "root" document for each typesetting project, as well as configure the commands used by the editor to create its preview.

For more information on `oge-settings.json` files and their syntax, see the [settings documentation](https://github.com/frabjous/open-guide-editor/blob/main/doc/settings.md) for the Open Guide Editor.

Each key in the assignment type `"output"` option should be the file extension for an output file which can be created from the input markdown document.
The `"editorcommand"` attribute of the object the extension is mapped to in the json will be used as the value of the `command` attribute in the editor's preview "routine" as set in the `oge-settings.file`.
This is in effect the command used to create the output file from the main source document.

The syntax of the command is the same as that described in the Open Guide Editor documentation, and allows the same placeholders, such as `%rootdocument%` (for the main file being edited) and `%outputfile%` for the file being created.
Additionally, a `%projectdir%` placeholder can be used, which will be replaced by the path to the project’s subdirectory of the framework’s data directory.
This can be useful for setting things like a resource path if the project templates make use of assets such as, e.g., images.
These commands are passed through a shell, so shell operators such as "`&&`" or "`||`" may be used to chain together multiple commands, create pipes and redirections, etc.

In a typical set up, these will make use of pandoc with options appropriate for the output type.
Certain pandoc options here are almost a requirement. Consider using these:

- `--metadata-file metadata.yaml` – Without this, the metadata specified in the metadata block will not be passed to pandoc.
- `--number-sections` – Without this, sections and subsections in the output document will not have numbers. 
- `--citeproc` – without this, no citations in the document will be processed or linked to the bibliography.
- `--bibliography bibliography.json` – without this, the bibliographic information specified in the framework's Bibliography block will not be used.
- `--resource-path .:%projectdir%` (or similar) – without this, assets such as images and auxiliary files will probably not be located by pandoc.
    Note this example starts with "`.:`" which specifies that the document's own directory should be searched first, and only then the project-wide resource directory if the required resource is not found in the document directory.
- `--template=[filename]` – Without this, the default pandoc template will be used. This may not be a problem if you have created your own pandoc settings folder with your own templates.
- `--standalone` and `--embed-resources` — Without using these for html output, the resulting html file will not be a complete file with a header and template applied, and resources such as images and stylesheets will likely not be available in the editor preview window.

There are many other options to consider, such as `--css` to add stylesheets to html and html-based output files, `--csl` to specify a citation and bibliography style different from the default Chicago style, and so on.

Some of the above could also be done using a pandoc defaults file (see [here](https://pandoc.org/MANUAL.html#defaults-files)), but specifying them here also makes sense, especially if pandoc is used for other things on the server.

The other thing the `output` option configures is how proof sets are created.
The creation process will run the same commands as used by the editor to produce all the output file-types whose extensions are listed.
Typically at least both an `html` and `pdf` output routine should be specified, and optionally others such as `epub`, etc.

### `createEdition` (assignment type option)

Finally, the `"createEdition"` option is used by the "Publication" block in the framework when preparing a final version of a document for publication.

Here is an example:

```json
{
    "assignmentTypes": {
        "article": {
            "createEdition": [
                {
                    "command": "pandoc --metadata-file metadata.yaml --wrap preserve --number-sections --citeproc --bibliography bibliography.json --resource-path .:%projectdir% -t epub -o %documentid%-%version%.epub main.md",
                    "outputfile": "%documentid%-%version%.epub"
                },
                {
                    "command": "pandoc --metadata-file metadata.yaml --wrap preserve --embed-resources --standalone --number-sections --citeproc --bibliography bibliography.json --resource-path .:%projectdir% -t html -o %documentid%-%version%.html main.md",
                    "outputfile": "%documentid%-%version%.html"
                },
                {
                    "command": "cp main.md %documentid%-%version%.md",
                    "outputfile": "%documentid%-%version%.md"
                },
                {
                    "command": "pandoc --metadata-file metadata.yaml --number-sections --citeproc --bibliography bibliography.json --resource-path .:%projectdir% -t pdf -o %documentid%-%version%-tmp.pdf main.md && gs -dNOPAUSE -dFastWebView -sDEVICE=pdfwrite -sOUTPUTFILE=%documentid%-%version%.pdf -dBATCH %documentid%-%version%-tmp.pdf && rm %documentid%-%version%-tmp.pdf",
                    "outputfile": "%documentid%-%version%.pdf"
                },
                {
                    "command": "php -r 'echo json_decode(file_get_contents(\"metadata.json\"))->abstract;' | pandoc -f markdown -t plain --wrap none -o abstract.txt",
                    "outputfile": "abstract.txt"
                },
                {
                    "command": "echo \"\" | pandoc --metadata-file metadata.yaml --citeproc --bibliography bibliography.json --resource-path .:%projectdir% --wrap none -t plain -o references.txt",
                    "outputfile": "references.txt"
                },
                {
                    "command": "cd editions/%version% && zip ../../%documentid%-%version%.zip *",
                    "outputfile": "%documentid%-%version%.zip"
                }
            ]
        }
    }
}
```

This option is an array of items.
Each item can have two attributes, `"command"` and `"outputfile"`. The `"command"` attribute determines a command to be run to create a final production file. The placeholders `%projectdir%`, `%documentid%` and `%version%`, for the project directory, unique document id, and the version number, respectively, can be used in the commands.

It may be useful to pass the `%version%` placeholder as the value of a pandoc metadata option, e.g., "`-M version=%version%`" if the pandoc template makes use of the version number, as this is not typically included in the `metadata.yaml` file.

These commands are run in turn.
Typically, the commands should be very close to those used in the `"output"` option (or else the previews and proof sets will not match the actual output files), but they may involve additional steps of post-processing.
For instance, the example above has a command resulting in a pdf, which uses pandoc only to create a temporary pdf.
A ghostscript (`gs`) command then follows which uses the temporary pdf to create an optimized and linearized pdf for fast web viewing (typically reducing the file size as well), and then the temporary pdf is deleted.
Each of these sub-steps is separated by the shell operator "`&&`" in the command field.

The `"outputfile"` attribute specifies a filename for the file that is expected to be created by the command.
After running the command, this file is looked for, and if found, moved into the production version’s subdirectory within the `editions` subdirectory of the document directory.

It is possible here to produce multiple output files with the same extension (e.g., different pdfs with different creation options or templates) so long as the output files are otherwise named differently.

If an output file has the plain text `.txt` extension, it will be made available directly in the framework as an "extraction" to be copied and pasted.
This is useful for things like abstracts and reference lists.

It is often useful to have the last command produce a compressed archive such as a `.zip` file of all the other output files (which at that point would be the entire contents of the edition/version's subdirectory).
This (along with all the others) will be given as download options in the framework.

## Templates and Other Ways to Customize

Modifying the `project-settings.json` file is only one way to customize what is produced by the typesetting process, and perhaps not even the most important.

Many if not most file formats that are produced via converting pandoc markdown files are created, directly or indirectly, with html or LaTeX intermediaries.
As examples, `.epub` files are just zipped `.(x)html` files, and unless a non-default `--pdf-engine` option is given, most `.pdf` files produced by pandoc are done by first converting to a LaTeX file and compiling the intermediate LaTeX file to pdf.

Project leaders using the typesetting framework will most likely want to create pandoc templates for both html and LaTeX output, if not additional templates. These templates might make use of additional customized files such as css stylesheets, or LaTeX document packages/document classes. These are the main ways for a project to truly make its output files fit its own style and brand.

The process of creating pandoc templates and related files is outside the scope of this documentation, but the [pandoc documentation on templates](https://pandoc.org/MANUAL.html#templates) is excellent. Even more fine-grained control can be accomplished with [pandoc filters](https://pandoc.org/filters.html) and the like. These are all compatible with the typesetting framework, and the command line flags to use them would simply have to be added to the `"output"` and/or `"createEdition"` options described above.
<!-- TODO: add link to jhap/og templates when created. maybe fregeifier -->

## Other Documentation

See also the other documentation files concerning [installation](https://github.com/frabjous/open-guide-typesetting-framework/blob/main/doc/installation.md) and [regular usage (by editors and typesetters)](https://github.com/frabjous/open-guide-typesetting-framework/blob/main/doc/usage.md).

## License

Copyright 2023 © Kevin C. Klement. This is free software, which can be redistributed and/or modified under the terms of the [GNU General Public License (GPL), version 3](https://www.gnu.org/licenses/gpl.html).
