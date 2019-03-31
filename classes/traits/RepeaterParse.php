<?php

namespace Wbry\Content\Classes\Traits;

use Lang;
use File;
use Yaml;
use Backend;
use Exception;
use Cms\Classes\Theme as CmsTheme;
use October\Rain\Exception\ApplicationException;

/**
 * RepeaterParse trait
 *
 * @package Wbry\Content\Classes\Traits
 * @author Diamond Systems
 */
trait RepeaterParse
{
    protected static $isRepeaterParse = false;

    public $menuList  = null;
    public $repeaters = null;

    public $repeaterList    = null;
    public $repeaterAllList = null;

    public $isRepeaterError = false;

    protected function parseRepeatersConfig($isController = false)
    {
        if (self::$isRepeaterParse)
            return;

        $theme = CmsTheme::getActiveTheme();
        $directory = $theme->getPath().'/repeaters';

        if (! File::isDirectory($directory))
            return;

        try {
            foreach (File::files($directory) as $file)
            {
                $fileName = $file->getFilename();
                if (! preg_match("#^config\-(.+?)\.yaml$#i", $fileName))
                    continue;

                $config = Yaml::parseFile($file->getRealPath());

                # menu
                #==========
                if (empty($config['menu']) || empty($config['menu']['label']) || empty($config['menu']['slug']))
                    throw new ApplicationException(Lang::get('wbry.content::lang.controllers.items.errors.repeater_menu', ['fileName' => $fileName]));

                $menuSlug = $config['menu']['slug'];
                $this->menuList[$menuSlug] = array_merge($config['menu'], [
                    'url' => Backend::url('wbry/content/items/'. $menuSlug),
                ]);

                # repeater
                #==========
                $isActiveRepeater = ($isController && $menuSlug == ($this->action ?? null));
                $errRepeater = Lang::get('wbry.content::lang.controllers.items.errors.repeater_list', ['fileName' => $fileName]);
                if (empty($config['repeater']) || ! is_array($config['repeater']))
                    throw new ApplicationException($errRepeater);

                $this->repeaterAllList[$menuSlug] = [];

                foreach ($config['repeater'] as $rAction => $repeater)
                {
                    if (empty($repeater['fields']) || empty($repeater['label']))
                        throw new ApplicationException($errRepeater);

                    $this->repeaterAllList[$menuSlug][$rAction] = $repeater['label'];

                    if ($isActiveRepeater)
                    {
                        $this->repeaters[$rAction]    = ['fields' => $repeater['fields']];
                        $this->repeaterList[$rAction] = $repeater['label'];
                    }
                }
            }

            self::$isRepeaterParse = false;
        }
        catch (Exception $e) {
            $this->isRepeaterError = true;

            if ($isController)
                $this->handleError($e);
        }
    }
}