
# Open Guide Typesetting Framework Documentation

# Installation

## Requirements

You will need the following.

1. A [php](https://www.php.net/)-enabled web server, probably something GNU/Linux based.
   Other Unix-like operating systems like FreeBSD or even MacOS may work, but have not been tested.
   You will probably want to use [nginx](https://www.nginx.com/) or [apache](https://httpd.apache.org/).

    Instructions for setting up a standard php-enabled web-server are beyond the scope of this documentation, but there are many guides and tutorials for doing this online for all major linux distributions.
    A MySQL or other database is not needed.

3. You will need to install the programs used in the typesetting, including [pandoc](https://pandoc.org), a TeX distribution such as [texlive](https://tug.org/texlive/), and others. See the [instructions for setting up the Open Guide Editor](https://github.com/frabjous/open-guide-editor/blob/main/doc/installation.md) for the full list of programs it uses by default.
    The sample settings also make use of [ghostscript](https://www.ghostscript.com/), which often comes bundled with your TeX distribution, and a zip implementation like [info-zip](https://infozip.sourceforge.net/Zip.html), but these are not necessary for basic functionality.

   All these programs can likely be installed easily with your linux distribution's package manager.

5. You will need the programs used in the installation steps, including [git](https://git-scm.com/) and [npm](https://www.npmjs.com/).
    There’s a good chance that you have these installed already.
    They do not need root access.
    Again, if they are not already installed, your linux distribution's package manager will be able to install them.

## Steps

Installing the framework is straightforward.

1. Clone this repository and its submodules somewhere inside the directories served by the webserver. First navigate to such a directory and run:

    ```sh
    git clone --recurse-submodules --depth 1 \
        https://github.com/frabjous/open-guide-typesetting-framework.git
    ```

    This will also install the `open-guide-editor` git submodule and its own `open-guide-misc` submodule.

    Change into the directory created.

    ```sh
    cd open-guide-typesetting-framework
    ```

2. Follow the instructions for finishing [installing](https://github.com/frabjous/open-guide-editor/blob/main/doc/installation.md) and, if you wish to change the defaults, for [configuring](https://github.com/frabjous/open-guide-editor/blob/main/doc/settings.md), the Open Guide Editor used by the framework. (The `newproject.php` script, mentioned below, will do a minimal job doing this if it detects it hasn't been done already.)

    This script can be run multiple times if you wish to use the same site for multiple projects.
       It can also be removed after use for security reasons.
       Projects can also be created manually by copying an existing project in the data directory and renaming it and changing its settings files.

3. Configure the directory where all data will be kept (preferably outside the directories served by the web server) by copying the file `sample-settings.json` to become `settings.json` and editing it.
    (The `newproject.php` script will also prompt you for this information if it detects it hasn't been done already.)

4. Create at least one project by running the `newproject.php` script, which must be run from the command line:

    ```sh
    php php/newproject.php
    ```

    This will prompt you for a short name as well as a full title for your project, as well as a site maintainer contact name and email. It will create a initial user of the site with the same email, and provide a password.

5. You should now be able to visit the site if your web server is up and running, e.g.:

    ```
    https://yourdomain.com/open-guide-typesetting-framework/
    ```

    You can skip right to the log in for a given project by adding `?project=shortname` to the URL. Follow the [usage instructions](https://github.com/frabjous/open-guide-typesetting-framework/blob/main/doc/usage.md) for adding any additional editors. The site maintainer can then be removed as an editor if desired.

   You can rename the directory hosting the framework to something shorter than `open-guide-typesetting-framework`, or even unpack it directly into the server's root directory, but the submodule directories like `open-guide-editor` and `open-guide-misc` are expected to remain as is.

6. You will likely want to finish configuring your project by editing the `project-settings.json` file in the project’s subdirectory of the data directory.
    Instructions for configuring projects can be found in the [configuration](https://github.com/frabjous/open-guide-typesetting-framework/blob/main/doc/configuration.md) documentation.

## Other Documentation

See also the other documentation files concerning [project configuration](https://github.com/frabjous/open-guide-typesetting-framework/blob/main/doc/configuration.md) and [regular usage (by editors and typesetters)](https://github.com/frabjous/open-guide-typesetting-framework/blob/main/doc/usage.md).

## License

Copyright 2023 © Kevin C. Klement.
This is free software, which can be redistributed and/or modified under the terms of the [GNU General Public License (GPL), version 3](https://www.gnu.org/licenses/gpl.html).
