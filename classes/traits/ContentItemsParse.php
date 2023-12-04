<?php

namespace ForWinterCms\Content\Classes\Traits;

use Db;
use Lang;
use File;
use Yaml;
use Event;
use Backend;
use Validator;
use Cms\Classes\Theme as CmsTheme;
use ForWinterCms\Content\Models\Item as ItemModel;
use ForWinterCms\Content\Models\Page as PageModel;
use Winter\Storm\Exception\SystemException;
use Winter\Storm\Exception\ValidationException;
use Winter\Storm\Exception\ApplicationException;

/**
 * ContentItemsParse trait
 *
 * @package ForWinterCms\Content\Classes\Traits
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
     * @var array - [page_slug => [item_slug => [title => item_name, section => section_name]]]
     */
    protected $contentItemList = [];

    /**
     * @var array - [item_slug => [title => item_name, partial => partial_file]]
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
                File::makeDirectory($directory);

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
            if (! $this->validateAlphaDash('file', $fileBasename, ['ascii']))
                continue;

            $config = Yaml::parseFile($file->getRealPath());

            if (! is_array($config) || empty($config['label']) || ! isset($config['form']))
                throw new ApplicationException(Lang::get('forwintercms.content::content.errors.section_config', ['fileName' => $file->getFilename()]));

            $this->contentItemSectionsList[$fileBasename] = [
                'title'   => $config['label'],
                'partial' => $config['partial'] ?? '',
            ];
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

            $menuSlug = $file->getBasename('.'.$fileExt);
            if (! $this->validateAlphaDash('file', $menuSlug, ['ascii']) || ! PageModel::slug($menuSlug)->count())
                continue;

            # content items
            #================
            $fileName = $file->getFilename();
            $config   = Yaml::parseFile($file->getRealPath());
            $errItem  = Lang::get('forwintercms.content::content.errors.pages_list', ['fileName' => $fileName]);

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
                        throw new ApplicationException(Lang::get('forwintercms.content::content.errors.no_section_file', ['sectionFile' => $item['section'].'.yaml']));
                }
                elseif (! isset($item['form']))
                    throw new ApplicationException($errItem);

                $this->contentItemList[$menuSlug][$rAction] = [
                    'title'   => $item['label'],
                    'section' => $item['section'] ?? '',
                ];
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
        $this->contentItemFiles = [];
        $this->contentItemList = [];
        $this->contentItemSectionsList = [];

        $this->parseContentItems();
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
     *              title => (required) menu title,
     *              slug  => (required) menu slug and\or URN slug,
     *              icon  => (optional) menu icon, default ''
     *              order => (optional) menu order, default '100'
     *          ]
     * @param string  $action    - create, clone or edit. Default 'create'
     * @param string  $old_slug  - (required for edit page) old menu slug and\or URN slug,
     * @throws
     */
    public function buildContentItemPage(array $pageAttr, string $action = self::CONTENT_ITEM_ACTION_CREATE, string $old_slug = null)
    {
        /*
         * Validate
         */

        Validator::extend('no_exists_page', function($attr, $value) use ($action, $old_slug)
        {
            if ($action === self::CONTENT_ITEM_ACTION_EDIT && $old_slug === $value)
                return true;
            return (! PageModel::slug($value)->count());
        });

        $pageTableName = PageModel::make()->getTable();
        $rules = [
            'old_slug' => 'required|alpha_dash|min:2|exists:'. $pageTableName .',slug',
            'title' => 'required',
            'slug'  => 'required|alpha_dash|between:2,255|no_exists_page',
            'icon'  => 'alpha_dash|min:2',
            'order' => 'numeric|min:-999|max:1000',
        ];
        $slug = $pageAttr['slug'] ?? '';

        if ($action === self::CONTENT_ITEM_ACTION_CREATE)
            unset($rules['old_slug']);
        else
            $pageAttr['old_slug'] = $old_slug;

        $validator = Validator::make($pageAttr, $rules, [
            'no_exists_page' => Lang::get('forwintercms.content::content.errors.no_exists_page', ['slug' => $slug]),
        ]);
        $validator->setAttributeNames([
            'title' => Lang::get('forwintercms.content::content.pages.field_title'),
            'slug'  => Lang::get('forwintercms.content::content.pages.field_slug'),
            'icon'  => Lang::get('forwintercms.content::content.pages.field_icon'),
            'order' => Lang::get('forwintercms.content::content.pages.field_order'),
        ]);

        if ($validator->fails())
            throw new ValidationException($validator);

        /*
         * Produce
         */

        Db::transaction(function() use (&$pageAttr, $action, $old_slug)
        {
            $configPath = null;
            $saveConfig = ['items' => []];
            $saveData   = [
                'title' => $pageAttr['title'],
                'slug'  => $pageAttr['slug'],
                'icon'  => $pageAttr['icon'],
                'order' => $pageAttr['order'],
            ];

            if ($action === self::CONTENT_ITEM_ACTION_CREATE)
                PageModel::create($saveData);
            else
            {
                if (isset($this->contentItemFiles[$old_slug]))
                {
                    $configPath = $this->contentItemsPagesPath .'/'. $this->contentItemFiles[$old_slug];
                    if (file_exists($configPath))
                    {
                        if ($action === self::CONTENT_ITEM_ACTION_EDIT && $old_slug !== $pageAttr['slug'])
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

                if ($action === self::CONTENT_ITEM_ACTION_CLONE)
                {
                    $configPath = null;
                    $page = PageModel::slug($old_slug)->first();

                    if (! $page)
                        PageModel::create($saveData);
                    else
                    {
                        $newPage = $page->replicate();
                        $newPage->title = $pageAttr['title'];
                        $newPage->slug  = $pageAttr['slug'];
                        $newPage->icon  = $pageAttr['icon'];
                        $newPage->order = $pageAttr['order'];
                        $newPage->save();

                        $newPageId = $newPage->id;
                        foreach (ItemModel::where('page_id', $page->id)->get() as $item)
                        {
                            $newItem = $item->replicate();
                            $newItem->page_id = $newPageId;
                            $newItem->save();
                        }
                    }
                }
                else
                    PageModel::slug($old_slug)->update($saveData);
            }

            if (! $configPath)
                $configPath = $this->newConfigFilePath($pageAttr['slug']);
            $pageId = PageModel::slug($pageAttr['slug'])->value('id');

            Event::fire('forwintercms.content.buildContentItemsPageSave.before', [&$saveConfig, $pageId, $action]);
            $this->saveContentItemConfigFile($saveConfig, $configPath);
            Event::fire('forwintercms.content.buildContentItemsPageSave.after', [&$saveConfig, $pageId, $action]);
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
            $pageId = PageModel::slug($pageSlug)->value('id');
            PageModel::slug($pageSlug)->delete();
            ItemModel::where('page_id', $pageId)->delete();

            if (isset($this->contentItemFiles[$pageSlug]))
            {
                $configPath = $this->contentItemsPagesPath .'/'. $this->contentItemFiles[$pageSlug];
                if (file_exists($configPath))
                    File::delete($configPath);
            }
        });
    }

    /**
     * Get content item page config path
     *
     * @param string $pageSlug
     * @return string
     * @throws
     */
    protected function getContentItemPageConfigPath(string $pageSlug)
    {
        $langErrPage = Lang::get('forwintercms.content::content.errors.no_page', ['pageSlug' => $pageSlug]);
        if (! $pageSlug || ! isset($this->contentItemFiles[$pageSlug]))
            throw new ApplicationException($langErrPage);

        $configPath = $this->contentItemsPagesPath .'/'. $this->contentItemFiles[$pageSlug];
        if (! file_exists($configPath))
            throw new ApplicationException($langErrPage);

        return $configPath;
    }

    /**
     * Get content item page config path
     *
     * @param string $pageSlug
     * @param string $configPath
     *
     * @return array
     * @throws
     */
    protected function getContentItemPageConfig(string $pageSlug, string $configPath)
    {
        $config = Yaml::parseFile($configPath);
        $fileName = $this->contentItemFiles[$pageSlug] ?? basename($configPath);
        if (! is_array($config) || ! isset($config['items']))
            throw new ApplicationException(Lang::get('forwintercms.content::content.errors.page_config', ['fileName' => $fileName]));

        return $config;
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
     *              item_key     => (optional) item key,
     *              section_name => (required for addType 'section') section file basename,
     *          ]
     *
     * @throws
     */
    public function addContentItem(string $pageSlug, string $itemSlug, string $addType, array $parameters = [])
    {
        if (! in_array($addType, ['new', 'ready', 'section']))
            throw new ApplicationException(Lang::get('forwintercms.content::content.errors.add_item_type'));

        # check page slug
        # ===================
        $configPath = $this->getContentItemPageConfigPath($pageSlug);

        # check item slug
        # ===================
        $saveItemSlug = empty($parameters['item_key']) ? $itemSlug : $parameters['item_key'];
        if (! $this->validateAlphaDash('itemSlug', $saveItemSlug, ['ascii']))
            throw new ApplicationException(Lang::get('forwintercms.content::content.errors.item_slug', ['itemSlug' => $saveItemSlug]));

        if (isset($this->contentItemList[$pageSlug][$saveItemSlug]))
        {
            if (ItemModel::item($pageSlug, $saveItemSlug)->count())
                throw new ApplicationException(Lang::get('forwintercms.content::content.errors.no_exists_item', ['itemSlug' => $saveItemSlug]));
            elseif ($addType != 'ready')
                throw new ApplicationException(Lang::get('forwintercms.content::content.errors.available_item', ['pageSlug' => $pageSlug, 'itemSlug' => $saveItemSlug]));
        }

        # item config
        # ==============
        $config = $this->getContentItemPageConfig($pageSlug, $configPath);
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
                $langErrSection = Lang::get('forwintercms.content::content.errors.no_item_tmp', ['itemSlug' => $sectionSlug]);
                if (empty($sectionSlug) || ! $this->validateAlphaDash('sectionSlug', $sectionSlug, ['ascii']))
                    throw new ApplicationException($langErrSection);
                elseif (! isset($this->contentItemSectionsList[$sectionSlug]))
                    throw new ApplicationException($langErrSection);
                elseif (! file_exists($this->contentItemsSectionsPath .'/'. $sectionSlug .'.yaml'))
                    throw new ApplicationException($langErrSection);

                if (! isset($addItemConfig['label']))
                    $addItemConfig['label'] = $this->contentItemSectionsList[$sectionSlug]['title'];
                $addItemConfig['section'] = $sectionSlug;
                break;

            case 'ready':
                if (isset($config['items'][$itemSlug]))
                {
                    $addItemConfig = $config['items'][$itemSlug];
                    unset($config['items'][$itemSlug]);
                }
                elseif (isset($config['items'][$saveItemSlug]))
                    $addItemConfig = $config['items'][$saveItemSlug];
                else
                    $addItemConfig['form'] = [];
                break;
        }

        # save
        # =======
        $config['items'][$saveItemSlug] = $addItemConfig;
        Db::transaction(function () use (&$config, $configPath, $pageSlug, $saveItemSlug)
        {
            $result = ItemModel::firstOrCreate([
                'page_id' => PageModel::getId($pageSlug),
                'name' => $saveItemSlug,
            ]);
            if (! isset($config['items'][$saveItemSlug]['label']))
                $config['items'][$saveItemSlug]['label'] = 'Block - '. ($result->id ?? 'item');
            $this->saveContentItemConfigFile($config, $configPath);
        });

        try {
            $this->reParseContentItems();
        }
        catch (\Exception $e){}
    }

    /**
     * Rename content item block
     *
     * @param string $pageSlug
     * @param string $itemSlug
     * @param array  $parameters
     *          [
     *              new_title => (required) new item title,
     *              new_slug => (required) new item slug,
     *          ]
     *
     * @return bool
     * @throws
     */
    public function renameContentItem(string $pageSlug, string $itemSlug, array $parameters = [])
    {
        /*
         * Validate
         */
        $newTitle = $parameters['new_title'] ?? '';
        $newSlug  = $parameters['new_slug'] ?? '';
        if ((empty($newTitle) && empty($newSlug)) || ! is_string($newTitle) || ! is_string($newSlug))
            return false;

        $configPath = $this->getContentItemPageConfigPath($pageSlug);

        if (!$itemSlug || ! isset($this->contentItemList[$pageSlug][$itemSlug]))
            throw new ApplicationException(Lang::get('forwintercms.content::content.errors.no_item', ['itemSlug' => $itemSlug]));

        $rules   = [];
        $isTitle = (! empty($newTitle) && $newTitle !== $this->contentItemList[$pageSlug][$itemSlug]['title']);
        $isSlug  = (! empty($newSlug) && $newSlug !== $itemSlug);
        if (! $isTitle && ! $isSlug)
            return true;

        if ($isTitle)
            $rules['title'] = 'required|between:2,255';
        if ($isSlug)
            $rules['name'] = 'required|between:2,255|alpha_dash|no_exists_item';

        Validator::extend('no_exists_item', function($attr, $value) use ($pageSlug) {
            return ($value && ! isset($this->contentItemList[$pageSlug][$value]) && ! ItemModel::item($pageSlug, $value)->count());
        });
        $validator = Validator::make([
            'title' => $newTitle,
            'name'  => $newSlug,
        ], $rules, [
            'no_exists_item' => Lang::get('forwintercms.content::content.errors.no_exists_item', ['itemSlug' => $newSlug]),
        ]);
        $validator->setAttributeNames([
            'title' => Lang::get('forwintercms.content::content.items.title_label'),
            'name'  => Lang::get('forwintercms.content::content.items.name_label'),
        ]);
        if ($validator->fails())
            throw new ValidationException($validator);

        /*
         * Save
         */
        $config = $this->getContentItemPageConfig($pageSlug, $configPath);
        if ($isTitle)
            $config['items'][$itemSlug]['label'] = $newTitle;
        if ($isSlug)
        {
            $config['items'][$newSlug] = $config['items'][$itemSlug];
            unset($config['items'][$itemSlug]);
        }

        Db::transaction(function () use (&$config, $configPath, $pageSlug, $itemSlug, $newSlug, $isSlug)
        {
            if ($isSlug)
            {
                ItemModel::item($pageSlug, $itemSlug)->update([
                    'name' => $newSlug,
                ]);
            }
            $this->saveContentItemConfigFile($config, $configPath);
        });

        try {
            $this->reParseContentItems();
        }
        catch (\Exception $e){}
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
     * @param array  $config
     * @param string $configPath
     * @param string $newConfigPath
     *
     * @throws
     */
    private function saveContentItemConfigFile(array $config, string $configPath, string $newConfigPath = NULL)
    {
        @File::chmod($this->contentItemsPagesPath);

        $configStr = Yaml::render($config);
        if (! File::put($configPath, $configStr))
            throw new SystemException(sprintf('Error saving file %s', $configPath));
        if ($newConfigPath && ! File::put($configPath, $newConfigPath))
            throw new SystemException(sprintf('Error rename file %s', $configPath));

        @File::chmod($configPath);
    }

    private function newConfigFilePath(string $pageSlug)
    {
        return $this->contentItemsPagesPath .'/'. $pageSlug .'.yaml';
    }
}