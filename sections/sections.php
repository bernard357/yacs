<?php
/**
 * the database abstraction layer for sections
 *
 * @todo ensure that locked sections cannot receive images
 *
 * [title]How do sections differ from categories?[/title]
 *
 * Sections are the top-level containers of articles.
 *
 * Sections provide following advanced features:
 *
 * [*] Access restriction - Some sections are public, while others are reserved to authenticated members or only to associates.
 * Of course, YACS ensures that access rules are cascaded to anchored articles.
 * Create protected sections each time you want to restrict access to some information.
 *
 * [*] Overlay extension - Articles and sub-sections can be extended using the overlay interface.
 * You just have to edit a section and to mention, in the content overlay field, the name of the PHP class to use.
 * For example, drop 'recipe' to transform a section to a cookbook, or 'poll' to create a section dedicated to polls.
 * Of course, you can create overlays of your own.
 * The index of supported overlays is at [link]overlays/index.php[/link].
 *
 * [*] Skin variant - Each section can have its own skin, that will be cascaded to related articles as well.
 * Basically, this gives you the power of selecting quite rich rendering options for your site.
 * For example, you can select one blog style for section A, and another blog style for section B.
 * If you have a single skin, and want to transmit the variant you want to use, add the 'variant_XXXX' to the options field,
 * where XXXX is your actual option.
 * If you prefer to use a particular skin at some section, use 'skin_YYYY' instead, where YYYY is the name of the target skin.
 *
 *
 * [title]Pre-defined sections[/title]
 *
 * At the moment following section nick names are used throughout yacs:
 * - 'clicks' - to put orphan links when clicked - created automatically in [script]links/links.php[/script]
 * - 'covers' - the articles displayed at the very first page of the site
 * - 'extra_boxes' - boxes only displayed at the front page, in the extra panel
 * - 'files' - a sample library of files
 * - 'forum' - a sample forum
 * - 'gadget_boxes' - boxes only displayed at the front page, as gadgets
 * - 'letters' - pages sent by e-mail to subscribers - created automatically in [script]letters/new.php[/script]
 * - 'links' - a sample library of links
 * - 'menus' - the articles used to build the main menu of the server
 * - 'my_section' - a sample plain section
 * - 'navigation_boxes' - boxes on the left of the page
 * - 'partners' - our preferred partners
 * - 'polls' - some of the polls published on this site
 * - 'queries' - pages sent by surfers to submit their queries to the webmaster - created automatically in [script]query.php[/script]
 * - 'recipes' - a sample cookbook
 * - 'threads' - private pages
 *
 * @see letters/new.php
 * @see links/links.php
 * @see query.php
 *
 *
 * [title]How to lock a section?[/title]
 *
 * A section can be locked to avoid new posts.
 * This feature only concerns regular members of the community, as associates and editors are always allowed to add, change of remove any page.
 * Note that locked sections do not appear in w.bloggar lists, even to authenticated associates.
 *
 * [title]How to manage options for sections?[/title]
 *
 * The options fields are a convenient place to save attributes for any section without extending the database schema.
 * As sections are commonly used to anchor some pages, their options can also be checked through the [code]has_option()[/code]
 * member function of the [code]Anchor[/code] interface. Check [script]shared/anchor.php[/script] for more information.
 *
 * Options for section content (articles, etc.) are distinct from options for the section itself.
 *
 * You can combine any of following keywords in fields for content, with the separator (spaces, tabs, commas) of your choice:
 *
 * [*] [code]anonymous_edit[/code] - Allow anonymous surfers to handle pages, files, etc., attached to the section.
 * The section itself cannot be modified anonymously. Use this setting to configure Wiki-like sections.
 *
 * [*] [code]auto_publish[/code] - Every post is automatically published, without control from an associate.
 * Posters can prevent publication by adding the option 'draft' to their post.
 *
 * [*] [code]members_edit[/code] - Allow authenticated members to handle pages, files, etc., attached to the section.
 * Use this setting to create Wikis restricted to the community.
 *
 * [*] [code]no_comments[/code] - Prevent surfers to react to posted articles.
 *
 * [*] [code]with_export_tools[/code] - Add tools to convert page text
 * to PDF, MS-Word or to a printer. These tools are not displayed by default.
 * You may find useful to activate them to further help surfers to reuse published material.
 *
 * [*] [code]with_extra_profile[/code] - Display poster profile in the extra panel of the template.
 * This setting is suitable to blogs. By default YACS does not display poster profile.
 *
 * [*] [code]with_neighbours[/code] - Add previous / next links to navigate a section.
 *
 * [*] [code]with_prefix_profile[/code] - Display poster profile at the top of the page, after page title.
 * This setting is suitable to original publications, white papers, etc. By default YACS does not display poster profile.
 *
 * [*] [code]without_rating[/code] - Prevent surfers to rate pages
 * By default YACS does display rating tools.
 * Use this setting at special sections such as cover pages, etc.
 *
 * [*] [code]with_suffix_profile[/code] - Display poster profile at the bottom of the page, after main content.
 * This setting is suitable to original publications, white papers, etc. By default YACS does not display poster profile.
 *
 *
 * You can combine any of following keywords in fields for section options, with the separator (spaces, tabs, commas) of your choice:
 *
 * [*] [code]articles_by_title[/code] - Order pages by alphabetical order instead of using edition time information.
 *
 * [*] [code]files_by_title[/code] - Order files by alphabetical order instead of using edition time information.
 * To be used jointly with '[code]with_files[/code]', to activate the posting of files.
 *
 * [*] [code]links_by_title[/code] - Rank links by alphabetical order instead of using edition time information.
 * To be used jointly with '[code]with_links[/code]', to activate the posting of links.
 *
 * [*] [code]skin_&lt;xxxx&gt;[/code] - Select one skin explicitly.
 * Use this option to apply a specific skin to a section.
 * Articles anchored to this section will use the same skin.
 *
 * [*] [code]variant_&lt;xxxx&gt;[/code] - Select one skin variant explicitly.
 * Usually only the variant '[code]sections[/code]' is used throughout sections.
 * This can be changed to '[code]xxxx[/code]' by using the option [code]variant_&lt;xxxx&gt;[/code].
 * Then the underlying skin may adapt to this code by looking at [code]$context['skin_variant'][/code].
 * Basically, use variants to change the rendering of individual articles of your site, if the theme allows it.
 *
 * [*] [code]with_comments[/code] - The section index page is a thread, and can be commented.
 * By default YACS allows comments only in content pages.
 * However, in some situations you may ned to capture surfers feed-back directly at some particular section.
 * Set the option [code]with_comments[/code] to activate the commenting system.
 * Please note that threads based on sections differ from threads based on articles.
 * For example, they are not listed at the front page.
 *
 * [*] [code]with_files[/code] - Files can be attached to the section index page.
 * By default YACS allows for file attachment only in content.
 * But you may have to create a special set of files out of a section.
 * If this is the case, add the option [code]with_files[/code] manually and upload shared files.
 *
 * [*] [code]with_links[/code] - Links can be posted to the section index page.
 * By default inks can be attached only to content pages.
 * But you may have to create a special set of bookmarks out of a section.
 * If this is the case, add the option [code]with_links[/code] manually and add shared links.
 *
 *
 * [title]How to change sections layout?[/title]
 *
 *
 * [*] [code]manual[/code]
 *
 * @see articles/layout_articles_as_manual.php
 * @see comments/layout_comments_as_manual.php
 *
 * [*] [code]jive[/code]
 *
 * @see articles/layout_articles_as_jive.php
 * @see comments/layout_comments_as_jive.php
 *
 * [*] [code]yabb[/code] - This section acts a threaded forum, or bulletin board.
 * Each article is a topic. Comments are attached to articles to build a straightforward threaded system.
 *
 * @see articles/layout_articles_as_yabb.php
 * @see comments/layout_comments_as_yabb.php
 *
 *
 * [title]Handling sections at the index page[/title]
 *
 * This applies to sections that are not at the top level of the hierarchy.
 * For anchored sections, the parameter 'index_panel' defines how their content is handled on index pages of their parent section.
 *
 * [*] '[code]main[/code]' - The default value.
 * Use the layout specified in the field 'articles_layout' of the parent section ('daily', etc.)
 *
 * [*] '[code]extra[/code]' - Summarize most recent entries in an extra box at the index page.
 * May prove to be useful with discussion boards.
 *
 * [*] '[code]extra_boxes[/code]' - Same as the previous one, except that YACS creates one extra box per article.
 *
 * [*] '[code]gadget[/code]' - Summarize most recent entries in a gadget box at the index page.
 * May prove to be useful with discussion boards for example.
 *
 * [*] '[code]gadget_boxes[/code]' - Same as the previous one, except that YACS creates one gadget box per article.
 *
 * [*] '[code]news[/code]' - List articles in the area dedicated to flashy news
 *
 * [*] '[code]none[/code]' - Do not list section content at the front page.
 * Useful to cache some sections, such as the archives newsletters.
 *
 *
 * [title]How to order sections?[/title]
 *
 * Usually sections are ranked by edition date, with the most recent section coming first.
 * You can change this 'natural' order by modifying the value of the rank field.
 *
 * What is the result obtained, depending on the value set?
 *
 * [*] 10000 - This is the default value. All sections created by YACS are ranked equally.
 *
 * [*] Less than 10000 - Useful to order sections, and to make them listed at the front page.
 * Ordered, since the lower rank values come before higher rank values.
 * Pages that have the same rank value are ordered by dates, with the newest item coming first.
 * Moreover, the very first sections of the list can be listed at the front page either in
 * tabs, if the template manages this, or in the page menu, if activated at the configuration panel for skins.
 *
 * [*] More than 10000 - To reject sections at the end of the site map.
 *
 *
 * [title]Handling sections at the front page[/title]
 *
 * By default section content is automatically featured at the front page.
 * This can be changed through the 'home_panel' field.
 *
 * [*] '[code]main[/code]' - The default value.
 * Use the main layout specified in the configuration panel for skins ('alistapart', etc.)
 *
 * [*] '[code]extra[/code]' - Summarize most recent entries in an extra box at the front page.
 * May prove to be useful with discussion boards.
 *
 * [*] '[code]extra_boxes[/code]' - Same as the previous one, except that YACS creates one extra box per article.
 *
 * [*] '[code]gadget[/code]' - Summarize most recent entries in a gadget box at the front page.
 * May prove to be useful with discussion boards for example.
 *
 * [*] '[code]gadget_boxes[/code]' - Same as the previous one, except that YACS creates one gadget box per article.
 *
 * [*] '[code]icon[/code]' - List thumbnails of related articles at the bottom of the page.
 * Useful to feature logos of partners at the front page.
 *
 * [*] '[code]news[/code]' - List articles in the area dedicated to flashy news
 * Actual rendering depends of parameters 'root_news_layout' and 'root_news_count', set in [script]configure.php[/script]
 *
 * [*] '[code]none[/code]' - Do not list section content at the front page.
 * Useful to cache some sections, such as the archives newsletters.
 *
 *
 * [title]Handling sections at the site map[/title]
 *
 * By default top most sections (aka, not anchored to another section) are publicly listed at the site map.
 * Change the field 'index_map' to 'N' to prevent this behaviour. Hidden sections are listed among other special sections to preserve access from associates.
 *
 * @author Bernard Paques
 * @author Christophe Battarel [email]christophe.battarel@altairis.fr[/email]
 * @tester Fw_crocodile
 * @tester Tingadon
 * @tester Mark
 * @tester Ddaniel
 * @tester Olivier
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */
Class Sections {

	/**
	 * check if new sections can be added
	 *
	 * This function returns TRUE if sections can be added to some place,
	 * and FALSE otherwise.
	 *
	 * @param array attributes of the target section
	 * @param object parent container of the target section, if any
	 * @return boolean TRUE or FALSE
	 */
	function allow_creation($item, $anchor=NULL) {
		global $context;

		// sections are prevented in this item through layout
		if(isset($item['sections_layout']) && ($item['sections_layout'] == 'none'))
			return FALSE;

		// surfer is an associate
		if(Surfer::is_associate())
			return TRUE;

		// submissions have been disallowed
		if(isset($context['users_without_submission']) && ($context['users_without_submission'] == 'Y'))
			return FALSE;

		// surfer owns the section
		if(Sections::is_owned($item, $anchor, FALSE))
			return TRUE;

		// not for subscribers
		if(Surfer::is_member()) {

			// surfer is an editor, and the section is not private
			if(isset($item['active']) && ($item['active'] != 'N') && Sections::is_assigned($item['id']))
				return TRUE;
			if(isset($item['active']) && ($item['active'] != 'N') && is_object($anchor) && $anchor->is_assigned())
				return TRUE;
			if(!isset($item['id']) && is_object($anchor) && !$anchor->is_hidden() && $anchor->is_assigned())
				return TRUE;

		}

		// the default is to not allow for new sections
		return FALSE;
	}

	/**
	 * check if a surfer can send a message to group participants
	 *
	 * @param array a set of item attributes, aka, the target section
	 * @param object an instance of the Anchor interface
	 * @return TRUE or FALSE
	 */
	function allow_message($item, $anchor=NULL) {
		global $context;

		// subscribers can never sned a message
		if(!Surfer::is_member())
			return FALSE;

		// sanity check
		if(!isset($item['id']))
			return FALSE;

		// surfer is an associate
		if(Surfer::is_associate())
			return TRUE;

		// surfer owns the container or the section
		if(Sections::is_owned($item, $anchor, TRUE))
			return TRUE;

		// section editors can proceed
		if(isset($item['id']) && Sections::is_assigned($item['id']))
			return TRUE;

		// container editors can proceed
		if(is_object($anchor) && $anchor->is_assigned())
			return TRUE;

		// default case
		return FALSE;
	}

	/**
	 * check if a section can be modified
	 *
	 * This function returns TRUE if the section can be modified,
	 * and FALSE otherwise.
	 *
	 * @param array a set of item attributes, aka, the target section
	 * @param object an instance of the Anchor interface
	 * @return TRUE or FALSE
	 */
	function allow_modification($item, $anchor=NULL) {
		global $context;

		// sanity check
		if(!isset($item['id']) && !$anchor)
			return FALSE;

		// surfer is an associate
		if(Surfer::is_associate())
			return TRUE;

		// submissions have been disallowed
		if(isset($context['users_without_submission']) && ($context['users_without_submission'] == 'Y'))
			return FALSE;

		// surfer owns the container or the section
		if(Sections::is_owned($item, $anchor, TRUE))
			return TRUE;

		// allow editor of parent section, if not subscriber, to manage content, except on private sections
		if(Surfer::is_member() && is_object($anchor) && !$anchor->is_hidden() && $anchor->is_assigned())
			return TRUE;

		// default case
		return FALSE;
	}

	/**
	 * document modification dates for this item
	 *
	 * @param object anchor of the section
	 * @param array the section to be documented
	 * @return array strings detailed labels
	 */
	function &build_dates($anchor, $item) {
		global $context;

		// we return an array of strings
		$details = array();

		// we do want details for this page
		if(strpos($item['options'], 'with_details') !== FALSE)
			;

		// no details please
		elseif(isset($context['content_without_details']) && ($context['content_without_details'] == 'Y') && !Sections::is_owned($item, $anchor))
			return $details;

		// last modification
		if($item['edit_action'])
			$action = Anchors::get_action_label($item['edit_action']).' ';
		else
			$action = i18n::s('edited');

		if($item['edit_name'])
			$details[] = sprintf(i18n::s('%s by %s %s'), $action, Users::get_link($item['edit_name'], $item['edit_address'], $item['edit_id']), Skin::build_date($item['edit_date']));
		else
			$details[] = $action.' '.Skin::build_date($item['edit_date']);

		// post date and author
		if($item['create_date']) {

			// creation and last modification happen on same day by the same person
			if(!strcmp(substr($item['create_date'], 0, 10), substr($item['edit_date'], 0, 10)) && ($item['create_id'] == $item['edit_id']))
				;

			// mention creation date
			elseif($item['create_name'])
				$details[] = sprintf(i18n::s('posted by %s %s'), Users::get_link($item['create_name'], $item['create_address'], $item['create_id']), Skin::build_date($item['create_date']));
			else
				$details[] = Skin::build_date($item['create_date']);

		}

		// job done
		return $details;
	}

	/**
	 * clear cache entries for one item
	 *
	 * @param array item attributes
	 */
	function clear(&$item) {

		// where this item can be displayed
		$topics = array('categories', 'sections', 'users');

		// clear anchor page
		if(isset($item['anchor']))
			$topics[] = $item['anchor'];

		// clear this page
		if(isset($item['id']))
			$topics[] = 'section:'.$item['id'];

		// clear the cache
		Cache::clear($topics);

	}

	/**
	 * count records for some anchor
	 *
	 * Only sections matching following criteria are returned:
	 * - section is visible (active='Y')
	 * - section is restricted (active='R'), but surfer is a logged user
	 * - section is hidden (active='N'), but surfer is an associate
	 *
	 * Non-activated and expired sections are counted as well.
	 *
	 * @param string the selected anchor (e.g., 'section:12')
	 * @return int resulting count, or NULL on error
	 */
	function count_for_anchor($anchor) {
		global $context;

		// sanity check
		if(!$anchor)
			return NULL;

		// profiling mode
		if($context['with_profile'] == 'Y')
			logger::profile('sections::count_for_anchor');

		// limit the query to one level
		if($anchor)
			$where = "(sections.anchor LIKE '".SQL::escape($anchor)."')";
		else
			$where = "(sections.anchor='' OR sections.anchor is NULL)";

		// display active and restricted items
		$where .= "AND (sections.active='Y'";

		// list restricted sections to authenticated surfers
		if(Surfer::is_logged())
			$where .= " OR sections.active='R'";

		// list hidden sections to associates, editors and readers
		if(Surfer::is_empowered('S'))
			$where .= " OR sections.active='N'";

		// include managed sections
		if($my_sections = Surfer::assigned_sections()) {
			$where .= " OR sections.id IN (".join(", ", $my_sections).")";
			$where .= " OR sections.anchor IN ('section:".join("', 'section:", $my_sections)."')";
		}

		$where .= ")";

		// hide sections removed from index maps
		$where .= " AND (sections.index_map = 'Y')";

		// non-associates will have only live sections
		if($anchor && !Surfer::is_empowered()) {
			$where .= " AND ((sections.activation_date is NULL)"
				."	OR (sections.activation_date <= '".$context['now']."'))"
				." AND ((sections.expiry_date is NULL)"
				."	OR (sections.expiry_date <= '".NULL_DATE."') OR (sections.expiry_date > '".$context['now']."'))";
		}

		// count records sections
		$query = "SELECT sections.id"
			." FROM ".SQL::table_name('sections')." AS sections"
			." WHERE ".$where;
		return SQL::query_count($query);
	}

	/**
	 * count sections owned by a surfer
	 *
	 * @param integer owner id
	 * @return integer starting at zero
	 *
	 */
	function count_for_owner($id = NULL) {
		global $context;

		// default to current surfer
		if(!$id)
			$id = Surfer::get_id();

		// sanity check
		if(!$id)
			return 0;

		// sections are owned by this surfer
		$where = "(sections.owner_id = ".SQL::escape($id).")";

		// count sections
		$query = "SELECT sections.id FROM ".SQL::table_name('sections')." AS sections WHERE ".$where;
		return SQL::query_count($query);
	}

	/**
	 * count sections for one user
	 *
	 * @param int id of the target user
	 * @return int number of sections
	 *
	 * @see users/view.php
	 */
	function count_for_user($user_id) {
		global $context;

		// sanity check
		if(!$user_id)
			return NULL;

		// limit the scope of the request
		$where = "(sections.active='Y'";
		if(Surfer::is_logged())
			$where .= " OR sections.active='R'";
		if(Surfer::is_associate())
			$where .= " OR sections.active='N'";

		// include assigned sections
		if($my_sections = Surfer::assigned_sections())
			$where .= " OR sections.id IN (".join(', ', $my_sections).")";

		$where .= ')';

		// strip dead sections
		if((Surfer::get_id() != $user_id) && !Surfer::is_associate())
			$where .= " AND ((sections.expiry_date is NULL) "
					."OR (sections.expiry_date <= '".NULL_DATE."') OR (sections.expiry_date > '".$context['now']."'))";

		// look for watched sections with sub-queries
		if(version_compare(SQL::version(), '4.1.0', '>=')) {
			$query = "SELECT sections.id FROM (SELECT DISTINCT CAST(SUBSTRING(members.anchor, 9) AS UNSIGNED) AS target FROM ".SQL::table_name('members')." AS members WHERE (members.member LIKE 'user:".SQL::escape($user_id)."') AND (members.anchor LIKE 'section:%')) AS ids"
				.", ".SQL::table_name('sections')." AS sections"
				." WHERE (sections.id = ids.target)"
				."	AND ".$where;

		// use joined queries
		} else {
			$query = "SELECT sections.id FROM ".SQL::table_name('members')." AS members"
				.", ".SQL::table_name('sections')." AS sections"
				." WHERE (members.member LIKE 'user:".SQL::escape($user_id)."')"
				."	AND (members.anchor LIKE 'section:%')"
				."	AND (sections.id = SUBSTRING(members.anchor, 9))"
				."	AND ".$where;

		}

		// include sections assigned to this surfer
		if($these_items = Surfer::assigned_sections($user_id))
			$query = "(SELECT sections.id FROM ".SQL::table_name('sections')." AS sections"
				." WHERE sections.id IN (".join(', ', $these_items).")"
				."	AND ".$where.")"
				." UNION (".$query.")";

		// count records
		return SQL::query_count($query);
	}

	/**
	 * delete one section
	 *
	 * @param int the id of the section to delete
	 * @return boolean TRUE on success, FALSE otherwise
	 *
	 * @see sections/delete.php
	 */
	function delete($id) {
		global $context;

		// load the row
		$item =& Sections::get($id);
		if(!$item['id']) {
			Logger::error(i18n::s('No item has the provided id.'));
			return FALSE;
		}

		// delete related items
		Anchors::delete_related_to('section:'.$item['id']);

		// delete the record in the database
		$query = "DELETE FROM ".SQL::table_name('sections')." WHERE id = ".SQL::escape($item['id']);
		if(SQL::query($query) === FALSE)
			return FALSE;

		// job done
		return TRUE;
	}

	/**
	 * delete all sections for a given anchor
	 *
	 * @param string the anchor to check (e.g., 'section:123')
	 * @return void
	 *
	 * @see shared/anchors.php
	 */
	function delete_for_anchor($anchor) {
		global $context;

		// seek all records attached to this anchor
		$query = "SELECT id FROM ".SQL::table_name('sections')." AS sections "
			." WHERE sections.anchor LIKE '".SQL::escape($anchor)."'";
		if(!$result =& SQL::query($query))
			return;

		// empty list
		if(!SQL::count($result))
			return;

		// delete silently all matching items
		while($row =& SQL::fetch($result))
			Sections::delete($row['id']);
	}

	/**
	 * duplicate all sections for a given anchor
	 *
	 * This function duplicates records in the database, and changes anchors
	 * to attach new records as per second parameter.
	 *
	 * @param string the source anchor
	 * @param string the target anchor
	 * @return int the number of duplicated records
	 *
	 * @see shared/anchors.php
	 */
	function duplicate_for_anchor($anchor_from, $anchor_to) {
		global $context;

		// look for records attached to this anchor
		$count = 0;
		$query = "SELECT * FROM ".SQL::table_name('sections')." WHERE anchor LIKE '".SQL::escape($anchor_from)."'";
		if(($result =& SQL::query($query)) && SQL::count($result)) {

			// the list of transcoded strings
			$transcoded = array();

			// process all matching records one at a time
			while($item =& SQL::fetch($result)) {

				// a new id will be allocated
				$old_id = $item['id'];
				unset($item['id']);

				// creator has to be the person who duplicates
				unset($item['create_address']);
				unset($item['create_date']);
				unset($item['create_id']);
				unset($item['create_name']);

				unset($item['edit_address']);
				unset($item['edit_date']);
				unset($item['edit_id']);
				unset($item['edit_name']);

				// target anchor
				$item['anchor'] = $anchor_to;

				// actual duplication
				if($item['id'] = Sections::post($item, FALSE)) {

					// more pairs of strings to transcode
					$transcoded[] = array('/\[section='.preg_quote($old_id, '/').'/i', '[section='.$item['id']);

					// duplicate elements related to this item
					Anchors::duplicate_related_to('section:'.$old_id, 'section:'.$item['id']);

					// stats
					$count++;
				}
			}

			// transcode in anchor
			if($anchor =& Anchors::get($anchor_to))
				$anchor->transcode($transcoded);

		}

		// number of duplicated records
		return $count;
	}

	/**
	 * get one section
	 *
	 * @param int or string the id or nick name of the section
	 * @param boolean TRUE to always fetch a fresh instance, FALSE to enable cache
	 * @return the resulting $item array, with at least keys: 'id', 'title', 'description', etc.
	 */
	function &get($id, $mutable=FALSE) {
		global $context;

		// sanity check
		if(!$id) {
			$output = NULL;
			return $output;
		}

		// ensure proper unicode encoding
		$id = (string)$id;
		$id = utf8::encode($id);

//		// strip extra text from enhanced ids '3-section-title' -> '3'
//		if($position = strpos($id, '-'))
//			$id = substr($id, 0, $position);

		// cache previous answers
		static $cache;
		if(!is_array($cache))
			$cache = array();

		// cache hit, but only for immutable objects
		if(!$mutable && isset($cache[$id]))
			return $cache[$id];

		// search by id
		if(is_numeric($id))
			$query = "SELECT * FROM ".SQL::table_name('sections')." AS sections"
				." WHERE (sections.id = ".SQL::escape((integer)$id).")";

		// or look for given name of handle
		else
			$query = "SELECT * FROM ".SQL::table_name('sections')." AS sections"
				." WHERE (sections.nick_name LIKE '".SQL::escape($id)."')"
				." ORDER BY edit_date DESC LIMIT 1";

		// do the job
		$output =& SQL::query_first($query);

		// save in cache
		if(!$mutable && isset($output['id']))
			$cache[$id] = $output;

		// return by reference
		return $output;
	}

	/**
	 * list sections as anchors
	 *
	 * This function is mainly used to build the front page and section index pages.
	 * It is call to list sections and, in a second time, articles related to these
	 * sections are actually listed.
	 *
	 * If the anchor parameter is null, this function will locate sections having the given variant
	 * in the field 'home_panel'.
	 * Else it will locate sections having the given variant in the field 'index_panel'.
	 *
	 * It accepts following variants:
	 * - 'extra' - one extra box per section
	 * - 'extra_boxes' - one extra box per article
	 * - 'gadget' - one gadget box per section
	 * - 'gadget_boxes' - one gadget box per article
	 * - 'main' - the main part of the index page
	 * - 'news' - flashy news
	 * - 'none' - sections are not displayed at all
	 *
	 * Normally this function is used before listing related articles. Ths is why we are not checking 'index_map' here.
	 *
	 * @param string the main anchor (e.g., 'section:123')
	 * @param string the target area (e.g., 'main')
	 * @return an array of anchors (e.g., array('section:456', 'section:789'))
	 *
	 * @see index.php
	 * @see sections/view.php
	 */
	function &get_anchors_for_anchor($anchor=NULL, $variant='main') {
		global $context;

		$criteria = array();

		// we are targeting a section index page
		if($anchor) {
			$criteria[] = "sections.anchor LIKE '".SQL::escape($anchor)."'";
			$target = 'index_panel';

		// we are targeting the front page
		} else {
			$target = 'home_panel';
		}

		// target a index area
		switch($variant) {
		case 'extra':
			$criteria[] = "(sections.".$target." = 'extra')";
			break;

		case 'extra_boxes':
			$criteria[] = "(sections.".$target." = 'extra_boxes')";
			break;

		case 'gadget':
			$criteria[] = "(sections.".$target." = 'gadget')";
			break;

		case 'gadget_boxes':
			$criteria[] = "(sections.".$target." = 'gadget_boxes')";
			break;

		case 'main':
		default:
			$criteria[] = "((sections.".$target." IS NULL) OR (sections.".$target." = '') OR (sections.".$target." = 'main'))";
			break;

		case 'news':
			$criteria[] = "(sections.".$target." = 'news')";
			break;

		case 'none':
			$criteria[] = "(sections.".$target." = 'none')";
			break;

		}

		// display active items
		$active = "(sections.active='Y'";

		// add restricted items to members, or if teasers are allowed
		if(Surfer::is_logged() || Surfer::is_teased())
			$active .= " OR sections.active='R'";

		// include hidden sections for associates
		if(Surfer::is_associate())
			$active .= " OR sections.active='N'";

		// end of filter on active field
		$criteria[] = $active.")";

		// use only live sections
		$criteria[] = "((sections.activation_date is NULL)"
			." OR (sections.activation_date <= '".$context['now']."'))"
			." AND ((sections.expiry_date is NULL)"
			." OR (sections.expiry_date <= '".NULL_DATE."') OR (sections.expiry_date > '".$context['now']."'))";

		// list up to 200 sections
		$query = "SELECT sections.id FROM ".SQL::table_name('sections')." AS sections"
			." WHERE ".implode(' AND', $criteria)
			." ORDER BY sections.rank, sections.title, sections.edit_date DESC LIMIT 200";
		if(!$result =& SQL::query($query)) {
			$output = NULL;
			return $output;
		}

		// process all matching sections
		$anchors = array();
		while($item =& SQL::fetch($result))
			$anchors[] = 'section:'.$item['id'];

		// return a list of anchors
		return $anchors;
	}

	/**
	 * list anchors that are part of the content tree
	 *
	 * This function is mainly used to list all containers of the content tree at once.
	 * It is called to list sections and, in a second time, articles related to these
	 * sections are actually listed.
	 *
	 * The variant is used to filter sections, as follows:
	 * - 'main' - list sections that send articles in main panel
	 * - 'index' - list sections that are listed in the main panel
	 *
	 * @param string the main anchor (e.g., 'section:123')
	 * @param string filter to apply
	 * @return an array of anchors (e.g., array('section:456', 'section:789'))
	 *
	 * @see sections/feed.php
	 */
	function &get_children_of_anchor($anchor=NULL, $variant='main') {
		global $context;

		$criteria = array();

		// several anchors
		if(is_array($anchor) && count($anchor)) {
			$items = array();
			foreach($anchor as $token)
				$items[] = "sections.anchor LIKE '".SQL::escape($token)."'";
			$criteria[] = join(' OR ', $items);
			$target = 'index_panel';

		// we are targeting a section index page
		} elseif(is_string($anchor)) {
			$criteria[] = "sections.anchor LIKE '".SQL::escape($anchor)."'";
			$target = 'index_panel';

		// we are targeting the front page
		} else {
			$target = 'home_panel';
		}

		// list sections listed in the main panel
		if($variant == 'index')
			$criteria[] = "(sections.index_map = 'Y')";

		// list sections that produce main content
		else
			$criteria[] = "((sections.".$target." IS NULL) OR (sections.".$target." = '') OR (sections.".$target." = 'main') OR (sections.".$target." = 'none'))";

		// display active items
		$active = "(sections.active='Y'";

		// add restricted items to members, or if teasers are allowed
		if(Surfer::is_logged() || Surfer::is_teased())
			$active .= " OR sections.active='R'";

		// include hidden sections for associates
		if(Surfer::is_associate())
			$active .= " OR sections.active='N'";

		// end of filter on active field
		$criteria[] = $active.")";

		// use only live sections
		$criteria[] = "((sections.activation_date is NULL)"
			." OR (sections.activation_date <= '".$context['now']."'))"
			." AND ((sections.expiry_date is NULL)"
			." OR (sections.expiry_date <= '".NULL_DATE."') OR (sections.expiry_date > '".$context['now']."'))";

		// ensure reasonable limit
		$query = "SELECT sections.id FROM ".SQL::table_name('sections')." AS sections"
			." WHERE ".implode(' AND ', $criteria)
			." ORDER BY sections.rank, sections.title, sections.edit_date DESC LIMIT 200";
		if(!$result =& SQL::query($query)) {
			$output = NULL;
			return $output;
		}

		// process all matching sections
		$anchors = array();
		while($item =& SQL::fetch($result))
			$anchors[] = 'section:'.$item['id'];

		// return a list of anchors
		return $anchors;
	}

	/**
	 * get the default section
	 *
	 * The default section is the one that has the nick name 'default'.
	 *
	 * Else it is the first top level section that appears at the site map
	 * (actually, at [script]sections/index.php[/script]).
	 *
	 * @return NULL on error, else the id of the default section
	 *
	 * @see files/edit.php
	 */
	function get_default() {
		global $context;

		// look for a 'default' section
		if($item =& Sections::get('default'))
			return $item['id'];

		// look only at top level
		$where = "(sections.anchor='' OR sections.anchor is NULL)";

		// display only active sections
		$where .= " AND (sections.active='Y'";
		if(Surfer::is_logged())
			$where .= " OR sections.active='R'";

		// include managed sections
		if($my_sections = Surfer::assigned_sections()) {
			$where .= " OR sections.id IN (".join(", ", $my_sections).")";
			$where .= " OR sections.anchor IN ('section:".join("', 'section:", $my_sections)."')";
		}

		// end of scope
		$where .= ")";

		// hide sections removed from site map
		$where .= " AND (sections.index_map = 'Y')";

		// only consider live sections
		$where .= " AND ((sections.activation_date is NULL)"
			."	OR (sections.activation_date <= '".$context['now']."'))"
			." AND ((sections.expiry_date is NULL)"
			."	OR (sections.expiry_date <= '".NULL_DATE."') OR (sections.expiry_date > '".$context['now']."'))";

		// select among available sections
		$query = "SELECT sections.id FROM ".SQL::table_name('sections')." AS sections"
			." WHERE ".$where
			." ORDER BY sections.rank, sections.title, sections.edit_date DESC LIMIT 0, 1";
		if($item =& SQL::query_first($query))
			return $item['id'];

		return NULL;
	}

	function &get_layout($variant='full') {
		global $context;

		// special layouts
		if(is_object($variant)) {
			$output = $variant;
			return $output;
		}

		// no layout yet
		$layout = NULL;

		// separate options from layout name
		$attributes = explode(' ', $variant, 2);

		// instanciate the provided name
		if($attributes[0]) {
			$name = 'layout_sections_as_'.$attributes[0];
			if(is_readable($context['path_to_root'].'sections/'.$name.'.php')) {
				include_once $context['path_to_root'].'sections/'.$name.'.php';
				$layout = new $name;

				// provide parameters to the layout
				if(isset($attributes[1]))
					$layout->set_variant($attributes[1]);

			}
		}

		// use default layout
		if(!$layout) {
			include_once $context['path_to_root'].'sections/layout_sections.php';
			$layout = new Layout_sections();
			$layout->set_variant($variant);
		}

		// do the job
		return $layout;
	}

	/**
	 * get sections as options of a &lt;SELECT&gt; field
	 *
	 * Only sections matching following criteria are returned:
	 * - section is visible (active='Y')
	 * - section is restricted (active='R'), but surfer is a logged user
	 * - section is restricted (active='N'), but surfer is an associate
	 * - an expiry date has not been defined, or is not yet passed
	 *
	 * This function uses the cache to save on database requests.
	 *
	 * @param string the current anchor to an existing section (e.g., 'section:12')
	 * @param array list of sections made of $id => $attributes
	 * @return the HTML to insert in the page
	 *
	 * @see articles/edit.php
	 * @see articles/import.php
	 * @see files/edit.php
	 * @see images/edit.php
	 * @see links/edit.php
	 * @see panel.php
	 * @see skins/upload.php
	 */
	function get_options($default=NULL, $to_avoid=NULL) {
		global $context;

		// all options
		$text = '';

		// we don't want a default section
		if($default == 'none')
			$default = NULL;

		// use the default section
		elseif(!$default)
			$default = 'section:'.Sections::get_default();

		// list sections recursively
		$text .= Sections::get_options_for_anchor(NULL, '', $default, $to_avoid);

		// associates can also see inactive sections at the top level
		if(Surfer::is_associate() && ($sections = Sections::list_inactive_by_title_for_anchor(NULL, 0, 100, 'raw'))) {

			$text .= '<optgroup label="'.i18n::s('Other sections').'">';

			// add to text
			foreach($sections as $id => $attributes) {

				if(Sections::match($id, $to_avoid))
					continue;

				// this section
				$reference = 'section:'.$id;
				$text .= '<option value="'.$reference.'"';
				if($default && ($default == $reference))
					$text .= ' selected="selected"';
				$text .= '>'.Skin::strip($attributes['title'])."</option>\n";

				// list sub-sections recursively
				$text .= Sections::get_options_for_anchor($reference, '&nbsp;&nbsp;', $default, $to_avoid);

			}

			$text .= "</optgroup>\n";
		}

		return $text;
	}

	/**
	 * get options recursively
	 *
	 * This function is called internally by Sections::get_options(), above.
	 *
	 * @param string the current anchor to an existing section (e.g., 'section:12')
	 * @param string spaces to prepend before section name -- to reflect depth
	 * @param string the reference of the default section
	 * @param array list of sections made of $id => $attributes
	 * @return the HTML to insert in the page
	 *
	 */

	function get_options_for_anchor($anchor, $spaces, $default, $to_avoid) {
		global $context;

		// add to text
		$text = '';

		// list sections at this level
		if($sections = Sections::list_by_title_for_anchor($anchor, 0, 1000, 'raw')) {

			foreach($sections as $id => $attributes) {

				if(Sections::match($id, $to_avoid))
					continue;

				// this section
				$reference = 'section:'.$id;
				$text .= '<option value="'.$reference.'"';

				// the section is locked
				if(isset($attributes['locked']) && ($attributes['locked'] == 'Y') && !Surfer::is_associate())
					$text .= ' style="font-style: italic;" disabled="disabled"';

				// currently selected
				if($default && ($default == $reference))
					$text .= ' selected="selected"';
				$text .='>'.$spaces.Skin::strip($attributes['title'])."</option>\n";

				// depending sections, if any
				if($to_avoid == 'no_subsections')
					;
				elseif($depending = Sections::get_options_for_anchor($reference, $spaces.'&nbsp;&nbsp;', $default, $to_avoid))
					$text .= $depending;

			}
		}

		// associates can also access inactive sections at this level
		if($anchor && Surfer::is_associate()) {

			if($sections = Sections::list_inactive_by_title_for_anchor($anchor, 0, 100, 'raw')) {

				foreach($sections as $id => $attributes) {

					if(Sections::match($id, $to_avoid))
						continue;

					// this section
					$reference = 'section:'.$id;
					$text .= '<option value="'.$reference.'"';
					if($default && ($default == $reference))
						$text .= ' selected="selected"';
					$text .='>'.$spaces.Skin::strip($attributes['title'])."</option>\n";

					// depending sections, if any
					if($depending = Sections::get_options_for_anchor($reference, $spaces.'&nbsp;&nbsp;', $default, $to_avoid))
						$text .= $depending;

				}
			}
		}

		// end of job
		return $text;
	}

	/**
	 * get permanent address
	 *
	 * @param array page attributes
	 * @return string the permalink
	 */
	function &get_permalink($item) {
		$output = Sections::get_url($item['id'], 'view', $item['title'], isset($item['nick_name']) ? $item['nick_name'] : '');
		return $output;
	}

	/**
	 * get sections as radio buttons
	 *
	 * This allow to move a page to a parent section, or to a child section
	 *
	 * @param string the current anchor to an existing section (e.g., 'section:12')
	 * @param int id of the section that is moved
	 * @return the HTML to insert in the page
	 *
	 * @see articles/edit.php
	 * @see articles/import.php
	 * @see files/edit.php
	 * @see images/edit.php
	 * @see links/edit.php
	 * @see panel.php
	 * @see skins/upload.php
	 */
	function &get_radio_buttons($current=NULL, $me=NULL) {
		global $context;

		// we put radio buttons in a string
		$text = '';

		// get section id from reference
		if(!strncmp($current, 'section:', 8))
			$current = substr($current, 8);

		// get the current parent, if any
		$item = Sections::get($current);

		// list everything to associates
		if(Surfer::is_associate())
			$where = " AND (sections.active IN ('Y', 'R', 'N')";

		// list unlocked sections
		else {

			// display active items
			$where = " AND ((sections.active='Y')";

			// add restricted items to logged members, or if teasers are allowed
			if(Surfer::is_logged() || Surfer::is_teased())
				$where .= " OR (sections.active='R')";

		}

		// always mention current and parent sections
		if($me)
			$where .= " OR (sections.id=".$me.")";
		if($current)
			$where .= " OR (sections.id=".$current.")";

		// include managed sections for editors
		if($my_sections = Surfer::assigned_sections()) {
			$where .= " OR sections.id IN (".join(", ", $my_sections).")";
			$where .= " OR sections.anchor IN ('section:".join("', 'section:", $my_sections)."')";
		}

		// end of scope
		$where .= ")";

		// list all children sections
		$children = '';
		if(isset($item['id'])) {

			$query = "SELECT * FROM ".SQL::table_name('sections')." AS sections"
				." WHERE (anchor LIKE 'section:".$item['id']."')"
				." ORDER BY sections.rank, sections.title, sections.edit_date DESC LIMIT 200";
			if($result =& SQL::query($query)) {
				while($row =& SQL::fetch($result)) {
					if($children)
						$children .= BR;
					if($row['id'] == $me)
						$children .= '<input type="radio" name="anchor" disabled="disabled" /> '.Codes::beautify_title($row['title']);
					else
						$children .= '<input type="radio" name="anchor" value="section:'.$row['id'].'" /> '.Codes::beautify_title($row['title']);
				}
			}

		}

		// list sections at the same level
		$family = '';
		if(isset($item['anchor']) && ($parent =& Anchors::get($item['anchor']))) {

			$query = "SELECT * FROM ".SQL::table_name('sections')." AS sections"
				." WHERE (anchor LIKE '".$item['anchor']."')"
				." ORDER BY sections.rank, sections.title, sections.edit_date DESC LIMIT 200";
			if($result =& SQL::query($query)) {

				// brothers and sisters
				while($row =& SQL::fetch($result)) {

					if($family && strncmp(substr($family, -6), '</div>', 6))
						$family .= BR;

					if($row['id'] == $item['id']) {

						if($children)
							$family .= '<input type="radio" name="anchor" value="section:'.$item['id'].'" checked="checked" /> '.Codes::beautify_title($item['title'])
								.'<div style="margin: 0 0 0 3em">'.$children.'</div>';
						else
							$family .= '<input type="radio" name="anchor" value="section:'.$item['id'].'" checked="checked" /> '.Codes::beautify_title($item['title']);

					} else
						$family .= '<input type="radio" name="anchor" value="section:'.$row['id'].'" /> '.Codes::beautify_title($row['title']);
				}
			}

			// move to parent
			if($parent->is_assigned() && !$parent->has_option('locked'))
				$family = '<input type="radio" name="anchor" value="'.$parent->get_reference().'" /> '.$parent->get_title()
					.'<div style="margin: 0 0 0 3em">'.$family.'</div>';
			else
				$family = '<input type="radio" name="anchor" value="'.$parent->get_reference().'"  disabled="disabled" /> '.$parent->get_title()
					.'<div style="margin: 0 0 0 3em">'.$family.'</div>';

		// list top-level sections
		} else {

			// at the very top of the content tree
			if(!isset($item['id']))
				$family .= '<input type="radio" name="anchor" value="" checked="checked" /> '.i18n::s('Top of the content tree').BR
					.'<div style="margin: 0 0 0 3em">';

			// list regular sections first
			$query = "SELECT * FROM ".SQL::table_name('sections')." AS sections"
				." WHERE (sections.anchor='' OR sections.anchor IS NULL)".$where
				." AND (sections.index_map = 'Y')"
				." ORDER BY sections.rank, sections.title, sections.edit_date DESC LIMIT 200";
			if($result =& SQL::query($query)) {

				// process all matching sections
				while($row =& SQL::fetch($result)) {

					if($row['id'] == $item['id']) {

						if($children)
							$family .= '<input type="radio" name="anchor" value="section:'.$item['id'].'" checked="checked" /> '.Codes::beautify_title($item['title'])
								.'<div style="margin: 0 0 0 3em">'.$children.'</div>';
						else
							$family .= '<input type="radio" name="anchor" value="section:'.$item['id'].'" checked="checked" /> '.Codes::beautify_title($item['title']).BR;

					} elseif(!Surfer::is_associate())
						continue;
					elseif($row['id'] == $me)
						$family .= '<input type="radio" name="anchor" disabled="disabled" /> '.Codes::beautify_title($row['title']).BR;
					else
						$family .= '<input type="radio" name="anchor" value="section:'.$row['id'].'" /> '.Codes::beautify_title($row['title']).BR;
				}
			}

			// close the top of the content tree
			if(!isset($item['id']))
				$family .= '</div>';

			// list special sections to associates
			if(Surfer::is_associate()) {
				$query = "SELECT * FROM ".SQL::table_name('sections')." AS sections"
					." WHERE (sections.anchor='' OR sections.anchor IS NULL)"
					." AND (sections.index_map != 'Y')"
					." ORDER BY sections.rank, sections.title, sections.edit_date DESC LIMIT 200";
				if($result =& SQL::query($query)) {

					$family .= '<hr />';

					// process all matching sections
					while($row =& SQL::fetch($result)) {

						if($row['id'] == $item['id']) {

							if($children)
								$family .= '<input type="radio" name="anchor" value="section:'.$item['id'].'" checked="checked" /> '.Codes::beautify_title($item['title'])
									.'<div style="margin: 0 0 0 3em">'.$children.'</div>';
							else
								$family .= '<input type="radio" name="anchor" value="section:'.$item['id'].'" checked="checked" /> '.Codes::beautify_title($item['title']).BR;

						} else
							$family .= '<input type="radio" name="anchor" value="section:'.$row['id'].'" /> '.Codes::beautify_title($row['title']).BR;
					}
				}
			}

		}

		$text .= $family;

		// at least show where we are
		if(!$text && isset($item['id']))
			$text .= '<input type="hidden" name="anchor" value="section:'.$item['id'].'" />'.Codes::beautify_title($item['title']);

		return $text;
	}

	function &get_tree() {
		global $context;

		// do this only once!
		static $sections_tree;
		if(isset($sections_tree))
			return $sections_tree;

		$sections_tree = array();

		// list everything to associates
		if(Surfer::is_associate())
			$where = "sections.active IN ('Y', 'R', 'N')";

		// restrict the scope
		else {

			// display active items
			$where = "(sections.active='Y')";

			// add restricted items to logged surfers
			if(Surfer::is_logged())
				$where .= " OR (sections.active='R')";

			// include managed sections for editors
			if($my_sections = Surfer::assigned_sections()) {
				$where .= " OR sections.id IN (".join(", ", $my_sections).")";
				$where .= " OR sections.anchor IN ('section:".join("', 'section:", $my_sections)."')";
			}

			// end of scope
			$where = '('.$where.')';

		}

		// lookup all sections
		$query = "SELECT * FROM ".SQL::table_name('sections')." AS sections"
			." WHERE ".$where." ORDER BY sections.rank, sections.title, sections.edit_date DESC LIMIT 5000";
		if(!$result =& SQL::query($query))
			return $sections_tree;

		// scan all sections
		$sections_handles = array();
		while($item =& SQL::fetch($result)) {

			$reference = 'section:'.$item['id'];
			$label = $item['title'];
			if($item['anchor'])
				$handle = $item['anchor'];
			else
				$handle = 'top';

			$sections_handles[$handle] = array('content' => $label, 'reference' => $reference);

		}

		// build the tree
		Sections::get_tree_level($sections_tree, $sections_handles, 'top');

//		logger::debug($sections_tree);
		return $sections_tree;
	}

	function get_tree_level(&$tree, $handles, $handle, $level=0) {

		// process all sections at this level
		$items = $handles[$handle];
		foreach($items as $item) {

			// extend the tree
			$tree[] = array('level' => $level, 'content' => $item['content']);

			// add all sub-branches
			Sections::get_tree_level($tree, $handles, $item['reference'], $level+1);
		}
	}

	/**
	 * build a reference to a section
	 *
	 * Depending on parameter '[code]with_friendly_urls[/code]' and on action,
	 * following results can be observed:
	 *
	 * - view - sections/view.php?id=123 or sections/view.php/123 or section-123
	 *
	 * - other - sections/edit.php?id=123 or sections/edit.php/123 or section-edit/123
	 *
	 * If a fourth parameter is provided, it will take over the third one. This
	 * is used to leverage nick names in YACS, as per the following invocation:
	 * [php]
	 * Sections::get_url($item['id'], 'view', $item['title'], $item['nick_name']);
	 * [/php]
	 *
	 * @param int the id of the section to handle
	 * @param string the expected action ('view', 'print', 'edit', 'delete', ...)
	 * @param string additional data, such as section nick name, if any
	 * @param string alternate name, if any, to take over on previous parameter
	 * @return string a normalized reference
	 *
	 * @see control/configure.php
	 */
	function get_url($id, $action='view', $name=NULL, $alternate_name=NULL) {
		global $context;

		// use nick name instead of regular name, if one is provided
		if($alternate_name && ($context['with_alternate_urls'] == 'Y'))
			$name = str_replace('_', ' ', $alternate_name);

		// the service to check for updates
		if($action == 'check') {
			if($context['with_friendly_urls'] == 'Y')
				return 'services/check.php/section/'.rawurlencode($id);
			elseif($context['with_friendly_urls'] == 'R')
				return 'services/check.php?id='.urlencode('section:'.$id);
			else
				return 'services/check.php?id='.urlencode('section:'.$id);
		}

		// the RSD link
		if($action == 'EditURI') {
			if($context['with_friendly_urls'] == 'Y')
				return 'services/describe.php/'.rawurlencode($id);
			elseif($context['with_friendly_urls'] == 'R')
				return 'services/describe.php/'.rawurlencode($id);
			else
				return 'services/describe.php?anchor='.urlencode($id);
		}

		// the rss feed for files --deprecated to files::get_url()
		if($action == 'files') {
			if($context['with_friendly_urls'] == 'Y')
				return 'files/feed.php/section/'.rawurlencode($id);
			elseif($context['with_friendly_urls'] == 'R')
				return 'files/feed.php/section/'.rawurlencode($id);
			else
				return 'files/feed.php?anchor='.urlencode('section:'.$id);
		}

		// the prefix for managing content
		if($action == 'manage') {
			if($name)
				return 'sections/manage.php?id='.urlencode($id).'&amp;'.urlencode($name).'=';
			else
				return 'sections/manage.php?id='.urlencode($id);
		}

		// check the target action
		if(!preg_match('/^(delete|describe|duplicate|edit|feed|freemind|import|invite|lock|mail|navigate|own|print|view|view_as_freemind)$/', $action))
			return 'sections/'.$action.'.php?id='.urlencode($id).'&action='.urlencode($name);

		// normalize the link
		return normalize_url(array('sections', 'section'), $action, $id, $name);
	}

	/**
	 * check if an option has been set for a page
	 *
	 * The option can be set either in the page itself, or cascaded from parent sections.
	 *
	 * @param string the option
	 * @param object parent anchor, if any
	 * @param array page attributes
	 * @return TRUE or FALSE
	 */
	 function has_option($option, $anchor=NULL, $item=NULL) {
		global $context;

		// sanity check
		if(!$option)
			return FALSE;

		// option check for this page
		if(isset($item['options']) && (strpos($item['options'], $option) !== FALSE))
			return TRUE;

		// check in anchor
		if(is_object($anchor) && $anchor->has_option($option))
			return TRUE;

		// sorry
		return FALSE;
	}

	/**
	 * set the hits counter - errors are not reported, if any
	 *
	 * Hits are aiming to track service usage of anonymous and of authenticated users.
	 * Normally this function is not called is the surfer is either an associates or an editor for this section.
	 *
	 * @param the id of the section to update
	 *
	 * @see sections/view.php
	 */
	function increment_hits($id) {
		global $context;

		// id cannot be empty
		if(!$id || !is_numeric($id))
			return;

		// do the job
		$query = "UPDATE ".SQL::table_name('sections')." SET hits=hits+1 WHERE id = ".SQL::escape($id);
		SQL::query($query);

	}

	/**
	 * has the surfer been assign to this section?
	 *
	 * This would be the case either:
	 * - if he is a member and has been granted the editor privilege
	 * - if he is a subscriber and has been granted the reader privilege
	 *
	 * @param int the id of the target section
	 * @param int optional id to impersonate
	 * @return TRUE or FALSE
	 */
	function is_assigned($id, $surfer_id=NULL) {
		global $context;

		// no impersonation
		if(!$surfer_id) {

			// a managed section requires an authenticated user
			if(!Surfer::get_id())
				return FALSE;

			// use surfer profile
			$surfer_id = Surfer::get_id();

		}

		// ensure this section has been linked to this user
		return Members::check('user:'.$surfer_id, 'section:'.$id);
	}

	/**
	 * check if a surfer can edit a section
	 *
	 * @param object parent anchor, if any
	 * @param array page attributes
	 * @param int optional reference to some user profile
	 * @return TRUE or FALSE
	 */
	 function is_editable($anchor=NULL, $item=NULL, $user_id=NULL, $strict=FALSE) {
		global $context;

		// id of requesting user
		if(!$user_id) {
			if(!Surfer::get_id())
				return FALSE;
			$user_id = Surfer::get_id();
		}

		// surfer is an editor of this section
		if(Members::check('user:'.$user_id, 'section:'.$item['id']))
			return TRUE;

		if($strict)
			return FALSE;

		// surfer is assigned to parent container
		if(is_object($anchor) && $anchor->is_assigned($user_id))
			return TRUE;

		// sorry
		return FALSE;
	}

	/**
	 * check if a surfer owns a section
	 *
	 * @param array section attributes
	 * @param object parent anchor, if any
	 * @param boolean FALSE if the surfer can be an editor of parent section
	 * @param int optional reference to some user profile
	 * @return TRUE or FALSE
	 */
	 function is_owned($item=NULL, $anchor=NULL, $strict=FALSE, $user_id=NULL) {
		global $context;

		// id of requesting user
		if(!$user_id) {
			if(!Surfer::get_id())
				return FALSE;
			$user_id = Surfer::get_id();
		}

		// surfer owns this section
		if(isset($item['owner_id']) && ($item['owner_id'] == $user_id))
			return TRUE;

		// do not look upwards
		if(!$anchor || !is_object($anchor))
			return FALSE;

		// associates can do what they want
		if(Surfer::is($user_id) && Surfer::is_associate())
			return TRUE;

		// we are owning one of the parents
		if($anchor->is_owned($user_id))
			return TRUE;

		// surfer is a member assigned to one of the parents
		if(!$strict && Surfer::is_member() && is_object($anchor) && !$anchor->is_hidden() && $anchor->is_assigned($user_id))
			return TRUE;

		// sorry
		return FALSE;
	}

	/**
	 * is the surfer watching this section?
	 *
	 * @param int the id of the target section
	 * @param int optional id to impersonate
	 * @return TRUE or FALSE
	 */
	function is_watched($id, $surfer_id=NULL) {
		global $context;

		// no impersonation
		if(!$surfer_id) {

			// a managed section requires an authenticated user
			if(!Surfer::is_logged())
				return FALSE;

			// use surfer profile
			$surfer_id = Surfer::get_id();

		}

		// ensure this section has been linked to this user
		return Members::check('section:'.$id, 'user:'.$surfer_id);
	}

	/**
	 * list sections assigned to one surfer
	 *
	 * Only sections matching following criteria are returned:
	 * - section is visible (active='Y')
	 * - or section is restricted (active='R'), but surfer is a logged user
	 * - or section is hidden (active='N'), but surfer is an associate
	 *
	 * @param int surfer id
	 * @param int the offset from the start of the list; usually, 0 or 1
	 * @param int the number of items to display
	 * @param string 'full', etc or object, i.e., an instance of Layout_Interface
	 * @return NULL on error, else an ordered array with $url => ($prefix, $label, $suffix, $icon)
	 */
	function &list_assigned_by_title($surfer_id, $offset=0, $count=20, $variant='full') {
		global $context;

		// obviously we need some assigned sections
		if(!$assigned = Surfer::assigned_sections($surfer_id)) {
			$output = NULL;
			return $output;
		}

		// limit the query to one level
		$where = "(sections.id = ".join(" OR sections.id = ", $assigned).")";

		// display active items
		$where .= " AND (sections.active='Y'";

		// add restricted items to logged members, or if teasers are allowed
		if(Surfer::is_logged() || Surfer::is_teased())
			$where .= " OR sections.active='R'";

		// list hidden sections to associates and to editors
		if(is_callable(array('Surfer', 'is_empowered')) && Surfer::is_empowered())
			$where .= " OR sections.active='N'";

		// end of scope
		$where .= ")";

		// list sections
		$query = "SELECT sections.*"
			." FROM ".SQL::table_name('sections')." AS sections"
			." WHERE ".$where
			." ORDER BY sections.title, sections.edit_date DESC LIMIT ".$offset.','.$count;

		$output =& Sections::list_selected(SQL::query($query), $variant);
		return $output;
	}

	/**
	 * apply a layout to one section
	 *
	 * Only sections matching following criteria are returned:
	 * - related section is visible (active='Y')
	 * - related section is restricted (active='R'), but the surfer is an authenticated member,
	 * or YACS is allowed to show restricted teasers
	 * - related section is hidden (active='N'), but the surfer is an associate or an editor,
	 *
	 * @param int id of the target section
	 * @param string the list variant, if any
	 * @return NULL on error, else the outcome of the layout
	 */
	function &list_for_id($id, $variant='compact') {
		global $context;

		// select among active items
		$where = "sections.active='Y'";

		// add restricted items to members, or if teasers are allowed
		if(Surfer::is_logged() || Surfer::is_teased())
			$where .= " OR sections.active='R'";

		// add hidden items to associates, editors and readers
		if(Surfer::is_empowered('S'))
			$where .= " OR sections.active='N'";

		// bracket OR statements
		$where = '('.$where.')';

		// sections by title
		$query = "SELECT sections.*"
			." FROM ".SQL::table_name('sections')." AS sections"
			." WHERE (sections.id = LIKE '".SQL::escape($name)."') AND ".$where;

		$output =& Sections::list_selected(SQL::query($query), $variant);
		return $output;
	}

	/**
	 * list named sections
	 *
	 * This function lists all sections with the same nick name.
	 *
	 * This is used by the page locator to offer alternatives when several pages have the same nick names.
	 * It is also used to link a page to twins, these being, most of the time, translations.
	 *
	 * Only sections matching following criteria are returned:
	 * - related section is visible (active='Y')
	 * - related section is restricted (active='R'), but the surfer is an authenticated member,
	 * or YACS is allowed to show restricted teasers
	 * - related section is hidden (active='N'), but the surfer is an associate or an editor,
	 * - an expiry date has not been defined, or is not yet passed
	 *
	 * @param string the nick name
	 * @param int the id of the current page, which will not be listed
	 * @param string the list variant, if any
	 * @return NULL on error, else an ordered array with $url => ($prefix, $label, $suffix, $icon)
	 */
	function &list_for_name($name, $exception=NULL, $variant='compact') {
		global $context;

		// select among active items
		$where = "sections.active='Y'";

		// add restricted items to members, or if teasers are allowed
		if(Surfer::is_logged() || Surfer::is_teased())
			$where .= " OR sections.active='R'";

		// add hidden items to associates, editors and readers
		if(Surfer::is_empowered('S'))
			$where .= " OR sections.active='N'";

		// bracket OR statements
		$where = '('.$where.')';

		// avoid exception, if any
		if($exception)
			$where .= " AND (sections.id != ".SQL::escape($exception).")";

		// only consider live sections
		$where .= " AND ((sections.expiry_date is NULL) "
				."OR (sections.expiry_date <= '".NULL_DATE."') OR (sections.expiry_date > '".$context['now']."'))";

		// sections by title -- up to 100 sections with the same name
		$query = "SELECT sections.*"
			." FROM ".SQL::table_name('sections')." AS sections"
			." WHERE (sections.nick_name LIKE '".SQL::escape($name)."') AND ".$where
			." ORDER BY sections.title LIMIT 100";

		$output =& Sections::list_selected(SQL::query($query), $variant);
		return $output;
	}

	/**
	 * list sections for one user
	 *
	 * @param int the id of the target user
	 * @param int the offset from the start of the list; usually, 0 or 1
	 * @param int the number of items to display
	 * @param string the list variant, if any
	 * @return mixed the outcome of the layout
	 *
	 * @see shared/codes.php
	 */
	function &list_by_date_for_user($user_id, $offset=0, $count=10, $variant='full') {
		global $context;

		// limit the scope of the request
		$where = "(sections.active='Y'";
		if(Surfer::is_logged())
			$where .= " OR sections.active='R'";
		if(Surfer::is_associate())
			$where .= " OR sections.active='N'";

		// include managed sections
		if($my_sections = Surfer::assigned_sections())
			$where .= " OR sections.id IN (".join(", ", $my_sections).")";

		$where .= ')';

		// strip dead sections
		if((Surfer::get_id() != $user_id) && !Surfer::is_associate())
			$where .= " AND ((sections.expiry_date is NULL) "
					."OR (sections.expiry_date <= '".NULL_DATE."') OR (sections.expiry_date > '".$context['now']."'))";

		// look for watched sections with sub-queries
		if(version_compare(SQL::version(), '4.1.0', '>=')) {
			$query = "SELECT sections.* FROM (SELECT DISTINCT CAST(SUBSTRING(members.anchor, 9) AS UNSIGNED) AS target FROM ".SQL::table_name('members')." AS members WHERE (members.member LIKE 'user:".SQL::escape($user_id)."') AND (members.anchor LIKE 'section:%')) AS ids"
				.", ".SQL::table_name('sections')." AS sections"
				." WHERE (sections.id = ids.target)"
				."	AND ".$where;

		// use joined queries
		} else {
			$query = "SELECT sections.* FROM ".SQL::table_name('members')." AS members"
				.", ".SQL::table_name('sections')." AS sections"
				." WHERE (members.member LIKE 'user:".SQL::escape($user_id)."')"
				."	AND (members.anchor LIKE 'section:%')"
				."	AND (sections.id = SUBSTRING(members.anchor, 9))"
				."	AND ".$where;

		}

		// include sections assigned to this surfer
		if($these_items = Surfer::assigned_sections($user_id))
			$query = "(SELECT sections.* FROM ".SQL::table_name('sections')." AS sections"
				." WHERE sections.id IN (".join(', ', $these_items).")"
				."	AND ".$where.")"
				." UNION (".$query.")";

		// finalize the query
		$query .= " ORDER BY edit_date DESC, title LIMIT ".$offset.','.$count;

		// use existing listing facility
		$output =& Sections::list_selected(SQL::query($query), $variant);
		return $output;
	}

	/**
	 * list sections by title at a given level of the hierarchy
	 *
	 * Actually list sections by rank, then by title, then by edition date.
	 * If you select to not use the ranking system, sections will be ordered by title only.
	 * Else sections with a low ranking mark will appear at the beginning of the list,
	 * and sections with a high ranking mark will be put at the end of the list.
	 *
	 * @param mixed the section anchor(s) to which these sections are linked
	 * @param int the offset from the start of the list; usually, 0 or 1
	 * @param int the number of items to display
	 * @param string 'full', etc or object, i.e., an instance of Layout_Interface
	 * @return NULL on error, else an ordered array with $url => ($prefix, $label, $suffix, $icon)
	 *
	 * @see articles/edit.php
	 * @see feeds/describe.php
	 * @see index.php
	 * @see sections/index.php
	 * @see sections/layout_sections_as_inline.php
	 * @see sections/layout_sections_as_yahoo.php
	 * @see sections/view.php
	 */
	function &list_by_title_for_anchor($anchor, $offset=0, $count=20, $variant='full') {
		global $context;

		// limit the query to one level
		if(is_array($anchor))
			$where = "(sections.anchor LIKE '".join("' OR sections.anchor LIKE '", $anchor)."')";
		elseif($anchor)
			$where = "(sections.anchor LIKE '".SQL::escape($anchor)."')";
		else
			$where = "(sections.anchor='' OR sections.anchor IS NULL)";

		// display active items
		$where .= " AND (sections.active='Y'";

		// add restricted items to logged members, or if teasers are allowed
		if(Surfer::is_logged() || Surfer::is_teased())
			$where .= " OR sections.active='R'";

		// list hidden sections to associates, editors and subscribers
		if(Surfer::is_empowered('S'))
			$where .= " OR sections.active='N'";

		// include managed sections
		if($my_sections = Surfer::assigned_sections()) {
			$where .= " OR sections.id IN (".join(", ", $my_sections).")";
			$where .= " OR sections.anchor IN ('section:".join("', 'section:", $my_sections)."')";
		}

		// end of scope
		$where .= ")";

		// limit to regular sections
		$where .= " AND (sections.index_panel LIKE 'main')";

		// hide sections removed from index maps
		$where .= " AND (sections.index_map = 'Y')";

		// non-associates will have only live sections
		if(!Surfer::is_associate()) {
			$where .= " AND ((sections.activation_date is NULL)"
				."	OR (sections.activation_date <= '".$context['now']."'))"
				." AND ((sections.expiry_date is NULL)"
				."	OR (sections.expiry_date <= '".NULL_DATE."') OR (sections.expiry_date > '".$context['now']."'))";
		}

		// if the user is listing sections to write an article, only consider open sections, even for associates
		if(is_string($variant) && ($variant == 'select'))
			$where .= ' AND (sections.locked NOT LIKE "Y")';

		// list sections
		$query = "SELECT sections.*"
			." FROM ".SQL::table_name('sections')." AS sections"
			." WHERE ".$where
			." ORDER BY sections.rank, sections.title, sections.edit_date DESC LIMIT ".$offset.','.$count;

		// don't stop on error if we are building tabs
		if(is_string($variant) && ($variant == '$tabs'))
			$silent = TRUE;
		else
			$silent = FALSE;

		// provide context to layout
		$layout =& Sections::get_layout($variant);
		if($anchor)
			$layout->set_variant($anchor);

		// do the job
		$output =& Sections::list_selected(SQL::query($query, $silent), $layout);
		return $output;
	}

	/**
	 * list all sections for a given parent
	 *
	 * This function is suitable is you want to handle all sub-sections
	 * of a section.
	 *
	 * Only sections matching following criteria are returned:
	 * - section is visible (active='Y')
	 * - or section is restricted (active='R'), but surfer is a logged user
	 * - or section is hidden (active='N'), but surfer is an associate
	 *
	 * @param string reference to the parent section
	 * @param string 'full', etc or object, i.e., an instance of Layout_Interface
	 * @return array an ordered array with $url => ($prefix, $label, $suffix, $icon), else NULL on error
	 */
	function &list_for_anchor($anchor, $variant='raw') {
		global $context;

		// limit the scope to one section
		$where = "(sections.anchor LIKE '".SQL::escape($anchor)."')";

		// display active items
		$where .= " AND (sections.active='Y'";

		// add restricted items to logged members, or if teasers are allowed
		if(Surfer::is_logged() || Surfer::is_teased())
			$where .= " OR sections.active='R'";

		// list hidden sections to associates, editors and subscribers
		if(Surfer::is_empowered('S'))
			$where .= " OR sections.active='N'";

		// include managed sections
		if($my_sections = Surfer::assigned_sections()) {
			$where .= " OR sections.id IN (".join(", ", $my_sections).")";
			$where .= " OR sections.anchor IN ('section:".join("', 'section:", $my_sections)."')";
		}

		// end of scope
		$where .= ')';

		// non-associates will have only live sections
		if(!Surfer::is_associate()) {
			$where .= " AND ((sections.activation_date is NULL)"
				."	OR (sections.activation_date <= '".$context['now']."'))"
				." AND ((sections.expiry_date is NULL)"
				."	OR (sections.expiry_date <= '".NULL_DATE."') OR (sections.expiry_date > '".$context['now']."'))";
		}

		// list sections
		$query = "SELECT sections.*"
			." FROM ".SQL::table_name('sections')." AS sections"
			." WHERE ".$where
			." ORDER BY sections.rank, sections.title, sections.edit_date DESC LIMIT 0, 500";

		$output =& Sections::list_selected(SQL::query($query), $variant);
		return $output;
	}

	/**
	 * list inactive sub-sections by title for a given anchor
	 *
	 * Actually list sections by rank, then by title, then by edition date.
	 * If you select to not use the ranking system, sections will be ordered by title only.
	 * Else sections with a low ranking mark will appear first,
	 * and sections with a high ranking mark will be put at the end of the list.
	 *
	 * To be used by associates to access special sections (menu, boxes, etc.)
	 *
	 * Only sections matching following criteria are returned:
	 * - an activation date has been set in the future
	 * - or an expiry date has been defined, and the section is now dead
	 * - or the section has been removed from index maps (index_map='N')
	 *
	 * Alternatively, all sub-sections are listed if the parent section
	 * does not feature sections (sections_layout='none').
	 *
	 * @param the section anchor to which these sections are linked
	 * @param int the offset from the start of the list; usually, 0 or 1
	 * @param int the number of items to display
	 * @param string 'full', etc or object, i.e., an instance of Layout_Interface
	 * @return NULL on error, else an ordered array with $url => ($prefix, $label, $suffix, $icon)
	 *
	 * @see sections/index.php
	 * @see sections/view.php
	 */
	function &list_inactive_by_title_for_anchor($anchor, $offset=0, $count=20, $variant='full') {
		global $context;

		// only for associates and editors
		if(!Surfer::is_empowered())
			return NULL;

		// limit the query to one level
		if($anchor)
			$where = "(sections.anchor LIKE '".SQL::escape($anchor)."')";
		else
			$where = "(sections.anchor='' OR sections.anchor is NULL)";

		// display everything if no sub-section is laid out in parent section
		if($anchor && ($parent =& Anchors::get($anchor)) && $parent->has_value('sections_layout', 'none'))
			;

		// only inactive sections have to be displayed
		else {

			// restrict the scope
			$where .= ' AND (';

			// list dead sections
			$where .= "(sections.activation_date >= '".$context['now']."')"
				." OR ((sections.expiry_date > '".NULL_DATE."') AND (sections.expiry_date <= '".$context['now']."'))";

			// add sections not listed in main panel
			$where .= " OR (sections.index_panel != 'main')";

			// add sections removed from normal index map
			$where .= " OR (sections.index_map != 'Y')";

			// end of scope
			$where .= ')';

		}

		// list sections
		$query = "SELECT sections.*"
			." FROM ".SQL::table_name('sections')." AS sections"
			." WHERE ".$where
			." ORDER BY sections.rank, sections.title, sections.edit_date DESC	LIMIT ".$offset.','.$count;

		$output =& Sections::list_selected(SQL::query($query), $variant);
		return $output;
	}

	/**
	 * list selected sections
	 *
	 * If variant is provided as a string, the functions looks for a script featuring this name.
	 * E.g., for variant 'compact', the file 'sections/layout_sections_as_compact.php' is loaded.
	 * If no file matches then the default 'sections/layout_sections.php' script is loaded.
	 *
	 * @param resource result of database query
	 * @param string 'full', etc or object, i.e., an instance of Layout_Interface
	 * @return NULL on error, else an ordered array with $url => ($prefix, $label, $suffix, $icon)
	 *
	 */
	function &list_selected(&$result, $variant='full') {
		global $context;

		// no result
		if(!$result) {
			$output = NULL;
			return $output;
		}

		// special layouts
		if(is_object($variant)) {
			$output =& $variant->layout($result);
			return $output;
		}

		// get a layout
		$layout =& Sections::get_layout($variant);

		// do the job
		$output =& $layout->layout($result);
		return $output;
	}

	/**
	 * lock/unlock a section
	 *
	 * @param int the id of the section to update
	 * @param string the previous locking state
	 * @return TRUE on success toggle, FALSE otherwise
	 */
	function lock($id, $status='Y') {
		global $context;

		// id cannot be empty
		if(!$id || !is_numeric($id)) {
			Logger::error(i18n::s('No item has the provided id.'));
			return FALSE;
		}

		// toggle status
		if($status == 'Y')
			$status = 'N';
		else
			$status = 'Y';

		// do the job
		$query = "UPDATE ".SQL::table_name('sections')." SET locked='".SQL::escape($status)."' WHERE id = ".SQL::escape($id);
		if(SQL::query($query) === FALSE)
			return FALSE;
		return TRUE;
	}

	/**
	 * get the id of one section knowing its nick name
	 *
	 * At the moment following section nick names are used throughout yacs:
	 * - 'clicks' - to host external link that are embed into ordinary pages
	 * - 'covers' - pages to be displayed as introductory articles at the front page
	 * - 'forum' - the coffee machine, for those that are not drinking so much coffee
	 * - 'extra_boxes' - page to be displayed as small boxes at the front page
	 * - 'letters' - pages sent by e-mail to subscribers
	 * - 'menus' - page to be displayed on all pages as the main site menu
	 * - 'my_section' - sample section for demonstrattion purpose
	 * - 'navigation_boxes' - pages to be displayed as small boxes on all pages
	 * - 'partners' - pages dedicated to external partners
	 * - 'queries' - pages sent by surfers to submit their queries to the webmaster
	 *
	 * @param string the nick name looked for
	 * @return string either 'section:&lt;id&gt;', or NULL
	 *
	 * @see articles/populate.php
	 * @see articles/review.php
	 * @see index.php
	 * @see letters/index.php
	 * @see letters/new.php
	 * @see links/index.php
	 * @see links/links.php
	 */
	function lookup($nick_name) {
		if($item =& Sections::get($nick_name))
			return 'section:'.$item['id'];
		return NULL;
	}

	/**
	 * look for a section in a list
	 *
	 * @param int id of the section we are looking for
	 * @param mixed int or array($id => $attributes)
	 * @return boolean TRUE if match, FALSE otherwise
	 */
	function match($id, $items) {
		global $context;

		return FALSE;

		// sanity check
		if(!$items)
			return FALSE;

		// exact match
		if(is_int($items))
			return ($id == $items);

		// array search
		if(is_array($items))
			return isset($items[ $id ]);

		// no match
		return FALSE;

	}

	/**
	 * post a new section
	 *
	 * This function populates the error context, where applicable.
	 *
	 * @param array an array of fields
	 * @param boolean TRUE to update the watch list of the poster
	 * @return the id of the new article, or FALSE on error
	 *
	 * @see sections/edit.php
	 * @see sections/populate.php
	 * @see letters/new.php
	 * @see links/links.php
	 * @see query.php
	**/
	function post(&$fields, $watch=TRUE) {
		global $context;

		// title cannot be empty
		if(!isset($fields['title']) || !trim($fields['title'])) {
			Logger::error(i18n::s('No title has been provided.'));
			return FALSE;
		}

		// protect from hackers
		if(isset($fields['icon_url']))
			$fields['icon_url'] =& encode_link($fields['icon_url']);
		if(isset($fields['thumbnail_url']))
			$fields['thumbnail_url'] =& encode_link($fields['thumbnail_url']);

		// set default values for this editor
		Surfer::check_default_editor($fields);

		// reinforce date formats
		if(!isset($fields['activation_date']) || ($fields['activation_date'] <= NULL_DATE))
			$fields['activation_date'] = NULL_DATE;
		if(!isset($fields['create_date']) || ($fields['create_date'] <= NULL_DATE))
			$fields['create_date'] = $fields['edit_date'];
		if(!isset($fields['expiry_date']) || ($fields['expiry_date'] <= NULL_DATE))
			$fields['expiry_date'] = NULL_DATE;
		if(!isset($fields['publish_date']) || ($fields['publish_date'] <= NULL_DATE))
			$fields['publish_date'] = NULL_DATE;

		// set conservative default values
		if(!isset($fields['active_set']))
			$fields['active_set'] = 'Y';
		if(isset($fields['edit_action']))
			$fields['edit_action'] = preg_replace('/import$/i', 'update', $fields['edit_action']);
		if(!isset($fields['home_panel']) || !$fields['home_panel'])
			$fields['home_panel'] = 'main';
		if(!isset($fields['index_map']) || !$fields['index_map'])
			$fields['index_map'] = 'Y';
		if(!isset($fields['index_news']) || !$fields['index_news'])
			$fields['index_news'] = 'none'; // save on requests
		if(!isset($fields['index_panel']) || !$fields['index_panel'])
			$fields['index_panel'] = 'main';
		if(!isset($fields['rank']) || !$fields['rank'])
			$fields['rank'] = 10000;

		// set layout for sections
		if(!isset($fields['sections_layout']) || !$fields['sections_layout'] || !preg_match('/(accordion|carrousel|compact|custom|decorated|folded|freemind|inline|jive|map|slashdot|titles|yabb|none)/', $fields['sections_layout']))
			$fields['sections_layout'] = 'none';
		elseif($fields['sections_layout'] == 'custom') {
			if(isset($fields['sections_custom_layout']) && $fields['sections_custom_layout'])
				$fields['sections_layout'] = $fields['sections_custom_layout'];
			else
				$fields['sections_layout'] = 'none';
		}

		// set layout for articles
		if(!isset($fields['articles_layout']) || !$fields['articles_layout'] || !preg_match('/(accordion|alistapart|carrousel|custom|compact|daily|decorated|digg|hardboiled|jive|map|newspaper|none|simile|slashdot|table|tagged|threads|titles|yabb)/', $fields['articles_layout']))
			$fields['articles_layout'] = 'decorated';
		elseif($fields['articles_layout'] == 'custom') {
			if(isset($fields['articles_custom_layout']) && $fields['articles_custom_layout'])
				$fields['articles_layout'] = $fields['articles_custom_layout'];
			else
				$fields['articles_layout'] = 'decorated';
		}

		// clean provided tags
		if(isset($fields['tags']))
			$fields['tags'] = trim($fields['tags'], " \t.:,!?");

		// cascade anchor access rights
		if(isset($fields['anchor']) && ($anchor =& Anchors::get($fields['anchor'])))
			$fields['active'] = $anchor->ceil_rights($fields['active_set']);
		else
			$fields['active'] = $fields['active_set'];

		// create a random handle for this section
		if(!isset($fields['handle']) || !$fields['handle'])
			$fields['handle'] = md5(mt_rand());
		$handle = "handle='".SQL::escape($fields['handle'])."',";

		// allow anonymous surfer to access this section during his session
		if(!Surfer::get_id())
			Surfer::add_handle($fields['handle']);

		// insert a new record
		$query = "INSERT INTO ".SQL::table_name('sections')." SET ";

		// on import
		if(isset($fields['id']))
			$query .= "id='".SQL::escape($fields['id'])."',";

		// all fields should be visible
		$query .= "anchor='".SQL::escape(isset($fields['anchor']) ? $fields['anchor'] : '')."',"
			."activation_date='".SQL::escape($fields['activation_date'])."',"
			."active='".SQL::escape($fields['active'])."',"
			."active_set='".SQL::escape($fields['active_set'])."',"
			."articles_layout='".SQL::escape(isset($fields['articles_layout']) ? $fields['articles_layout'] : 'decorated')."',"
			."articles_templates='".SQL::escape(isset($fields['articles_templates']) ? $fields['articles_templates'] : '')."',"
			."behaviors='".SQL::escape(isset($fields['behaviors']) ? $fields['behaviors'] : '')."',"
			."content_options='".SQL::escape(isset($fields['content_options']) ? $fields['content_options'] : '')."',"
			."content_overlay='".SQL::escape(isset($fields['content_overlay']) ? $fields['content_overlay'] : '')."',"
			."create_address='".SQL::escape(isset($fields['create_address']) ? $fields['create_address'] : $fields['edit_address'])."', "
			."create_date='".SQL::escape($fields['create_date'])."',"
			."create_id=".SQL::escape(isset($fields['create_id']) ? $fields['create_id'] : $fields['edit_id']).", "
			."create_name='".SQL::escape(isset($fields['create_name']) ? $fields['create_name'] : $fields['edit_name'])."', "
			."description='".SQL::escape(isset($fields['description']) ? $fields['description'] : '')."',"
			."edit_action='".SQL::escape(isset($fields['edit_action']) ? $fields['edit_action'] : 'section:create')."', "
			."edit_address='".SQL::escape($fields['edit_address'])."', "
			."edit_date='".SQL::escape($fields['edit_date'])."',"
			."edit_id=".SQL::escape($fields['edit_id']).", "
			."edit_name='".SQL::escape($fields['edit_name'])."', "
			."expiry_date='".SQL::escape($fields['expiry_date'])."',"
			."extra='".SQL::escape(isset($fields['extra']) ? $fields['extra'] : '')."',"
			."family='".SQL::escape(isset($fields['family']) ? $fields['family'] : '')."',"
			.$handle
			."hits=".SQL::escape(isset($fields['hits']) ? $fields['hits'] : 0).","
			."home_panel='".SQL::escape(isset($fields['home_panel']) ? $fields['home_panel'] : 'main')."',"
			."icon_url='".SQL::escape(isset($fields['icon_url']) ? $fields['icon_url'] : '')."',"
			."index_map='".SQL::escape(isset($fields['index_map']) ? $fields['index_map'] : 'Y')."',"
			."index_news='".SQL::escape(isset($fields['index_news']) ? $fields['index_news'] : 'static')."',"
			."index_news_count=".SQL::escape(isset($fields['index_news_count']) ? $fields['index_news_count'] : 5).","
			."index_panel='".SQL::escape(isset($fields['index_panel']) ? $fields['index_panel'] : 'main')."',"
			."index_title='".SQL::escape(isset($fields['index_title']) ? $fields['index_title'] : '')."',"
			."introduction='".SQL::escape(isset($fields['introduction']) ? $fields['introduction'] : '')."',"
			."language='".SQL::escape(isset($fields['language']) ? $fields['language'] : '')."',"
			."locked='".SQL::escape(isset($fields['locked']) ? $fields['locked'] : 'N')."',"
			."meta='".SQL::escape(isset($fields['meta']) ? $fields['meta'] : '')."',"
			."nick_name='".SQL::escape(isset($fields['nick_name']) ? $fields['nick_name'] : '')."',"
			."options='".SQL::escape(isset($fields['options']) ? $fields['options'] : '')."',"
			."overlay='".SQL::escape(isset($fields['overlay']) ? $fields['overlay'] : '')."',"
			."overlay_id='".SQL::escape(isset($fields['overlay_id']) ? $fields['overlay_id'] : '')."',"
			."owner_id=".SQL::escape(isset($fields['create_id']) ? $fields['create_id'] : $fields['edit_id']).", "
			."prefix='".SQL::escape(isset($fields['prefix']) ? $fields['prefix'] : '')."',"
			."rank='".SQL::escape(isset($fields['rank']) ? $fields['rank'] : 10000)."',"
			."section_overlay='".SQL::escape(isset($fields['section_overlay']) ? $fields['section_overlay'] : '')."',"
			."sections_count=".SQL::escape(isset($fields['sections_count']) ? $fields['sections_count'] : 30).","
			."sections_layout='".SQL::escape(isset($fields['sections_layout']) ? $fields['sections_layout'] : 'map')."',"
			."suffix='".SQL::escape(isset($fields['suffix']) ? $fields['suffix'] : '')."',"
			."tags='".SQL::escape(isset($fields['tags']) ? $fields['tags'] : '')."',"
			."template='".SQL::escape(isset($fields['template']) ? $fields['template'] : '')."',"
			."thumbnail_url='".SQL::escape(isset($fields['thumbnail_url']) ? $fields['thumbnail_url'] : '')."',"
			."title='".SQL::escape(isset($fields['title']) ? $fields['title'] : '')."',"
			."trailer='".SQL::escape(isset($fields['trailer']) ? $fields['trailer'] : '')."'";

		// actual insert
		if(SQL::query($query) === FALSE)
			return FALSE;

		// remember the id of the new item
		$fields['id'] = SQL::get_last_id($context['connection']);

		// assign the page to related categories
		Categories::remember('section:'.$fields['id'], NULL_DATE, isset($fields['tags']) ? $fields['tags'] : '');

		// turn author to page editor and update author's watch list
		if($watch && isset($fields['edit_id']) && $fields['edit_id']) {
			Members::assign('user:'.$fields['edit_id'], 'section:'.$fields['id']);
			Members::assign('section:'.$fields['id'], 'user:'.$fields['edit_id']);
		}

		// clear the cache
		Sections::clear($fields);

		// return the id of the new item
		return $fields['id'];
	}

	/**
	 * put an updated section in the database
	 *
	 * @param array an array of fields
	 * @return TRUE on success, or FALSE on error
	 *
	 * @see sections/edit.php
	**/
	function put(&$fields) {
		global $context;

		// id cannot be empty
		if(!isset($fields['id']) || !is_numeric($fields['id'])) {
			Logger::error(i18n::s('No item has the provided id.'));
			return FALSE;
		}

		// title cannot be empty
		if(!isset($fields['title']) || !trim($fields['title'])) {
			Logger::error(i18n::s('No title has been provided.'));
			return FALSE;
		}

		// protect from hackers
		if(isset($fields['icon_url']))
			$fields['icon_url'] =& encode_link($fields['icon_url']);
		if(isset($fields['thumbnail_url']))
			$fields['thumbnail_url'] =& encode_link($fields['thumbnail_url']);

		// set default values for this editor
		Surfer::check_default_editor($fields);

		// reinforce date formats
		if(!isset($fields['activation_date']) || ($fields['activation_date'] <= NULL_DATE))
			$fields['activation_date'] = NULL_DATE;
		if(!isset($fields['expiry_date']) || ($fields['expiry_date'] <= NULL_DATE))
			$fields['expiry_date'] = NULL_DATE;
		if(!isset($fields['publish_date']) || ($fields['publish_date'] <= NULL_DATE))
			$fields['publish_date'] = NULL_DATE;

		// set conservative default values
		if(!isset($fields['active_set']))
			$fields['active_set'] = 'Y';
		if(isset($fields['edit_action']))
			$fields['edit_action'] = preg_replace('/import$/i', 'update', $fields['edit_action']);
		if(!isset($fields['home_panel']) || !$fields['home_panel'])
			$fields['home_panel'] = 'main';
		if(!isset($fields['index_map']) || !$fields['index_map'])
			$fields['index_map'] = 'Y';
		if(!isset($fields['index_news']) || !$fields['index_news'])
			$fields['index_news'] = 'static';
		if(!isset($fields['index_panel']) || !$fields['index_panel'])
			$fields['index_panel'] = 'main';
		if(!isset($fields['rank']) || !$fields['rank'])
			$fields['rank'] = 10000;

		// set layout for sections
		if(!isset($fields['sections_layout']) || !$fields['sections_layout'] || !preg_match('/(accordion|carrousel|compact|custom|decorated|folded|freemind|inline|jive|map|slashdot|titles|yabb|none)/', $fields['sections_layout']))
			$fields['sections_layout'] = 'map';
		elseif($fields['sections_layout'] == 'custom') {
			if(isset($fields['sections_custom_layout']) && $fields['sections_custom_layout'])
				$fields['sections_layout'] = $fields['sections_custom_layout'];
			else
				$fields['sections_layout'] = 'map';
		}

		// set layout for articles
		if(!isset($fields['articles_layout']) || !$fields['articles_layout'] || !preg_match('/(accordion|alistapart|carrousel|compact|custom|daily|decorated|digg|hardboiled|jive|map|newspaper|none|simile|slashdot|table|tagged|threads|titles|yabb)/', $fields['articles_layout']))
			$fields['articles_layout'] = 'decorated';
		elseif($fields['articles_layout'] == 'custom') {
			if(isset($fields['articles_custom_layout']) && $fields['articles_custom_layout'])
				$fields['articles_layout'] = $fields['articles_custom_layout'];
			else
				$fields['articles_layout'] = 'decorated';
		}

		// clean provided tags
		if(isset($fields['tags']))
			$fields['tags'] = trim($fields['tags'], " \t.:,!?");

		// cascade anchor access rights
		if(isset($fields['anchor']) && ($anchor =& Anchors::get($fields['anchor'])))
			$fields['active'] = $anchor->ceil_rights($fields['active_set']);
		else
			$fields['active'] = $fields['active_set'];

		// fields to update
		$query = array();

		// regular fields
		$query[] = "anchor='".SQL::escape(isset($fields['anchor'])?$fields['anchor']:'')."'";
		$query[] = "title='".SQL::escape($fields['title'])."'";
		$query[] = "activation_date='".SQL::escape($fields['activation_date'])."'";
		$query[] = "active='".SQL::escape($fields['active'])."'";
		$query[] = "active_set='".SQL::escape($fields['active_set'])."'";
		$query[] = "articles_layout='".SQL::escape(isset($fields['articles_layout']) ? $fields['articles_layout'] : 'decorated')."'";
		$query[] = "content_options='".SQL::escape(isset($fields['content_options']) ? $fields['content_options'] : '')."'";
		$query[] = "expiry_date='".SQL::escape($fields['expiry_date'])."'";
		$query[] = "extra='".SQL::escape(isset($fields['extra']) ? $fields['extra'] : '')."'";
		$query[] = "family='".SQL::escape($fields['family'])."'";
		$query[] = "icon_url='".SQL::escape(isset($fields['icon_url']) ? $fields['icon_url'] : '')."'";
		$query[] = "index_map='".SQL::escape(isset($fields['index_map']) ? $fields['index_map'] : 'Y')."'";
		$query[] = "index_news='".SQL::escape(isset($fields['index_news']) ? $fields['index_news'] : 'static')."'";
		$query[] = "index_news_count=".SQL::escape(isset($fields['index_news_count']) ? $fields['index_news_count'] : 5);
		$query[] = "index_panel='".SQL::escape(isset($fields['index_panel']) ? $fields['index_panel'] : 'main')."'";
		$query[] = "index_title='".SQL::escape(isset($fields['index_title']) ? $fields['index_title'] : '')."'";
		$query[] = "introduction='".SQL::escape(isset($fields['introduction']) ? $fields['introduction'] : '')."'";
		$query[] = "description='".SQL::escape(isset($fields['description']) ? $fields['description'] : '')."'";
		$query[] = "nick_name='".SQL::escape(isset($fields['nick_name']) ? $fields['nick_name'] : '')."'";
		$query[] = "language='".SQL::escape(isset($fields['language']) ? $fields['language'] : '')."'";
		$query[] = "locked='".SQL::escape(isset($fields['locked']) ? $fields['locked'] : 'N')."'";
		$query[] = "meta='".SQL::escape(isset($fields['meta']) ? $fields['meta'] : '')."'";
		$query[] = "options='".SQL::escape(isset($fields['options']) ? $fields['options'] : '')."'";
		$query[] = "prefix='".SQL::escape(isset($fields['prefix']) ? $fields['prefix'] : '')."'";
		$query[] = "rank='".SQL::escape($fields['rank'])."'";
		$query[] = "section_overlay='".SQL::escape(isset($fields['section_overlay']) ? $fields['section_overlay'] : '')."'";
		$query[] = "sections_count='".SQL::escape(isset($fields['sections_count']) ? $fields['sections_count'] : 30)."'";
		$query[] = "sections_layout='".SQL::escape(isset($fields['sections_layout']) ? $fields['sections_layout'] : 'map')."'";
		$query[] = "suffix='".SQL::escape(isset($fields['suffix']) ? $fields['suffix'] : '')."'";
		$query[] = "tags='".SQL::escape(isset($fields['tags']) ? $fields['tags'] : '')."'";
		$query[] = "thumbnail_url='".SQL::escape(isset($fields['thumbnail_url']) ? $fields['thumbnail_url'] : '')."'";
		$query[] = "trailer='".SQL::escape(isset($fields['trailer']) ? $fields['trailer'] : '')."'";

		// fields visible only to associates
		if(Surfer::is_associate()) {
			$query[] = "articles_templates='".SQL::escape(isset($fields['articles_templates']) ? $fields['articles_templates'] : '')."'";
			$query[] = "behaviors='".SQL::escape(isset($fields['behaviors']) ? $fields['behaviors'] : '')."'";
			$query[] = "content_overlay='".SQL::escape(isset($fields['content_overlay']) ? $fields['content_overlay'] : '')."'";
			$query[] = "home_panel='".SQL::escape(isset($fields['home_panel']) ? $fields['home_panel'] : 'main')."'";
			$query[] = "overlay='".SQL::escape(isset($fields['overlay']) ? $fields['overlay'] : '')."'";
			$query[] = "overlay_id='".SQL::escape(isset($fields['overlay_id']) ? $fields['overlay_id'] : '')."'";
		}

		// don't stamp silent updates
		if(!isset($fields['silent']) || ($fields['silent'] != 'Y')) {
			$query[] = "edit_name='".SQL::escape($fields['edit_name'])."'";
			$query[] = "edit_id=".SQL::escape($fields['edit_id'])."";
			$query[] = "edit_address='".SQL::escape($fields['edit_address'])."'";
			$query[] = "edit_action='section:update'";
			$query[] = "edit_date='".SQL::escape($fields['edit_date'])."'";
		}

		// update an existing record
		$query = "UPDATE ".SQL::table_name('sections')." SET ".implode(', ', $query)." WHERE id = ".SQL::escape($fields['id']);
		if(SQL::query($query) === FALSE)
			return FALSE;

		// assign the page to related categories
		Categories::remember('section:'.$fields['id'], NULL_DATE, isset($fields['tags']) ? $fields['tags'] : '');

		// clear the cache
		Sections::clear($fields);

		// end of job
		return TRUE;
	}

	/**
	 * change only some attributes
	 *
	 * @param array an array of fields
	 * @return TRUE on success, or FALSE on error
	**/
	function put_attributes(&$fields) {
		global $context;

		// id cannot be empty
		if(!isset($fields['id']) || !is_numeric($fields['id'])) {
			Logger::error(i18n::s('No item has the provided id.'));
			return FALSE;
		}

		// set default values for this editor
		Surfer::check_default_editor($fields);

		// quey components
		$query = array();

		if(isset($fields['anchor']))
			$query[] = "anchor='".SQL::escape($fields['anchor'])."'";
		if(isset($fields['prefix']) && Surfer::is_associate())
			$query[] = "prefix='".SQL::escape($fields['prefix'])."'";
		if(isset($fields['suffix']) && Surfer::is_associate())
			$query[] = "suffix='".SQL::escape($fields['suffix'])."'";
		if(isset($fields['nick_name']))
			$query[] = "nick_name='".SQL::escape($fields['nick_name'])."'";
		if(isset($fields['behaviors']))
			$query[] = "behaviors='".SQL::escape($fields['behaviors'])."'";
		if(isset($fields['extra']))
			$query[] = "extra='".SQL::escape($fields['extra'])."'";
		if(isset($fields['icon_url']))
			$query[] = "icon_url='".SQL::escape(preg_replace('/[^\w\/\.,:%&\?=-]+/', '_', $fields['icon_url']))."'";
		if(isset($fields['rank']))
			$query[] = "rank='".SQL::escape($fields['rank'])."'";
		if(isset($fields['thumbnail_url']))
			$query[] = "thumbnail_url='".SQL::escape(preg_replace('/[^\w\/\.,:%&\?=-]+/', '_', $fields['thumbnail_url']))."'";
		if(isset($fields['locked']))
			$query[] = "locked='".SQL::escape($fields['locked'])."'";
		if(isset($fields['meta']))
			$query[] = "meta='".SQL::escape($fields['meta'])."'";
		if(isset($fields['options']))
			$query[] = "options='".SQL::escape($fields['options'])."'";
		if(isset($fields['trailer']))
			$query[] = "trailer='".SQL::escape($fields['trailer'])."'";
//		if(Surfer::is_empowered())
//			$query[] = "active='".SQL::escape($fields['active'])."',";
//		if(Surfer::is_empowered())
//			$query[] = "active_set='".SQL::escape($fields['active_set'])."',";
		if(isset($fields['owner_id']))
			$query[] = "owner_id=".SQL::escape($fields['owner_id']);
		if(isset($fields['title']))
			$query[] = "title='".SQL::escape($fields['title'])."'";
		if(isset($fields['introduction']))
			$query[] = "introduction='".SQL::escape($fields['introduction'])."'";
		if(isset($fields['description']))
			$query[] = "description='".SQL::escape($fields['description'])."'";
		if(isset($fields['handle']) && $fields['handle'])
			$query[] = "handle='".SQL::escape($fields['handle'])."'";
		if(isset($fields['language']))
			$query[] = "language='".SQL::escape($fields['language'])."'";
		if(isset($fields['overlay']))
			$query[] = "overlay='".SQL::escape($fields['overlay'])."'";
		if(isset($fields['overlay_id']))
			$query[] = "overlay_id='".SQL::escape($fields['overlay_id'])."'";

		if(isset($fields['tags']))
			$query[] = "tags='".SQL::escape($fields['tags'])."'";

		// nothing to update
		if(!count($query))
			return TRUE;

		// maybe a silent update
		if(!isset($fields['silent']) || ($fields['silent'] != 'Y')) {
			$query[] = "edit_name='".SQL::escape($fields['edit_name'])."'";
			$query[] = "edit_id=".SQL::escape($fields['edit_id']);
			$query[] = "edit_address='".SQL::escape($fields['edit_address'])."'";
			$query[] = "edit_action='article:update'";
			$query[] = "edit_date='".SQL::escape($fields['edit_date'])."'";
		}

		// actual update query
		$query = "UPDATE ".SQL::table_name('sections')
			." SET ".implode(', ', $query)
			." WHERE id = ".SQL::escape($fields['id']);
		if(!SQL::query($query))
			return FALSE;

		// clear the cache
		Sections::clear($fields);

		// end of job
		return TRUE;
	}

	/**
	 * change the template of a section
	 *
	 * This function saves the template as an attribute of the section.
	 *
	 * Also, it attempts to translate it as a valid YACS skin made
	 * of [code]template.php[/code] and [code]skin.php[/code].
	 * The theme name is [code]section_&lt;id&gt;[/code].
	 *
	 * Lastly, it updates the options field to actually use the template for pages of this section.
	 *
	 * @param int the id of the target section
	 * @param string the new or updated template
	 * @return string either a null string, or some text describing an error to be inserted into the html response
	 *
	 * @see services/blog.php
	 * @see skins/import.php
	**/
	function put_template($id, $template, $directory=NULL) {
		global $context;

		// id cannot be empty
		if(!$id || !is_numeric($id))
			return i18n::s('No item has the provided id.');

		// load section attributes
		if(!$item =& Sections::get($id))
			return i18n::s('No item has the provided id.');

		// locate the new skin
		if(!$directory)
			$directory = 'section_'.$id;

		// make a valid YACS skin
		include_once $context['path_to_root'].'skins/import.php';
		if($error = Import::process($template, $directory))
			return $error;

		// change the skin for this section
		$options = preg_replace('/\bskin_.+\b/i', '', $item['options']).' skin_'.$directory;

		// set default values for this editor
		Surfer::check_default_editor(array());

		// update an existing record
		$query = "UPDATE ".SQL::table_name('sections')." SET "
			."template='".SQL::escape($template)."',\n"
			."options='".SQL::escape($options)."',\n"
			."edit_name='".SQL::escape($fields['edit_name'])."',\n"
			."edit_id=".SQL::escape($fields['edit_id']).",\n"
			."edit_address='".SQL::escape($fields['edit_address'])."',\n"
			."edit_action='section:update',\n"
			."edit_date='".SQL::escape($fields['edit_date'])."'\n"
			."	WHERE id = ".SQL::escape($id);
		SQL::query($query);

		// clear the cache because of the new rendering
		Sections::clear(array('sections', 'section:'.$id, 'categories'));

	}

	/**
	 * search for some keywords in all sections
	 *
	 * @param the search string
	 * @param int the offset from the start of the list; usually, 0 or 1
	 * @param int the number of items to display
	 * @param mixed the layout, if any
	 * @return NULL on error, else an ordered array with $url => ($prefix, $label, $suffix, $icon)
	 */
	function &search($pattern, $offset=0, $count=50, $layout='decorated') {
		global $context;

		$output =& Sections::search_in_section(NULL, $pattern, $offset, $count, $layout);
		return $output;
	}

	/**
	 * search for some keywords in sub-sections
	 *
	 * This function also searches in sub-sections, with up to three levels of depth.
	 *
	 * @see search.php
	 *
	 * @param the id of the section to look in
	 * @param the search string
	 * @param int the offset from the start of the list; usually, 0 or 1
	 * @param int the number of items to display
	 * @param mixed the layout, if any
	 * @return NULL on error, else an ordered array with $url => ($prefix, $label, $suffix, $icon)
	 */
	function &search_in_section($section_id, $pattern, $offset=0, $count=10, $layout='decorated') {
		global $context;

		// sanity check
		if(!$pattern = trim($pattern)) {
			$output = NULL;
			return $output;
		}

		// search is restricted to one section
		$sections_where = '';
		if($section_id) {

			// look for children
			$anchors = array();

			// first level of depth
			$topics =& Sections::get_children_of_anchor('section:'.$section_id, 'main');
			$anchors = array_merge($anchors, $topics);

			// second level of depth
			if(count($topics) && (count($anchors) < 2000)) {
				$topics =& Sections::get_children_of_anchor($topics, 'main');
				$anchors = array_merge($anchors, $topics);
			}

			// third level of depth
			if(count($topics) && (count($anchors) < 2000)) {
				$topics =& Sections::get_children_of_anchor($topics, 'main');
				$anchors = array_merge($anchors, $topics);
			}

			// fourth level of depth
			if(count($topics) && (count($anchors) < 2000)) {
				$topics =& Sections::get_children_of_anchor($topics, 'main');
				$anchors = array_merge($anchors, $topics);
			}

			// fifth level of depth
			if(count($topics) && (count($anchors) < 2000)) {
				$topics =& Sections::get_children_of_anchor($topics, 'main');
				$anchors = array_merge($anchors, $topics);
			}

			// also include the top level, of course
			$anchors[] = $section_id;

			// the full set of sections searched
			$sections_where = "sections.id IN (".str_replace('section:', '', join(", ", $anchors)).")";

		}

		// select among active sections
		if($sections_where)
			$sections_where = " AND (".$sections_where.")";

		// select among active sections
		$where = "sections.active='Y'";

		// add restricted items to authenticated surfers, or if teasers are allowed
		if(Surfer::is_logged() || Surfer::is_teased())
			$where .= " OR sections.active='R'";

		// associates can access hidden articles
		if(is_string($layout) && ($layout == 'feed'))
			;
		elseif(Surfer::is_associate())
			$where .= " OR sections.active='N'";

		// include managed pages for editors
		if($my_sections = Surfer::assigned_sections()) {
			$where .= " OR sections.id IN (".join(", ", $my_sections).")";
			$where .= " OR sections.anchor IN ('section:".join("', 'section:", $my_sections)."')";
		}

		$where = "(".$where.")";

		// only consider live sections
		$where .= " AND ((sections.expiry_date is NULL) "
				."OR (sections.expiry_date <= '".NULL_DATE."') OR (sections.expiry_date > '".$context['now']."'))";

		// match
		$match = '';
		$words = preg_split('/\s/', $pattern);
		while($word = each($words))
			$match .=  " AND MATCH(title, introduction, description) AGAINST('".SQL::escape($word['value'])."')";

		// the list of articles
		$query = "SELECT sections.*"
			." FROM ".SQL::table_name('sections')." AS sections"
			." WHERE (".$where.")".$sections_where.$match
			." ORDER BY sections.edit_date DESC"
			." LIMIT ".$offset.','.$count;

		$output =& Sections::list_selected(SQL::query($query), $layout);
		return $output;
	}

	/**
	 * create tables for sections
	 *
	 * @see control/populate.php
	 */
	function setup() {
		global $context;

		$fields = array();
		$fields['id']			= "MEDIUMINT UNSIGNED NOT NULL AUTO_INCREMENT";
		$fields['activation_date']	= "DATETIME";
		$fields['active']		= "ENUM('Y','R','N') DEFAULT 'Y' NOT NULL";
		$fields['active_set']	= "ENUM('Y','R','N') DEFAULT 'Y' NOT NULL";
		$fields['anchor']		= "VARCHAR(64)";
		$fields['articles_layout']	= "VARCHAR(255) DEFAULT 'decorated' NOT NULL";
		$fields['articles_templates']	= "VARCHAR(255) DEFAULT '' NOT NULL";
		$fields['behaviors']	= "TEXT NOT NULL";
		$fields['content_options']	= "VARCHAR(255) DEFAULT '' NOT NULL";
		$fields['content_overlay']	= "VARCHAR(64) DEFAULT '' NOT NULL";
		$fields['create_address']	= "VARCHAR(128) DEFAULT '' NOT NULL";
		$fields['create_date']	= "DATETIME";
		$fields['create_id']	= "MEDIUMINT UNSIGNED DEFAULT 1 NOT NULL";
		$fields['create_name']	= "VARCHAR(128) DEFAULT '' NOT NULL";
		$fields['description']	= "TEXT NOT NULL";
		$fields['edit_action']	= "VARCHAR(128) DEFAULT '' NOT NULL";
		$fields['edit_address'] = "VARCHAR(128) DEFAULT '' NOT NULL";
		$fields['edit_date']	= "DATETIME";
		$fields['edit_id']		= "MEDIUMINT UNSIGNED DEFAULT 1 NOT NULL";
		$fields['edit_name']	= "VARCHAR(128) DEFAULT '' NOT NULL";
		$fields['expiry_date']	= "DATETIME";
		$fields['extra']		= "TEXT NOT NULL";
		$fields['family']		= "VARCHAR(255) DEFAULT '' NOT NULL";
		$fields['hits'] 		= "INT UNSIGNED DEFAULT 0 NOT NULL";
		$fields['handle']		= "VARCHAR(128) DEFAULT '' NOT NULL";
		$fields['home_panel']	= "VARCHAR(255) DEFAULT 'main' NOT NULL";
		$fields['icon_url'] 	= "VARCHAR(255) DEFAULT '' NOT NULL";
		$fields['index_map']	= "ENUM('Y', 'N') DEFAULT 'Y' NOT NULL";
		$fields['index_news']	= "VARCHAR(255) DEFAULT 'static' NOT NULL";
		$fields['index_news_count'] = "SMALLINT UNSIGNED DEFAULT 5 NOT NULL";
		$fields['index_panel']	= "VARCHAR(255) DEFAULT 'main' NOT NULL";
		$fields['index_title']	= "VARCHAR(255) DEFAULT '' NOT NULL";
		$fields['introduction'] = "TEXT NOT NULL";
		$fields['language'] 	= "VARCHAR(64) DEFAULT '' NOT NULL";
		$fields['locked']		= "ENUM('Y', 'N') DEFAULT 'N' NOT NULL";
		$fields['maximum_items']	= "MEDIUMINT UNSIGNED";
		$fields['meta'] 		= "TEXT NOT NULL";
		$fields['nick_name']	= "VARCHAR(128) DEFAULT '' NOT NULL";
		$fields['options']		= "VARCHAR(255) DEFAULT '' NOT NULL";
		$fields['overlay']		= "TEXT NOT NULL";
		$fields['overlay_id']	= "VARCHAR(128) DEFAULT '' NOT NULL";
		$fields['owner_id']		= "MEDIUMINT UNSIGNED DEFAULT 0 NOT NULL";
		$fields['prefix']		= "TEXT NOT NULL";
		$fields['rank'] 		= "MEDIUMINT UNSIGNED DEFAULT 10000 NOT NULL";
		$fields['section_overlay']	= "VARCHAR(64) DEFAULT '' NOT NULL";
		$fields['sections_count']	= "SMALLINT UNSIGNED DEFAULT 5 NOT NULL";
		$fields['sections_layout']	= "VARCHAR(255) DEFAULT 'none' NOT NULL";
		$fields['suffix']		= "TEXT NOT NULL";
		$fields['tags'] 		= "VARCHAR(255) DEFAULT '' NOT NULL";
		$fields['template'] 	= "TEXT NOT NULL";
		$fields['thumbnail_url']= "VARCHAR(255) DEFAULT '' NOT NULL";
		$fields['title']		= "VARCHAR(255) DEFAULT '' NOT NULL";
		$fields['trailer']		= "TEXT NOT NULL";											// up to 64k chars

		$indexes = array();
		$indexes['PRIMARY KEY'] 		= "(id)";
		$indexes['INDEX activation_date'] = "(activation_date)";
		$indexes['INDEX active']		= "(active)";
		$indexes['INDEX anchor']		= "(anchor)";
		$indexes['INDEX create_date']	= "(create_date)";
		$indexes['INDEX create_id'] 	= "(create_id)";
		$indexes['INDEX edit_date'] 	= "(edit_date)";
		$indexes['INDEX edit_id']		= "(edit_id)";
		$indexes['INDEX expiry_date']	= "(expiry_date)";
		$indexes['INDEX handle']		= "(handle)";
		$indexes['INDEX hits']			= "(hits)";
		$indexes['INDEX home_panel']	= "(home_panel)";
		$indexes['INDEX index_map'] 	= "(index_map)";
		$indexes['INDEX index_panel']	= "(index_panel)";
		$indexes['INDEX language']		= "(language)";
		$indexes['INDEX locked']		= "(locked)";
		$indexes['INDEX nick_name'] 	= "(nick_name)";
		$indexes['INDEX overlay_id']	= "(overlay_id)";
		$indexes['INDEX rank']			= "(rank)";
		$indexes['INDEX title'] 		= "(title(255))";
		$indexes['FULLTEXT INDEX']		= "full_text(title, introduction, description)";

		return SQL::setup_table('sections', $fields, $indexes);
	}

	/**
	 * get some statistics for some sections
	 *
	 * Only sections matching following criteria are returned:
	 * - section is visible (active='Y')
	 * - section is restricted (active='R'), but surfer is a logged user
	 * - section is hidden (active='N'), but surfer is an associate
	 *
	 * Non-activated and expired sections are counted as well.
	 *
	 * @param string the selected anchor (e.g., 'section:12')
	 * @return array the resulting ($count, $min_date, $max_date) array
	 *
	 * @see sections/delete.php
	 * @see sections/index.php
	 * @see sections/layout_sections.php
	 * @see sections/layout_sections_as_yahoo.php
	 * @see sections/view.php
	 */
	function &stat_for_anchor($anchor = '') {
		global $context;

		// profiling mode
		if($context['with_profile'] == 'Y')
			logger::profile('sections::stat_for_anchor');

		// limit the query to one level
		if($anchor)
			$where = "(sections.anchor LIKE '".SQL::escape($anchor)."')";
		else
			$where = "(sections.anchor='' OR sections.anchor is NULL)";

		// show everything if we are about to suppress a section
		if(!preg_match('/delete\.php/', $context['script_url'])) {

			// display active and restricted items
			$where .= "AND (sections.active='Y'";

			// list restricted sections to authenticated surfers
			if(Surfer::is_logged())
				$where .= " OR sections.active='R'";

			// list hidden sections to associates, editors and readers
			if(Surfer::is_empowered('S'))
				$where .= " OR sections.active='N'";

			$where .= ")";

			// hide sections removed from index maps
			$where .= " AND (sections.index_map = 'Y')";

			// non-associates will have only live sections
			if($anchor && !Surfer::is_empowered()) {
				$where .= " AND ((sections.activation_date is NULL)"
					."	OR (sections.activation_date <= '".$context['now']."'))"
					." AND ((sections.expiry_date is NULL)"
					."	OR (sections.expiry_date <= '".NULL_DATE."') OR (sections.expiry_date > '".$context['now']."'))";
			}
		}

		// list sections
		$query = "SELECT COUNT(*) as count, MIN(edit_date) as oldest_date, MAX(edit_date) as newest_date"
			." FROM ".SQL::table_name('sections')." AS sections"
			." WHERE ".$where;

		$output =& SQL::query_first($query);
		return $output;
	}

}

// load localized strings
if(is_callable(array('i18n', 'bind')))
	i18n::bind('sections');

?>