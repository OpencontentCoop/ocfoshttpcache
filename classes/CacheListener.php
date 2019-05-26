<?php

namespace Opencontent\FosHttpCache;

use FOS\HttpCache\ProxyClient\Varnish;

class CacheListener
{
    public static function onRequestPreinput(\eZURI $uri)
    {
        if ($uri->uriString() == '_fos_user_context_hash') {
            $GLOBALS['eZHTTPHeaderCustom'] = false;
        }
    }

    /**
     * @param integer $nodeID
     * @param \eZINI $siteINI
     * @return integer
     */
    public static function onContentView($nodeID, \eZINI $siteINI)
    {
        ResponseTagger::instance()->addTags(["node-{$nodeID}"]);

        return $nodeID;
    }

    public static function onContentCacheAll()
    {
        $tagList = \ezpEvent::getInstance()->filter('ocfoshttpcache/invalidate_tag_all', []);

        if (!empty($tagList)) {
            CacheInvalidator::instance()->invalidateTags($tagList);
        }
        (new Logger())->debug('Clear all content cache');
        CacheInvalidator::instance()->invalidateRegex(Varnish::REGEX_MATCH_ALL);
    }

    public static function onContentCache($nodeIdList)
    {
        $tagList = [];

        if (!self::hasStaticCache()) {
            $tagList = array_map(function ($nodeId) {
                return "node-{$nodeId}";
            }, $nodeIdList);
        }

        $tagList = \ezpEvent::getInstance()->filter('ocfoshttpcache/invalidate_tag_node_list', $tagList, $nodeIdList);

        if (!empty($tagList)) {
            (new Logger())->debug('Clear content tag list: ' . implode(', ', $tagList));
            CacheInvalidator::instance()->invalidateTags($tagList);
        }

        return $nodeIdList;
    }

    public static function onContentPreRendering(\eZContentObjectTreeNode $node, \eZTemplate $tpl, $viewMode)
    {
        $tpl->setVariable('cache_ttl', 0);
        \eZDebug::writeNotice("Force set cache_ttl 0", __METHOD__);

        $tagList = [
            'view-' . $viewMode,
            'object-' . $node->object()->attribute('id'),
            'class-' . $node->object()->attribute('contentclass_id'),
            'node-' . $node->attribute('node_id'),
            'parent-' . $node->attribute('parent_node_id'),
        ];

        foreach ($node->pathArray() as $path) {
            $tagList[] = 'path-' . $path;
        }

        if ($tpl->hasVariable('response_tag_list')) {
            $tagList = (array)$tpl->variable('response_tag_list');
        }

        $tagList = \ezpEvent::getInstance()->filter('ocfoshttpcache/node_response_tag_list', $tagList, $node);

        if (!empty($tagList)) {
            ResponseTagger::instance()->addTags($tagList);
        }
    }

    public static function onResponseOutput($templateResult)
    {
        $responseTagger = ResponseTagger::instance();

        if ($responseTagger->hasTags()) {
            (new Logger())->debug('Add cache tags headers: ' . $responseTagger->getTagsHeaderValue());
            header(sprintf('%s: %s',
                $responseTagger->getTagsHeaderName(),
                $responseTagger->getTagsHeaderValue()
            ));
        }

        return $templateResult;
    }

    private static function hasStaticCache()
    {
        $ini = \eZINI::instance();
        return $ini->variable('ContentSettings', 'StaticCache') == 'enabled'
            && $ini->variable('ContentSettings', 'StaticCacheHandler') == 'Opencontent\FosHttpCache\StaticCache';
    }

}