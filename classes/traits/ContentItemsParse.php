<?php

namespace Wbry\Content\Classes\Traits;

use Db;
use Lang;
use File;
use Yaml;
use Backend;
use Validator;
use Cms\Classes\Theme as CmsTheme;
use Wbry\Content\Models\Item as ItemModel;
use October\Rain\Exception\SystemException;
use October\Rain\Exception\ValidationException;
use October\Rain\Exception\ApplicationException;

/**
 * ContentItemsParse trait
 *
 * @package Wbry\Content\Classes\Traits
 * @author Wbry, Diamond <me@diamondsystems.org>
 */
trait ContentItemsParse
{
    use \Illuminate\Validation\Concerns\ValidatesAttributes;

    /**
     * @var string - dir name for content item configs (content_items_dir)
     */
    protected static $contentItemsDir = 'content-items';

    /**
     * @var string - config files path (domain_path/themes/your_theme/content_items_dir)
     */
    protected $contentItemsPath = null;
    protected $contentItemsPagesPath = null;
    protected $contentItemsSectionsPath = null;
    protected $contentItemsContentPath = null;

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
     * @var array - [item_slug => item_name]
     */
    protected $contentItemSectionsList = [];

    private $isContentItemsParse = false;

    /*
     * Parse
     */

    /**
     * @return bool
     * @throws
     */
    private function buildContentItemsPaths()
    {
        # main path
        # ======================
        if (! $this->contentItemsPath || ! File::isDirectory($this->contentItemsPath))
        {
            $directory = CmsTheme::getActiveTheme()->getPath().'/'.self::$contentItemsDir;

            if (! File::isDirectory($directory))
                return false;

            $this->contentItemsPath = $directory;
        }
        $this->contentItemsPath = rtrim($this->contentItemsPath, '/');

        # pages, sections and contents paths
        # ====================================
        $directories = [
            'contentItemsPagesPath'    => 'pages',
            'contentItemsSectionsPath' => 'sections',
            'contentItemsContentPath'  => 'contents',
        ];
        foreach ($directories as $dirK => $dirV)
        {
            $path = $this->{$dirK};
            if (! $path || ! File::isDirectory($path))
            {
                $this->{$dirK} = $path = rtrim($this->contentItemsPath, '/') .'/'. $dirV;

                if (! File::isDirectory($path))
                    File::makeDirectory($path);
            }
        }

        return true;
    }

    /**
     * @throws
     */
    private function parseContentItemSections()
    {
        foreach (File::files($this->contentItemsSectionsPath) as $file)
        {
            $fileExt = $file->getExtension();
            if ($fileExt !== 'yaml')
                continue;

            $fileBasename = $file->getBasename('.'.$fileExt);
            if (! $this->validateAlphaDash('file', $fileBasename))
                continue;

            $config = Yaml::parseFile($file->getRealPath());

            if (! is_array($config) || empty($config['label']) || ! isset($config['form']))
                throw new ApplicationException(Lang::get('wbry.content::content.errors.section_config', ['fileName' => $file->getFilename()]));

            $this->contentItemSectionsList[$fileBasename] = $config['label'];
        }
    }

    /**
     * @throws
     */
    private function parseContentItemPages()
    {
        foreach (File::files($this->contentItemsPagesPath) as $file)
        {
            $fileExt = $file->getExtension();
            if ($fileExt !== 'yaml')
                continue;

            $fileName = $file->getFilename();
            $config   = Yaml::parseFile($file->getRealPath());

            # menu
            #========
            if (! is_array($config) || empty($config['menu']) || empty($config['menu']['label']) || empty($config['menu']['slug']))
                throw new ApplicationException(Lang::get('wbry.content::content.errors.pages_menu', ['fileName' => $fileName]));

            $menuSlug = $config['menu']['slug'];
            if (! $this->validateAlphaDash('slug', $menuSlug))
                throw new ApplicationException(Lang::get('wbry.content::content.errors.file_item_slug', ['fileName' => $fileName, 'itemSlug' => $menuSlug]));

            $this->menuList[$menuSlug] = array_merge($config['menu'], [
                'url' => Backend::url('wbry/content/items/'. $menuSlug),
            ]);

            # content items
            #================
            $errItem = Lang::get('wbry.content::content.errors.pages_list', ['fileName' => $fileName]);

            if (! isset($config['items']) || ! is_array($config['items']))
                throw new ApplicationException($errItem);

            $this->contentItemFiles[$menuSlug] = $fileName;
            $this->contentItemList[$menuSlug]  = [];

            foreach ($config['items'] as $rAction => $item)
            {
                if (empty($rAction) || empty($item['label']))
                    throw new ApplicationException($errItem);
                elseif (isset($item['section']))
                {
                    if (empty($item['section']) || ! isset($this->contentItemSectionsList[$item['section']]))
                        throw new ApplicationException(Lang::get('wbry.content::content.errors.no_section_file', ['sectionFile' => $item['section'].'.yaml']));
                }
                elseif (! isset($item['form']))
                    throw new ApplicationException($errItem);

                $this->contentItemList[$menuSlug][$rAction] = $item['label'];
            }
        }
    }

