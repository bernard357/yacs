<?php
/**
 * retrieve value for a key in database
 * called by view.php scripts to get real value for inline editing
 *
 * @author Christophe Battarel [email]christophe.battarel@altairis.fr[/email]
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

include_once '../shared/global.php';
include_once $context['path_to_root'].'shared/surfer.php';
include_once $context['path_to_root'].'shared/anchors.php';
include_once $context['path_to_root'].'overlays/overlay.php';

$anchor =& Anchors::get($_REQUEST['anchor']);

if ($anchor && $anchor->is_viewable()) {

  $class = $anchor->get_type();
  if ($class == 'category')
    $class = 'categories';
  else
    $class .= 's';

  include_once $context['path_to_root'].$class.'/'.$class.'.php';
  eval('$record = '.$class.'::get($anchor->item[\'id\']);');

  $key = $_REQUEST['key'];
  
	echo $record[$key];
}
else
  echo '';
?>