<?php

include '../src/ImageResize.php';
include '../src/ImageResize/Helper.php';
include '../src/ImageResize/AbstractResize.php';
include '../src/ImageResize/Jpeg.php';

$resizer = new DBlackborough\GrabBag\ImageResize\Jpeg();

$resizer->setOptions(128, 76, 100)
    ->loadImage('tower-bridge-1280-760.jpg')
    ->resizeSource()
    ->createCopy()
    ->save();

/*$resizer = new DBlackborough\GrabBag\ImageResize();
$resizer->resizeTo(256, 152)->source('tower-bridge-1280-760.jpg')->target('tower-bridge-256-152.jpg');*/
