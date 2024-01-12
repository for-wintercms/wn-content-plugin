<?php

namespace ForWinterCms\Content\Models;

use Model;
use Winter\Storm\Database\Builder;
use ForWinterCms\Content\Classes\ContentItems;
use ForWinterCms\Content\Models\Page as PageModel;

/**
 * Item model
 *
 * @package ForWinterCms\Content\Models
 */
class Item extends Model
{
    const TRANSLATE_ITEM_TABLE_NAME = 'forwn_content_translate_items';

    use \Winter\Storm\Database\Traits\Validation;
    use \Winter\Storm\Database\Traits\Sortable;

    public $implement = [];

    public $table = 'forwn_content_items';

    protected $jsonable = ['items'];

    public $fillable = ['page_id', 'name', 'items'];

    /**
     * @var array Validation rules
     */
    public $rules = [
        'page_id' => 'required|numeric',
        'name'    => 'required|between:1,256|alpha_dash',
    ];

    public $attributeNames = [
        'name' => 'forwintercms.content::content.items.name_label',
    ];

    /*
     * Scopes
     */

    public function scopePage(Builder $query, string $page)
    {
        $query->where('page_id', PageModel::where('slug', $page)->value('id'));
    }

    public function scopeItem(Builder $query, string $page, string $name)
    {
        $query->page($page)->where('name', $name);
    }

    /*
     * Dropdown
     */

    public function formListItems($keyValue = null, $fieldName = null)
    {
        $return = [
            '' => '---'
        ];

        if ($this->page_id && $this->name)
        {
            $list = self::where('page_id', $this->page_id)->where('name', '!=', $this->name)->lists('name', 'name');
            if (! $list)
                return $return;

            $return = array_merge($return, $list);
            $items = ContentItems::instance();
            $itemsList = $items->getItemsList(PageModel::where('id', $this->page_id)->value('slug'));

            if (count($itemsList))
            {
                foreach ($return as &$item)
                {
                    if (isset($itemsList[$item]))
                        $item = $itemsList[$item]['title'];
                }
            }
        }

        return $return;
    }
}
