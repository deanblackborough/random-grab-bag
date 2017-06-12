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
    /**
     * @var array Source image properties
     */
    protected $source = [];

    /**
     * @var array $destination Intermediate image properties
     */
    protected $intermediate = [];

    /**
     * @var array $canvas Final canvas properties
     */
    protected $canvas = [];

    /**
     * Set the required options for the image resizer. To allow batch processing we set the
     * majority of the options in the constructor to allow reuse of the object
     *
     * @param integer $width Required width for the new image
     * @param integer $height Required height for the new image
     * @param integer $quality Quality or compression level for new image, this value depends
     * on the desired format, the format classes will document the acceptable values
     * @param boolean $maintain_aspect Maintain aspect ratio of the original image? If set to
     * true padding will be calculated and added around a best fit re-sampled image, otherwise,
     * the image will be stretched to fit the desired canvas
     * @param array $canvas_color Canvas background color, passed in as an rgb array
     *
     * @throws \InvalidArgumentException If any of the params are invalid we throw an exception
     */
    public function __construct(
        int $width,
        int $height,
        int $quality,
        bool $maintain_aspect = false,
        array $canvas_color = array('r' => 255, 'g' => 255, 'b' => 255)
    ) {
        if ($width < 1) {
            throw new \InvalidArgumentException('Width not valid, must be greater than 0');
        }

        if ($height < 1) {
            throw new \InvalidArgumentException('Height not valid, must be greater than 0');
        }

        if ($this->colorIndexValid('r', $canvas_color) === false ||
            $this->colorIndexValid('g', $canvas_color) === false ||
            $this->colorIndexValid('b', $canvas_color) === false
        ) {
            throw new \InvalidArgumentException('Canvas colour array invalid, it should contain three indexes, r, g 
                and b and each should have a value between 0 and 255');
        }

        $this->canvas['width'] = $width;
        $this->canvas['height'] = $height;
        $this->canvas['quality'] = $quality;
        $this->canvas['color'] = $canvas_color;
        $this->intermediate['maintain_aspect'] = $maintain_aspect;
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
     * @throws \Exception Throws an exception if the image can't be loaded
     */
    public function loadImage(string $file, string $path = '')
    {
        if (file_exists($path . $file) === true) {
            $this->source['path'] = $path;
            $this->source['file'] = $file;
            $this->sourceDimensions();
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
     * @return void
     * @throws \Exception
     */
    protected function sourceDimensions()
    {
        $dimensions = getimagesize($this->source['path'] . $this->source['file']);

        $this->source['width'] = intval($dimensions[0]);
        $this->source['height'] = intval($dimensions[1]);
        $this->source['aspect_ratio'] = floatval($this->source['width'] / $this->source['height']);

        if ($this->canvas['width'] > $this->source['width'] || $this->canvas['height'] > $this->source['height']) {
            throw new \Exception("The options are set to upscale the image, this class 
             does not support that.");
        }
    }

    /**
     * Resize the image
     *
     * Calculates the size of the resized image taking into account all the set options including
     * spacing if the existing aspect ratio is to be retained and then calls the create method in
     * the relevant format based class
     *
     * @param string $suffix The suffix for the new image
     *
     * @return void
     * @throws \Exception Throws an exception if unable to create image
     */
    public function resize($suffix = '-thumb')
    {
        $this->canvas['suffix'] = trim($suffix);

        if ($this->intermediate['maintain_aspect'] === true) {
            if ($this->source['aspect_ratio'] > 1.00) {
                $this->intermediateSizeLandscape();
            } else {
                if ($this->source['aspect_ratio'] === 1.00) {
                    $this->intermediateSizeSquare();
                } else {
                    $this->intermediateSizePortrait();
                }
            }
        } else {
            $this->intermediate['width'] = $this->canvas['width'];
            $this->intermediate['height'] = $this->canvas['height'];
        }

        $this->canvasSpacingX();
        $this->canvasSpacingY();

        $this->create();
    }

    /**
     * The source image is landscape, maintaining aspect ratio calculate the intermediate
     * image height and width
     *
     * @return void
     */
    protected function intermediateSizeLandscape()
    {
        // Set width and then calculate height
        $this->intermediate['width'] = $this->canvas['width'];
        $this->intermediate['height'] = intval(round(
            $this->intermediate['width'] / $this->source['aspect_ratio'], 0));

        // If height larger than requested, set and calculate new width
        if ($this->intermediate['height'] > $this->canvas['height']) {
            $this->intermediate['height'] = $this->canvas['height'];
            $this->intermediate['width'] = intval(round(
                $this->intermediate['height'] * $this->source['aspect_ratio'], 0));
        }
    }

    /**
     * The source image is landscape, fit as appropriate
     *
     * @return void
     */
    protected function intermediateSizeSquare()
    {
        if ($this->canvas['height'] === $this->canvas['width']) {
            $this->intermediate['width'] = $this->canvas['width'];
            $this->intermediate['height'] = $this->canvas['height'];
        } else {
            if ($this->canvas['width'] > $this->canvas['height']) {
                $this->intermediate['width'] = $this->canvas['height'];
                $this->intermediate['height'] = $this->canvas['height'];
            } else {
                $this->intermediate['height'] = $this->canvas['width'];
                $this->intermediate['width'] = $this->canvas['width'];
            }
        }
    }

    /**
     * The source image is portrait, maintaining aspect ratio calculate the intermediate
     * image height and width
     *
     * @return void
     */
    protected function intermediateSizePortrait()
    {
        // Set height and then calculate width
        $this->intermediate['height'] = $this->canvas['height'];
        $this->intermediate['width'] = intval(round(
            $this->intermediate['height'] * $this->source['aspect_ratio'], 0));

        // If width larger than requested, set and calculate new height
        if ($this->intermediate['width'] > $this->canvas['width']) {
            $this->intermediate['width'] = $this->canvas['width'];
            $this->intermediate['height'] = intval(round(
                $this->intermediate['width'] / $this->source['aspect_ratio'], 0));
        }
    }

    /**
     * Calculate any required x canvas spacing, necessary if the intermediate image will be
     * smaller than the canvas
     *
     * @return void
     */
    protected function canvasSpacingX()
    {
        $this->canvas['spacing']['x'] = 0;

        if ($this->intermediate['width'] < $this->canvas['width']) {
            $difference = $this->canvas['width'] - $this->intermediate['width'];

            if ($difference % 2 === 0) {
                $this->canvas['spacing']['x'] = $difference / 2;
            } else {
                if ($difference > 1) {
                    $this->canvas['spacing']['x'] = ($difference - 1) / 2 + 1;
                } else {
                    $this->canvas['spacing']['x'] = 1;
                }
            }
        }
    }

    /**
     * Calculate any required y canvas spacing, necessary if the intermediate image will be
     * smaller than the canvas
     *
     * @return void
     */
    protected function canvasSpacingY()
    {
        $this->canvas['spacing']['y'] = 0;

        if ($this->intermediate['height'] < $this->canvas['height']) {

            $difference = $this->canvas['height'] - $this->intermediate['height'];

            if ($difference % 2 === 0) {
                $this->canvas['spacing']['y'] = $difference / 2;
            } else {
                if ($difference > 1) {
                    $this->canvas['spacing']['y'] = ($difference - 1) / 2 + 1;
                } else {
                    $this->canvas['spacing']['y'] = 1;
                }
            }
        }
    }

    /**
     * Destroy the created image resources
     *
     * @return void
     */
    public function __destruct()
    {
        if (isset($this->canvas['canvas']) === true) {
            imagedestroy($this->canvas['canvas']);
        }
        if (isset($this->intermediate['copy']) === true) {
            imagedestroy($this->intermediate['copy']);
        }
    }

    /**
     * Create the image in the required format
     *
     * @return void
     * @throws \Exception Throws an exception if there was an error creating or saving the new image
     */
    abstract protected function create();

    /**
     * Attempt to save the new image
     *
     * @return boolean
     * @throws \Exception Throws an exception if the save fails
     */
    abstract protected function save();
}
