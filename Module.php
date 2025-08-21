<?php
namespace ShowMore;

use Omeka\Module\AbstractModule;
use Laminas\EventManager\Event;
use Laminas\EventManager\SharedEventManagerInterface;
use Laminas\ServiceManager\ServiceLocatorInterface;

class Module extends AbstractModule
{
    public function getConfig()
    {
        return include __DIR__ . '/config/module.config.php';
    }

    public function onBootstrap($event)
    {
        parent::onBootstrap($event);

        $acl = $this->getServiceLocator()->get('Omeka\Acl');
        $acl->allow(
            null,
            ['ShowMore\Controller\SiteAdmin\Index']
        );
    }

    public function attachListeners(SharedEventManagerInterface $sharedEventManager)
    {
        // Auto-inject CSS and JavaScript on item show pages
        $sharedEventManager->attach(
            'Omeka\Controller\Site\Item',
            'view.show.after',
            [$this, 'injectShowMoreAssets']
        );
    }

    /**
     * Inject Show More assets specifically on item show pages
     */
    public function injectShowMoreAssets(Event $event)
    {
        $view = $event->getTarget();
        $this->injectAssets($view);
    }

    /**
     * Inject CSS and JavaScript assets for Show More functionality
     */
    protected function injectAssets($view)
    {
        // Check if site has Show More enabled (has settings configured)
        $showMoreMode = $view->siteSetting('show_more_mode');
        $showMoreLimit = $view->siteSetting('show_more_limit', 50);

        // Only inject assets if show more is configured for this site
        if ($showMoreMode) {
            $view->headStyle()->appendStyle($this->getShowMoreCss());

            // Inject JavaScript with configuration embedded
            $jsWithConfig = str_replace(
                ['__SHOW_MORE_MODE__', '__SHOW_MORE_LIMIT__'],
                [$view->escapeJs($showMoreMode), (int) $showMoreLimit],
                $this->getShowMoreJs()
            );
            $view->headScript()->appendScript($jsWithConfig);
        }
    }

    /**
     * Get CSS for Show More functionality
     */
    protected function getShowMoreCss()
    {
        return '
.show-more-content {
    position: relative;
}

.show-more-btn {
    background: none;
    border: none;
    color: #0073aa;
    cursor: pointer;
    text-decoration: underline;
    padding: 0;
    margin-left: 5px;
    font-size: inherit;
    font-family: inherit;
    display: inline;
}

.show-more-btn:hover {
    color: #005a87;
}

.show-more-btn:focus {
    outline: 1px dotted #0073aa;
    outline-offset: 2px;
}

.show-more-content .content-text {
    display: inline;
}

.show-more-content.expanded .content-text {
    display: inline;
}

/* Specific styling for metadata properties */
.show-more-metadata {
    line-height: 1.5;
}

.property .show-more-content {
    margin-bottom: 0.5em;
}

/* Property-specific styling */
.property-dcterms-description .show-more-btn,
.property-dcterms-abstract .show-more-btn {
    font-weight: normal;
    font-style: italic;
}

/* Ensure proper spacing in metadata context */
.metadata .property .value .show-more-content {
    display: block;
}

.metadata .property .value .show-more-content .content-text {
    display: inline;
}
';
    }

    /**
     * Get JavaScript for Show More functionality
     */
    protected function getShowMoreJs()
    {
        $js_content = file_get_contents('./modules/ShowMore/asset/js/show-more.js');
        return $js_content;
    }
}