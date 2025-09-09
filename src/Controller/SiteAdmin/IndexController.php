<?php
namespace ShowMore\Controller\SiteAdmin;

use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;

class IndexController extends AbstractActionController
{
    public function indexAction()
    {
        $site = $this->currentSite();
        $siteSettings = $this->siteSettings();

        if ($this->getRequest()->isPost()) {
            $data = $this->params()->fromPost();

            // DEBUG: Log what we received
            error_log('=== ShowMore POST received ===');
            error_log('POST data: ' . print_r($data, true));

            // Save basic settings
            $siteSettings->set('show_more_mode', $data['show_more_mode'] ?? 'words');
            $siteSettings->set('show_more_limit', (int)($data['show_more_limit'] ?? 0));

            // Save expand all setting (defaults to enabled)
            $expandAllEnabled = isset($data['show_more_expand_all_enabled']) ? (bool)$data['show_more_expand_all_enabled'] : false;
            $siteSettings->set('show_more_expand_all_enabled', $expandAllEnabled);

            // Handle excluded properties - the key difference here!
            // The propertySelect helper may send data in a nested array format
            $excludedProperties = [];

            if (isset($data['show_more_excluded_properties'])) {
                $rawExcluded = $data['show_more_excluded_properties'];

                // Handle different possible formats from propertySelect
                if (is_array($rawExcluded)) {
                    // Check if it's a nested array (which propertySelect sometimes creates)
                    if (isset($rawExcluded[0]) && is_array($rawExcluded[0])) {
                        // Extract values from nested structure
                        foreach ($rawExcluded as $item) {
                            if (is_array($item) && isset($item['property_id'])) {
                                $excludedProperties[] = $item['property_id'];
                            } elseif (is_numeric($item)) {
                                $excludedProperties[] = (int) $item;
                            }
                        }
                    } else {
                        // Direct array of property IDs
                        $excludedProperties = array_filter(array_map('intval', $rawExcluded));
                    }
                } elseif (is_numeric($rawExcluded)) {
                    $excludedProperties = [(int) $rawExcluded];
                }
            }

            // Remove duplicates and ensure all values are integers
            $excludedProperties = array_unique(array_filter($excludedProperties));

            error_log('Processed excluded properties: ' . print_r($excludedProperties, true));
            error_log('Expand all enabled: ' . ($expandAllEnabled ? 'true' : 'false'));

            // Save to site settings
            $siteSettings->set('show_more_excluded_properties', $excludedProperties);

            // Verify save
            $verification = $siteSettings->get('show_more_excluded_properties', []);
            error_log('Verification read back: ' . print_r($verification, true));

            $this->messenger()->addSuccess('Show More settings saved.');
            return $this->redirect()->toRoute('admin/site/slug/show-more', [], true);
        }

        // GET request - load current settings
        $excludedPropertyIds = $siteSettings->get('show_more_excluded_properties', []);
        $expandAllEnabled = $siteSettings->get('show_more_expand_all_enabled', true); // Default to enabled

        // Ensure we have proper format for the view
        if (!is_array($excludedPropertyIds)) {
            $excludedPropertyIds = [];
        }

        error_log('=== ShowMore GET request ===');
        error_log('Excluded property IDs loaded: ' . print_r($excludedPropertyIds, true));
        error_log('Expand all enabled: ' . ($expandAllEnabled ? 'true' : 'false'));

        // Prepare settings for the view
        $settings = [
            'show_more_mode' => $siteSettings->get('show_more_mode', 'words'),
            'show_more_limit' => $siteSettings->get('show_more_limit', 0),
            'show_more_excluded_properties' => $excludedPropertyIds,
            'show_more_expand_all_enabled' => $expandAllEnabled,
        ];

        return new ViewModel([
            'site' => $site,
            'settings' => $settings,
        ]);
    }
}