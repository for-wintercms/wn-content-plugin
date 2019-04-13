<?php

namespace Wbry\Content\Classes\Traits;

use Lang;
use File;
use Yaml;
use Backend;
use Cms\Classes\Theme as CmsTheme;
use October\Rain\Exception\SystemException;
use October\Rain\Exception\ApplicationException;

/**
 * ContentItemsParse trait
 *
 * @package Wbry\Content\Classes\Traits
 * @author Diamond Systems
 */
trait ContentItemsParse
{
    /**
     * @var string - dir name for content item configs (content_items_dir)
     */
    protected static $contentItemsDir = 'content-items';

    /**
     * @var string - config files path (domain_path/themes/your_theme/content_items_dir)
     */
    protected $contentItemsPath = null;

    /**
     * @var array - [page_slug => filename]
     */
    protected $contentItemFiles = [];

    /**
     * @var array - [page_slug => [
     *                  label => menu title,
     *                  slug => menu slug,
     *                  url => menu url,
     *                  icon => menu icon,
     *                  order => menu order,
     *              ]]
     */
    protected $menuList = null;

    /**
     * @var array - [page_slug => [item_slug => item_name]]
     */
    protected $contentItemList = [];

    /**
     * @var array - [item_slug => [
     *                  label => item_name
     *                  form => [active_item_form_data] (@see https://octobercms.com/docs/backend/forms#form-fields)
     *              ]]
     */
    protected $activeContentItemForm = [];


    private $isContentItemsParse = false;

    /*
     * Parse
     */

    /**
     * @param string $activePage - active menu slug
     * @throws
     */
    protected function parseContentItemsConfig(string $activePage = null)
    {
        if ($this->isContentItemsParse)
            return;

        if (! $this->contentItemsPath || ! File::isDirectory($this->contentItemsPath))
        {
            $directory = CmsTheme::getActiveTheme()->getPath().'/'.self::$contentItemsDir;

            if (! File::isDirectory($directory))
                return;

            $this->contentItemsPath = $directory;
        }

        foreach (File::files($this->contentItemsPath) as $file)
        {
            $fileName = $file->getFilename();
            if (! preg_match("#^config\-(.+?)\.yaml$#i", $fileName))
                continue;

            $config = Yaml::parseFile($file->getRealPath());

            # menu
            #========
            if (empty($config['menu']) || empty($config['menu']['label']) || empty($config['menu']['slug']))
                throw new ApplicationException(Lang::get('wbry.content::content.errors.content_menu', ['fileName' => $fileName]));

            $menuSlug = camel_case($config['menu']['slug']);
            $this->menuList[$menuSlug] = array_merge($config['menu'], [
                'url' => Backend::url('wbry/content/items/'. $menuSlug),
            ]);

            # content items
            #================
            $errItem = Lang::get('wbry.content::content.errors.content_list', ['fileName' => $fileName]);

            if (empty($config['items']) || ! is_array($config['items']))
                throw new ApplicationException($errItem);

            $this->contentItemFiles[$menuSlug] = $fileName;
            $this->contentItemList[$menuSlug]  = [];

            foreach ($config['items'] as $rAction => $item)
            {
                if (empty($rAction) || empty($item['label']) || ! isset($item['form']))
                    throw new ApplicationException($errItem);

                $this->contentItemList[$menuSlug][$rAction] = $item['label'];
            }

            if ($menuSlug == $activePage)
                $this->activeContentItemForm = $config['items'];
        }

        $this->isContentItemsParse = true;
    }

    /**
     * @throws
     */
    public function reParseContentItemsConfig()
    {
        $this->isContentItemsParse = false;
        $this->menuList = null;
        $this->contentItemFiles = [];
        $this->contentItemList = [];
        $this->activeContentItemForm = [];

        $this->parseContentItemsConfig();
    }