    /**
     * @throws
     */
    protected function parseContentItems()
    {
        if ($this->isContentItemsParse || ! $this->buildContentItemsPaths())
            return;

        $this->parseContentItemSections();
        $this->parseContentItemPages();

        $this->isContentItemsParse = true;
    }

    /**
     * @throws
     */
    public function reParseContentItems()
    {
        $this->isContentItemsParse = false;
        $this->menuList = null;
        $this->contentItemFiles = [];
        $this->contentItemList = [];
        $this->contentItemSectionsList = [];

        $this->parseContentItems();
    }

    /**
     * @return string
     */
    public function getContentItemDefaultPage()
    {
        $page = 'index';
        if (! $this->menuList || isset($this->menuList[$page]))
            return $page;

        $min = null;
        foreach ($this->menuList as $menu)
        {
            if (isset($menu['order']) && is_numeric($menu['order']) && (is_null($min) || $menu['order'] < $min))
            {
                $page = $menu['slug'];
                $min  = $menu['order'];
            }
        }
        return $page;
    }

    /**
     * Get active content item form
     *
     * @param $pageSlug
     * @param $itemSlug
     * @return array|null
     */
    public function getActiveContentItemForm(string $pageSlug, string $itemSlug)
    {
        if (empty($pageSlug) || empty($itemSlug) || ! isset($this->contentItemFiles[$pageSlug]))
            return null;

        $filePath = $this->contentItemsPagesPath .'/'. $this->contentItemFiles[$pageSlug];
        if (! file_exists($filePath))
            return null;

        $config = Yaml::parseFile($filePath);
        if (is_array($config))
        {
            if (isset($config['items'][$itemSlug]['form']))
                return $config['items'][$itemSlug]['form'];
            else
            {
                $section = $config['items'][$itemSlug]['section'] ?? '';
                if (empty($section) || ! is_string($section))
                    return null;

                $sectionPath = $this->contentItemsSectionsPath .'/'. $section .'.yaml';
                if (! file_exists($sectionPath))
                    return null;

                $sectionConfig = Yaml::parseFile($sectionPath);
                if (! is_array($sectionConfig) || ! isset($sectionConfig['form']))
                    return null;

                return $sectionConfig['form'];
            }
        }

        return null;
    }

