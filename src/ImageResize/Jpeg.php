<?php
declare(strict_types=1);

namespace DBlackborough\GrabBag\ImageResize;

use Exception;

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
     * Set the required options for the image resizer. To allow batch processing we set the
     * majority of the options in the constructor to allow reuse of the object
     *
     * @param integer $width Required width for the new image
     * @param integer $height Required height for the new image
     * @param integer $quality Quality or compression level, must be a value between 1 and 100
     * on the desired format, the format classes will document the acceptable values
     * @param array $canvas_color Canvas background color, passed in as an rgb array
     * @param boolean $maintain_aspect Maintain aspect ratio of the original image? If set to
     * true padding will be calculated and added around a best fit re-sampled image, otherwise,
     * the image will be stretched to fit the desired canvas
     *
     * @throws \InvalidArgumentException If any of the params are invalid we throw an exception
     */
    public function __construct(
        int $width,
        int $height,
        int $quality,
        array $canvas_color = array('r' => 255, 'g' => 255, 'b' => 255),
        $maintain_aspect = false
    ) {
        if ($quality < 1 || $quality > 100) {
            throw new \InvalidArgumentException('Quality must be a value between 1 and 100');
        }

        parent::__construct($width, $height, $quality, $canvas_color, $maintain_aspect);
    }

    /**
     * Create the image
     *
     * @return void
     * @throws \Exception Throws an exception if any step fails
     */
    protected function create()
    {
        $this->canvas['canvas'] = imagecreatetruecolor($this->canvas['width'], $this->canvas['height']);
        if ($this->canvas['canvas'] === false) {
            throw new Exception('Call to imagecreatetruecolor failed');
        }

        $fill_color = imagecolorallocate($this->canvas['canvas'], $this->canvas['color']['r'],
            $this->canvas['color']['g'], $this->canvas['color']['b']);
        if ($fill_color === false) {
            throw new Exception('Call to imagecolorallocate failed');
        }

        if (imagefill($this->canvas['canvas'], 0, 0, $fill_color) === false) {
            throw new Exception('Call to imagefill failed');
        };

        $this->intermediate['copy'] = imagecreatefromjpeg($this->source['path'] . $this->source['file']);
        if ($this->intermediate['copy'] === false) {
            throw new Exception('Call to imagecreatefromjpeg failed');
        }

        $result = imagecopyresampled($this->canvas['canvas'], $this->intermediate['copy'],
            $this->canvas['spacing']['x'], $this->canvas['spacing']['y'], 0 ,0,
            $this->intermediate['width'], $this->intermediate['height'], $this->source['width'],
            $this->source['height']);

        if($result === true) {
            $result = $this->save();

            if($result === FALSE) {
                throw new \Exception('Unable to save new image');
            }
        } else {
            throw new \Exception('Call to imagecopyresampled failed');
        }
    }

    /**
     * Attempt to save the new image
     *
     * @return boolean
     * @throws \Exception Throws an exception if the save fails
     */
    protected function save()
    {
        return imagejpeg($this->canvas['canvas'], $this->canvas['path'] .
            str_replace($this->canvas['extension'], $this->canvas['suffix'] . $this->canvas['extension'], $this->canvas['file']),
            $this->canvas['quality']);
    }
}
