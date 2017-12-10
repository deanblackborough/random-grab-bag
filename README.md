[![Latest Stable Version](https://img.shields.io/packagist/v/deanblackborough/random-grab-bag.svg?style=flat-square)](https://packagist.org/packages/deanblackborough/random-grab-bag)
[![License](https://img.shields.io/badge/license-MIT-blue.svg)](https://github.com/deanblackborough/random-grab-bag/blob/master/LICENSE)
[![Minimum PHP Version](https://img.shields.io/badge/php-%3E%3D%207.1-8892BF.svg)](https://php.net/)

# Random grab bag

*Catch all library for utility classes.*

## Description

A bunch of utility classes that don't currently deserve their own dedicated library.

## Installation
 
The easiest way to use any of these classes is via composer. ```composer require deanblackborough/random-grab-bag```, 
alternatively include the classes in src/ in your library.

## The classes

### Image Resize

You can use the resize class via the API or directly if you want a little more control over the 
output and options.

#### API

```
$resizer = new DBlackborough\GrabBag\ImageResize($format);

$resizer->resizeTo(
        $width, 
        $height, 
        $maintain_aspect = true, 
        $canvas_color = [ 'r' => 0, 'g' => 0, 'b' => 0]
    )
    ->source($source_file, $source_path = '')
    ->target($target_file, $target_path = '');
```

#### Direct

```
$resizer = new DBlackborough\GrabBag\ImageResize\Jpeg();

$resizer->setOptions(
        $width, 
        $height, 
        $maintain_aspect = true, 
        $canvas_color = [ 'r' => 0, 'g' => 0, 'b' => 0]
    )
    ->loadImage($source_file, $source_path = '')
    ->resizeSource()
    ->createCopy()
    ->save();
```

#### Public methods

I've listed the methods, their params and the return type. If it isn't obvious what a method 
does I have done a bad job of naming it, if so, please let me know. 

* createCopy() : `AbstractResize`
* getInfo() : `array`
* loadImage(`string` $file, `string` $path = '') : `AbstractResize`
* resizeSource() : `AbstractResize`
* save() : `AbstractResize`
* setCanvasColor(`array` $canvas_color) : `AbstractResize`
* setFileName(`string` $filename) : `AbstractResize`
* setHeight(`int` $height) : `AbstractResize`
* setOptions(`int` $width, `int` $height, `int` $quality, `bool` $maintain_aspect = true, `array` $canvas_color = array('r' => 255, 'g' => 255, 'b' => 255)) : `AbstractResize`
* setPath($path) : `AbstractResize`
* setWidth(int $width) : `AbstractResize`
