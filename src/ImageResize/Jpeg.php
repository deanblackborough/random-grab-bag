<?php
declare(strict_types=1);

namespace DBlackborough\GrabBag\ImageResize;

/**
 * Resize a jpeg image
 *
 * @author Dean Blackborough <dean@g3d-development.com>
 * @copyright Dean Blackborough
 * @license https://github.com/deanblackborough/random-grab-bag/blob/master/LICENSE
 */
class Jpeg extends AbstractResize
{
    /**
     * Jpeg constructor.
     */
    public function __construct()
    {
        $this->extension = '.jpg';

        parent::__construct();
    }

    /**
     * Set the required options for the image resizer.
     *
     * @param integer $width Required width for the new image
     * @param integer $height Required height for the new image
     * @param integer $quality Quality or compression level, must be a value between 1 and 100,
     * 100 being best quality
     * @param boolean $maintain_aspect Maintain aspect ratio of the original image? If set to
     * true padding will be calculated and added around a best fit re-sampled image, otherwise,
     * the image will be stretched to fit the desired canvas
     * @param array $canvas_color Canvas background color, passed in as an rgb array
     *
     * @throws \InvalidArgumentException If any of the params are invalid we throw an exception
     */
    public function setOptions(
        int $width,
        int $height,
        int $quality,
        bool $maintain_aspect = false,
        array $canvas_color = array('r' => 255, 'g' => 255, 'b' => 255)
    ) {
        if ($quality < 1 || $quality > 100) {
            throw new \InvalidArgumentException('Quality must be a value between 1 and 100');
        }

        parent::setOptions($width, $height, $quality, $maintain_aspect, $canvas_color);
    }

    /**
     * Create the image
     *
     * @return AbstractResize
     * @throws \Exception Throws an exception if any step fails
     */
    public function createCopy() : AbstractResize
    {
        $this->createCanvas();

        $this->intermediate['copy'] = imagecreatefromjpeg($this->source['path'] . $this->source['file']);
        if ($this->intermediate['copy'] === false) {
            throw new \Exception('Call to imagecreatefromjpeg failed');
        }

        $this->resampleCopy();

        return $this;
    }

    /**
     * Attempt to save the new image file
     *
     * @return AbstractResize
     * @throws \Exception Throws an exception if the save fails
     */
    protected function saveFile(): AbstractResize
    {
        $result = imagejpeg($this->canvas['canvas'], $this->path . $this->file, $this->canvas['quality']);

        if ($result === false) {
            throw new \Exception('Unable to save new image');
        }

        return $this;
    }
}
