Sources and Footnotes
=================

A WordPress plugin for managing sources and footnotes.

## Installation

Note that the plugin folder should be named `sources-footnotes`. This is because if the [GitHub Updater plugin](https://github.com/afragen/github-updater) is used to update this plugin, if the folder is named something other than this, it will get deleted, and the updated plugin folder with a different name will cause the plugin to be silently deactivated.

## Basic usage

1. Go to _Settings > Sources and footnotes_ and check the settings.
1. When editing a post, click the _Insert footnote_ button in the content editor to insert a footnote.
1. You can use markup in the note, but you might find it easiest to apply markup once the note's inserted and you can use the editor.
1. Your theme should use the `post_class()` function on each the containing element for each post in the loop, for notes to work properly across posts in a list.

## IDs and classes for CSS

* `.sf-number` - The number link to the note
* `#sf-footnotes` - The wrapper for the list of footnotes
* `.sf-jump-back` - The link to jump back from a note to the text
* `.sf-tooltip` - The footnote tooltips
* `.sf-sources` - List of sources
* `.sf-thumb` - Container for thumbnail in lists of sources
* `.sf-text` - Container for text in lists of sources
* `.sf-title` - Title heading in lists of sources
* `.sf-meta` - Meta information list in lists of sources
* `.sf-description` - Description in lists of sources

## Shortcodes

* `[sf_footnote]` - Usually inserted by the editor button. The note itself is the content enclosed by the shortcode. If there is no note, make sure to include the self-enclosing slash. Attributes: `source` (ID); `page` (string)
* `[sf_list_footnotes]` - List footnotes.
* `[sf_list_sources]` - List sources. Attributes: `type` (taxonomy slug, default: `book`); `author` (taxonomy slug); `recommended` (`1` to list recommended, `0` to list not recommended, omit to list all); `list_type` (`ul` [default] or `ol`); `format` (`listing` [default] or `citation`); `listing_heading_level` (default: `3`); `thumbnail_size` (default: `post-thumbnail`)

## Filter hooks

* `sf_source_post_type_args` - Modify the default arguments for registering the source post type (passes the arguments)
* `sf_custom_field_details_box_args` - Modify the default arguments for registering the details custom fields with Developer's Custom Fields (passes the arguments)
* `sf_custom_field_details_box_args_cmb2` - Modify the default arguments for registering the details custom fields with CMB2 (passes the box args array with `cmb2_box` as the key, plus arrays of field args with the field IDs as keys)
* `sf_source_title` - Modify the output for a sources's title (passes the formatted title, and the source details array)
* `sf_date_format` - Modify date formats (passes the formatted date, and the original date string)
* `sf_footnote` - Modify the markup output for each footnote (passes the footnote and the source ID)
* `sf_compiled_source` - Modify the compiled markup output for a source (passes the current output, the source details array, and the format)
* `sf_jump_back_link_text` - Modify the glyph used for the 'jump back' link at the end of each footnote
