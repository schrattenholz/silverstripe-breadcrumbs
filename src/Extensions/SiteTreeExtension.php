<?php

namespace Innoweb\Breadcrumbs\Extensions;

use Innoweb\Breadcrumbs\Model\Crumb;
use Innoweb\Breadcrumbs\Model\CrumbsList;
use SilverStripe\CMS\Controllers\RootURLController;
use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Core\ClassInfo;
use SilverStripe\Core\Config\Config;

class SiteTreeExtension extends \SilverStripe\CMS\Model\SiteTreeExtension
{
    private static $crumbs_include_home = true;
    private static $crumbs_show_hidden = false;

    public function getCrumbsList()
    {
        $page = $this->owner;
        $pages = [];

        $includeHome = Config::inst()->get(SiteTreeExtension::class, 'crumbs_include_home');
        $showHidden = Config::inst()->get(SiteTreeExtension::class, 'crumbs_show_hidden');

        if (ClassInfo::exists('Symbiote\Multisites\Control\MultisitesRootController')) {
            $homeLink = \Symbiote\Multisites\Control\MultisitesRootController::get_homepage_link();
            $homePage = SiteTree::get_by_link($homeLink);
        } else {
            $homeLink = RootURLController::get_homepage_link();
            $homePage = SiteTree::get_by_link($homeLink);
        }

        while ($page) {
            if ($page->ID === $homePage->ID) {
                if ($includeHome) {
                    $pages[] = $page;
                }
            } else if ($showHidden || $page->ShowInMenus || ($page->ID === $this->owner->ID)) {
                $pages[] = $page;
            }
            if (!$page->ParentID && $page->ID !== $homePage->ID) {
                $pages[] = $homePage;
            }
            $page = $page->ParentID ? $page->Parent() : false;
        }

        $list = CrumbsList::create();

        if (count($pages)) {
            $pages = array_reverse($pages);
            foreach ($pages as $page) {
                $crumb = Crumb::create($page);
                $list->push($crumb);
            }
        }

        if ($this->owner->hasMethod('updateCrumbsList')) {
            $list = $this->owner->updateCrumbsList($list);
        }
        return $list;
    }

    public function CrumbsCacheKey()
    {
        $includeHome = Config::inst()->get(SiteTreeExtension::class, 'crumbs_include_home');
        $showHidden = Config::inst()->get(SiteTreeExtension::class, 'crumbs_show_hidden');
        $pageIDs = $this->owner->getAncestors(true)->map()->keys();
        $maxLastEdited = SiteTree::get()->filter('ID', $pageIDs)->max('LastEdited');

        $key = implode('-', $pageIDs);
        $key .= $maxLastEdited;
        $key .= ($includeHome) ? 'hometrue' : 'homefalse';
        $key .= ($showHidden) ? 'hiddentrue' : 'hiddenfalse';

        if ($this->owner->hasMethod('updateCrumbsCacheKey')) {
            $key = $this->owner->updateCrumbsCacheKey($key);
        }
        return $key;
    }
}