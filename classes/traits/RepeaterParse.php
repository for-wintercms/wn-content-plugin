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

    protected $repeatersPath = null;
    protected $repeaterFiles = [];

    public $menuList  = null;
    public $repeaters = null;

    public $repeaterList    = null;
    public $repeaterAllList = null;

    public $isRepeaterError = false;

    protected function parseRepeatersConfig(bool $isController = false)
    {
        if (self::$isRepeaterParse)
            return;

        $theme = CmsTheme::getActiveTheme();
        $directory = $theme->getPath().'/repeaters';

        if (! File::isDirectory($directory))
            return;

        $this->repeatersPath = $directory;

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

                $this->repeaterFiles[$menuSlug]   = $fileName;
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

    public function reParseRepeatersConfig()
    {
        self::$isRepeaterParse = false;
        $this->parseRepeatersConfig();
    }

    public function generateRepeaterConfig($content, string $page, string $repeater)
    {
        if (! $content || ! $page || ! $repeater)
            return false;
        elseif (isset($this->repeaterAllList[$page][$repeater]))
            return true;

        $forms = [
            'label'  => studly_case($repeater),
            'fields' => [],
        ];

        if (is_array($content))
        {
            $key = array_key_first($content);
            $forms['fields'] = $this->repeaterConstructor($content[$key], $key);
        }
        elseif (is_string($content))
            $forms['fields'] = $this->repeaterConstructor($content, 'item');
        else
            return false;

        return true;
    }

    private function repeaterConstructor($val, $key)
    {
        $res = [];

        if (is_array($val))
        {
            $firstKey = array_key_first($val);
            if (is_numeric($firstKey))

            foreach ($val as $iK => $iV)
            {
                # $firstKey
            }
        }
        else
        {
            if (is_numeric($val))
                $type = 'number';
            elseif (is_string($val))
                $type = $this->repeaterInputTextType($val);
            else
                return $res;

            $res[$key] = [
                'label' => studly_case($key),
                'type'  => $type,
            ];
        }

        return $res;
    }

    private function repeaterInputTextType($text)
    {
        return (preg_match("#(\<|\>|\r|\n|\r\n)#i", $text) || strlen($text) > 64) ? 'textarea' : 'text';
    }
}