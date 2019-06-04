<?php

namespace Wbry\Content\Updates;

use DB;
use Schema;
use Wbry\Content\Models\Item as ItemModel;
use Wbry\Content\Models\Page as PageModel;
use October\Rain\Database\Schema\Blueprint;
use October\Rain\Database\Updates\Migration;

class TableUpdateWbryContentItems extends Migration
{
    public static $tableItems = 'wbry_content_items';

    public function up()
    {
        if (Schema::hasColumn(self::$tableItems, 'page'))
        {
            # add new column
            Schema::table(self::$tableItems, function (Blueprint $table) {
                $table->integer('page_id')->after('page');
                $table->unique(['page_id', 'name']);
            });

            # convert page names
            try {
                $items = DB::table('wbry_content_items')->select('page')->distinct()->get();
                $savePages = [];
                foreach ($items as $item)
                    $savePages[] = ['slug' => $item->page];
                if ($savePages)
                {
                    DB::transaction(function() use ($savePages)
                    {
                        PageModel::insert($savePages);
                        foreach (PageModel::lists('slug', 'id') as $id => $page)
                            ItemModel::where('page', $page)->update(['page_id' => $id]);
                    });
                }
            }
            catch (\Exception $e) {}

            # drop old column
            Schema::table(self::$tableItems, function (Blueprint $table) {
                $table->dropUnique(['page', 'name']);
                $table->dropColumn('page');
            });
        }
    }

    public function down()
    {}
}
