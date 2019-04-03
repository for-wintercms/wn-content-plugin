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

                $menuSlug = camel_case($config['menu']['slug']);
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

    public function generateRepeaterConfig(&$content, string $page, string $repeater)
    {
        if (! $content || ! $page || ! $repeater)
            return false;

        $forms = [
            'label'  => studly_case($repeater),
            'fields' => [],
        ];

        if (is_array($content))
        {
            if ($content)
            {
                if (is_numeric(array_keys($content)[0]))
                {
                    $parseConfig = $this->repeaterConstructor($content, 'item', true);
                    if (isset($parseConfig['item']['form']['fields']))
                        $forms['fields'] = $parseConfig['item']['form']['fields'];
                }
                else
                {
                    foreach ($content as $dK => &$dV)
                        $forms['fields'][$dK] = $this->repeaterConstructor($dV, $dK);
                    $content = [$content];
                }
            }
        }
        elseif (is_string($content) || is_numeric($content))
        {
            $forms['fields']['item'] = $this->repeaterConstructor($content, 'item');
            $content = [['item' => $content]];
        }
        else
            return false;

        # get or create page config
        # ===========================
        if (isset($this->repeaterFiles[$page]))
        {
            if (isset($this->repeaterAllList[$page][$repeater]))
                return $content;

            $configPath = $this->repeatersPath .'/'.$this->repeaterFiles[$page];
            $config = Yaml::parseFile($configPath);
        }
        else
        {
            $configPath = $this->repeatersPath .'/config-'.$page.'.yaml';
            if (file_exists($configPath))
                $configPath = $this->repeatersPath .'/config-'.$page.'-'.time().'.yaml';

            $config = [
                'menu' => [
                    'label' => studly_case($page),
                    'slug'  => camel_case($page),
                    'icon'  => 'icon-'.$page,
                    'order' => count($this->repeaterFiles)+1
                ],
                'repeater' => [],
            ];
        }

        # create repeater
        # =================
        if (! isset($config['repeater']))
            $config['repeater'] = [];
        $config['repeater'][$repeater] = $forms;
        $configStr = Yaml::render($config);

        @File::chmod($this->repeatersPath);

        if (! File::put($configPath, $configStr))
            throw new SystemException(sprintf('Error saving file %s', $configPath));

        @File::chmod($configPath);

        $this->reParseRepeatersConfig();

        return $content;
    }

    private function repeaterConstructor(&$val, $key, $isParent = false)
    {
        $arrD = [
            'label' => studly_case($key),
            'type'  => 'section',
        ];

        if (is_array($val))
        {
            $arrSubD = [];
            if ($val)
            {
                if (is_numeric(array_keys($val)[0]))
                {
                    $x1 = 0;
                    foreach ($val as &$dV)
                    {
                        if (++$x1 === 1)
                            $arrSubD['item'] = $this->repeaterConstructor($dV, 'item');
                        else
                            $this->repeaterConstructor($dV, 'item');

                        if (! $isParent)
                            $dV = ['item' => $dV];
                    }
                }
                else
                {
                    foreach ($val as $dK => &$dV)
                        $arrSubD[$dK] = $this->repeaterConstructor($dV, $dK);
                }
            }

            if ($isParent)
                return $arrSubD;

            $arrD['type'] = 'repeater';
            $arrD['form'] = ['fields' => $arrSubD];
        }
        elseif (is_numeric($val))
            $arrD['type'] = 'number';
        elseif (is_string($val))
            $arrD['type'] = $this->repeaterInputTextType($val);

        return $arrD;
    }

    private function repeaterInputTextType($text)
    {
        return (preg_match("#(\<|\>|\r|\n|\r\n)#i", $text) || strlen($text) > 64) ? 'textarea' : 'text';
    }
}