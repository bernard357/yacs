<?php
/**
 * achieve large support
 *
 * This overlay allows surfers to attach at most one decision to one page.
 *
 * To create a petition, create a new page with this overlay.
 * Then define the scope of your petition through following attributes:
 * - voters - members, editors, or associates
 * - end date and hour - signatures won't be accepted afterwards
 *
 * @author Bernard Paques
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */
class Petition extends Overlay {

	/**
	 * allow or block operations
	 *
	 * @param string the kind of item to handle
	 * @param string the foreseen operation ('edit', 'new', ...)
	 * @return TRUE if the operation is accepted, FALSE otherwise
	 */
	function allows($type, $action) {
		global $context;

		// we filter only votes
		if($type != 'decision')
			return TRUE;

		// we filter only new votes
		if($action != 'new')
			return TRUE;

		// block if this surfer has already voted
		include_once $context['path_to_root'].'decisions/decisions.php';
		if(isset($this->attributes['id']) && ($ballot = Decisions::get_ballot('article:'.$this->attributes['id']))) {
			Skin::error(i18n::s('You have already signed'));
			return FALSE;
		}

		// current time
		$now = gmstrftime('%Y-%m-%d %H:%M:%S');

		// vote is open
		$open = FALSE;

		// no end date
		if(!isset($this->attributes['end_date']) || ($this->attributes['end_date'] <= NULL_DATE))
			$open = TRUE;

		// vote has not ended yet
		elseif($now < $this->attributes['end_date'])
			$open = TRUE;

		// wait a minute
		if(!$open) {
			Skin::error(i18n::s('Petition has been closed.'));
			return FALSE;
		}

		// allowed
		return TRUE;
	}

	/**
	 * build the list of fields for one overlay
	 *
	 * @see overlays/overlay.php
	 *
	 * @param the hosting attributes
	 * @return a list of ($label, $input, $hint)
	 */
	function get_fields($host) {
		global $context;

		// accepted voters
		$label = i18n::s('Scope');
		$input = '<input type="radio" name="voters" value="members"';
		if(!isset($this->attributes['voters']) || ($this->attributes['voters'] == 'members'))
			$input .= ' checked="checked"';
		$input .= ' /> '.i18n::s('All members of the community').BR."\n";
		$input .= '<input type="radio" name="voters" value="editors"';
		if(isset($this->attributes['voters']) && ($this->attributes['voters'] == 'editors'))
			$input .= ' checked="checked"';
		$input .= ' /> '.i18n::s('Editors of this section').BR."\n";
		$input .= '<input type="radio" name="voters" value="associates"';
		if(isset($this->attributes['voters']) && ($this->attributes['voters'] == 'associates'))
			$input .= ' checked="checked"';
		$input .= ' /> '.i18n::s('Associates only').BR."\n";
		$input .= '<input type="radio" name="voters" value="custom"';
		if(isset($this->attributes['voters']) && ($this->attributes['voters'] == 'custom'))
			$input .= ' checked="checked"';
		$input .= ' /> '.i18n::s('Following people:')
			.' <input type="text" name="voter_list"  onfocus="document.main_form.voters[3].checked=\'checked\'" size="40" />'.BR."\n";
		$fields[] = array($label, $input);

		// end date
		$label = i18n::s('End date');

		// adjust date from UTC time zone to surfer time zone
		$value = '';
		if(isset($this->attributes['end_date']) && ($this->attributes['end_date'] > NULL_DATE))
			$value = Surfer::from_GMT($this->attributes['end_date']);

		$input = '<input type="text" name="end_date" value ="'.encode_field($value).'" size="32" maxlength="64" />';
		$hint = i18n::s('YYYY-MM-AA HH:MM');
		$fields[] = array($label, $input, $hint);

		return $fields;
	}

	/**
	 * get an overlaid label
	 *
	 * Accepted action codes:
	 * - 'edit' the modification of an existing object
	 * - 'delete' the deleting form
	 * - 'new' the creation of a new object
	 * - 'view' a displayed object
	 *
	 * @see overlays/overlay.php
	 *
	 * @param string the target label
	 * @param string the on-going action
	 * @return the label to use
	 */
	function get_label($name, $action='view') {
		global $context;

		// the target label
		switch($name) {

		// description label
		case 'description':
			return i18n::s('Petition description');

		// page title
		case 'page_title':

			switch($action) {

			case 'edit':
				return i18n::s('Edit petition record');

			case 'delete':
				return i18n::s('Delete petition record');

			case 'new':
				return i18n::s('New petition');

			case 'view':
			default:
				// use the article title as the page title
				return NULL;

			}
		}

		// no match
		return NULL;
	}

