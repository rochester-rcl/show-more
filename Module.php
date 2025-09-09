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
        $showMoreLimit = $view->siteSetting('show_more_limit', 0);
        $expandAllEnabled = $view->siteSetting('show_more_expand_all_enabled', true);

        // Get excluded property IDs and convert to terms
        $excludedPropertyIds = $view->siteSetting('show_more_excluded_properties', []);
        $excludedPropertyTerms = $this->getPropertyTerms($view, $excludedPropertyIds);

        // Only inject assets if show more is configured for this site
        if ($showMoreMode) {
            $view->headStyle()->appendStyle($this->getShowMoreCss());

            // Inject JavaScript with configuration including excluded property terms and expand all setting
            $jsWithConfig = str_replace(
                ['__SHOW_MORE_MODE__', '__SHOW_MORE_LIMIT__', '__EXCLUDED_PROPERTIES__', '__EXPAND_ALL_ENABLED__'],
                [
                    $view->escapeJs($showMoreMode),
                    (int) $showMoreLimit,
                    json_encode($excludedPropertyTerms),
                    $expandAllEnabled ? 'true' : 'false'
                ],
                $this->getShowMoreJs()
            );
            $view->headScript()->appendScript($jsWithConfig);
        }
    }

    protected function getPropertyTerms($view, $propertyIds)
    {
        if (empty($propertyIds)) {
            return [];
        }

        $terms = [];
        try {
            $properties = $view->api()->search('properties', ['id' => $propertyIds])->getContent();
            foreach ($properties as $property) {
                $terms[] = $property->term();
            }
        } catch (\Exception $e) {
            // Fail silently - just return empty array
        }

        return $terms;
    }

    /**
     * Get CSS for Show More functionality
     */
    protected function getShowMoreCss()
    {
        return file_get_contents(__DIR__ . '/asset/css/show-more.css');
    }

    /**
     * Get JavaScript for Show More functionality
     */
    protected function getShowMoreJs()
    {
        return file_get_contents(__DIR__ . '/asset/js/show-more.js');
    }
}