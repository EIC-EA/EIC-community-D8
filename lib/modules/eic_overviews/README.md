# EIC Overviews

This module provides the ability to manage the _global overviews_ and provides _group overviews_.

## Global overviews

This module provides a new overview_page (content) entity type which can be used to create overview pages that reference block of _eic_search_overview_ type.

This content entity is fieldable, so it can be extended, and is also translatable.

By default it provides following fields/properties:
- Title
- Page description
- Banner image
- Overview block
- URL alias
- Status (published)

Those pages can be managed from `/admin/community/overview-pages`.

## Group overviews

Group overviews are not manageable but are provided directly from this module (hard-coded).

Those routes are provided only within a group context (Group entity type). E.g. `/group/{group}/my-overview`

They need a specific group permission to be accessed and return nothing, they are just placeholder pages.

You are still required to provide content to them (such as adding blocks to the page) if you want to show something.
