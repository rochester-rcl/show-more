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
        return '
document.addEventListener("DOMContentLoaded", function() {
    console.log("ShowMore: DOM loaded, starting processing");
    
    // First handle any existing show-more-btn elements (from view helper usage)
    const showMoreButtons = document.querySelectorAll(".show-more-btn");
    
    showMoreButtons.forEach(function(button) {
        button.addEventListener("click", function() {
            const container = button.closest(".show-more-content");
            const contentSpan = container.querySelector(".content-text");
            const isExpanded = container.classList.contains("expanded");
            
            if (isExpanded) {
                // Collapse
                contentSpan.textContent = container.dataset.truncatedContent;
                button.textContent = button.dataset.showText;
                container.classList.remove("expanded");
            } else {
                // Expand
                contentSpan.textContent = container.dataset.fullContent;
                button.textContent = button.dataset.hideText;
                container.classList.add("expanded");
            }
        });
    });
    
    // Auto-apply ShowMore to item metadata if on item show page
    if (document.body.classList.contains("item") && document.body.classList.contains("show")) {
        console.log("ShowMore: On item show page");
        
        // Configuration is embedded directly in the script
        var showMoreMode = "__SHOW_MORE_MODE__";
        var showMoreLimit = __SHOW_MORE_LIMIT__;
        
        console.log("ShowMore: Mode:", showMoreMode, "Limit:", showMoreLimit);
        
        // Properties that should have ShowMore applied
        var showMoreProperties = ["Description", "Abstract", "Table of Contents", "Bibliographic Citation"];
        
        // Function to count words
        function countWords(text) {
            return text.trim().split(/\s+/).filter(function(word) { return word.length > 0; }).length;
        }
        
        // Function to truncate by words
        function truncateByWords(text, limit) {
            var words = text.trim().split(/\s+/);
            if (words.length <= limit) return text;
            return words.slice(0, limit).join(" ") + "...";
        }
        
        // Function to truncate by characters
        function truncateByCharacters(text, limit) {
            if (text.length <= limit) return text;
            var truncated = text.substring(0, limit);
            var lastSpace = truncated.lastIndexOf(" ");
            if (lastSpace > limit * 0.8) {
                truncated = truncated.substring(0, lastSpace);
            }
            return truncated.replace(/[.,;:!?]\s*$/, "") + "...";
        }
        
        // Process each property
        showMoreProperties.forEach(function(propertyLabel) {
            console.log("ShowMore: Processing property:", propertyLabel);
            var dtElements = document.querySelectorAll("dt");
            
            dtElements.forEach(function(dt) {
                if (dt.textContent.trim() === propertyLabel) {
                    console.log("ShowMore: Found", propertyLabel, "property");
                    var nextElement = dt.nextElementSibling;
                    
                    while (nextElement && nextElement.tagName === "DD") {
                        var valueContent = nextElement.querySelector(".value-content");
                        if (valueContent) {
                            var originalText = valueContent.textContent.trim();
                            var needsTruncation = false;
                            
                            if (showMoreMode === "words") {
                                var wordCount = countWords(originalText);
                                needsTruncation = wordCount > showMoreLimit;
                                console.log("ShowMore: Word count:", wordCount, "Needs truncation:", needsTruncation);
                            } else {
                                needsTruncation = originalText.length > showMoreLimit;
                                console.log("ShowMore: Character count:", originalText.length, "Needs truncation:", needsTruncation);
                            }
                            
                            if (needsTruncation) {
                                console.log("ShowMore: Applying truncation to", propertyLabel);
                                var truncatedText = showMoreMode === "words" 
                                    ? truncateByWords(originalText, showMoreLimit)
                                    : truncateByCharacters(originalText, showMoreLimit);
                                
                                var uniqueId = "show-more-" + Math.random().toString(36).substr(2, 9);
                                var container = document.createElement("div");
                                container.className = "show-more-content show-more-metadata";
                                container.id = uniqueId;
                                
                                var contentSpan = document.createElement("span");
                                contentSpan.className = "content-text";
                                contentSpan.textContent = truncatedText;
                                
                                var button = document.createElement("button");
                                button.type = "button";
                                button.className = "show-more-btn";
                                button.setAttribute("data-show-text", "Show more");
                                button.setAttribute("data-hide-text", "Show less");
                                button.textContent = "Show more";
                                
                                container.setAttribute("data-full-content", originalText);
                                container.setAttribute("data-truncated-content", truncatedText);
                                
                                button.addEventListener("click", function() {
                                    var isExpanded = container.classList.contains("expanded");
                                    
                                    if (isExpanded) {
                                        contentSpan.textContent = container.getAttribute("data-truncated-content");
                                        button.textContent = button.getAttribute("data-show-text");
                                        container.classList.remove("expanded");
                                    } else {
                                        contentSpan.textContent = container.getAttribute("data-full-content");
                                        button.textContent = button.getAttribute("data-hide-text");
                                        container.classList.add("expanded");
                                    }
                                });
                                
                                container.appendChild(contentSpan);
                                container.appendChild(document.createTextNode(" "));
                                container.appendChild(button);
                                
                                valueContent.innerHTML = "";
                                valueContent.appendChild(container);
                                
                                console.log("ShowMore: Successfully applied to", propertyLabel);
                            }
                        }
                        
                        nextElement = nextElement.nextElementSibling;
                    }
                }
            });
        });
    }
});
';
    }
}