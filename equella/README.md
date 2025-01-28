Moodle Module For openEQUELLA Integration
=========================================

Requirements
------------

- This module requires openEQUELLA 4.1 QA3 or higher.
- The master branch works with the Moodle 2.7 and up.

For Moodle earlier versions, choose the appropriate git branches.

Support
-------

Feel free to fork this and send back Pull Requests for any defects or features
that you want to contribute back. Opening issues
[here](https://github.com/openequella/moodle-mod_equella/issues) is also recommended.

Docker Installation For Testing Purposes
----------------------------------------
 
Clone [this link](https://github.com/jmhjjardison/docker-moodle) to get a docker instance of Moodle.
Then use Docker to build it.

```sh
git clone https://github.com/jmhardison/docker-moodle
cd docker-moodle
docker build -t moodle .
```

Then setup and run the MYSQL database for use with the docker Moodle.
 
```sh
docker run -d --name DB -p 3306:3306 -e MYSQL_DATABASE=moodle -e MYSQL_ROOT_PASSWORD=moodle -e MYSQL_USER=moodle -e MYSQL_PASSWORD=moodle mysql
```

Then, run the Moodle instance. Give it a URL  and a matching port. 

```sh
docker run -d -P --name moodle --link DB:DB -e MOODLE_URL=http://localhost:8099 -p 8099:80 jhardison/moodle
```

__NOTE:__ 
This port and URL should not conflict with that of your openEQUELLA.

From this point on, you should open the Moodle instance in your web browser
and follow the installation process.
 
You can access the Terminal of your Moodle if you so wish with the following command:

```sh
docker exec -it moodle bash
```

This project folder should be copied into the Moodle at `/var/www/html/mod`. It must be renamed from `moodle-mod_equella`
to simply `equella` in order to work properly. From your machine terminal in the folder that contains the `moodle-mod_equella` directory:

```sh
docker cp  moodle-mod_equella/ moodle:/var/www/html/mod/
docker exec -it moodle bash
cd ./var/www/html/mod/
mv moodle-mod_equella/ equella/
```

Login to Moodle. It should notify you that a new module has been detected. Click to upgrade.

In the settings, set an openEQUELLA URL. Note: this must have the URI `/[institutionname]/signon.do` so 
for example, `http://localhost:8080/vanilla/signon.do`.

In the `openEQUELLA action` setting, type `structured`, assuming you are using a recent (6.1 and above) version of openEQUELLA.

You're done! You now have a Moodle instance and an openEQUELLA instance integrated together.

More Information
----------------

For any more information regarding integration with Moodle from openEQUELLA, visit [this documentation page.](http://openequella.github.io/guides/MoodleIntegrationGuide.html)

