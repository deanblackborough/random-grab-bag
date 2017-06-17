<?php
declare(strict_types=1);

namespace DBlackborough\GrabBag\ImageResize;

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
     * @var boolean Has the process() method been called?
     */
    protected $processed = false;

    /**
     * @var string Filename for new image file
     */
    protected $file = null;

    /**
     * @var string Path for new image file
     */
    protected $path = null;

    protected $extension;

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
            throw new \InvalidArgumentException(Helper::ERROR_WIDTH_INVALID);
        }

        if ($height < 1) {
            throw new \InvalidArgumentException(Helper::ERROR_HEIGHT_INVALID);
        }

        if (Helper::colorIndexValid('r', $canvas_color) === false ||
            Helper::colorIndexValid('g', $canvas_color) === false ||
            Helper::colorIndexValid('b', $canvas_color) === false
        ) {
            throw new \InvalidArgumentException(Helper::ERROR_CANVAS_COLOR_ARRAY_INVALID);
        }

        $this->canvas['width'] = $width;
        $this->canvas['height'] = $height;
        $this->canvas['quality'] = $quality;
        $this->canvas['color'] = $canvas_color;
        $this->intermediate['maintain_aspect'] = $maintain_aspect;
    }

    /**
     * Set a new width value, useful if you are using the resize class within a loop/iterator
     *
     * @param int $width
     *
     * @return AbstractResize
     * @throws \Exception
     */
    public function setWidth(int $width) : AbstractResize
    {
        if ($width < 1) {
            throw new \InvalidArgumentException(Helper::ERROR_WIDTH_INVALID);
        }

        $this->canvas['width'] = $width;

        return $this;
    }

    /**
     * Set a new height value, useful if you are using the resize class within a loop/iterator
     *
     * @param int $height
     *
     * @return AbstractResize
     * @throws \Exception
     */
    public function setHeight(int $height) : AbstractResize
    {
        if ($height < 1) {
            throw new \InvalidArgumentException(Helper::ERROR_HEIGHT_INVALID);
        }

        $this->canvas['height'] = $height;

        return $this;
    }

    /**
     * Set a new canvas color
     *
     * @param array $canvas_color Indexed array, r, g, b for color
     *
     * @return AbstractResize
     * @throws \Exception
     */
    public function setCanvasColor(array $canvas_color) : AbstractResize
    {
        if (Helper::colorIndexValid('r', $canvas_color) === false ||
            Helper::colorIndexValid('g', $canvas_color) === false ||
            Helper::colorIndexValid('b', $canvas_color) === false
        ) {
            throw new \InvalidArgumentException(Helper::ERROR_CANVAS_COLOR_ARRAY_INVALID);
        }

        $this->canvas['color'] = $canvas_color;
    }

    /**
     * Load the image
     *
     * @param string $file File name and extension
     * @param string $path Full patch to image
     *
     * @return AbstractResize
     * @throws \Exception Throws an exception if the image can't be loaded
     */
    public function loadImage(string $file, string $path = '') : AbstractResize
    {
        if (file_exists($path . $file) === true) {
            $this->source['path'] = $path;
            $this->source['file'] = $file;
            $this->sourceProperties();
        } else {
            throw new \Exception("File couldn't be found in supplied destination: '" . $path . $file . "'");
        }

        // Reset the processed status when a new image is loaded
        $this->processed = false;
        $this->file = null;
        $this->path = null;

        return $this;
    }

    /**
     * Fetch the dimensions of the source image and calculate the aspect ratio. We also check
     * to ensure that the image is being resized down, currently we don't support upscaling the
     * image
     *
     * @return void
     * @throws \Exception
     */
    protected function sourceProperties()
    {
        $properties = Helper::imageProperties($this->source['file'], $this->source['path']);

        if ($properties['width'] !== null) {
            $this->source['width'] = $properties['width'];
            $this->source['height'] = $properties['height'];
            $this->source['aspect_ratio'] = $properties['aspect_ratio'];
        } else {
            throw new \Exception(Helper::ERROR_CALL_GETIMAGESIZE);
        }

        if ($this->canvas['width'] > $this->source['width'] || $this->canvas['height'] > $this->source['height']) {
            throw new \Exception(Helper::ERROR_UPSCALE);
        }
    }

    /**
     * Process the request, generate the size required for the image along with the
     * canvas spacing
     *
     * @return AbstractResize
     * @throws \Exception Throws an exception if unable to create image
     */
    public function resizeSource() : AbstractResize
    {
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

        $this->processed = true;

        return $this;
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
     * Return all the info for the image that will/has be/been created
     *
     * @return array
     * @throws \Exception Throws an exception if called before process()
     */
    public function getInfo() : array
    {
        if ($this->processed === true) {
            return [
                'canvas' => [
                    'width' => $this->canvas['width'],
                    'height' => $this->canvas['height'],
                    'color' => $this->canvas['color']
                ],
                'resized-image' => [
                    'width' => $this->intermediate['width'],
                    'height' => $this->intermediate['height']
                ],
                'canvas-placement' => [
                    'x' => $this->canvas['spacing']['x'],
                    'y' => $this->canvas['spacing']['y']
                ],
                'resizer' => [
                    'maintain-aspect-ratio' => $this->intermediate['maintain_aspect'],
                    'quality' => $this->canvas['quality']
                ]
            ];
        } else {
            throw new \Exception(Helper::ERROR_CALL_GETINFO);
        }
    }

    /**
     * Create the canvas and fill with the canvas colour
     *
     * @throws \Exception
     */
    protected function createCanvas()
    {
        $this->canvas['canvas'] = imagecreatetruecolor($this->canvas['width'], $this->canvas['height']);
        if ($this->canvas['canvas'] === false) {
            throw new \Exception(Helper::ERROR_CALL_IMAGECREATETRUECOLOR);
        }

        $fill_color = imagecolorallocate($this->canvas['canvas'], $this->canvas['color']['r'],
            $this->canvas['color']['g'], $this->canvas['color']['b']);
        if ($fill_color === false) {
            throw new \Exception(Helper::ERROR_CALL_IMAGECOLORALLOCATE);
        }

        if (imagefill($this->canvas['canvas'], 0, 0, $fill_color) === false) {
            throw new \Exception(Helper::ERROR_CALL_IMAGEFILL);
        };
    }

    /**
     * Resample the image copy
     *
     * @throws \Exception
     */
    protected function resampleCopy()
    {
        $result = imagecopyresampled($this->canvas['canvas'], $this->intermediate['copy'],
            $this->canvas['spacing']['x'], $this->canvas['spacing']['y'], 0 ,0,
            $this->intermediate['width'], $this->intermediate['height'], $this->source['width'],
            $this->source['height']);

        if($result === false) {
            throw new \Exception(Helper::ERROR_CALL_IMAGECOPYRESAMPLED);
        }
    }

    /**
     * Create the image in the required format
     *
     * @return AbstractResize
     * @throws \Exception Throws an exception if there was an error creating or saving the new image
     */
    abstract public function createCopy() : AbstractResize;

    /**
     * Attempt to save the new image file
     *
     * @throws \Exception Throws an exception if the save fails
     */
    abstract protected function saveFile();

    /**
     * Optionally set the filename for the new image, if not set we just append -copy to the
     * existing filename
     *
     * @param string $filename Filename of new image
     *
     * @return AbstractResize
     */
    public function setFileName(string $filename) : AbstractResize
    {
        $this->file = $filename;

        return $this;
    }

    /**
     * Optionally set the path for the new image file otherwise we use the same path as the
     * source image
     *
     * @param string $path Path for new image file
     *
     * @return AbstractResize
     */
    public function setPath($path) : AbstractResize
    {
        $this->path = $path;

        return $this;
    }

    /**
     * Save the new image
     *
     * @return AbstractResize
     * @throws \Exception Throws an exception if the save fails
     */
    public function save()
    {
        if ($this->file === null) {
            $this->file = str_replace($this->extension, '-copy' . $this->extension, $this->source['file']);
        }
        if ($this->path === null) {
            $this->path = $this->source['path'];
        }

        $this->saveFile();

        return $this;
    }
}
