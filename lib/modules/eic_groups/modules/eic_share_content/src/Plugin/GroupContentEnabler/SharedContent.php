<?php

namespace Drupal\eic_share_content\Plugin\GroupContentEnabler;

use Drupal\group\Plugin\GroupContentEnablerBase;

/**
 * Provides a content enabler to request the archival of the group.
 *
 * @GroupContentEnabler(
 *   id = "group_shared_content",
 *   label = @Translation("Group Shared content"),
 *   description = @Translation("Adds shared content to groups."),
 *   entity_type_id = "node",
 *   pretty_path_key = "shared_content",
 *   reference_label = @Translation("Shared content"),
 *   reference_description = @Translation("Shared content."),
 * )
 */
class SharedContent extends GroupContentEnablerBase {

}