    /**
     * Create or edit content item page
     *
     * @param array  $pageAttr
     *          [
     *              label => (required) menu title,
     *              slug  => (required) menu slug and\or URN slug,
     *              icon  => (optional) menu icon, default ''
     *              order => (optional) menu order, default '100'
     *          ]
     * @param bool    $isEditPage - default false = create page
     * @param string  $old_slug   - (required for edit page) old menu slug and\or URN slug,
     * @throws
     */
    public function buildContentItemPage(array $pageAttr, bool $isEditPage = false, string $old_slug = null)
    {
        /*
         * Validate
         */

        Validator::extend('check_old_slug', function($attr, $value) {
            return (! empty($value) && isset($this->menuList[$value]));
        });

        Validator::extend('no_exists_page', function($attr, $value) use ($isEditPage, $old_slug)
        {
            if ($isEditPage && $old_slug === $value)
                return true;
            return (! isset($this->menuList[$value]));
        });

        $rules = [
            'old_slug' => 'required|alpha_dash|min:2|check_old_slug',
            'title' => 'required',
            'slug'  => 'required|alpha_dash|between:2,255|no_exists_page',
            'icon'  => 'alpha_dash|min:2',
            'order' => 'numeric|min:-999|max:1000',
        ];
        $slug = $pageAttr['slug'] ?? '';

        if ($isEditPage)
            $pageAttr['old_slug'] = $old_slug;
        else
            unset($rules['old_slug']);

        $validator = Validator::make($pageAttr, $rules, [
            'check_old_slug' => Lang::get('wbry.content::content.errors.exists_old_page', ['slug' => $old_slug]),
            'no_exists_page' => Lang::get('wbry.content::content.errors.no_exists_page', ['slug' => $slug]),
        ]);
        $validator->setAttributeNames([
            'title' => Lang::get('wbry.content::content.popup.page.field_title'),
            'slug'  => Lang::get('wbry.content::content.popup.page.field_slug'),
            'icon'  => Lang::get('wbry.content::content.popup.page.field_icon'),
            'order' => Lang::get('wbry.content::content.popup.page.field_order'),
        ]);

        if ($validator->fails())
            throw new ValidationException($validator);

        /*
         * Produce
         */

        Db::transaction(function () use (&$pageAttr, $isEditPage, $old_slug)
        {
            $configPath = null;
            $saveConfig = ['menu' => [], 'items' => []];

            if ($isEditPage)
            {
                if (isset($this->contentItemFiles[$old_slug]))
                {
                    $configPath = $this->contentItemsPagesPath .'/'. $this->contentItemFiles[$old_slug];
                    if (file_exists($configPath))
                    {
                        if ($old_slug !== $pageAttr['slug'])
                        {
                            $oldConfigPath = $configPath;
                            $configPath    = $this->newConfigFilePath($pageAttr['slug']);

                            File::move($oldConfigPath, $configPath);
                        }

                        $parseConfigs = Yaml::parseFile($configPath);
                        if (is_array($parseConfigs))
                            $saveConfig = array_merge($saveConfig, $parseConfigs);
                    }
                    else
                        $configPath = null;
                }

                ItemModel::where('page', $old_slug)->update(['page' => $pageAttr['slug']]);
            }

            if (! $configPath)
                $configPath = $this->newConfigFilePath($pageAttr['slug']);

            $saveConfig['menu'] = [
                'label' => $pageAttr['title'],
                'slug'  => $pageAttr['slug'],
                'icon'  => $pageAttr['icon'] ?? '',
                'order' => $pageAttr['order'] ?? '100',
            ];

            $this->saveContentItemConfigFile($saveConfig, $configPath);
        });

        try {
            $this->reParseContentItems();
        }
        catch (\Exception $e){}
    }

    /**
     * Delete content item page
     *
     * @param string  $pageSlug
     * @throws
     */
    public function deleteContentItemPage(string $pageSlug)
    {
        if (empty($pageSlug))
            return;

        Db::transaction(function () use ($pageSlug)
        {
            ItemModel::where('page', $pageSlug)->delete();

            if (isset($this->contentItemFiles[$pageSlug]))
            {
                $configPath = $this->contentItemsPagesPath .'/'. $this->contentItemFiles[$pageSlug];
                if (file_exists($configPath))
                    File::delete($configPath);
            }
        });
    }

