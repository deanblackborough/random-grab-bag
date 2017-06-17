<?php

include '../src/ImageResize/Helper.php';
include '../src/ImageResize/AbstractResize.php';
include '../src/ImageResize/Jpeg.php';

$resizer = new DBlackborough\GrabBag\ImageResize\Jpeg(128, 76, 100);
$resizer->loadImage('tower-bridge-1280-760.jpg')
    ->resizeSource()
    ->createCopy()
    ->save();
