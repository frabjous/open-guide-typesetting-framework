
# Open Guide Typesetting Framework Documentation

# Project Configuration and Settings

Follow these instructions after completing the [installation instructions](https://github.com/frabjous/open-guide-typesetting-framework/blob/main/doc/installation.md) for the framework.

## Projects, Project Directories and Settings File

Each site installation of the typesetting framework must host one or more "projects". A "project" could be a journal, or an anthology, an online guide, or similar. Each login account and typesetting assignment will be attached to project. A first or additional project can be created using the `newproject.php` script in the `php` subdirectory of the cloned git repo.

```sh
php php/newproject.php
```

Each project has its own subdirectory of the site's data directory (using its "short" name). Projects can also be created by simply copying an existing subdirectory with a new short name, and editing its settings.

Inside the project's subdirectory is a file, "`project-settings.json`", which is where the settings for each project are configured, and customizations should be made. If the project as created with the `newproject.php` script, this file will begin as a near-clone of the `sample-project-settings-journal.php` file found in the main directory of the git repo. These settings are appropriate for a journal.

For any other kind of project, and most likely even for a journal, the `project-settings.json` file should be edited and customized for the actual project. Most of the below describes what it is in this file and how it can be tweaked.



## Other Documentation

See also the other documentation files concerning [installation](https://github.com/frabjous/open-guide-typesetting-framework/blob/main/doc/installation.md) and [regular usage (by editors and typesetters)](https://github.com/frabjous/open-guide-typesetting-framework/blob/main/doc/usage.md).

## License

Copyright 2023 © Kevin C. Klement. This is free software, which can be redistributed and/or modified under the terms of the [GNU General Public License (GPL), version 3](https://www.gnu.org/licenses/gpl.html).