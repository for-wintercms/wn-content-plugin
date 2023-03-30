<?php

namespace ForWinterCms\Content\Updates;

use Schema;
use October\Rain\Database\Schema\Blueprint;
use October\Rain\Database\Updates\Migration;
use ForWinterCms\Content\Classes\Interfaces\ContentItems;
use ForWinterCms\Content\Models\Page as PageModel;

class TableCreateForwintercmsContentPages extends Migration implements ContentItems
{
    use \ForWinterCms\Content\Classes\Traits\ContentItemsParse;

    public function up()
    {
        Schema::create('forwintercms_content_pages', function(Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->string('title', 255);
            $table->string('slug', 255)->unique();
            $table->string('icon', 127)->nullable();
            $table->integer('order')->nullable()->default(0);
            $table->timestamps();
        });

        try {
            $this->buildContentItemsPaths();
            $this->updatePageAttr();
        }
        catch (\Exception $e) {}
    }

    public function down()
    {
        Schema::dropIfExists('forwintercms_content_pages');
    }

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
