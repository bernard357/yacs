<?php
/**
 * invite participants to a section
 *
 * This script has a form to post a mail message related to an existing page.
 *
 * When a message is sent to invited people, these may, or not, be part of
 * the community.
 *
 * Long lines of the message are wrapped according to [link=Dan's suggestion]http://mailformat.dan.info/body/linelength.html[/link].
 *
 * @link http://mailformat.dan.info/body/linelength.html Dan's Mail Format Site: Body: Line Length
 *
 * Surfer signature is appended to the message, if any.
 * Else a default signature is used instead, with a link to the site front page.
 *
 * Senders can get a copy of messages if they want.
 *
 * Messages are sent using utf-8, and are either base64-encoded, or send as-is.
 *
 * @link http://www.sitepoint.com/article/advanced-email-php/3 Advanced email in PHP
 *
 * If the file [code]parameters/demo.flag[/code] exists, the script assumes that this instance
 * of YACS runs in demonstration mode, and no message is actually posted.
 *
 * Accepted calls:
 * - invite.php/&lt;id&gt;
 * - invite.php?id=&lt;id&gt;
 * - invite.php/&lt;id&gt;
 * - invite.php?id=&lt;id&gt;
 *
 * If this article, or one of its anchor, specifies a specific skin (option keyword '[code]skin_xyz[/code]'),
 * or a specific variant (option keyword '[code]variant_xyz[/code]'), they are used instead default values.
 *
 * @author Bernard Paques
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// common definitions and initial processing
include_once '../shared/global.php';

// look for the id
$id = '';
if(isset($_REQUEST['id']))
	$id = $_REQUEST['id'];
elseif(isset($context['arguments'][0]))
	$id = $context['arguments'][0];
$id = strip_tags($id);

// get the item from the database
$item =& Sections::get($id);

// get the related anchor, if any
$anchor = NULL;
if(isset($item['anchor']))
	$anchor =& Anchors::get($item['anchor']);

// get the related overlay, if any
$overlay = NULL;
include_once '../overlays/overlay.php';
if(isset($item['overlay']))
	$overlay = Overlay::load($item);

// link to contribute
if(Surfer::is_empowered() && isset($_REQUEST['provide_credentials']) && ($_REQUEST['provide_credentials'] == 'Y'))
	$link = $context['url_to_home'].$context['url_to_root'].Sections::get_url($item['id']); // to be expanded to credentials
else
	$link = $context['url_to_home'].$context['url_to_root'].Sections::get_permalink($item);

// message prefix
$message_prefix = i18n::s('I would like to invite you to the following page.')
	."\n\n".$link."\n\n";

// owners can do what they want
if(Sections::is_owned($anchor, $item))
	Surfer::empower();

// associates and editors can do what they want
if(Surfer::is_empowered())
	$permitted = TRUE;

// help to share public items
elseif(isset($item['active']) && ($item['active'] == 'Y'))
	$permitted = TRUE;

// the default is to disallow access
else
	$permitted = FALSE;

// load the skin, maybe with a variant
load_skin('articles', $anchor, isset($item['options']) ? $item['options'] : '');

// clear the tab we are in, if any
if(is_object($anchor))
	$context['current_focus'] = $anchor->get_focus();

// path to this page
$context['path_bar'] = Surfer::get_path_bar($anchor, FALSE);
if(isset($item['id']))
	$context['path_bar'] = array_merge($context['path_bar'], array(Sections::get_permalink($item) => $item['title']));

// page title
if(isset($item['title']))
	$context['page_title'] = sprintf(i18n::s('Share: %s'), $item['title']);

// stop crawlers
if(Surfer::is_crawler()) {
	Safe::header('Status: 401 Forbidden', TRUE, 401);
	Logger::error(i18n::s('You are not allowed to perform this operation.'));

// not found
} elseif(!isset($item['id'])) {
	Safe::header('Status: 404 Not Found', TRUE, 404);
	Logger::error(i18n::s('No item has the provided id.'));

// e-mail has not been enabled
} elseif(!isset($context['with_email']) || ($context['with_email'] != 'Y')) {
	Safe::header('Status: 401 Forbidden', TRUE, 401);
	Logger::error(i18n::s('E-mail has not been enabled on this system.'));

// permission denied
} elseif(!$permitted) {

	// anonymous users are invited to log in or to register
	if(!Surfer::is_logged())
		Safe::redirect($context['url_to_home'].$context['url_to_root'].'users/login.php?url='.urlencode(Sections::get_url($item['id'], 'invite')));

	// permission denied to authenticated user
	Safe::header('Status: 401 Forbidden', TRUE, 401);
	Logger::error(i18n::s('You are not allowed to perform this operation.'));

// no mail in demo mode
} elseif(isset($_SERVER['REQUEST_METHOD']) && ($_SERVER['REQUEST_METHOD'] == 'POST') && file_exists($context['path_to_root'].'parameters/demo.flag')) {
	Safe::header('Status: 401 Forbidden', TRUE, 401);
	Logger::error(i18n::s('You are not allowed to perform this operation in demonstration mode.'));

// no recipient has been found
} elseif(isset($_SERVER['REQUEST_METHOD']) && ($_SERVER['REQUEST_METHOD'] == 'POST') && (!isset($_REQUEST['to']) || !$_REQUEST['to'])) {
	Safe::header('Status: 401 Forbidden', TRUE, 401);
	Logger::error(i18n::s('Please provide a recipient address.'));

// stop robots
} elseif(isset($_SERVER['REQUEST_METHOD']) && ($_SERVER['REQUEST_METHOD'] == 'POST') && Surfer::may_be_a_robot()) {
	Safe::header('Status: 401 Forbidden', TRUE, 401);
	Logger::error(i18n::s('Please prove you are not a robot.'));

// process submitted data
} elseif(isset($_SERVER['REQUEST_METHOD']) && ($_SERVER['REQUEST_METHOD'] == 'POST')) {

	// ensure the section has a private handle
	if(!isset($item['handle']) || !$item['handle']) {
		$item['handle'] = md5(mt_rand());

		// save in the database
		$fields = array();
		$fields['id'] = $item['id'];
		$fields['handle'] = $item['id'];
		$fields['silent'] = 'Y';
		Sections::put_attributes($fields);
	}

	// track anonymous surfers
	Surfer::track($_REQUEST);

	// sender address
	$from = Surfer::from();

	// recipient(s) address(es)
	$to = '';
	if(isset($_REQUEST['to']))
		$to = strip_tags($_REQUEST['to']);
	if(isset($_REQUEST['self_copy']) && ($_REQUEST['self_copy'] == 'Y') && strpos($from, '@')) {
		if($to)
			$to .= ', ';
		$to .= $from;
	}

	// message subject
	$subject = '';
	if(isset($_REQUEST['subject']))
		$subject = strip_tags($_REQUEST['subject']);

	// message body
	$message = $message_prefix;
	if(isset($_REQUEST['message']))
		$message .= strip_tags($_REQUEST['message']);

	// add a tail to the sent message
	if($message) {

		// use surfer signature, if any
		$signature = '';
		if(($user =& Users::get(Surfer::get_id())) && $user['signature'])
			$signature = $user['signature'];

		// else use default signature
		else
			$signature = sprintf(i18n::s('Visit %s to get more interesting pages.'), $context['url_to_home'].$context['url_to_root']);

		// transform YACS code, if any
		if(is_callable('Codes', 'render'))
			$signature = Codes::render($signature);

		// plain text only
		$signature = trim(strip_tags($signature));

		// append the signature
		if($signature)
			$message .= "\n\n-----\n".$signature;

	}

	// make an array of recipients
	if(!is_array($to))
		$to = explode(',', $to);

	// process every recipient
	$actual_names = array();
	foreach($to as $recipient) {

		// clean the provided string
		$recipient = trim(str_replace(array("\r\n", "\r", "\n", "\t"), ' ', $recipient));

		// assume regular message
		$actual_message = $message;

		// we have a valid e-mail address
		if(preg_match('/\w+@\w+\.\w+/', $recipient)) {

			// add credentials in message
			if(Surfer::is_empowered() && isset($_REQUEST['provide_credentials']) && ($_REQUEST['provide_credentials'] == 'Y')) {

				// extract the actual e-mail address -- Foo Bar <foo@bar.com> => foo@bar.com
				$tokens = explode(' ', $recipient);
				$actual_recipient = trim(str_replace(array('<', '>'), '', $tokens[count($tokens)-1]));

				// build credentials --see users/login.php
				$credentials = array();
				$credentials[0] = 'visit';
				$credentials[1] = 'section:'.$item['id'];
				$credentials[2] = $actual_recipient;
				$credentials[3] = sprintf('%u', crc32($actual_recipient.':'.$item['handle']));

				// the secret link
				$link = Users::get_url($credentials, 'credentials');

				// translate strings to allow for one-click authentication
				$actual_message = str_replace(Sections::get_url($item['id']), $link, $message);

			}

		// look for a user with this nick name
		} elseif(($user =& Users::get($recipient))) {

			// make this user an editor of the target section
			if(Surfer::is_empowered() && isset($_REQUEST['provide_credentials']) && ($_REQUEST['provide_credentials'] == 'Y'))
				Members::assign('user:'.$user['id'], 'section:'.$item['id']);

			// always add the item to the watch list
			Members::assign('section:'.$item['id'], 'user:'.$user['id']);

			// this person has no email address
			if(!$user['email'])
				continue;

			// use this email address
			if($user['full_name'])
				$recipient = '"'.$user['full_name'].'" <'.$user['email'].'>';
			else
				$recipient = '"'.$user['nick_name'].'" <'.$user['email'].'>';

		// skip this recipient
		} else {
			if($recipient)
				Logger::error(sprintf(i18n::s('Error while sending the message to %s'), $recipient));
			continue;
		}

		// change content for message poster
		if(!strcmp($recipient, $from)) {
			$actual_message = i18n::s('This is a copy of the message you have sent, for your own record.')."\n".'-------'."\n".htmlspecialchars_decode(join(', ', $actual_names))."\n".'-------'."\n\n".$actual_message;
		}

		// post it
		if(Mailer::post($from, $recipient, $subject, $actual_message))
			$actual_names[] = htmlspecialchars($recipient);
	}
	Mailer::close();

	// display the list of actual recipients
	if($actual_names)
		$context['text'] .= '<p>'.sprintf(i18n::s('Your message is being transmitted to %s'), Skin::finalize_list($actual_names, 'compact')).'</p>';

	// follow-up commands
	$follow_up = i18n::s('What do you want to do now?');
	$menu = array();
	$menu = array_merge($menu, array(Sections::get_permalink($item) => i18n::s('Back to main page')));
	$menu = array_merge($menu, array(Sections::get_url($item['id'], 'invite') => i18n::s('Invite participants')));
	$follow_up .= Skin::build_list($menu, 'menu_bar');
	$context['text'] .= Skin::build_block($follow_up, 'bottom');

// a form to send an invitation to several people
} else {

	// the form to send a message
	$context['text'] .= '<form method="post" action="'.$context['script_url'].'" onsubmit="return validateDocumentPost(this)" id="main_form"><div>';
	$fields = array();

	// additional fields for anonymous surfers
	if(!Surfer::is_logged()) {

		// splash
		$login_url = $context['url_to_root'].'users/login.php?url='.urlencode(Sections::get_url($item['id'], 'invite'));
		$context['text'] .= '<p>'.sprintf(i18n::s('If you have previously registered to this site, please %s. Then the server will automatically put your name and address in following fields.'), Skin::build_link($login_url, 'authenticate'))."</p>\n";

		// the name, if any
		$label = i18n::s('Your name');
		$input = '<input type="text" name="edit_name" size="45" maxlength="128" accesskey="n" value="'.encode_field(Surfer::get_name(' ')).'" />';
		$hint = i18n::s('Let us a chance to know who you are');
		$fields[] = array($label, $input, $hint);

		// the address, if any
		$label = i18n::s('Your e-mail address');
		$input = '<input type="text" name="edit_address" size="45" maxlength="128" accesskey="a" value="'.encode_field(Surfer::get_email_address()).'" />';
		$hint = i18n::s('Put your e-mail address to receive feed-back');
		$fields[] = array($label, $input, $hint);

		// stop robots
		if($field = Surfer::get_robot_stopper())
			$fields[] = $field;

	}

	// recipients
	$label = i18n::s('Invite participants');
	$input = '';
	if(Surfer::is_empowered()) {
		// share a private page
		if($item['active'] == 'N')
			$input .= '<input type="radio" name="provide_credentials" value="Y" checked="checked" /> '.i18n::s('to manage content').BR;

		// page can be accessed by many people
		else
			$input .= '<input type="radio" name="provide_credentials" value="N" checked="checked" /> '.i18n::s('to review content')
				.' &nbsp; <input type="radio" name="provide_credentials" value="Y" /> '.i18n::s('to manage content').BR;
	}
	$input .= '<textarea name="to" id="names" rows="3" cols="50"></textarea><div id="names_choices" class="autocomplete"></div>';
	$hint = i18n::s('Enter nick names, or email addresses, separated by commas.');
	$fields[] = array($label, $input, $hint);

	// the subject
	$label = i18n::s('Message title');
	$title = '';
	if($name = Surfer::get_name())
		$title = sprintf(i18n::s('Invitation: %s'), $item['title']);
	$input = '<input type="text" name="subject" size="50" maxlength="255" value="'.encode_field($title).'" />';
	$fields[] = array($label, $input);

	// message author
	$author = Surfer::get_name();
	if($author_id = Surfer::get_id())
		$author .= "\n".$context['url_to_home'].$context['url_to_root'].Users::get_url($author_id, 'view', Surfer::get_name());

	// the message
	$label = i18n::s('Message content');
	$content = i18n::s('Please let me thank you for your kind support.')."\n\n".$author;
	$input = str_replace("\n", BR, $message_prefix)
		.'<textarea name="message" rows="15" cols="50">'.encode_field($content).'</textarea>';
	$hint = i18n::s('Use only plain ASCII, no HTML.');
	$fields[] = array($label, $input, $hint);

	// build the form
	$context['text'] .= Skin::build_form($fields);

	//
	// bottom commands
	//
	$menu = array();

	// the submit button
	$menu[] = Skin::build_submit_button(i18n::s('Submit'), i18n::s('Press [s] to submit data'), 's');

	// cancel button
	if(isset($item['id']))
		$menu[] = Skin::build_link(Sections::get_permalink($item), i18n::s('Cancel'), 'span');

	// insert the menu in the page
	$context['text'] .= Skin::finalize_list($menu, 'assistant_bar');

	// get a copy of the sent message
	$context['text'] .= '<p><input type="checkbox" name="self_copy" value="Y" checked="checked" /> '.i18n::s('Send me a copy of this message.').'</p>';

	// transmit the id as a hidden field
	$context['text'] .= '<input type="hidden" name="id" value="'.$item['id'].'" />';

	// end of the form
	$context['text'] .= '</div></form>';

	// append the script used for data checking on the browser
	$context['text'] .= JS_PREFIX
		.'// check that main fields are not empty'."\n"
		.'func'.'tion validateDocumentPost(container) {'."\n"
		."\n"
		.'	// to is mandatory'."\n"
		.'	if(!container.to.value) {'."\n"
		.'		alert("'.i18n::s('Please provide a recipient address.').'");'."\n"
		.'		Yacs.stopWorking();'."\n"
		.'		return false;'."\n"
		.'	}'."\n"
		."\n"
		.'	// title is mandatory'."\n"
		.'	if(!container.subject.value) {'."\n"
		.'		alert("'.i18n::s('Please provide a meaningful title.').'");'."\n"
		.'		Yacs.stopWorking();'."\n"
		.'		return false;'."\n"
		.'	}'."\n"
		."\n"
		.'	// body is mandatory'."\n"
		.'	if(!container.message.value) {'."\n"
		.'		alert("'.i18n::s('Message content can not be empty.').'");'."\n"
		.'		Yacs.stopWorking();'."\n"
		.'		return false;'."\n"
		.'	}'."\n"
		."\n"
		.'	// successful check'."\n"
		.'	return true;'."\n"
		.'}'."\n"
		."\n"
		.'// set the focus on first form field'."\n"
		.'Event.observe(window, "load", function() { $("names").focus() });'."\n"
		."\n"
		."\n"
		.'// enable autocompletion'."\n"
		.'Event.observe(window, "load", function() { new Ajax.Autocompleter("names", "names_choices", "'.$context['url_to_root'].'users/complete.php", { paramName: "q", minChars: 1, frequency: 0.4, tokens: "," }); });'."\n"
		.JS_SUFFIX;

	// help message
	$help = '<p>'.i18n::s('Recipient addresses are used only once, to send your message, and are not stored afterwards.').'</p>';
	$context['components']['boxes'] = Skin::build_box(i18n::s('Help'), $help, 'boxes', 'help');

}

// render the skin
render_skin();

?>