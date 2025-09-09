# Show More - An Omkea S Module
## Overview
This module adds customizable folding functionality to long metadata fields in Omeka S.

Normally when Omeka S displays a metadata field, the entire contents is displayed. This simple module allows for fields to be truncated, with a show more link/show less link to unfold the remaining content. The length of content to show before truncating is customizable on a site by site basis. This can be disabled per site by selecting "0" as the truncation length

## Customization

The styling of the "Show more" button can be adjusted by altering the CSS file at /ShowMore/assets/css/show-more.css

## Installation
Unzip a release of this module in your modules folder. Ensure the folder is named "ShowMore". Go into the Omeka S admin interface and hit install. From there, you will have to configure folding on each site that you want it enabled on.

## TODO
- [x] Add an "Excluded Properties" dropdown to exclude certain properties from folding.
- [x] Add Expand all/ Collapse all functionality.
- [ ] Implement Optional folding of Media entries and item set lists for items
- [ ] Implement folding of multi-entry items as selectable "As block" vs "Per Entry" (currently is always per entry)
- [ ] Add the ability to change the name of the "Show More" "Show Less" labels

## Known bugs
- None currently
  
## Support
This module is supported through Github. Should you encounter a bug, or to request an enhancement, please open an issue on this repository. I will address them as I am able.

## License
This software is provided "As Is" with no warranty, explicit or implied. It follows the GPLv3 license specified in the LICENSE file in this directory

## Credit
This module was written by [Channing Norton](https://github.com/C-Norton/), working on behalf of the University of Rochester - River Campus Libraries.

