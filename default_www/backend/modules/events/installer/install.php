<?php

/**
 * Installer for the events module
 *
 * @package		installer
 * @subpackage	events
 *
 * @author		Tijs Verkoyen <tijs@sumocoders.be>
 * @since		2.0
 */
class EventsInstall extends ModuleInstaller
{
	/**
	 * Add the default category for a language
	 *
	 * @return	int
	 * @param	string $language	The language to use.
	 * @param	string $name		The name of the category.
	 * @param	string $url			The URL for the category.
	 */
	private function addCategory($language, $name, $url)
	{
		return (int) $this->getDB()->insert('events_categories', array('language' => (string) $language, 'name' => (string) $name, 'url' => (string) $url));
	}


	/**
	 * Install the module
	 *
	 * @return	void
	 */
	protected function execute()
	{
		// load install.sql
		$this->importSQL(dirname(__FILE__) .'/data/install.sql');

		// add 'events' as a module
		$this->addModule('events', 'The events module.');

		// general settings
		$this->setSetting('events', 'allow_comments', true);
		$this->setSetting('events', 'requires_akismet', true);
		$this->setSetting('events', 'spamfilter', false);
		$this->setSetting('events', 'moderation', true);
		$this->setSetting('events', 'ping_services', true);
		$this->setSetting('events', 'overview_num_items', 10);
		$this->setSetting('events', 'max_num_revisions', 20);
		$this->setSetting('events', 'notify_by_email_on_new_comment_to_moderate', true);
		$this->setSetting('events', 'notify_by_email_on_new_comment', true);

		// make module searchable
		$this->makeSearchable('events');

		// module rights
		$this->setModuleRights(1, 'events');

		// action rights
		$this->setActionRights(1, 'events', 'categories');
		$this->setActionRights(1, 'events', 'add_category');
		$this->setActionRights(1, 'events', 'edit_category');
		$this->setActionRights(1, 'events', 'delete_category');
		$this->setActionRights(1, 'events', 'add_category');
		$this->setActionRights(1, 'events', 'index');
		$this->setActionRights(1, 'events', 'add');
		$this->setActionRights(1, 'events', 'edit');
		$this->setActionRights(1, 'events', 'delete');
		$this->setActionRights(1, 'events', 'comments');
		$this->setActionRights(1, 'events', 'edit_comment');
		$this->setActionRights(1, 'events', 'delete_spam');
		$this->setActionRights(1, 'events', 'mass_comment_action');
		$this->setActionRights(1, 'events', 'settings');

		// add extra's
		$eventsID = $this->insertExtra('events', 'block', 'Events', null, null, 'N', 5000);
		$this->insertExtra('events', 'widget', 'RecentComments', 'recent_comments', null, 'N', 5001);
		$this->insertExtra('events', 'widget', 'Categories', 'categories', null, 'N', 5002);
		$this->insertExtra('events', 'widget', 'Archive', 'archive', null, 'N', 5003);

		// loop languages
		foreach($this->getLanguages() as $language)
		{
			// fetch current categoryId
			$currentCategoryId = $this->getCategory($language);

			// no category exists
			if($currentCategoryId == 0)
			{
				// add default category
				$defaultCategoryId = $this->addCategory($language, 'Default', 'default');

				// insert default category setting
				$this->setSetting('events', 'default_category_'. $language, $defaultCategoryId, true);
			}

			// category exists
			else
			{
				// current default categoryId
				$currentDefaultCategoryId = $this->getSetting('events', 'default_category_'. $language);

				// does not exist
				if(!$this->existsCategory($language, $currentDefaultCategoryId))
				{
					// insert default category setting
					$this->setSetting('events', 'default_category_'. $language, $currentCategoryId, true);
				}
			}

			// feedburner URL
			$this->setSetting('events', 'feedburner_url_'. $language, '');

			// RSS settings
			$this->setSetting('events', 'rss_meta_'. $language, true);
			$this->setSetting('events', 'rss_title_'. $language, 'RSS');
			$this->setSetting('events', 'rss_description_'. $language, '');

			// check if a page for events already exists in this language
			if(!(bool) $this->getDB()->getVar('SELECT COUNT(p.id)
												FROM pages AS p
												INNER JOIN pages_blocks AS b ON b.revision_id = p.revision_id
												WHERE b.extra_id = ? AND p.language = ?',
												array($eventsID, $language)))
			{
				// insert page
				$this->insertPage(array('title' => 'Events',
										'language' => $language),
									null,
									array('extra_id' => $eventsID));
			}

			// install example data if requested
			if($this->installExample()) $this->installExampleData($language);
		}


		// insert locale (nl)
		$this->insertLocale('nl', 'backend', 'core', 'err', 'IntegerIsInvalid', 'Dit is een ongeldig geheel getal.');
		$this->insertLocale('nl', 'backend', 'events', 'err', 'RSSDescription', 'Evenementen RSS beschrijving is nog niet geconfigureerd. <a href="%1$s">Configureer</a>');
		$this->insertLocale('nl', 'backend', 'events', 'lbl', 'Add', 'evenement toevoegen');
		$this->insertLocale('nl', 'backend', 'events', 'msg', 'Added', 'Het evenement "%1$s" werd toegevoegd.');
		$this->insertLocale('nl', 'backend', 'events', 'msg', 'CommentOnWithURL', 'Reactie op: <a href="%1$s">%2$s</a>');
		$this->insertLocale('nl', 'backend', 'events', 'msg', 'ConfirmDelete', 'Ben je zeker dat je het evenement "%1$s" wil verwijderen?');
		$this->insertLocale('nl', 'backend', 'events', 'msg', 'Deleted', 'De geselecteerde evenementen werden verwijderd.');
		$this->insertLocale('nl', 'backend', 'events', 'msg', 'DeletedSpam', 'Alle spamberichten werden verwijderd.');
		$this->insertLocale('nl', 'backend', 'events', 'msg', 'DeleteAllSpam', 'Verwijdere all spam:');
		$this->insertLocale('nl', 'backend', 'events', 'msg', 'EditArticle', 'bewerk evenement "%1$s"');
		$this->insertLocale('nl', 'backend', 'events', 'msg', 'EditCommentOn', 'bewerk reactie op "%1$s"');
		$this->insertLocale('nl', 'backend', 'events', 'msg', 'Edited', 'Het evenement "%1$s" werd opgeslagen.');
		$this->insertLocale('nl', 'backend', 'events', 'msg', 'EditedComment', 'De reactie werd opgeslagen.');
		$this->insertLocale('nl', 'backend', 'events', 'msg', 'FollowAllCommentsInRSS', 'Volg alle reacties in een RSS feed: <a href="%1$s">%1$s</a>.');
		$this->insertLocale('nl', 'backend', 'events', 'msg', 'HelpMeta', 'Toon de meta informatie van de evenementen in de RSS feed (categorie, tags, ...)');
		$this->insertLocale('nl', 'backend', 'events', 'msg', 'HelpPingServices', 'Laat verschillende blogservices weten wanneer je een nieuw evenement plaatst.');
		$this->insertLocale('nl', 'backend', 'events', 'msg', 'HelpSummary', 'Maak voor lange artikels een inleiding of samenvatting. Die kan getoond worden op de homepage of het evenementenoverzicht.');
		$this->insertLocale('nl', 'backend', 'events', 'msg', 'HelpSpamFilter', 'Schakel de ingebouwde spam-filter (Akismet) in om spam-berichten in reacties te vermijden.');
		$this->insertLocale('nl', 'backend', 'events', 'msg', 'MakeDefaultCategory', 'Maak van deze categorie de standaardcategorie (de huidige standaardcategorie is %1$s).');
		$this->insertLocale('nl', 'backend', 'events', 'msg', 'NoItems', 'Er zijn nog geen evenementen. <a href="%1$s">Voeg het eerste evenement toe</a>.');
		$this->insertLocale('nl', 'backend', 'events', 'msg', 'NotifyByEmailOnNewComment', 'Verwittig via email als er een nieuwe reactie is.');
		$this->insertLocale('nl', 'backend', 'events', 'msg', 'NotifyByEmailOnNewCommentToModerate', 'Verwittig via email als er een nieuwe reactie te modereren is.');
		$this->insertLocale('nl', 'backend', 'core', 'lbl', 'Dates', 'datums');
		$this->insertLocale('nl', 'backend', 'core', 'lbl', 'EndsOn', 'eindigt op');
		$this->insertLocale('nl', 'backend', 'core', 'lbl', 'Event', 'evenement');
		$this->insertLocale('nl', 'backend', 'core', 'lbl', 'Events', 'evenementen');
		$this->insertLocale('nl', 'backend', 'core', 'lbl', 'StartsOn', 'start op');
		$this->insertLocale('nl', 'frontend', 'core', 'act', 'ArticleCommentsRss', 'reacties-op-rss');
//		$this->insertLocale('nl', 'frontend', 'core', 'act', 'Ical', 'ical');
//		$this->insertLocale('nl', 'frontend', 'core', 'act', 'IcalAll', 'ical-allemaal');
		$this->insertLocale('nl', 'frontend', 'core', 'lbl', 'SubscribeToTheRSSFeed', 'schrijf je in op de RSS-feed');
		$this->insertLocale('nl', 'frontend', 'core', 'lbl', 'EventsArchive', 'evenementenarchief');
		$this->insertLocale('nl', 'frontend', 'core', 'lbl', 'NextEvent', 'volgend evenement');
		$this->insertLocale('nl', 'frontend', 'core', 'lbl', 'PreviousEvent', 'vorig evenement');
		$this->insertLocale('nl', 'frontend', 'core', 'lbl', 'RecentEvents', 'recente evenement');
		$this->insertLocale('nl', 'frontend', 'core', 'lbl', 'Wrote', 'schreef');
		$this->insertLocale('nl', 'frontend', 'core', 'msg', 'EventsAllComments', 'Alle reacties op je evenementen.');
		$this->insertLocale('nl', 'frontend', 'core', 'msg', 'EventsNoComments', 'Reageer als eerste');
		$this->insertLocale('nl', 'frontend', 'core', 'msg', 'EventsNumberOfComments', 'Al %1$s reacties');
		$this->insertLocale('nl', 'frontend', 'core', 'msg', 'EventsOneComment', 'Al 1 reactie');
		$this->insertLocale('nl', 'frontend', 'core', 'msg', 'EventsCommentIsAdded', 'Je reactie werd toegevoegd.');
		$this->insertLocale('nl', 'frontend', 'core', 'msg', 'EventsCommentInModeration', 'Je reactie wacht op goedkeuring.');
		$this->insertLocale('nl', 'frontend', 'core', 'msg', 'EventsCommentIsSpam', 'Je reactie werd gemarkeerd als spam.');
		$this->insertLocale('nl', 'frontend', 'core', 'msg', 'EventsEmailNotificationsNewComment', '%1$s reageerde op <a href="%2$s">%3$s</a>.');
		$this->insertLocale('nl', 'frontend', 'core', 'msg', 'EventsEmailNotificationsNewCommentToModerate', '%1$s reageerde op <a href="%2$s">%3$s</a>. <a href="%4$s">Modereer</a> deze reactie om ze zichtbaar te maken op de website.');
		$this->insertLocale('nl', 'frontend', 'core', 'msg', 'EventsNoItems', 'Er zijn nog geen evenementen.');

		// insert locale (en)
		$this->insertLocale('en', 'backend', 'core', 'err', 'IntegerIsInvalid', 'Invalid integer.');
		$this->insertLocale('en', 'backend', 'events', 'err', 'RSSDescription', 'Events RSS description is not yet provided. <a href="%1$s">Configure</a>');
		$this->insertLocale('en', 'backend', 'events', 'lbl', 'Add', 'add event');
		$this->insertLocale('en', 'backend', 'events', 'msg', 'Added', 'The event "%1$s" was added.');
		$this->insertLocale('en', 'backend', 'events', 'msg', 'CommentOnWithURL', 'Comment on: <a href="%1$s">%2$s</a>');
		$this->insertLocale('en', 'backend', 'events', 'msg', 'ConfirmDelete', 'Are your sure you want to delete the event "%1$s"?');
		$this->insertLocale('en', 'backend', 'events', 'msg', 'Deleted', 'The selected events were deleted.');
		$this->insertLocale('en', 'backend', 'events', 'msg', 'DeletedSpam', 'All spam-comments were deleted.');
		$this->insertLocale('en', 'backend', 'events', 'msg', 'DeleteAllSpam', 'Delete all spam:');
		$this->insertLocale('en', 'backend', 'events', 'msg', 'EditArticle', 'edit event "%1$s"');
		$this->insertLocale('en', 'backend', 'events', 'msg', 'EditCommentOn', 'edit comment on "%1$s"');
		$this->insertLocale('en', 'backend', 'events', 'msg', 'Edited', 'The event "%1$s" was saved.');
		$this->insertLocale('en', 'backend', 'events', 'msg', 'EditedComment', 'The comment was saved.');
		$this->insertLocale('en', 'backend', 'events', 'msg', 'FollowAllCommentsInRSS', 'Follow all comments in a RSS feed: <a href="%1$s">%1$s</a>.');
		$this->insertLocale('en', 'backend', 'events', 'msg', 'HelpMeta', 'Show the meta information for the events in the RSS feed (category, tags, ...)');
		$this->insertLocale('en', 'backend', 'events', 'msg', 'HelpPingServices', 'Let various blogservices know when you\'ve posted a new event.');
		$this->insertLocale('en', 'backend', 'events', 'msg', 'HelpSummary', 'Write an introduction or summary for long articles. It will be shown on the homepage or the article overview.');
		$this->insertLocale('en', 'backend', 'events', 'msg', 'HelpSpamFilter', 'Enable the built-in spamfilter (Akismet) to help avoid spam comments.');
		$this->insertLocale('en', 'backend', 'events', 'msg', 'MakeDefaultCategory', 'Make default category (current default category is: %1$s).');
		$this->insertLocale('en', 'backend', 'events', 'msg', 'NoItems', 'There are no event yet. <a href="%1$s">Add the first event</a>.');
		$this->insertLocale('en', 'backend', 'events', 'msg', 'NotifyByEmailOnNewComment', 'Notify by email when there is a new comment.');
		$this->insertLocale('en', 'backend', 'events', 'msg', 'NotifyByEmailOnNewCommentToModerate', 'Notify by email when there is a new comment to moderate.');
		$this->insertLocale('en', 'backend', 'core', 'lbl', 'Dates', 'dates');
		$this->insertLocale('en', 'backend', 'core', 'lbl', 'EndsOn', 'ends on');
		$this->insertLocale('en', 'backend', 'core', 'lbl', 'Event', 'event');
		$this->insertLocale('en', 'backend', 'core', 'lbl', 'Events', 'events');
		$this->insertLocale('en', 'backend', 'core', 'lbl', 'StartsOn', 'starts on');
		$this->insertLocale('en', 'frontend', 'core', 'act', 'ArticleCommentsRss', 'comments-on-rss');
//		$this->insertLocale('en', 'frontend', 'core', 'act', 'Ical', 'ical');
//		$this->insertLocale('en', 'frontend', 'core', 'act', 'IcalAll', 'ical-all');
		$this->insertLocale('en', 'frontend', 'core', 'lbl', 'InTheCategory', 'in category');
		$this->insertLocale('en', 'frontend', 'core', 'lbl', 'SubscribeToTheRSSFeed', 'subscribe to the RSS feed');
		$this->insertLocale('en', 'frontend', 'core', 'lbl', 'EventsArchive', 'events archive');
		$this->insertLocale('en', 'frontend', 'core', 'lbl', 'NextEvent', 'next event');
		$this->insertLocale('en', 'frontend', 'core', 'lbl', 'PreviousEvent', 'previous event');
		$this->insertLocale('en', 'frontend', 'core', 'lbl', 'RecentEvents', 'recent events');
		$this->insertLocale('en', 'frontend', 'core', 'lbl', 'Wrote', 'wrote');
		$this->insertLocale('en', 'frontend', 'core', 'msg', 'EventsAllComments', 'All comments on your events.');
		$this->insertLocale('en', 'frontend', 'core', 'msg', 'EventsNoComments', 'Be the first to comment');
		$this->insertLocale('en', 'frontend', 'core', 'msg', 'EventsNumberOfComments', '%1$s comments');
		$this->insertLocale('en', 'frontend', 'core', 'msg', 'EventsOneComment', '1 comment already');
		$this->insertLocale('en', 'frontend', 'core', 'msg', 'EventsCommentIsAdded', 'Your comment was added.');
		$this->insertLocale('en', 'frontend', 'core', 'msg', 'EventsCommentInModeration', 'Your comment is awaiting moderation.');
		$this->insertLocale('en', 'frontend', 'core', 'msg', 'EventsCommentIsSpam', 'Your comment was marked as spam.');
		$this->insertLocale('en', 'frontend', 'core', 'msg', 'EventsEmailNotificationsNewComment', '%1$s commented on <a href="%2$s">%3$s</a>.');
		$this->insertLocale('en', 'frontend', 'core', 'msg', 'EventsEmailNotificationsNewCommentToModerate', '%1$s commented on <a href="%2$s">%3$s</a>. <a href="%4$s">Moderate</a> the comment to publish it.');
		$this->insertLocale('en', 'frontend', 'core', 'msg', 'EventsNoItems', 'There are no events yet.');
	}


