<?php
declare(strict_types=1);

namespace DBlackborough\GrabBag\ImageResize;

class Config
{
    CONST ERROR_WIDTH_INVALID = 'Width not valid, must be greater than 0';
    CONST ERROR_HEIGHT_INVALID = 'Height not valid, must be greater than 0';
    CONST ERROR_CANVAS_COLOR_ARRAY_INVALID = 'Canvas colour array invalid, it should contain three indexes, r, g 
        and b and each should have a value between 0 and 255';
    CONST ERROR_UPSCALE = "The options are set to upscale the image, this class does not support that.";

    CONST ERROR_CALL_IMAGECREATETRUECOLOR = 'Call to imagecreatetruecolor failed';
    CONST ERROR_CALL_IMAGECOLORALLOCATE = 'Call to imagecolorallocate failed';
    CONST ERROR_CALL_IMAGEFILL = 'Call to imagefill failed';
    CONST ERROR_CALL_IMAGECOPYRESAMPLED = 'Call to imagecopyresampled failed';

    CONST ERROR_CALL_GETINFO = 'Unable to getInfo(), process() not called';
}
