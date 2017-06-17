<?php
declare(strict_types=1);

namespace DBlackborough\GrabBag\ImageResize;

class Helper
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
    CONST ERROR_CALL_GETIMAGESIZE = 'Call to getimagesize failed, returned false';

    CONST ERROR_CALL_GETINFO = 'Unable to getInfo(), process() not called';

    /**
     * Check to see if the supplied color index is valid
     *
     * @param string $index
     * @param array $color The color array to check
     *
     * @return boolean
     */
    public static function colorIndexValid(string $index, array $color): bool
    {
        if (array_key_exists($index, $color) === true &&
            $color[$index] >= 0 && $color[$index] <= 255
        ) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Fetch the properties for an image, width, height and aspect_ration
     *
     * @param string $file Filename
     * @param string $path Optional file path
     *
     * @return array
     */
    public static function imageProperties(string $file, string $path = '') : array
    {
        $properties = [ 'width' => null, 'height' => null, 'aspect_ratio' => null ];

        $imageProperties = getimagesize($path . $file);

        if ($imageProperties !== false) {
            $properties['width'] = intval($imageProperties[0]);
            $properties['height'] = intval($imageProperties[1]);
            $properties['aspect_ratio'] = floatval($properties['width'] / $properties['height']);
        }

        return $properties;
    }
}
