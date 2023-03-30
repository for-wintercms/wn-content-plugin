<?php

namespace ForWinterCms\Content\Updates;

use Schema;
use Winter\Storm\Database\Schema\Blueprint;
use Winter\Storm\Database\Updates\Migration;
use ForWinterCms\Content\Classes\Interfaces\ContentItems;

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
        }
        catch (\Exception $e) {}
    }

    public function down()
    {
        Schema::dropIfExists('forwintercms_content_pages');
    }
}
