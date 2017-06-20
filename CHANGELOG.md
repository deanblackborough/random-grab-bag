# Changelog

Full changelog for the random grab bag

### v0.06 - API - 2017-06-20

* API for the image resize classes.
* Maintain aspect ration defaults to true.
* Modified resize classes, move setting of options outside of constructor.
* Updated README, added overview of first class.

### v0.05 - Options and refactoring - 2017-06-17

* Added a helper class for error message strings and base functions.
* Added setters for options, allows chaining, process image multiple times in one request.
* Refactoring and minor fixes.

### v0.04 - Filename and path - 2017-06-16

* Added the ability to optionally set the filename and path for the copy.
* Refactoring, better method names, reduce duplication.
* resampleCopy not being called [Bug]

### v0.03 - Png & Gif - 2017-05-15

* Added png and gif support to image resize.
* Minor refactoring to reduce duplication.

### v0.02 - Chaining - 2017-06-14

* Added method chaining to simplify usage.
* Added a getInfo() call to return the details for the canvas, newly/to be created image and resizer, required for unit tests.
* Minor updates to base class.

### v0.01 - Initial release - 2017-06-12

* Added image resize class, resize a jpeg image down optionally maintaining aspect ratio.
