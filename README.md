Sources and Footnotes
=================

A WordPress plugin for managing sources and footnotes.

## Installation

Note that the plugin folder should be named `sources-footnotes`. This is because if the [GitHub Updater plugin](https://github.com/afragen/github-updater) is used to update this plugin, if the folder is named something other than this, it will get deleted, and the updated plugin folder with a different name will cause the plugin to be silently deactivated.

## Basic usage

1. Go to _Settings > Sources and footnotes_ and check the settings.
1. When editing a post, click the _Insert footnote_ button in the content editor to insert a footnote.
1. You can use markup in the note, but you might find it easiest to apply markup once the note's inserted and you can use the editor.

## IDs and classes for CSS

* `.sf-number` - The number link to the note
* `#sf-footnotes` - The wrapper for the list of footnotes
* `.sf-jump-back` - The link to jump back from a note to the text
* `.sf-tooltip` - The footnote tooltips

## Filter hooks

* `sf_source_post_type_args` - Modify the default arguments for registering the source post type (passes the arguments)
* `sf_custom_field_details_box_args` - Modify the default arguments for registering the details custom fields (passes the arguments)
* `sf_source_title` - Modify the output for a sources's title (passes the formatted title, and the source details array)
* `sf_date_format` - Modify date formats (passes the formatted date, and the original date string)
* `sf_footnote` - Modify the markup output for each footnote (passes the footnote)
