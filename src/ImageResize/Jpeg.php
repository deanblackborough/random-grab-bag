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
     * Required process method in child classes, this method creates canvas,
     * copies and then saves new image
     *
     * @return void|Exception Throws an exception if there was an error
     *                        either creating or saving the new image
     */
    protected function create()
    {
        // TODO: Implement create() method.
    }
}