	/**
	 * Does the category with this id exist within this language.
	 *
	 * @return	bool
	 * @param	string $language	The langauge to use.
	 * @param	int $id				The id to exclude.
	 */
	private function existsCategory($language, $id)
	{
		return (bool) $this->getDB()->getVar('SELECT COUNT(id) FROM events_categories WHERE id = ? AND language = ?', array((int) $id, (string) $language));
	}


	/**
	 * Fetch the id of the first category in this language we come across
	 *
	 * @return	int
	 * @param	string $language	The language to use.
	 */
	private function getCategory($language)
	{
		return (int) $this->getDB()->getVar('SELECT id FROM events_categories WHERE language = ?', array((string) $language));
	}


	/**
	 * Install example data
	 *
	 * @return	void
	 * @param	string $language	The language to use.
	 */
	private function installExampleData($language)
	{
		// get db instance
		$db = $this->getDB();

		// check if eventsposts already exist in this language
		if(!(bool) $db->getVar('SELECT COUNT(id) FROM events WHERE language = ?', array($language)))
		{
			// insert sample eventspost 1
			$db->insert('events', array('id' => 1,
											'category_id' => $this->getSetting('events', 'default_category_'. $language),
											'user_id' => $this->getDefaultUserID(),
											'meta_id' => $this->insertMeta('Nunc sediam est', 'Nunc sediam est', 'Nunc sediam est', 'nunc-sediam-est'),
											'language' => $language,
											'starts_on' => gmdate('Y-06-20 11:24:00'),
											'title' => 'Nunc sediam est',
											'introduction' => SpoonFile::getContent(PATH_WWW .'/backend/modules/events/installer/data/'. $language .'/sample1.txt'),
											'text' => SpoonFile::getContent(PATH_WWW .'/backend/modules/events/installer/data/'. $language .'/sample1.txt'),
											'status' => 'active',
											'publish_on' => gmdate('Y-m-d H:i:00'),
											'created_on' => gmdate('Y-m-d H:i:00'),
											'edited_on' => gmdate('Y-m-d H:i:00'),
											'hidden' => 'N',
											'allow_comments' => 'Y',
											'num_comments' => '3'));

			// insert sample eventspost 2
			$db->insert('events', array('id' => 2,
											'category_id' => $this->getSetting('events', 'default_category_'. $language),
											'user_id' => $this->getDefaultUserID(),
											'meta_id' => $this->insertMeta('Lorem ipsum', 'Lorem ipsum', 'Lorem ipsum', 'lorem-ipsum'),
											'language' => $language,
											'starts_on' => gmdate('Y-10-11 09:i:00'),
											'ends_on' => gmdate('Y-10-11 18:i:00'),
											'title' => 'Lorem ipsum',
											'introduction' => SpoonFile::getContent(PATH_WWW .'/backend/modules/events/installer/data/'. $language .'/sample1.txt'),
											'text' => SpoonFile::getContent(PATH_WWW .'/backend/modules/events/installer/data/'. $language .'/sample1.txt'),
											'status' => 'active',
											'publish_on' => gmdate('Y-m-d H:i:00', (time() - 60)),
											'created_on' => gmdate('Y-m-d H:i:00', (time() - 60)),
											'edited_on' => gmdate('Y-m-d H:i:00', (time() - 60)),
											'hidden' => 'N',
											'allow_comments' => 'Y',
											'num_comments' => '0'));

			// insert example comment 1
			$db->insert('events_comments', array('event_id' => 1,
												'language' => $language,
												'created_on' => gmdate('Y-m-d H:i:00'),
												'author' => 'Matthias Mullie',
												'email' => 'matthias@spoon-library.com',
												'website' => 'http://www.anantasoft.com',
												'text' => 'cool!',
												'type' => 'comment',
												'status' => 'published',
												'data' => null));

			// insert example comment 2
			$db->insert('events_comments', array('event_id' => 1,
												'language' => $language,
												'created_on' => gmdate('Y-m-d H:i:00'),
												'author' => 'Davy Hellemans',
												'email' => 'davy@spoon-library.com',
												'website' => 'http://www.spoon-library.com',
												'text' => 'awesome!',
												'type' => 'comment',
												'status' => 'published',
												'data' => null));

			// insert example comment 3
			$db->insert('events_comments', array('event_id' => 1,
												'language' => $language,
												'created_on' => gmdate('Y-m-d H:i:00'),
												'author' => 'Tijs Verkoyen',
												'email' => 'tijs@spoon-library.com',
												'website' => 'http://www.sumocoders.be',
												'text' => 'wicked!',
												'type' => 'comment',
												'status' => 'published',
												'data' => null));
		}
	}
}

?>