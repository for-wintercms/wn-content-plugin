<?php

namespace Wbry\Content\Updates;

use Yaml;
use File;
use Lang;
use Schema;
use ApplicationException;
use Wbry\Content\Classes\Interfaces\ContentItems;

use October\Rain\Database\Schema\Blueprint;
use October\Rain\Database\Updates\Migration;

use Wbry\Content\Models\Page as PageModel;

class TableUpdateWbryContentPages extends Migration implements ContentItems
{
    use \Wbry\Content\Classes\Traits\ContentItemsParse;

    public static $tableItems = 'wbry_content_pages';

    public function up()
    {
        Schema::table(self::$tableItems, function(Blueprint $table)
        {
            $table->string('title', 255)->after('id');
            $table->string('icon', 127)->nullable();
            $table->integer('order')->nullable()->default(0);
        });

        try {
            $this->buildContentItemsPaths();
            $this->updatePageAttr();
        }
        catch (\Exception $e) {}
    }

    public function down()
    {}

    protected function updatePageAttr()
    {
        foreach (File::files($this->contentItemsPagesPath) as $file)
        {
            $fileExt = $file->getExtension();
            if ($fileExt !== 'yaml')
                continue;

            $fileName = $file->getFilename();
            $realPath = $file->getRealPath();
            $config   = Yaml::parseFile($realPath);

            # menu
            #========
            if (! is_array($config) || empty($config['menu']) || empty($config['menu']['label']) || empty($config['menu']['slug']))
                throw new ApplicationException('Correctly declare the page menu item in the file'.$fileName);

            $menuSlug = $config['menu']['slug'];
            if (! $this->validateAlphaDash('slug', $menuSlug))
                throw new ApplicationException('Invalid syntax in section name "'. $fileName .'" -> "'. $menuSlug .'"');

            PageModel::slug($menuSlug)->update([
                'title' => $config['menu']['label'],
                'icon'  => $config['menu']['icon'],
                'order' => (int)$config['menu']['order'],
            ]);
            unset($config['menu']);

            $this->saveContentItemConfigFile($config, $realPath);
        }
    }
}
