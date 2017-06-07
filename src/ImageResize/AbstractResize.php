<?php
declare(strict_types=1);

namespace DBlackborough\GrabBag\ImageResize;

use InvalidArgumentException;
use Exception;

/**
 * Base class for the format base resize classes
 *
 * @author Dean Blackborough <dean@g3d-development.com>
 * @copyright Dean Blackborough
 * @license https://github.com/deanblackborough/random-grab-bag/blob/master/LICENSE
 */
abstract class AbstractResize
{
    protected $width;
    protected $height;

    protected $dest_width;
    protected $dest_height;

    protected $spacing_x;
    protected $spacing_y;

    protected $src_width;
    protected $src_height;
    protected $src_file;
    protected $src_path;
    protected $src_aspect_ratio;

    protected $canvas;
    protected $copy;

    protected $maintain_aspect;

    protected $canvas_color;
    protected $quality;

    protected $mime;
    protected $extension;

    protected $suffix = '-thumb';

    protected $invalid;
    protected $errors = array();

    /**
     * Set the required options for the image resizer. To allow batch processing we set the
     * majority of the options in the constructor to allow reuse of the object
     *
     * @param integer $width Required width for the new image
     * @param integer $height Required height for the new image
     * @param integer $quality Quality or compression level for new image, this value depends
     * on the desired format, the format classes will document the acceptable values
     * @param array $canvas_color Canvas background color, passed in as an rgb array
     * @param boolean $maintain_aspect Maintain aspect ratio of the original image? If set to
     * true padding will be calculated and added around a best fit re-sampled image, otherwise,
     * the image will be stretched to fit the desired canvas
     *
     * @throws InvalidArgumentException If any of the params are invalid we throw an exception
     */
    public function __construct(
        int $width,
        int $height,
        int $quality,
        array $canvas_color = array('r' => 255, 'g' => 255, 'b' => 255),
        bool $maintain_aspect = false
    ) {
        if ($width < 1) {
            $this->invalid++;
            $this->errors[] = 'Width not valid, must be greater than 0';
        }

        if ($height < 1) {
            $this->invalid++;
            $this->errors[] = 'Height not valid, must be greater than 0';
        }

        if ($this->colorIndexValid('r', $canvas_color) === false ||
            $this->colorIndexValid('g', $canvas_color) === false ||
            $this->colorIndexValid('b', $canvas_color) === false
        ) {
            $this->invalid++;
            $this->errors[] = 'Canvas colour array invalid, it should contain three indexes, r, 
                g and b and each should have a value between 0 and 255';
        }

        if ($this->invalid === 0) {

            $this->width = $width;
            $this->height = $height;
            $this->quality = $quality;
            $this->canvas_color = $canvas_color;
            $this->maintain_aspect = $maintain_aspect;
        } else {
            throw new \InvalidArgumentException("Error(s) creating resize object: " . implode(' - ', $this->errors));
        }
    }

