<?php
declare(strict_types=1);

namespace DBlackborough\GrabBag\ImageResize;

/**
 * Resize a png image
 *
 * @author Dean Blackborough <dean@g3d-development.com>
 * @copyright Dean Blackborough
 * @license https://github.com/deanblackborough/random-grab-bag/blob/master/LICENSE
 */
class Png extends AbstractResize
{

    /**
     * Create the image in the required format
     *
     * @return AbstractResize
     * @throws \Exception Throws an exception if there was an error creating or saving the new image
     */
    public function create(): AbstractResize
    {
        // TODO: Implement create() method.
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
        // TODO: Implement save() method.
    }
}
