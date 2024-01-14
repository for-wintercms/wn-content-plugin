<?php

namespace ForWinterCms\Content\Models;

use DB;
use App;
use Event;
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

    public function newQuery()
    {
        $query = parent::newQuery();

        // Avoid to only query the x and y columns
        if (empty($query->getQuery()->columns))
            $query->addSelect(DB::raw($this->getTable().'.*'));

        // add page name column
        $query->leftJoin('forwn_content_pages as pg', 'pg.id', '=', $this->getTable().'.page_id');
        $query->addSelect(DB::raw('pg.slug as page'));

        return $query;
    }

    public function scopePage(Builder $query, string $page): void
    {
        $query->where('page_id', PageModel::where('slug', $page)->value('id'));
    }

    public function scopeItem(Builder $query, string $page, string $name): void
    {
        $query->page($page)->where('name', $name);
    }

    public function scopeItemsLang(Builder $query, ?string $lang = null): void
    {
        if (! $lang)
        {
            $getLang = Event::fire('forwintercms.content.locale', [], true);
            if (! empty($getLang) && is_string($getLang))
                $lang = $getLang;
            else
            {
                $lang = App::getLocale();
                if (empty($lang))
                    return;
            }
        }

        $query->leftJoin(DB::raw($this::TRANSLATE_ITEM_TABLE_NAME.' as ti'), function ($join) use ($lang) {
            $join->on('ti.item_id', '=', $this->getTable().'.id')
                ->where('ti.locale', '=', $lang);
        });
        $query->addSelect('ti.items as translate_items');
        $query->addSelect(DB::raw("'$lang' as lang"));
    }

    /*
     * Events
     */

    /**
     * Event "afterFetch"
     */
    public function afterFetch()
    {
        // correction of translatable fields
        if (! empty($this->translate_items))
        {
            $this->items = ($this->items ?: []) + (@json_decode($this->translate_items, true) ?: []);
            $this->offsetUnset('translate_items');
            unset($this->original['translate_items']);
            $this->original['items'] = '';
        }

        // correcting attributes in items
        if (! empty($this->items))
        {
            $contentItems = ContentItems::instance();
            $this->items = array_intersect_key($this->items, array_flip($contentItems->getContentItemIncludeFields($this->page, $this->name)));
        }
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
