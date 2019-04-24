<?php

namespace Wbry\Content\Classes;

use File;

/**
 * Contains a list of icons.
 * @see http://octobercms.com/docs/ui/icon
 *
 * @package Wbry\Content\Classes
 * @author Wbry, Diamond <me@diamondsystems.org>
 */
class IconList
{
    public static $iconsLessFilePath = 'modules/system/assets/ui/less/icon.icons.less';

    public static function getList()
    {
        static $list;
        if ($list !== null)
            return $list;

        try {
            $filePath = base_path(self::$iconsLessFilePath);
            if (! file_exists($filePath))
                $list = [];
            $strLess = File::get($filePath);
            preg_match_all("/oc\-icon\-(.+?)\:before/i", $strLess, $m, PREG_PATTERN_ORDER);
            return $list = ($m[1] ?? []);
        }
        catch (\Exception $e) {
            return $list = [];
        }
    }
}