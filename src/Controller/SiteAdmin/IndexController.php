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

            // Save settings
            $siteSettings->set('show_more_mode', $data['show_more_mode'] ?? 'words');
            $siteSettings->set('show_more_limit', (int)($data['show_more_limit'] ?? 50));

            $this->messenger()->addSuccess('Show More settings saved.');
            return $this->redirect()->toRoute('admin/site/slug/show-more', [], true);
        }

        // Get current settings
        $settings = [
            'show_more_mode' => $siteSettings->get('show_more_mode', 'words'),
            'show_more_limit' => $siteSettings->get('show_more_limit', 0),
        ];

        $view = new ViewModel([
            'site' => $site,
            'settings' => $settings,
        ]);
        $view->setTemplate('show-more/site-admin/index/index');
        return $view;
    }
}