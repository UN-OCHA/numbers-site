<?php

namespace Drupal\hr_paragraphs\Plugin\Filter;

use Drupal\filter\Plugin\Filter\FilterHtml;

/**
 * Provides a filter to display any HTML as plain text.
 *
 * @Filter(
 *   id = "filter_markdown",
 *   title = @Translation("Obsolete - Convert a markdown text to HTML"),
 *   type = Drupal\filter\Plugin\FilterInterface::TYPE_MARKUP_LANGUAGE,
 *   weight = -20
 * )
 */
class Markdown extends FilterHtml {
}
