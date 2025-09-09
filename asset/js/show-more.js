document.addEventListener("DOMContentLoaded", function () {
    console.log("ShowMore: DOM loaded, starting processing");

    // Global variables for expand all functionality
    let expandAllButton = null;
    let expandAllContainer = null;
    let showMoreContainers = [];

    function truncateHTML(html, maxLength, mode) {
        var tempDiv = document.createElement('div');
        tempDiv.innerHTML = html;
        var fullText = tempDiv.textContent || tempDiv.innerText;

        // If content is already short enough, return as-is
        var actualLength = mode === 'words' ? countWords(fullText) : fullText.length;
        if (actualLength <= maxLength) {
            return html;
        }

        var targetLength;
        if (mode === 'words') {
            var words = fullText.trim().split(/\s+/);
            targetLength = words.slice(0, maxLength).join(' ').length;
        } else {
            targetLength = maxLength;
        }

        // Walk through the DOM and build truncated HTML
        var result = '';
        var currentLength = 0;

        function walkNode(node) {
            if (currentLength >= targetLength) {
                return false; // Stop processing
            }

            if (node.nodeType === 3) { // TEXT_NODE
                var text = node.textContent;
                var remainingLength = targetLength - currentLength;

                if (text.length <= remainingLength) {
                    result += text;
                    currentLength += text.length;
                } else {
                    // Truncate at word boundary if possible
                    var truncated = text.substring(0, remainingLength);
                    if (mode === 'words') {
                        var lastSpace = truncated.lastIndexOf(' ');
                        if (lastSpace > remainingLength * 0.8) {
                            truncated = truncated.substring(0, lastSpace);
                        }
                    }
                    result += truncated;
                    currentLength = targetLength; // Stop further processing
                    return false;
                }
            } else if (node.nodeType === 1) { // ELEMENT_NODE
                var tagName = node.tagName.toLowerCase();
                var attributes = '';

                // Copy attributes
                for (var i = 0; i < node.attributes.length; i++) {
                    var attr = node.attributes[i];
                    attributes += ' ' + attr.name + '="' + attr.value + '"';
                }

                result += '<' + tagName + attributes + '>';

                // Process children
                for (var i = 0; i < node.childNodes.length; i++) {
                    if (!walkNode(node.childNodes[i])) {
                        break; // Stop if we've reached the limit
                    }
                }

                result += '</' + tagName + '>';
            }

            return true;
        }

        // Process all child nodes
        for (var i = 0; i < tempDiv.childNodes.length; i++) {
            if (!walkNode(tempDiv.childNodes[i])) {
                break;
            }
        }

        return result;
    }

    // Function to create expand all/collapse all button
    function createExpandAllButton() {
        expandAllContainer = document.createElement('div');
        expandAllContainer.className = 'show-more-expand-all-container hidden';

        // Override the fixed positioning with inline styles
        expandAllContainer.style.position = 'static';
        expandAllContainer.style.background = 'transparent';
        expandAllContainer.style.border = 'none';
        expandAllContainer.style.boxShadow = 'none';
        expandAllContainer.style.marginBottom = '20px';
        expandAllContainer.style.padding = '8px 0';

        expandAllButton = document.createElement('button');
        expandAllButton.type = 'button';
        expandAllButton.className = 'show-more-expand-all-btn';
        expandAllButton.textContent = 'Expand all';

        expandAllButton.addEventListener('click', function() {
            const isExpandingAll = expandAllButton.textContent === 'Expand all';

            showMoreContainers.forEach(function(container) {
                const button = container.querySelector('.show-more-btn');
                const contentSpan = container.querySelector('.content-text');
                const isCurrentlyExpanded = container.classList.contains('expanded');

                if (isExpandingAll && !isCurrentlyExpanded) {
                    // Expand this item
                    contentSpan.innerHTML = container.getAttribute('data-full-html');
                    button.textContent = button.getAttribute('data-hide-text');
                    container.classList.add('expanded');
                } else if (!isExpandingAll && isCurrentlyExpanded) {
                    // Collapse this item
                    contentSpan.innerHTML = container.getAttribute('data-truncated-html');
                    button.textContent = button.getAttribute('data-show-text');
                    container.classList.remove('expanded');
                }
            });

            // Update button text
            expandAllButton.textContent = isExpandingAll ? 'Collapse all' : 'Expand all';
        });

        expandAllContainer.appendChild(expandAllButton);

        // Insert after the "Item" heading but before the metadata dl
        const itemHeading = document.querySelector('h3');
        const metadataSection = document.querySelector('dl');
        if (itemHeading && metadataSection) {
            itemHeading.parentNode.insertBefore(expandAllContainer, metadataSection);
        } else if (metadataSection) {
            metadataSection.parentNode.insertBefore(expandAllContainer, metadataSection);
        } else {
            document.body.appendChild(expandAllContainer);
        }
    }

    // First handle any existing show-more-btn elements (from view helper usage)
    const showMoreButtons = document.querySelectorAll(".show-more-btn");

    showMoreButtons.forEach(function (button) {
        button.addEventListener("click", function () {
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
        var excludedProperties = __EXCLUDED_PROPERTIES__;
        var expandAllEnabled = __EXPAND_ALL_ENABLED__;
        console.log("ShowMore: Mode:", showMoreMode, "Limit:", showMoreLimit);

        // Create expand all button if enabled
        if (expandAllEnabled) {
            createExpandAllButton();
        }

        function isPropertyExcluded(propertyLabel) {
            return excludedProperties.some(function (term) {
                // Extract the local part of the term and compare with label
                var localPart = term.split(':')[1];
                // Handle camelCase to spaced words (e.g., accessRights -> access rights)
                var spacedLabel = localPart.replace(/([A-Z])/g, ' $1').toLowerCase().trim();
                return propertyLabel.toLowerCase() === spacedLabel || propertyLabel.toLowerCase().includes(localPart.toLowerCase());
            });
        }

        // Function to count words
        function countWords(text) {
            return text.trim().split(/\s+/).filter(function (word) {
                return word.length > 0;
            }).length;
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
        var dtElements = document.querySelectorAll("dt");

        dtElements.forEach(function (dt) {
            var propertyLabel = dt.textContent.trim();
            if (isPropertyExcluded(propertyLabel)) {
                return;
            }
            console.log("ShowMore: Found", propertyLabel, "property");
            var nextElement = dt.nextElementSibling;


            while (nextElement && nextElement.tagName === "DD") {
                var valueContent = nextElement.querySelector(".value-content");
                if (valueContent) {
                    var originalText = valueContent.textContent.trim();
                    var originalHTML = valueContent.innerHTML;
                    var needsTruncation = false;

                    if (showMoreMode === "words") {
                        var wordCount = countWords(originalText);
                        needsTruncation = wordCount > showMoreLimit;
                        console.log("ShowMore: Word count:", wordCount, "Needs truncation:", needsTruncation);
                    } else {
                        needsTruncation = originalText.length > showMoreLimit;
                        console.log("ShowMore: Character count:", originalText.length, "Needs truncation:", needsTruncation);
                    }
                    if (showMoreLimit === 0) {
                        needsTruncation = false;
                    }

                    if (needsTruncation) {
                        console.log("ShowMore: Applying truncation to", propertyLabel);

                        // Create properly truncated HTML
                        var truncatedHTML = truncateHTML(originalHTML, showMoreLimit, showMoreMode);

                        var uniqueId = "show-more-" + Math.random().toString(36).substr(2, 9);
                        var container = document.createElement("div");
                        container.className = "show-more-content show-more-metadata";
                        container.id = uniqueId;

                        var contentSpan = document.createElement("span");
                        contentSpan.className = "content-text";
                        contentSpan.innerHTML = truncatedHTML; // Start with properly truncated HTML

                        var button = document.createElement("button");
                        button.type = "button";
                        button.className = "show-more-btn";
                        button.setAttribute("data-show-text", "Show more");
                        button.setAttribute("data-hide-text", "Show less");
                        button.textContent = "Show more";

                        // Store both versions
                        container.setAttribute("data-full-html", originalHTML);
                        container.setAttribute("data-truncated-html", truncatedHTML);

                        // Add to tracking array for expand all functionality
                        showMoreContainers.push(container);

                        button.addEventListener("click", (function (btnContainer, btnContentSpan, btnButton) {
                            return function () {
                                var isExpanded = btnContainer.classList.contains("expanded");

                                if (isExpanded) {
                                    // Collapse - show truncated HTML
                                    btnContentSpan.innerHTML = btnContainer.getAttribute("data-truncated-html");
                                    btnButton.textContent = btnButton.getAttribute("data-show-text");
                                    btnContainer.classList.remove("expanded");
                                } else {
                                    // Expand - show full HTML
                                    btnContentSpan.innerHTML = btnContainer.getAttribute("data-full-html");
                                    btnButton.textContent = btnButton.getAttribute("data-hide-text");
                                    btnContainer.classList.add("expanded");
                                }
                            };
                        })(container, contentSpan, button));

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
        });

        // Show expand all button if we have containers and it's enabled
        if (expandAllEnabled && showMoreContainers.length > 0) {
            expandAllContainer.classList.remove('hidden');
            console.log("ShowMore: Expand all button shown with", showMoreContainers.length, "containers");
        }
    }
});