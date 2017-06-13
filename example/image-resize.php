<?php

include '../src/ImageResize/AbstractResize.php';
include '../src/ImageResize/Jpeg.php';

$resizer = new DBlackborough\GrabBag\ImageResize\Jpeg(128, 76, 100);
$resizer->loadImage('tower-bridge-1280-760.jpg')->
    process()->
    resize()->
    save('-down-100-percent');

$resizer = new DBlackborough\GrabBag\ImageResize\Jpeg(76, 76, 100, true);
$resizer->loadImage('tower-bridge-1280-760.jpg')->
    process()->
    resize()->
    save('-top-and-bottom-bar');