    /**
     * Check to see if the supplied color index is valid
     *
     * @param string $index
     * @param array $color The color array to check
     *
     * @return boolean
     */
    private function colorIndexValid(string $index, array $color)
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
     * Load the image
     *
     * @param string $file File name and extension
     * @param string $path Full patch to image
     *
     * @return void
     * @throws Exception Throws an exception if the image can't be loaded
     */
    public function loadImage(string $file, string $path = '')
    {
        if (file_exists($path . $file) === true) {
            $this->sourceDimensions($path, $file);
        } else {
            throw new \Exception("File couldn't be found, supplied 
			destination: '" . $path . $file . "'");
        }
    }

    /**
     * Fetch the dimensions of the source image and calculate the aspect ratio. We also check
     * to ensure that the image is being resized down, currently we don't support upscaling the
     * image
     *
     * @param string $path
     * @param string $file
     *
     * @return void
     * @throws Exception
     */
    protected function sourceDimensions(string $path, string $file)
    {
        $dimensions = getimagesize($path . $file);

        $this->src_width = $dimensions[0];
        $this->src_height = $dimensions[1];
        $this->src_aspect_ratio = $this->src_width / $this->src_height;

        if ($this->width > $this->src_width || $this->height > $this->src_height) {
            throw new Exception("The options are set to upscale the image, this class 
             does not support that.");
        }
    }

    /**
     * Resize, calculate the size for the resized image, the the maintain
     * aspect ratio value is set to true a best fit size is calculated and then
     * the required x and y spacing is calculated for when the image is copied
     * onto the canvas
     *
     * Although the suffix for the new image can be defined the path cannot be
     * changed, that is outside the scope of this class, it is down to the
     * client developer to create directories and then oevrride the save method
     *
     * @param string $suffix Suffix for newly created image
     * @return void|Exception Throws an exception if no suffic is supplied
     */
    public function resize($suffix = '-thumb')
    {
        if (strlen(trim($suffix)) > 0) {
            $this->suffix = trim($suffix);
        } else {
            throw new InvalidArgumentException("Suffix must be defined 
			otherwise newly created image conflit with source image");
        }

        if ($this->src_aspect_ratio > 1) {
            $this->resizeLandscape();
        } else {
            if ($this->src_aspect_ratio == 1) {
                $this->resizeSquare();
            } else {
                $this->resizePortrait();
            }
        }

        if ($this->maintain_aspect == true) {
            $this->spacingX();

            $this->spacingY();
        } else {
            $this->dest_width = $this->width;
            $this->dest_height = $this->height;
        }

        $this->create();
    }

    /**
     * Source image is a landscapoe based image, assume resizing to requested
     * width and then modify the values are required
     *
     * @return void
     */
    protected function resizeLandscape()
    {
        // Set width and then calculate height
        $this->dest_width = $this->width;
        $this->dest_height = intval(round(
            $this->dest_width / $this->src_aspect_ratio, 0));

        // If height larger than requested, set and calculate new width
        if ($this->dest_height > $this->height) {
            $this->dest_height = $this->height;
            $this->dest_width = intval(round(
                $this->dest_height * $this->src_aspect_ratio, 0));
        }
    }

    /**
     * Source image is a square, fit as appropriate
     *
     * @return void
     */
    protected function resizeSquare()
    {
        if ($this->height == $this->width) {
            // Requesting a sqaure image, set destination sizes, no spacing
            $this->dest_width = $this->width;
            $this->dest_height = $this->height;
        } else {
            if ($this->width > $this->height) {
                // Requested landscapoe image, set height as dimension, will need
                // horizontal spacing
                $this->dest_width = $this->height;
                $this->dest_height = $this->height;
            } else {
                // Requested portrait image, set width as dimension, will need
                // vertical spacing
                $this->dest_height = $this->width;
                $this->dest_width = $this->width;
            }
        }
    }

    /**
     * Source image is a portrait based image, assume resizing to requested
     * height and then modify the values are required
     *
     * @return void
     */
    protected function resizePortrait()
    {
        // Set height and then calculate width
        $this->dest_height = $this->height;
        $this->dest_width = intval(round(
            $this->dest_height * $this->src_aspect_ratio, 0));

        // If width larger than requested, set and calculate new height
        if ($this->dest_width > $this->width) {
            $this->dest_width = $this->width;
            $this->dest_height = intval(round(
                $this->dest_width / $this->src_aspect_ratio, 0));
        }
    }

    /**
     * Calculate the x spacing if the width of the resampled image will be
     * smaller than the width defined for the new thumbnail
     *
     * @return void
     */
    protected function spacingX()
    {
        $this->spacing_x = 0;

        if ($this->dest_width < $this->width) {
            $width_difference = $this->width - $this->dest_width;

            if ($width_difference % 2 == 0) {
                $this->spacing_x = $width_difference / 2;
            } else {
                if ($width_difference > 1) {
                    $this->spacing_x = ($width_difference - 1) / 2 + 1;
                } else {
                    $this->spacing_x = 1;
                }
            }
        }
    }

    /**
     * Calculate the y spacing if the height of the resampled image will be
     * smaller than the height defined for the new thumbnail
     *
     * @return void
     */
    protected function spacingY()
    {
        $this->spacing_y = 0;

        if ($this->dest_height < $this->height) {

            $height_difference = $this->height - $this->dest_height;

            if ($height_difference % 2 == 0) {
                $this->spacing_y = $height_difference / 2;
            } else {
                if ($height_difference > 1) {
                    $this->spacing_y = ($height_difference - 1) / 2 + 1;
                } else {
                    $this->spacing_y = 1;
                }
            }
        }
    }

    /**
     * Destroy the image resources
     *
     * @return void
     */
    public function __destruct()
    {
        if (isset($this->canvas) == true) {
            imagedestroy($this->canvas);
        }
        if (isset($this->copy) == true) {
            imagedestroy($this->copy);
        }
    }

    /**
     * Required process method in child classes, this method creates canvas,
     * copies and then saves new image
     *
     * @return void|Exception Throws an exception if there was an error
     *                        either creating or saving the new image
     */
    abstract protected function create();
}
