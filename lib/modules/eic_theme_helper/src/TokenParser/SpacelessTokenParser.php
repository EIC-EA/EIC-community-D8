<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Drupal\eic_theme_helper\TokenParser;

use Drupal\eic_theme_helper\Node\SpacelessNode;
use Twig\Token;
use Twig\TokenParser\AbstractTokenParser;

/**
 * Remove whitespaces between HTML tags.
 *
 *   {% spaceless %}
 *      <div>
 *          <strong>foo</strong>
 *      </div>
 *   {% endspaceless %}
 *   {# output will be <div><strong>foo</strong></div> #}
 *
 */
final class SpacelessTokenParser extends AbstractTokenParser {

  public function parse(Token $token) {
    $stream = $this->parser->getStream();
    $lineno = $token->getLine();

    $stream->expect(/* Token::BLOCK_END_TYPE */ 3);
    $body = $this->parser->subparse([$this, 'decideSpacelessEnd'], TRUE);
    $stream->expect(/* Token::BLOCK_END_TYPE */ 3);

    return new SpacelessNode($body, $lineno, $this->getTag());
  }

  public function decideSpacelessEnd(Token $token) {
    return $token->test('endspaceless');
  }

  public function getTag() {
    return 'spaceless';
  }

}
