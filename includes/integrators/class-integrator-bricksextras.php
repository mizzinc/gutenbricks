<?php
namespace Gutenbricks\Integrators;

class Integrator_Bricksextras extends Base_Integrator
{
  public static $plugin_path = 'bricksextras/bricksextras.php';

  public function action__pre_process_block($template_id, $elements, $block) {
    global $bricksExtrasElementCSSAdded;
    if (empty($bricksExtrasElementCSSAdded)) {
      $bricksExtrasElementCSSAdded = array(
        "version" => "1.4.0",
        "xbacktotop" => false,
        "xbeforeafterimage" => false,
        "xburgertrigger" => false,
        "xcontentswitcher" => false,
        "xcontenttimeline" => false,
        "xcopytoclipboard" => false,
        "xdynamicchart" => false,
        "xdynamiclightbox" => false,
        "xdynamictable" => false,
        "xcountdown" => false,
        "xfluentform" => false,
        "xnotificationbar" => false,
        "xheaderrow" => false,
        "xheadersearch" => false,
        "ximagehotspots" => false,
        "xinteractivecursor" => false,
        "xlottie" => false,
        "xpromodal" => false,
        "xpromodalnestable" => false,
        "xoffcanvas" => false,
        "xoffcanvasnestable" => false,
        "xpopover" => false,
        "xproaccordion" => false,
        "xproalert" => false,
        "xproslider" => false,
        "xproslidercontrol" => false,
        "xproslidergallery" => false,
        "xtabs" => false,
        "xqueryloopextras" => false,
        "xreadingprogressbar" => false,
        "xreadmoreless" => false,
        "xshortcodewrapper" => false,
        "xbreadcrumbs" => false,
        "xslidemenu" => false,
        "xsocialshare" => false,
        "xstarrating" => false,
        "xtableofcontents" => false,
        "xtoggleswitch" => false,
        "xwpgbfacetstyler" => false,
        "xwsforms" => false,
        "xraymode" => false,
      );
    }
  }
}

