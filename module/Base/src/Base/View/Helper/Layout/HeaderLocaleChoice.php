<?php

namespace Base\View\Helper\Layout;

use Base\Manager\ConfigManager;
use Base\Manager\OptionManager;
use Zend\Uri\Http as HttpUri;
use Zend\View\Helper\AbstractHelper;

class HeaderLocaleChoice extends AbstractHelper
{

    protected $configManager;
    protected $optionManager;
    protected $uri;

    public function __construct(ConfigManager $configManager, OptionManager $optionManager, HttpUri $uri)
    {
        $this->configManager = $configManager;
        $this->optionManager = $optionManager;
        $this->uri = $uri;
    }

    public function __invoke()
    {
        $localeChoice = $this->configManager->get('i18n.choice');

        if (! ($localeChoice && is_array($localeChoice))) {
            return null;
        }

        $view = $this->getView();
        $html = '';

        $html .= '<div id="topbar-i18n">';

        foreach ($localeChoice as $locale => $title) {
            // $uriString = $this->uri->toString();
            $uriString = $this->optionManager->need('service.website'); 
            $localePattern = '/locale=[^&]+/';

            if (preg_match($localePattern, $uriString)) {
                $href = preg_replace($localePattern, 'locale=' . $locale, $uriString);
            } else {
                /*
                if ($this->uri->getQuery()) {
                    $href = $uriString . '&locale=' . $locale;
                } else {
                    $href = $uriString . '?locale=' . $locale;
                }
                 */
                $href = $uriString . '?locale=' . $locale;
            }

        }

        $html .= '</div>';

        return $html;
    }

}
