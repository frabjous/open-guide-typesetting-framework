
# Open Guide Typesetting Framework Documentation

# Installation

## Requirements

You will need.

1. A [php](https://www.php.net/)-enabled web server, probably something GNU/Linux based.
   Other Unix-like operatubg systems like FreeBSD or even MacOS may work, but have not been tested.
   You will probably want to use [nginx](https://www.nginx.com/) or [apache](https://httpd.apache.org/).

    Instructions for setting up a standard php-enabled web-server are beyond the scope of this documentation, but there are many guides and tutorials for doing this online for all major linux distributions.
    A MySQL or other database is not needed.

3. You will need to install the programs used in the typesetting, including [pandoc](https://pandoc.org), a TeX distribution such as [texlive](https://tug.org/texlive/), and others. See the [instructions for setting up the open guide editor](https://github.com/frabjous/open-guide-editor/blob/main/doc/installation.md) for the list.
    The sample settings also make use of [ghostscript](https://www.ghostscript.com/), which often comes bundled with your TeX distribution, and a zip implementation like [info-zip](https://infozip.sourceforge.net/Zip.html), but these are not necessary for basic functionality.

4. You will need the programs used in the installation steps, including [git](https://git-scm.com/) and [npm](https://www.npmjs.com/).
    There’s a good chance that you have these installed already.
    They do not need root access.

## Steps

1. Clone this repository and its submodules somewhere inside the directory served by the webserver. First navigate to such a directory and run:

    ```sh
    git clone --recurse-submodules --depth 1 \
        https://github.com/frabjous/open-guide-typesetting-framework.git
    ```

    This will also install the open-guide-editor git submodule and its own submodule.

2. Follow the instructions for finishing [installing](https://github.com/frabjous/open-guide-editor/blob/main/doc/installation.md) and, if you wish not to stick with the defaults, for [configuring](https://github.com/frabjous/open-guide-editor/blob/main/doc/settings.md), the Open Guide Editor used by the framework. (The `newproject.php` script, mentioned below, will do a minimal job doing this if it detects it hasn't been done already.)

3. Configure the directory where all data will be kept (preferably outside the directories served by the web server) by copying the file `sample-settings.json` to become `settings.json` and editing it. (The `newproject.php` will also prompt you for this information if it detects it hasn't been done already.)

4. Create at least one project by running the `newproject.php` script, which must be run from the command line:

    ```sh
    php php/newproject.php
    ```

    This will prompt you for a site maintainer 