<?php

namespace ForWinterCms\Content\Classes;

use File;

/**
 * Contains a list of icons.
 * @see https://wintercms.com/docs/ui/icon
 *
 * @package ForWinterCms\Content\Classes
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