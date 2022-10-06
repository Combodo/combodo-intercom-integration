<?php

/*
 * @copyright   Copyright (C) 2010-2022 Combodo SARL
 * @license     http://opensource.org/licenses/AGPL-3.0
 */

namespace Combodo\iTop\Extension\IntercomIntegration\Helper;


use Combodo\iTop\Extension\IntercomIntegration\Helper\ConfigHelper;
use DBObjectSearch;
use DBObjectSet;
use IssueLog;

/**
 * Class DatamodelObjectFinder
 *
 * Helper to finder a specific datamodel object in the DB
 *
 * @author Guillaume Lajarige <guillaume.lajarige@combodo.com>
 * @since 1.1.0
 */
class DatamodelObjectFinder
{
	/**
	 * @param string $sFriendlyname Friendlyname of the Contact object to find
	 *
	 * @return \Contact|null The first *non* obsolete Contact object with $sFriendlyname
	 * @throws \CoreException
	 * @throws \CoreUnexpectedValue
	 * @throws \MySQLException
	 * @throws \OQLException
	 */
	public static function GetContactFromFriendlyname($sFriendlyname)
	{
		// Search for first corresponding contact
		$oSearch = DBObjectSearch::FromOQL('SELECT Person WHERE friendlyname = :friendlyname');
		$oSearch->SetShowObsoleteData(false);

		$oSet = new DBObjectSet($oSearch, [], ['friendlyname' => $sFriendlyname]);
		$oSet->SetLimit(1);

		$oContact = $oSet->Fetch();
		if (is_null($oContact)) {
			IssueLog::Debug('Unable to retrieve contact object from friendlyname', ConfigHelper::GetLogChannel(), [
				'friendlyname' => $sFriendlyname,
			]);
			return null;
		}

		return $oContact;
	}

	/**
	 * @param string $sEmail Email of the Contact object to find
	 *
	 * @return \Contact|null The first *non* obsolete Contact object with $sEmail
	 * @throws \CoreException
	 * @throws \CoreUnexpectedValue
	 * @throws \MySQLException
	 * @throws \OQLException
	 */
	public static function GetContactFromEmail($sEmail)
	{
		// Search for first corresponding contact
		$oSearch = DBObjectSearch::FromOQL('SELECT Person WHERE email = :email');
		$oSearch->SetShowObsoleteData(false);

		$oSet = new DBObjectSet($oSearch, [], ['email' => $sEmail]);
		$oSet->SetLimit(1);

		$oContact = $oSet->Fetch();
		if (is_null($oContact)) {
			IssueLog::Debug('Unable to retrieve contact object from email', ConfigHelper::GetLogChannel(), [
				'email' => $sEmail,
			]);
			return null;
		}

		return $oContact;
	}

	/**
	 * @param \Contact|null $oContact Contact object of the DM
	 *
	 * @return \User|null The first *non* obsolete User object for $oContact
	 * @throws \CoreException
	 * @throws \CoreUnexpectedValue
	 * @throws \MySQLException
	 * @throws \OQLException
	 */
	public static function GetUserFromContact($oContact)
	{
		if (is_null($oContact)) {
			IssueLog::Debug('Unable to retrieve user object, no contact passed', ConfigHelper::GetLogChannel());
			return null;
		}

		// Search for first corresponding user
		$oSearch = DBObjectSearch::FromOQL('SELECT User WHERE email = :email');
		$oSearch->SetShowObsoleteData(false);

		$oSet = new DBObjectSet($oSearch, [], ['email' => $oContact->Get('email')]);
		$oSet->SetLimit(1);

		$oUser = $oSet->Fetch();
		if (is_null($oUser)) {
			IssueLog::Debug('Unable to retrieve user object from contact object', ConfigHelper::GetLogChannel(), [
				'contact' => $oContact,
			]);
			return null;
		}

		return $oUser;
	}
}