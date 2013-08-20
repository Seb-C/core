<?php
/**
 * NOVIUS OS - Web OS for digital communication
 *
 * @copyright  2011 Novius
 * @license    GNU Affero General Public License v3 or (at your option) any later version
 *             http://www.gnu.org/licenses/agpl-3.0.html
 * @link http://www.novius-os.org
 */

namespace Nos;

class Orm_Behaviour_Urlenhancer extends Orm_Behaviour
{
    /**
     * enhancers
     */
    protected $_properties = array();

    public function dataset(Orm\Model $item, &$dataset)
    {
        if (!isset($dataset['preview_url'])) {
            $dataset['preview_url'] = array($this, 'preview_url');
        }
    }
    /**
     * Returns an array of all available URL for this item. The array contains:
     *
     *   page_id::item_slug => full_url (relative to base)
     *
     * This way, we get all the informations we want:
     *  - The page ID
     *  - The URL generated by the enhancer
     *  - The current full URL (current page URL + enhancer part)
     *
     * When retrieving only the first URL, we get only the first value (current full URL, without page_id).
     *
     * If there's no result, the function will return empty array()
     *
     * @param  \Nos\Orm\Model    $item
     * @param  array             $params
     * @return array
     */
    public function urls($item, $params = array())
    {
        $urls = array();
        $enhancers = $this->_properties['enhancers'];
        if (!empty($params['enhancer'])) {
            if (in_array($params['enhancer'], $enhancers)) {
                $enhancers = array($params['enhancer']);
            }
            unset($params['enhancer']);
        }
        foreach ($enhancers as $enhancer_name) {
            foreach (\Nos\Tools_Enhancer::url_item($enhancer_name, $item, $params) as $key => $url) {
                $urls[$key] = $url;
            }
        }

        return $urls;
    }

    /**
     * This is an alias for `$item->url(array('canonical' => true));`.
     *
     * If the item has Behaviour_Sharable, it will return the URL configured in the shared data (content nugget)
     *
     * @param type $item
     * @param array $params
     * @return  @see url()
     */
    public function url_canonical($item, $params = array())
    {
        $params['canonical'] = true;

        return $this->url($item, $params);
    }

    /**
     * Returns a valid URL for the item.
     *
     * @param  \Nos\Orm\Model    $item
     * @param  array             $params
     * @return null|string       Full URL (relative to base). null if the item is not displayed.
     */
    public function url($item, $params = array())
    {
        $canonical = \Arr::get($params, 'canonical', false);
        unset($params['canonical']);
        if ($canonical) {
            unset($params['urlPath']);
        }

        // Front-office (enhancer context) = calculate urlPath based on current page, except if canonical URL was asked for
        if (!$canonical && ($main_controller = \Nos\Nos::main_controller()) instanceof \Nos\Controller_Front) {
            $page_id = $main_controller->getPage()->page_id;
        }

        // Real canonical URL can only be computed using default sharable data
        if ($canonical && $item::behaviours('Nos\Orm_Behaviour_Sharable')) {
            $default_nuggets = $item->get_catcher_nuggets(Model_Content_Nuggets::DEFAULT_CATCHER);
            $nuggets = $default_nuggets->content_data;
            $nugget_url = \Arr::get($nuggets, \Nos\DataCatcher::TYPE_URL, false);
            if (!empty($nugget_url)) {
                list($page_id, $itemPath) = explode('::', $nugget_url);
            }
        }

        if (!empty($page_id)) {
            $page_enhanced = \Nos\Config_Data::get('page_enhanced', array());

            // The page should contain a valid enhancer for the current item
            foreach ($this->_properties['enhancers'] as $enhancer_name) {
                $page_contains_enhancer = !empty($page_enhanced[$enhancer_name][$page_id]);
                if ($page_contains_enhancer) {
                    break;
                }
            }
            if ($page_contains_enhancer) {
                $url_enhanced = \Nos\Config_Data::get('url_enhanced', array());
                $page_params = \Arr::get($url_enhanced, $page_id, false);
                if ($page_params) {
                    $params['urlPath'] = Tools_Url::context($page_params['context']).$page_params['url'];
                } else {
                    // This page does not contain an enhancer anymore... Can't use it to generate the canonical URL...
                }
            }
        }

        $urls = $this->urls($item, $params);
        return reset($urls) ?: null;
    }

    public function preview_url($item)
    {
        return $item->url_canonical(array('preview' => true));
    }

    public function after_save(Orm\Model $item)
    {
        $this->deleteCacheItem($item);
    }

    public function after_delete(Orm\Model $item)
    {
        $this->deleteCacheItem($item);
    }

    /**
     * Delete the cache of the pages containing an URL enhancer for the item.
     * Warning: this will delete for all the enhancer, not only the pages containing the item.
     *
     * @param Orm\Model $item
     */
    public function deleteCacheEnhancers(Orm\Model $item)
    {
        $page_ids = array();
        foreach ($this->_properties['enhancers'] as $enhancer_name) {
            foreach (Tools_Enhancer::url_item($enhancer_name, $item) as $key => $url) {
                list($page_id, ) = explode('::', $key);
                $page_ids[] = $page_id;
            }
        }

        if (!empty($page_ids)) {
            $pages = \Nos\Page\Model_Page::find('all', array(
                'where' => array(
                    array('page_id', 'IN', $page_ids),
                ),
            ));
            foreach ($pages as $page) {
                $page->delete_cache();
            }
        }
    }

    /**
     * Delete the cache of the pages containing the item.
     *
     * @param Orm\Model $item
     */
    public function deleteCacheItem(Orm\Model $item)
    {
        $base = \Uri::base(false);
        foreach ($this->_properties['enhancers'] as $enhancer_name) {
            foreach (Tools_Enhancer::url_item($enhancer_name, $item) as $key => $url) {

                $cache_path = \Nos\FrontCache::getPathFromUrl($base, parse_url($url, PHP_URL_PATH));
                \Nos\FrontCache::forge($cache_path)->delete();
            }
        }
    }

    /**
     * Returns an HTML anchor tag with, by default, item URL in href and item title in text.
     *
     * If key 'href' is set in $attributes parameter :
     * - if is a string, used for href attribute
     * - if is an array, used as argument of ->url() method
     *
     * If key 'text' is set in $attributes parameter, its value replace item title
     *
     * @param Orm\Model $item
     * @param array $attributes Array of attributes to be applied to the anchor tag.
     * @return string
     */
    public function htmlAnchor(Orm\Model $item, array $attributes = array())
    {
        $text = \Arr::get($attributes, 'text', e($item->title_item()));
        \Arr::delete($attributes, 'text');

        $href = \Arr::get($attributes, 'href', $item->url());
        if (is_array($href)) {
            $href = $item->url($href);
        }
        $href = Tools_Url::encodePath($href);
        \Arr::delete($attributes, 'href');

        return \Html::anchor($href, $text, $attributes);
    }
}
