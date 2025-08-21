<?php
namespace ShowMore\View\Helper;

use Laminas\View\Helper\AbstractHelper;

class ShowMore extends AbstractHelper
{
    /**
     * Generate truncated content with show more/less functionality
     *
     * @param string $content The original content to truncate
     * @param array $options Configuration options
     * @return string HTML output with truncation and show more button
     */
    public function __invoke($content, $options = [])
    {
        if (empty($content)) {
            return '';
        }

        $view = $this->getView();

        // Get configuration from site settings with fallbacks
        $mode = $view->siteSetting('show_more_mode', 'words');
        $limit = (int) $view->siteSetting('show_more_limit', 50);

        // Allow options to override site settings
        $mode = isset($options['mode']) ? $options['mode'] : $mode;
        $limit = isset($options['limit']) ? (int) $options['limit'] : $limit;

        // Additional options
        $showMoreText = isset($options['show_more_text']) ? $options['show_more_text'] : $view->translate('Show more');
        $showLessText = isset($options['show_less_text']) ? $options['show_less_text'] : $view->translate('Show less');
        $cssClass = isset($options['css_class']) ? $options['css_class'] : 'show-more-content';

        // Check if content needs truncation
        if (!$this->needsTruncation($content, $mode, $limit)) {
            return $view->escapeHtml($content);
        }

        // Generate truncated content
        $truncated = $this->truncateContent($content, $mode, $limit);
        $fullContent = $view->escapeHtml($content);
        $truncatedContent = $view->escapeHtml($truncated);

        // Generate unique ID for this instance
        $uniqueId = 'show-more-' . uniqid();

        // Build HTML output
        $html = sprintf(
            '<div class="%s" id="%s" data-full-content="%s" data-truncated-content="%s">',
            $view->escapeHtmlAttr($cssClass),
            $view->escapeHtmlAttr($uniqueId),
            $view->escapeHtmlAttr($fullContent),
            $view->escapeHtmlAttr($truncatedContent)
        );

        $html .= sprintf('<span class="content-text">%s</span>', $truncatedContent);
        $html .= sprintf(
            ' <button type="button" class="show-more-btn" data-show-text="%s" data-hide-text="%s">%s</button>',
            $view->escapeHtmlAttr($showMoreText),
            $view->escapeHtmlAttr($showLessText),
            $view->escapeHtml($showMoreText)
        );
        $html .= '</div>';

        return $html;
    }

    /**
     * Check if content needs truncation
     *
     * @param string $content
     * @param string $mode
     * @param int $limit
     * @return bool
     */
    protected function needsTruncation($content, $mode, $limit)
    {
        if ($limit <= 0) {
            return false;
        }

        switch ($mode) {
            case 'characters':
                return mb_strlen($content) > $limit;
            case 'words':
            default:
                return str_word_count($content) > $limit;
        }
    }

    /**
     * Truncate content based on mode and limit
     *
     * @param string $content
     * @param string $mode
     * @param int $limit
     * @return string
     */
    protected function truncateContent($content, $mode, $limit)
    {
        switch ($mode) {
            case 'characters':
                return $this->truncateByCharacters($content, $limit);
            case 'words':
            default:
                return $this->truncateByWords($content, $limit);
        }
    }

    /**
     * Truncate content by character count
     *
     * @param string $content
     * @param int $limit
     * @return string
     */
    protected function truncateByCharacters($content, $limit)
    {
        if (mb_strlen($content) <= $limit) {
            return $content;
        }

        $truncated = mb_substr($content, 0, $limit);

        // Try to break at word boundary if possible
        $lastSpace = mb_strrpos($truncated, ' ');
        if ($lastSpace !== false && $lastSpace > ($limit * 0.8)) {
            $truncated = mb_substr($truncated, 0, $lastSpace);
        }

        return rtrim($truncated, " \t\n\r\0\x0B.,;:!?") . '...';
    }

    /**
     * Truncate content by word count
     *
     * @param string $content
     * @param int $limit
     * @return string
     */
    protected function truncateByWords($content, $limit)
    {
        $words = preg_split('/\s+/', $content, -1, PREG_SPLIT_NO_EMPTY);

        if (count($words) <= $limit) {
            return $content;
        }

        $truncated = array_slice($words, 0, $limit);
        return implode(' ', $truncated) . '...';
    }

    /**
     * Helper method to initialize JavaScript for all show more instances
     * Call this once in your template to initialize the functionality
     *
     * @return string JavaScript initialization code
     */
    public function initializeJs()
    {
        return '<script>
document.addEventListener("DOMContentLoaded", function() {
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
});
</script>';
    }

    /**
     * Get CSS styles for show more functionality
     *
     * @return string CSS styles
     */
    public function getCss()
    {
        return '<style>
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
</style>';
    }
}