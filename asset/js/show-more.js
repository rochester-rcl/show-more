document.addEventListener("DOMContentLoaded", function () {
    console.log("ShowMore: DOM loaded, starting processing");

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
        console.log("ShowMore: Mode:", showMoreMode, "Limit:", showMoreLimit);

        function isPropertyExcluded(propertyLabel) {
            return excludedProperties.some(function (term) {
                return propertyLabel.toLowerCase().includes(term.split(':')[1]);
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
                        var truncatedText = showMoreMode === "words" ? truncateByWords(originalText, showMoreLimit) : truncateByCharacters(originalText, showMoreLimit);

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

                        button.addEventListener("click", function () {
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
        });
    }
});