    /**
     * Add content item
     *
     * @param string $pageSlug
     * @param string $itemSlug
     * @param string $addType   - 'new', 'ready' or 'section'. Default: 'new'
     * @param array  $parameters
     *          [
     *              item_title   => (optional) item title,
     *              section_name => (required for addType 'section') section file basename,
     *          ]
     *
     * @throws
     */
    public function addContentItem(string $pageSlug, string $itemSlug, string $addType, array $parameters = [])
    {
        if (! in_array($addType, ['new', 'ready', 'section']))
            throw new ApplicationException(Lang::get('wbry.content::content.errors.add_item_type'));

        # check page slug
        # ===================
        $langErrPage = Lang::get('wbry.content::content.errors.no_page', ['pageSlug' => $pageSlug]);
        if (! $pageSlug || ! isset($this->contentItemFiles[$pageSlug]))
            throw new ApplicationException($langErrPage);

        $configPath = $this->contentItemsPagesPath .'/'. $this->contentItemFiles[$pageSlug];
        if (! file_exists($configPath))
            throw new ApplicationException($langErrPage);

        # check item slug
        # ===================
        if (! $this->validateAlphaDash('itemSlug', $itemSlug))
            throw new ApplicationException(Lang::get('wbry.content::content.errors.item_slug', ['itemSlug' => $itemSlug]));

        if (isset($this->contentItemList[$pageSlug][$itemSlug]))
        {
            if (ItemModel::item($pageSlug, $itemSlug)->count())
                throw new ApplicationException(Lang::get('wbry.content::content.errors.no_exists_item', ['itemSlug' => $itemSlug]));
            elseif ($addType != 'ready')
                throw new ApplicationException(Lang::get('wbry.content::content.errors.available_item', ['pageSlug' => $pageSlug, 'itemSlug' => $itemSlug]));
        }

        # item config
        # ==============
        if ($addType != 'ready')
        {
            $config = Yaml::parseFile($configPath);
            if (! is_array($config) || ! isset($config['items']))
                throw new ApplicationException(Lang::get('wbry.content::content.errors.page_config', ['fileName' => $this->contentItemFiles[$pageSlug]]));

            $addItemConfig = [];
            if (! empty($parameters['item_title']) && is_string($parameters['item_title']))
                $addItemConfig['label'] = $parameters['item_title'];

            switch ($addType)
            {
                case 'new':
                    $addItemConfig['form'] = [];
                    break;

                case 'section':
                    $sectionSlug = $parameters['section_name'] ?? '';
                    $langErrSection = Lang::get('wbry.content::content.errors.no_item_tmp', ['itemSlug' => $sectionSlug]);
                    if (empty($sectionSlug) || ! $this->validateAlphaDash('sectionSlug', $sectionSlug))
                        throw new ApplicationException($langErrSection);
                    elseif (! isset($this->contentItemSectionsList[$sectionSlug]))
                        throw new ApplicationException($langErrSection);
                    elseif (! file_exists($this->contentItemsSectionsPath .'/'. $sectionSlug .'.yaml'))
                        throw new ApplicationException($langErrSection);

                    if (! isset($addItemConfig['label']))
                        $addItemConfig['label'] = $this->contentItemSectionsList[$sectionSlug];
                    $addItemConfig['section'] = $sectionSlug;
                    break;
            }

            # save
            # =======
            $config['items'][$itemSlug] = $addItemConfig;
            Db::transaction(function () use (&$config, $configPath, $pageSlug, $itemSlug)
            {
                $result = ItemModel::firstOrCreate([
                    'page' => $pageSlug,
                    'name' => $itemSlug,
                ]);
                if (! isset($config['items'][$itemSlug]['label']))
                    $config['items'][$itemSlug]['label'] = 'Block - '. ($result->id ?? 'item');
                $this->saveContentItemConfigFile($config, $configPath);
            });

            try {
                $this->reParseContentItems();
            }
            catch (\Exception $e){}
        }
        else
        {
            # save
            # =======
            ItemModel::create([
                'page' => $pageSlug,
                'name' => $itemSlug,
            ]);
        }
    }

    /**
     * Delete content items
     *
     * @param string $pageSlug
     * @param array  $itemSlugList
     * @throws
     */
    public function deleteContentItems(string $pageSlug, array $itemSlugList)
    {
        if (! $pageSlug || ! isset($this->contentItemFiles[$pageSlug]) || ! count($itemSlugList))
            return;

        $configPath = $this->contentItemsPagesPath .'/'. $this->contentItemFiles[$pageSlug];
        if (! file_exists($configPath))
            return;

        $config = Yaml::parseFile($configPath);
        if (! is_array($config) || ! isset($config['items']))
            return;

        $itemsCnt = count($config['items']);
        foreach ($itemSlugList as $itemSlug)
        {
            if (! $itemSlug || ! isset($config['items'][$itemSlug]))
                continue;
            unset($config['items'][$itemSlug]);
        }

        if ($itemsCnt != count($config['items']))
        {
            $this->saveContentItemConfigFile($config, $configPath);
            try {
                $this->reParseContentItems();
            }
            catch (\Exception $e){}
        }
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
        $this->reParseContentItems();

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
     * @param array  $config
     * @param string $configPath
     *
     * @throws
     */
    private function saveContentItemConfigFile(array $config, string $configPath)
    {
        @File::chmod($this->contentItemsPagesPath);

        $configStr = Yaml::render($config);
        if (! File::put($configPath, $configStr))
            throw new SystemException(sprintf('Error saving file %s', $configPath));

        @File::chmod($configPath);
    }

    private function newConfigFilePath(string $pageSlug)
    {
        $configPath = $this->contentItemsPagesPath .'/'. $pageSlug .'.yaml';
        if (file_exists($configPath))
            $configPath = $this->contentItemsPagesPath .'/'. $pageSlug .'_'. str_random(8) .'.yaml';

        return $configPath;
    }
}