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

    private $loaded = false;

    public function __construct($format = 'jpg')
    {

    }

    public function resizeTo($width, $height) : ImageResize
    {
        $this->resizer = new ImageResize\Jpeg($width, $height, 100);

        return $this;
    }

    public function source($file, $path = '') : ImageResize
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

    public function target($file, $path = '') : array
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
