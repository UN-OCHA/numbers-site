<?php

namespace Drupal\hr_paragraphs\Plugin\Filter;

use Drupal\Core\Link;
use Drupal\Core\Render\Markup;
use Drupal\Core\Url;
use Drupal\filter\FilterProcessResult;
use Drupal\filter\Plugin\FilterBase;
use League\CommonMark\Environment;
use League\CommonMark\Extension\Attributes\AttributesExtension;
use League\CommonMark\Extension\Autolink\AutolinkExtension;
use League\CommonMark\Extension\ExternalLink\ExternalLinkExtension;
use League\CommonMark\Extension\Table\TableExtension;
use League\CommonMark\MarkdownConverter;

/**
 * Provides a filter to display any HTML as plain text.
 *
 * @Filter(
 *   id = "filter_markdown",
 *   title = @Translation("Convert a markdown text to HTML"),
 *   type = Drupal\filter\Plugin\FilterInterface::TYPE_MARKUP_LANGUAGE,
 *   weight = -20
 * )
 */
class Markdown extends FilterBase {

  /**
   * {@inheritdoc}
   */
  public function process($text, $langcode) {
    // Add a space before the heading '#' which is fine as ReliefWeb doesn't use
    // hash tags.
    // @see https://talk.commonmark.org/t/heading-not-working/819/42
    $text = preg_replace('/^(#+)([^# ])/m', '$1 $2', $text);

    // Obtain a pre-configured Environment with all the CommonMark
    // parsers/renderers ready-to-go.
    $environment = Environment::createCommonMarkEnvironment();

    // Configuration to add attributes to external links.
    $external_link_config = [
      'external_link' => [
        'internal_hosts' => [
          \Drupal::request()->getHost(),
          'reliefweb.int',
        ],
        'open_in_new_window' => TRUE,
      ],
    ];
    $environment->mergeConfig($external_link_config);

    // Add the extension to convert external links.
    $environment->addExtension(new ExternalLinkExtension());

    // Add the extension to convert ID attributes.
    $environment->addExtension(new AttributesExtension());

    // Add the extension to convert links.
    $environment->addExtension(new AutolinkExtension());

    // Add the extension to convert the tables.
    $environment->addExtension(new TableExtension());

    // Create the converter with the extension(s).
    $converter = new MarkdownConverter($environment);

    // Convert to HTML.
    $html = $converter->convertToHtml($text);

    return new FilterProcessResult($html);
  }

  /**
   * {@inheritdoc}
   */
  public function tips($long = FALSE) {
    $help = '<h4>Markdown reference</h4>
    <table class="markdown-reference">
        <thead>
            <tr>
                <th>Type</th>
                <th>… to Get</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>*Italic*</td>
                <td><em>Italic</em></td>
            </tr>
            <tr>
                <td>**Bold**</td>
                <td><strong>Bold</strong></td>
            </tr>
            <tr>
                <td>
                    # Heading 1
                </td>
                <td>
                    <h1>Heading 1</h1>
                </td>
            </tr>
            <tr>
                <td>
                    ## Heading 2
                </td>
                <td>
                    <h2>Heading 2</h2>
                </td>
            </tr>
            <tr>
                <td>
                    ### Heading 3
                </td>
                <td>
                    <h3>Heading 3</h3>
                </td>
            </tr>
            <tr>
                <td>
                    #### Heading 4
                </td>
                <td>
                    <h4>Heading 4</h4>
                </td>
            </tr>
            <tr>
                <td>
                    ##### Heading 5
                </td>
                <td>
                    <h5>Heading 5</h5>
                </td>
            </tr>
            <tr>
                <td>
                    ###### Heading 6
                </td>
                <td>
                    <h6>Heading 6</h6>
                </td>
            </tr>
            <tr>
                <td>
                    [Link](http://a.com)
                </td>
                <td><a href="https://commonmark.org/help">Link</a></td>
            </tr>
            <tr>
                <td>
                    ![Image](http://url/reliefweb.png)
                </td>
                <td>
                    <img src="/themes/custom/common_design_subtheme/img/logos/rw-logo-desktop.svg" width="140" height="60" alt="">
                </td>
            </tr>
            <tr>
                <td>
                    &gt; Blockquote
                </td>
                <td>
                    <blockquote>Blockquote</blockquote>
                </td>
            </tr>
            <tr>
                <td>
                    <p>
                        - List<br>
                        - List<br>
                        - List
                    </p>
                </td>
                <td>
                    <ul>
                        <li>List</li>
                        <li>List</li>
                        <li>List</li>
                    </ul>
                </td>
            </tr>
            <tr>
                <td>
                    <p>
                        1. One<br>
                        2. Two<br>
                        3. Three
                    </p>
                </td>
                <td>
                    <ol>
                        <li>One</li>
                        <li>Two</li>
                        <li>Three</li>
                    </ol>
                </td>
            </tr>
            <tr>
                <td>
                    Horizontal rule:
                    <br>
                    ---
                </td>
                <td>
                    Horizontal rule:
                    <hr>
                </td>
            </tr>
        </tbody>
    </table>';

    $tips = [
      '#type' => 'container',
    ];

    $tips[] = [
      '#markup' => Markup::create($help),
    ];

    // Documentation link.
    $url = Url::fromUri('https://commonmark.org/help', [
      'attributes' => ['target' => '_blank', 'rel' => 'noopener noreferrer'],
    ]);
    $tips[] = [
      '#markup' => $this->t('For complete details on the Markdown syntax, see the @link', [
        '@link' => Link::fromTextAndUrl($this->t('Markdown documentation'), $url)->toString(),
      ]),
    ];

    return \Drupal::service('renderer')->render($tips);
  }

}
