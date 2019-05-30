<?php

namespace Wbry\Content\Classes\Interfaces;

/**
 * ContentItems interface
 * @package Wbry\Content\Classes\Interfaces
 * @author Wbry, Diamond <me@diamondsystems.org>
 */
interface ContentItems
{
    const CONTENT_ITEM_ADD_NEW     = 'new';
    const CONTENT_ITEM_ADD_READY   = 'ready';
    const CONTENT_ITEM_ADD_SECTION = 'section';

    const CONTENT_ITEM_ACTION_CREATE = 'create';
    const CONTENT_ITEM_ACTION_CLONE  = 'clone';
    const CONTENT_ITEM_ACTION_EDIT   = 'edit';
}