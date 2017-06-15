<?php
declare(strict_types=1);

namespace DBlackborough\GrabBag\ImageResize;

/**
* Resize a gif image
*
* @author Dean Blackborough <dean@g3d-development.com>
* @copyright Dean Blackborough
* @license https://github.com/deanblackborough/random-grab-bag/blob/master/LICENSE
*/
class Gif extends AbstractResize
{
    /**
     * Set the required options for the image resizer. To allow batch processing we set the
     * majority of the options in the constructor to allow reuse of the object
     *
     * @param integer $width Required width for the new image
     * @param integer $height Required height for the new image
     * @param integer $quality Quality or compression level, 0, image gif doesn't support quality
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
        $maintain_aspect = false,
        array $canvas_color = array('r' => 255, 'g' => 255, 'b' => 255)
    ) {
        if ($quality !== 0) {
            throw new \InvalidArgumentException('Quality must be set to 0');
        }

        parent::__construct($width, $height, $quality, $maintain_aspect, $canvas_color);
    }

    /**
     * Create the image in the required format
     *
     * @return AbstractResize
     * @throws \Exception Throws an exception if there was an error creating or saving the new image
     */
    public function create(): AbstractResize
    {
        $this->createCanvas();

        $this->intermediate['copy'] = imagecreatefromgif($this->source['path'] . $this->source['file']);
        if ($this->intermediate['copy'] === false) {
            throw new \Exception('Call to imagecreatefromjpeg failed');
        }

        return $this;
    }

    /**
     * Attempt to save the new image
     *
     * @param string $suffix Suffix for filename
     *
     * @return AbstractResize
     * @throws \Exception Throws an exception if the save fails
     */
    public function save($suffix): AbstractResize
    {
        $result = imagegif($this->canvas['canvas'], $this->source['path'] .
            str_replace('.jpg', $suffix . '.jpg', $this->source['file']));

        if ($result === false) {
            throw new \Exception('Unable to save new image');
        }

        return $this;
    }
}
