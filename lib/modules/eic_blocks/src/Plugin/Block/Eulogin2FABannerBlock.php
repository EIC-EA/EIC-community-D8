<?php

namespace Drupal\eic_blocks\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Markup;

/**
 * Provides an 2FA banner information block.
 *
 * @Block(
 *   id = "eulogin_2fa_banner_block",
 *   admin_label = @Translation("EULogin 2FA banner information block"),
 *   category = @Translation("EULogin 2FA banner information block")
 * )
 */
class Eulogin2FABannerBlock extends BlockBase {
  /**
   * {@inheritdoc}
   */
  public function build() {
    $template = '
 <div style="position: fixed;z-index: 1001;background-color: #004494;width: 100%;left: 0;right: 0;padding: 10px 15px 4px 31px;color: #FFD90E;box-sizing: border-box;line-height: 140%;box-shadow: 0 0 20px black;font-size: 0.8rem;" id="banner2FA" onclick="document.getElementById(\'block-eulogin2fabannerinformationblock\').style.visibility=\'hidden\';setCookie(\'banner2FA\');">
<script>
  function linkHandler(event) {
    event.stopPropagation();
  }
function setCookie(cname) {
    var d = new Date(); var days = 1; var cvalue = "1";
    d.setTime(d.getTime() + (days*12*60*60*1000));
    var expires = "expires="+d.toUTCString();
    document.cookie = cname + "=" + cvalue + "; " + expires + "; path=/community";
}
  function getCookie(name) {
    const value = `; ${document.cookie}`;
    const parts = value.split(`; ${name}=`);
    if (parts.length === 2) return parts.pop().split(";").shift();
  }
  if (getCookie("banner2FA")) {
    document.getElementById("block-eulogin2fabannerinformationblock").style.visibility="hidden";
  }
</script>
    <div>
      <div>
        <p><strong>Important upcoming Change.<br/> The EU login</strong> will require a <strong>2 factor authentication</strong> to access this platform from the 20th of December onward. Get ready and activate <b>now</b> the multi-factor authentication for your EU login account. For more details on how to proceed go to the <a onclick="linkHandler(event)" style="color:#FFF" href="https://webgate.ec.europa.eu/cas/manuals/EU_Login_Tutorial.pdf" target="_blank">User Guide</a> or the <a onclick="linkHandler(event)" style="color:#FFF" href="https://webgate.ec.europa.eu/cas/help.html" target="_blank">EU Login help page</a>. Should you require additional assistance you can contact the EU login team directly via the contact form. (Click here to close this message)</p>
      </div>
    </div>
</div>';

    return[
      '#title' => '',
      '#type' => 'inline_template',
      '#template' => $template
      ];
}

}
