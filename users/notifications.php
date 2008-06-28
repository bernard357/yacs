<?php
/**
 * the database abstraction layer for notifications
 *
 * This script takes care of real-time notifications to users.
 *
 * Notifications can be generated by another surfer, for example to start a new
 * chat session, or by the server itself, for example during co-browsing sessions,
 * to transmit web addresses from a master workstation to a slave workstation.
 *
 * Ultimately, notifications are always deleted after 180 seconds of life time
 * when there is no way to push them to their target recipients.
 *
 * [title]Transmission of notifications[/title]
 *
 * The transmission is a fast push from the server, but prepared by the browser.
 *
 * YACS adds to browsing sessions asynchronous calls to [script]users/heartbit.php[/script]
 * which does not answer until a new notification is ready for transmission.
 *
 * When several calls from the same surfer are pending, YACS always select the
 * most recent one for the actual transmission. Then the notification is purged
 * from the database.
 *
 * [title]Notification payload[/title]
 *
 * Notifications are made of named attributes transmitted to the browser
 * as JSON-encoded arrays.
 *
 * The first attribute, named 'type', defines the kind of notification that is
 * transmitted and can take following values:
 *
 * - 'alert' - a notification generated by the server to signal a new or updated
 * page to visit
 *
 * - 'browse' - a notification that carries a web link shared from a master
 * browser to slave browsers
 *
 * - 'hello' - a short message to be displayed on screen, that can also be used
 * to initiate a real-time chat session
 *
 *
 * [subtitle]Alert notification[/subtitle]
 *
 * This kind of notification is used to alert present users that some
 * page of their watch list has been updated.
 *
 * When the 'alert' notification reaches the browser, it is presented
 * to the surfer, in a dialog box, for validation. Then the suggested URI is
 * loaded in a separate window.
 *
 * All following attributes can be part of an 'alert' notification:
 * - 'recipient' - id of the target community member
 * - 'type' = 'alert'
 * - 'action' - coded label for the action (e.g., 'comment:create')
 * - 'address' - the fully qualified web link of the updated page
 * - 'nick_name' - short name of the surfer that has updated the page
 * - 'title' - title of the updated page
 *
 *
 * [subtitle]Hello notification[/subtitle]
 *
 * This kind of notification is used to communicate directly between surfers.
 * We are talking here of a very bare communication system, equivalent to SMS.
 *
 * This kind of notification can also be used to establish contact between two
 * on-line surfers:
 *
 * 1. Alice wishes to ask a fast and dirty question to Bob
 *
 * 2. For this purpose she visits the user profile of Bob, and checks contact
 * information provided there. Hopefully, the server reports to Alice that
 * Bob is currently browsing the site, and therefore is probably available
 * for contact.
 *
 * 3. Alice clicks on a link that triggers [script]users/contact.php[/script].
 * This page prepares a 'hello' notification and triggers [script]users/heartbit.php[/script],
 * which insert the notification in the database and fall asleep.
 *
 * 4. One of the asynchronous calls to [script]users/heartbit.php[/script] initiated
 * from Bob's browser wakes up. It pushes the new notification to the browser
 * and purges the database.
 *
 * 5. The YACS AJAX code decodes the notification and presents it to Bob, as a
 * dialog box. Bob may accept or refuse to contact Alice.
 *
 * 6a. When Bob accepts to start a new chat session, he is driven to the
 * web address transmitted in Alice's message.
 *
 * 6b. When Bob denegates the contact, nothing happens
 *
 * All following attributes can be part of a 'hello' notification:
 * - 'recipient' - id of the target community member
 * - 'type' = 'hello'
 * - 'nick_name' - short name of the surfer that is requesting some direct contact
 * - 'address' - a web link to browse, if accepted by receiving party (optional)
 * - 'message' - less than 200 characters submitted by originator
 *
 *
 * [subtitle]Browse notification[/subtitle]
 *
 * This kind of notification is used to allow several surfers to meet at the
 * same page, as explained previously.
 *
 * The behavior of the YACS AJAX code depends if the notification is expected
 * or not. When the 'browse' notification arrives asynchronously, it is presented
 * to the surfer, in a dialog box, for validation. Then the suggested URI is
 * loaded in a separate window. In case of continuous co-browsing, the
 * navigation window is updated without notice.
 *
 * The implementation is straightforward, since YACS asks the surfer only when
 * the target window does not exist.
 *
 * All following attributes can be part of an 'browse' notification:
 * - 'recipient' - id of the target community member
 * - 'type' = 'browse'
 * - 'nick_name' - short name of the surfer that is generating the link
 * - 'message' - title or label of the target link (optional)
 * - 'address' - the fully qualified web link to be loaded
 *
 *
 * [title]Implementation architecture[/title]
 *
 * The YACS notification system is spread in several files:
 * - shared/yacs.js - the AJAX code that manages notifications on browser side
 * - users/notifications.php - the storage engine for transient notifications
 * - users/heartbit.php - receives and pushes notifications
 * - users/contact.php - send a textual notification, or ask for chat
 *
 * @author Bernard Paques
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */
Class Notifications {

	/**
	 * store a new notification
	 *
	 * @param array attributes of the notification
	 * @return boolean TRUE on success, FALSE otherwise
	 *
	 * @see users/heartbit.php
	**/
	function post(&$fields) {
		global $context;

		// delete obsoleted notifications
		$threshold = gmstrftime('%Y-%m-%d %H:%M:%S', time() - 180);

		// suppress previous record, if any --do not report on error, if any
		$query = "DELETE FROM ".SQL::table_name('notifications')
			." WHERE (edit_date < '".SQL::escape($threshold)."')";
		SQL::query($query, TRUE);

		// update the database; do not report on error
		$query = "INSERT INTO ".SQL::table_name('notifications')." SET"
			." recipient='".SQL::escape($fields['recipient'])."',"
			." data='".SQL::escape(serialize($fields))."',"
			." edit_date='".gmstrftime('%Y-%m-%d %H:%M:%S')."'";
		SQL::query($query, TRUE);

		// end of job
		return TRUE;
	}

	/**
	 * pull most recent notification
	 *
	 * This script will wait for new updates before providing them to caller.
	 * Because of potential time-outs, you have to care of retries.
	 *
	 * @return array attributes of the oldest notification, if any
	 *
	 * @see users/heartbit.php
	 */
	function &pull() {
		global $context;

		// return by reference
		$output = NULL;

		// only authenticated surfers can be notified
		if(!Surfer::get_id()) {

			// dump profile information, if any
			Logger::profile_dump();

			Safe::header('Status: 403 Forbidden', TRUE, 403);
			die(i18n::s('You are not allowed to perform this operation.'));
		}

		// only consider recent records -- 180 = 3 minutes * 60 seconds
		$threshold = gmstrftime('%Y-%m-%d %H:%M:%S', time() - 180);

		// the query to get time of last update
		$query = "SELECT * FROM ".SQL::table_name('notifications')." AS notifications "
			." WHERE (notifications.recipient = ".SQL::escape(Surfer::get_id()).")"
			."	AND (edit_date >= '".SQL::escape($threshold)."')"
			." ORDER BY notifications.edit_date"
			." LIMIT 1";

		// kill the request if there is nothing to return
		if((!$record =& SQL::query_first($query)) || !isset($record['data'])) {

			// dump profile information, if any
			Logger::profile_dump();

			header('Status: 504 Gateway Timeout', TRUE, 504);
			die('Retry');
		}

		// restore the entire record
		$output = Safe::unserialize($record['data']);

		// localize on server-side message displayed by the client software
		$lines = array();
		switch($output['type']) {

		case 'alert':
			// a new item has been created
			if(strpos($output['action'], ':create')) {

				$lines[] = sprintf(i18n::s('New: %s'), $output['title'])."\n"
					.sprintf(i18n::s('%s by %s'), ucfirst(get_action_label($output['action'])), $output['nick_name'])."\n";

				// surfer prompt
				$lines[] = i18n::s('Would you like to browse the new page?');

			// else consider this as an update
			} else {

				// provide a localized message
				$lines[] = sprintf(i18n::s('Updated: %s'), $output['title'])."\n"
					.sprintf(i18n::s('%s by %s'), ucfirst(get_action_label($output['action'])), $output['nick_name'])."\n";

				// surfer prompt
				$lines[] = i18n::s('Would you like to browse the updated page?');

			}
			break;

		case 'browse':
			// message is optional
			if(isset($output['message']) && trim($output['message']))
				$lines[] = sprintf(i18n::s('From %s:'), $output['nick_name'])."\n".$output['message']."\n";

			// address is mandatory
			$lines[] = sprintf(i18n::s('Would you like to browse the link sent by %s?'), $output['nick_name']);
			break;

		case 'hello':
			// message is optional
			if(isset($output['message']) && trim($output['message']))
				$lines[] = sprintf(i18n::s('From %s:'), $output['nick_name'])."\n".$output['message']."\n";

			// address is present on new chat
			if(isset($output['address']) && trim($output['address']))
				$lines[] = sprintf(i18n::s('Would you like to chat with %s?'), $output['nick_name']);
			break;

		}

		// content of the dialog box that will be displayed to surfer
		if(count($lines))
			$output['dialog_text'] = implode("\n", $lines);

		// forget this notification
		$query = "DELETE FROM ".SQL::table_name('notifications')." WHERE id = ".SQL::escape($record['id']);
		SQL::query($query, TRUE);

		// return the new notification
		return $output;

	}

	/**
	 * create table for notifications
	 */
	function setup() {
		global $context;

		$fields = array();
		$fields['id']			= "MEDIUMINT UNSIGNED NOT NULL AUTO_INCREMENT";
		$fields['recipient']	= "MEDIUMINT NOT NULL";
		$fields['edit_date']	= "DATETIME";
		$fields['data'] 		= "TEXT";

		$indexes = array();
		$indexes['PRIMARY KEY'] 	= "(id)";
		$indexes['INDEX recipient'] = "(recipient)";
		$indexes['INDEX edit_date'] = "(edit_date)";

		return SQL::setup_table('notifications', $fields, $indexes);
	}

	/**
	 * get some statistics
	 *
	 * @return the resulting ($count, $min_date, $max_date) array
	 *
	 * @see control/index.php
	 */
	function &stat() {
		global $context;

		// only consider recent presence records
		$threshold = gmstrftime('%Y-%m-%d %H:%M:%S', time() - 180);

		// select among available items
		$query = "SELECT COUNT(*) as count, MIN(notifications.edit_date) as oldest_date, MAX(notifications.edit_date) as newest_date"
			." FROM ".SQL::table_name('notifications')." AS notifications"
			." WHERE (notifications.edit_date >= '".SQL::escape($threshold)."')";

		$output =& SQL::query_first($query);
		return $output;
	}

}
?>