    /**
     * addContentItem
     *
     * @param string $pageSlug
     * @param string $itemSlug
     * @param string $itemLabel
     *
     * @throws
     */
    public function addContentItem(string $pageSlug, string $itemSlug, string $itemLabel)
    {
        if (! $pageSlug || ! $itemLabel || ! $itemSlug)
            throw new ApplicationException(Lang::get('wbry.content::content.errors.add_item_empty_args'));

        $langErrPage = Lang::get('wbry.content::content.errors.no_page', ['pageSlug' => $pageSlug]);

        if (! isset($this->contentItemFiles[$pageSlug]))
            throw new ApplicationException($langErrPage);

        $configPath = $this->contentItemsPath .'/'. $this->contentItemFiles[$pageSlug];

        if (! file_exists($configPath))
            throw new ApplicationException($langErrPage);

        if (isset($this->contentItemList[$pageSlug][$itemSlug]))
            throw new ApplicationException(Lang::get('wbry.content::content.errors.available_item', ['pageSlug' => $pageSlug, 'itemSlug' => $itemSlug]));

        $config = Yaml::parseFile($configPath);

        if (! isset($config['items']))
            throw new ApplicationException(Lang::get('wbry.content::content.errors.error_config', ['fileName' => $this->contentItemFiles[$pageSlug]]));

        $config['items'][$itemSlug] = [
            'label' => $itemLabel,
            'form'  => [],
        ];

        $this->saveContentItemConfigFile($config, $configPath);
        $this->reParseContentItemsConfig();
    }

    /**
     * @param $content
     * @param string $page
     * @param string $item
     *
     * @return array|bool|int|string
     * @throws
     */
    public function generateRepeaterConfig(&$content, string $page, string $item)
    {
        if (! $content || ! $page || ! $item)
            return false;

        $forms = [
            'label'  => studly_case($item),
            'fields' => [],
        ];

        if (is_array($content))
        {
            if ($content)
            {
                if (is_numeric(array_keys($content)[0]))
                {
                    $parseConfig = $this->repeaterConstructor($content, 'item', true);
                    $forms['fields'] = $parseConfig['item']['form']['fields'] ?? $parseConfig;
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
        if (isset($this->contentItemFiles[$page]))
        {
            if (isset($this->contentItemList[$page][$item]))
                return $content;

            $configPath = $this->contentItemsPath .'/'.$this->contentItemFiles[$page];
            $config = Yaml::parseFile($configPath);
        }
        else
        {
            $configPath = $this->contentItemsPath .'/config-'.$page.'.yaml';
            if (file_exists($configPath))
                $configPath = $this->contentItemsPath .'/config-'.$page.'-'.time().'.yaml';

            $config = [
                'menu' => [
                    'label' => studly_case($page),
                    'slug'  => camel_case($page),
                    'icon'  => 'icon-'.$page,
                    'order' => count($this->contentItemFiles)+1
                ],
                'repeater' => [],
            ];
        }

        # create repeater
        # =================
        if (! isset($config['repeater']))
            $config['repeater'] = [];
        $config['repeater'][$item] = $forms;

        $this->saveContentItemConfigFile($config, $configPath);
        $this->reParseContentItemsConfig();

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
                $firstKey = array_keys($val)[0];
                if (is_numeric($firstKey))
                {
                    $x1 = 0;
                    $isArr = false;
                    foreach ($val as &$dV)
                    {
                        if (++$x1 === 1)
                        {
                            $isArr = is_array($dV);
                            if ($isArr)
                            {
                                foreach ($dV as $d2K => &$d2V)
                                    $arrSubD[$d2K] = $this->repeaterConstructor($d2V, $d2K);
                            }
                            else
                                $arrSubD['item'] = $this->repeaterConstructor($dV, 'item');
                        }
                        else
                            $this->repeaterConstructor($dV, 'item');

                        if (! $isArr)
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

    /**
     * @param $config
     * @param $configPath
     *
     * @throws
     */
    private function saveContentItemConfigFile($config, $configPath)
    {
        @File::chmod($this->contentItemsPath);

        $configStr = Yaml::render($config);
        if (! File::put($configPath, $configStr))
            throw new SystemException(sprintf('Error saving file %s', $configPath));

        @File::chmod($configPath);
    }
}