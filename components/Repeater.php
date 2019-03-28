<?php

namespace Wbry\Content\Components;

use Cms\Classes\ComponentBase;

/**
 * Repeater component
 *
 * @package Wbry\Content\Components
 * @author Diamond Systems
 */
class Repeater extends ComponentBase
{
    public function componentDetails()
    {
        return [
            'name'        => 'wbry.content::components.repeater.name',
            'description' => 'wbry.content::components.repeater.desc',
        ];
    }
}
