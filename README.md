Sources and Footnotes
=================

A WordPress plugin for managing sources and footnotes.

## Installation

Note that the plugin folder should be named `sources-footnotes`. This is because if the [GitHub Updater plugin](https://github.com/afragen/github-updater) is used to update this plugin, if the folder is named something other than this, it will get deleted, and the updated plugin folder with a different name will cause the plugin to be silently deactivated.

## Filter hooks

* `sf_footnotes_post_types` - Modify the array of post types that footnotes are made available for; defaults to posts, pages, and all public custom post types
* `sf_source_post_type_args` - Modify the default arguments for registering the source post type
* `sf_custom_field_details_box_args` - Modify the default arguments for registering the details custom fields