	/**
	 * display the content of one petition
	 *
	 * @see overlays/overlay.php
	 *
	 * @param array the hosting record
	 * @return some HTML to be inserted into the resulting page
	 */
	function &get_view_text($host=NULL) {
		global $context;

		include_once $context['path_to_root'].'decisions/decisions.php';

		// the text
		$text = '';

		// get ballot
		$vote = NULL;
		if($ballot = Decisions::get_ballot('article:'.$this->attributes['id'])) {

			// link to ballot page
			if($variant == 'view')
				$text .= '<p>'.Skin::build_link(Decisions::get_url($ballot), i18n::s('View your signature'), 'shortcut').'</p>';

		// link to vote
		} elseif(Surfer::is_member())
			$vote = ' '.Skin::build_link(Decisions::get_url('article:'.$this->attributes['id'], 'decision'), i18n::s('Sign this petition'), 'shortcut').' ';

		// vote is open
		$open = FALSE;

		// current time
		$now = gmstrftime('%Y-%m-%d %H:%M:%S');

		// no end date
		if(!isset($this->attributes['end_date']) || ($this->attributes['end_date'] <= NULL_DATE)) {

			$text .= '<p>'.i18n::s('Petition is currently open').$vote.'</p>';

			$open = TRUE;

		// not ended yet
		} elseif($now < $this->attributes['end_date']) {

			$text .= '<p>'.sprintf(i18n::s('Petition is open until %s'), Skin::build_date($this->attributes['end_date'], 'standalone').$vote).'</p>';

			$open = TRUE;

		// petition is over
		} else {

			$text .= '<p>'.sprintf(i18n::s('Petition has ended on %s'), Skin::build_date($this->attributes['end_date'], 'standalone')).'</p>';

		}

		// decisions for this vote
		list($total, $yes, $no) = Decisions::get_results_for_anchor('article:'.$this->attributes['id']);

		// show results
		if($total) {

			$label = '';

			// total number of votes
			if($total)
				$label .= sprintf(i18n::ns('1 signature', '%d signatures', $total), $total);

			// count of yes
			if($yes)
				$label .= ', '.sprintf(i18n::ns('1 approval', '%d approvals', $yes), $yes).' ('.(int)($yes*100/$total).'%)';

			// count of no
			if($no)
				$label .= ', '.sprintf(i18n::ns('1 reject', '%d rejects', $no), $no).' ('.(int)($no*100/$total).'%)';

			// a link to ballots
			$text .= '<p>'.Skin::build_link(Decisions::get_url('article:'.$this->attributes['id'], 'list'), $label, 'basic', i18n::s('See ballot papers')).'</p>';

		}

		// voters, only before vote end
		$text .= '<p>';

		if(!isset($this->attributes['voters']) || ($this->attributes['voters'] == 'members'))
			$text .= i18n::s('All members of the community are allowed to sign');

		elseif($this->attributes['voters'] == 'editors')
			$text .= i18n::s('Editors of this section are allowed to sign');

		elseif($this->attributes['voters'] == 'associates')
			$text .= i18n::s('Only associates are allowed to sign');

		elseif($this->attributes['voters'] == 'custom') {
			$text .= i18n::s('Allowed: ');
			if(!isset($this->attributes['voter_list']) || !trim($this->attributes['voter_list'])) {
				$text .= i18n::s('(to be defined)');
			} else
				$text .= $this->attributes['voter_list'];
		}

		$text .= '</p>';

		$text = '<div class="overlay">'.Codes::beautify($text).'</div>';
		return $text;
	}

	/**
	 * retrieve the content of one modified overlay
	 *
	 * @see overlays/overlay.php
	 *
	 * @param the fields as filled by the end user
	 * @return the updated fields
	 */
	function parse_fields($fields) {
		global $context;

		$this->attributes['voters'] = isset($fields['voters']) ? $fields['voters'] : '';
		$this->attributes['voter_list'] = isset($fields['voter_list']) ? $fields['voter_list'] : '';
		$this->attributes['end_date'] = isset($fields['end_date']) ? $fields['end_date'] : '';

		// adjust date from surfer time zone to UTC time zone
		if(isset($fields['end_date']) && $fields['end_date'])
			$this->attributes['end_date'] = Surfer::to_GMT($fields['end_date']);

		return $this->attributes;
	}

	/**
	 * remember an action once it's done
	 *
	 * To be overloaded into derivated class
	 *
	 * @param string the action 'insert', 'update' or 'delete'
	 * @param array the hosting record
	 * @return FALSE on error, TRUE otherwise
	 */
	function remember($variant, $host) {
		global $context;

		// remember the id of the master record
		$id = $host['id'];

		// build the update query
		switch($variant) {

		case 'delete':
			include_once $context['path_to_root'].'decisions/decisions.php';
			Decisions::delete_for_anchor('article:'.$this->attributes['id']);
			break;

		case 'insert':
			break;

		case 'update':
			break;
		}

		return TRUE;
	}

}

?>