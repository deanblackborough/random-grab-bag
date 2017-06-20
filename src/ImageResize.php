<?php
declare(strict_types=1);

namespace DBlackborough\GrabBag;

/**
 * Resize an image
 *
 * @author Dean Blackborough <dean@g3d-development.com>
 * @copyright Dean Blackborough
 * @license https://github.com/deanblackborough/random-grab-bag/blob/master/LICENSE
 */
class ImageResize
{
    /**
     * @var ImageResize\AbstractResize
     */
    private $resizer = null;

    /**
     * @var boolean Has a source image been loaded
     */
    private $loaded = false;

    /**
     * @var integer Quality setting for resizer
     */
    private $quality;

    /**
     * ImageResize constructor.
     *
     * @param string $format Format of source [jpg|png|gif]
     *
     * @throws \Exception
     */
    public function __construct(string $format = 'jpg')
    {
        switch ($format) {
            case 'jpg':
                $this->resizer = new ImageResize\Jpeg();
                $this->quality = 100;
                break;
            case 'png':
                $this->resizer = new ImageResize\Png();
                $this->quality = 0;
                break;
            case 'gif':
                $this->resizer = new ImageResize\Gif();
                $this->quality = 0;
                break;
            default:
                throw new \Exception('Format not supported');
                break;
        }
    }

    /**
     * Set the resize to options
     *
     * @param integer $width
     * @param integer $height
     * @param boolean $maintain_aspect
     * @param array $canvas_color
     *
     * @return ImageResize
     */
    public function resizeTo(int $width, int $height, bool $maintain_aspect = true, array $canvas_color = array('r' => 255, 'g' => 255, 'b' => 255)) : ImageResize
    {
        if ($this->resizer !== null) {
            try {
                $this->resizer->setOptions($width, $height, $this->quality, $maintain_aspect, $canvas_color);
            } catch (\Exception $e) {
                echo $e->getMessage();
            }
        }

        return $this;
    }

    /**
     * Set the source image
     *
     * @param string $file File name of the source image
     * @param string $path Optional path for the source image
     *
     * @return ImageResize
     */
    public function source(string $file, string $path = '') : ImageResize
    {
        if ($this->resizer !== null) {
            try {
                $this->resizer->loadImage($file, $path);
                $this->loaded = true;
            } catch (\Exception $e) {
                echo $e->getMessage();
            }
        }

        return $this;
    }

    /**
     * Set the file name and path for the target, this will be your newly resized image
     *
     * @param string $file File name for the target image
     * @param string $path Optional path for target image, if not set, source path is used
     *
     * @return array Returns an array of the properties for the resizer
     */
    public function target(string $file, string $path = '') : array
    {
        if ($this->resizer !== null && $this->loaded === true) {
            try {
                $this->resizer->resizeSource()
                    ->createCopy()
                    ->setFileName($file)
                    ->setPath($path)
                    ->save();

                return $this->resizer->getInfo();
            } catch (\Exception $e) {
                echo $e->getMessage();
            }
        }
    }
}
