Contributing to the Moodle Module for openEQUELLA
==================================================

Thank you for your interest in contributing! This document describes how to
report issues, submit changes, and set up a local development environment for
working on the plugin.

Reporting Issues and Submitting Pull Requests
---------------------------------------------

- Open issues on the
  [issue tracker](https://github.com/openequella/moodle-mod_equella/issues).
- Fork the repository and send pull requests for any defects or features that
  you would like to contribute back.

Branching
---------

- The `master` branch targets Moodle 2.7 and later.
- For earlier Moodle versions, choose the appropriate Git branch.

Docker Installation For Testing Purposes
----------------------------------------

Clone [this repository](https://github.com/jmhardison/docker-moodle) to get a
Docker instance of Moodle, then use Docker to build it.

```sh
git clone https://github.com/jmhardison/docker-moodle
cd docker-moodle
docker build -t moodle .
```

Set up and run the MySQL database for use with the Docker Moodle.

```sh
docker run -d --name DB -p 3306:3306 -e MYSQL_DATABASE=moodle -e MYSQL_ROOT_PASSWORD=moodle -e MYSQL_USER=moodle -e MYSQL_PASSWORD=moodle mysql
```

Then run the Moodle instance. Give it a URL and a matching port.

```sh
docker run -d -P --name moodle --link DB:DB -e MOODLE_URL=http://localhost:8099 -p 8099:80 jhardison/moodle
```

__NOTE:__ This port and URL should not conflict with that of your openEQUELLA.

From this point on, open the Moodle instance in your web browser and follow the
installation process.

You can access the terminal of your Moodle container if you so wish:

```sh
docker exec -it moodle bash
```

This project folder should be copied into Moodle at `/var/www/html/mod`. It
must be renamed from `moodle-mod_equella` to simply `equella` in order to work
properly. From the directory that contains the `moodle-mod_equella` directory:

```sh
docker cp  moodle-mod_equella/ moodle:/var/www/html/mod/
docker exec -it moodle bash
cd ./var/www/html/mod/
mv moodle-mod_equella/ equella/
```

Log in to Moodle. It should notify you that a new module has been detected.
Click to upgrade.

In the settings, set an openEQUELLA URL. Note: this must have the URI
`/[institutionname]/signon.do`, for example
`http://localhost:8080/vanilla/signon.do`.

In the `openEQUELLA action` setting, type `structured`, assuming you are using
a recent (6.1 and above) version of openEQUELLA.

You're done! You now have a Moodle instance and an openEQUELLA instance
integrated together.

Development: DND Upload with Metadata Interception
--------------------------------------------------

This section applies when a site administrator has enabled **(Intercept drag
and drop files → Auto contribute file to openEQUELLA with meta data)** in the
openEQUELLA module plugin settings. When active, dragging a file onto a course
page opens a custom metadata modal (title, description, copyright, keywords)
instead of using Moodle's default upload behaviour.

The DND upload feature is built using TypeScript and Webpack. The source code
is located in the `tsrc` directory.

If you are modifying the DND feature, you must compile the TypeScript code
into the `amd/build` and `amd/src` directories for Moodle to recognize the
changes.

### Prerequisites

* [Node.js](https://nodejs.org/): Use [nvm](https://github.com/nvm-sh/nvm) to
  install the version defined in `tsrc/.nvmrc`.

### Build Instructions

1. Navigate to the source directory:

   ```sh
   cd tsrc
   ```

2. Install the required dependencies:

   ```sh
   npm ci
   ```

3. Build the files for production:

   ```sh
   npm run build
   ```
