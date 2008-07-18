<?php
/*
	*********************************************************************
	* phpLogCon - http://www.phplogcon.org
	* -----------------------------------------------------------------
	* User Admin File											
	*																	
	* -> Helps administrating custom user views
	*																	
	* All directives are explained within this file
	*
	* Copyright (C) 2008 Adiscon GmbH.
	*
	* This file is part of phpLogCon.
	*
	* PhpLogCon is free software: you can redistribute it and/or modify
	* it under the terms of the GNU General Public License as published by
	* the Free Software Foundation, either version 3 of the License, or
	* (at your option) any later version.
	*
	* PhpLogCon is distributed in the hope that it will be useful,
	* but WITHOUT ANY WARRANTY; without even the implied warranty of
	* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	* GNU General Public License for more details.
	*
	* You should have received a copy of the GNU General Public License
	* along with phpLogCon. If not, see <http://www.gnu.org/licenses/>.
	*
	* A copy of the GPL can be found in the file "COPYING" in this
	* distribution				
	*********************************************************************
*/

// *** Default includes	and procedures *** //
define('IN_PHPLOGCON', true);
$gl_root_path = './../';

// Now include necessary include files!
include($gl_root_path . 'include/functions_common.php');
include($gl_root_path . 'include/functions_frontendhelpers.php');
include($gl_root_path . 'include/functions_filters.php');

// Set PAGE to be ADMINPAGE!
define('IS_ADMINPAGE', true);
$content['IS_ADMINPAGE'] = true;
InitPhpLogCon();
InitSourceConfigs();
InitFrontEndDefaults();	// Only in WebFrontEnd
InitFilterHelpers();	// Helpers for frontend filtering!

// Init admin langauge file now!
IncludeLanguageFile( $gl_root_path . '/lang/' . $LANG . '/admin.php' );
// --- 

// --- BEGIN Custom Code

// Only if the user is an admin!
//if ( !isset($_SESSION['SESSION_ISADMIN']) || $_SESSION['SESSION_ISADMIN'] == 0 ) 
//	DieWithFriendlyErrorMsg( $content['LN_ADMIN_ERROR_NOTALLOWED'] );

if ( isset($_GET['op']) )
{
	if ($_GET['op'] == "add") 
	{
		// Set Mode to add
		$content['ISEDITORNEWVIEW'] = "true";
		$content['VIEW_FORMACTION'] = "addnewview";
		$content['VIEW_SENDBUTTON'] = $content['LN_VIEWS_ADD'];
		
		//PreInit these values 
		$content['DisplayName'] = "";
		$content['userid'] = null;
		$content['CHECKED_ISUSERONLY'] = "";
		$content['VIEWID'] = "";

		// --- Check if groups are available
		$content['SUBGROUPS'] = GetGroupsForSelectfield();
		if ( is_array($content['SUBGROUPS']) )
			$content['ISGROUPSAVAILABLE'] = true;
		else
			$content['ISGROUPSAVAILABLE'] = false;
		// ---
	}
	else if ($_GET['op'] == "edit") 
	{
		// Set Mode to edit
		$content['ISEDITORNEWVIEW'] = "true";
		$content['VIEW_FORMACTION'] = "editview";
		$content['VIEW_SENDBUTTON'] = $content['LN_VIEWS_EDIT'];
		
		// View must be loaded as well already!
		if ( isset($_GET['id']) && $content['VIEWS'][$_GET['id']] )
		{
			//PreInit these values 
			$content['VIEWID'] = DB_RemoveBadChars($_GET['id']);

			$sqlquery = "SELECT ID, DisplayName " . 
						" FROM " . DB_VIEWS . 
						" WHERE ID = " . $content['VIEWID'];

			$result = DB_Query($sqlquery);
			$myview = DB_GetSingleRow($result, true);
			if ( isset($myview['DisplayName']) )
			{
				$content['VIEWID'] = $myview['ID'];

/*
				$content['DisplayName'] = $mysearch['DisplayName'];
				$content['SearchQuery'] = $mysearch['SearchQuery'];
				if ( $mysearch['userid'] != null )
					$content['CHECKED_ISUSERONLY'] = "checked";
				else
					$content['CHECKED_ISUSERONLY'] = "";
*/

				// --- Check if groups are available
				$content['SUBGROUPS'] = GetGroupsForSelectfield();
				if ( is_array($content['SUBGROUPS']) )
				{
					// Process All Groups
					for($i = 0; $i < count($content['SUBGROUPS']); $i++)
					{
						if ( $myview['groupid'] != null && $content['SUBGROUPS'][$i]['mygroupid'] == $myview['groupid'] )
							$content['SUBGROUPS'][$i]['group_selected'] = "selected";
						else
							$content['SUBGROUPS'][$i]['group_selected'] = "";
					}

					// Enable Group Selection
					$content['ISGROUPSAVAILABLE'] = true;
				}
				else
					$content['ISGROUPSAVAILABLE'] = false;
				// ---
			}
			else
			{
				$content['ISEDITORNEWVIEW'] = false;
				$content['ISERROR'] = true;
				$content['ERROR_MSG'] = GetAndReplaceLangStr( $content['LN_VIEWS_ERROR_IDNOTFOUND'], $content['VIEWID'] );
			}
		}
		else
		{
			$content['ISEDITORNEWVIEW'] = false;
			$content['ISERROR'] = true;
			$content['ERROR_MSG'] =  $content['LN_VIEWS_ERROR_INVALIDID'];
		}
	}
	else if ($_GET['op'] == "delete") 
	{
		if ( isset($_GET['id']) )
		{
			//PreInit these values 
			$content['VIEWID'] = DB_RemoveBadChars($_GET['id']);

			// Get UserInfo
			$result = DB_Query("SELECT DisplayName FROM " . DB_VIEWS . " WHERE ID = " . $content['VIEWID'] ); 
			$myrow = DB_GetSingleRow($result, true);
			if ( !isset($myrow['DisplayName']) )
			{
				$content['ISERROR'] = true;
				$content['ERROR_MSG'] = GetAndReplaceLangStr( $content['LN_VIEWS_ERROR_IDNOTFOUND'], $content['VIEWID'] ); 
			}

			// --- Ask for deletion first!
			if ( (!isset($_GET['verify']) || $_GET['verify'] != "yes") )
			{
				// This will print an additional secure check which the user needs to confirm and exit the script execution.
				PrintSecureUserCheck( GetAndReplaceLangStr( $content['LN_VIEWS_WARNDELETEVIEW'], $myrow['DisplayName'] ), $content['LN_DELETEYES'], $content['LN_DELETENO'] );
			}
			// ---

			// do the delete!
			$result = DB_Query( "DELETE FROM " . DB_VIEWS . " WHERE ID = " . $content['VIEWID'] );
			if ($result == FALSE)
			{
				$content['ISERROR'] = true;
				$content['ERROR_MSG'] = GetAndReplaceLangStr( $content['LN_VIEWS_ERROR_DELSEARCH'], $content['VIEWID'] ); 
			}
			else
				DB_FreeQuery($result);

			// Do the final redirect
			RedirectResult( GetAndReplaceLangStr( $content['LN_VIEWS_ERROR_HASBEENDEL'], $myrow['DisplayName'] ) , "views.php" );
		}
		else
		{
			$content['ISERROR'] = true;
			$content['ERROR_MSG'] = $content['LN_VIEWS_ERROR_INVALIDID'];
		}
	}
}

// --- Additional work todo for the edit view
if ( isset($content['ISEDITORNEWVIEW']) && $content['ISEDITORNEWVIEW'] )
{
	// Read Columns from FORM data!
	if ( isset($_POST['Columns']) )
	{
		// --- Read Columns from Formdata
		if ( is_array($_POST['Columns']) )
		{
			// Copy columns ID's
			foreach ($_POST['Columns'] as $myColKey)
				$content['SUBCOLUMNS'][$myColKey]['ColFieldID'] = $myColKey;
		}
		else	// One element only
			$content['SUBCOLUMNS'][$_POST['Columns']]['ColFieldID'] = $_POST['Columns'];
		// --- 

		// --- Process Columns for display 
		$i = 0; // Help counter!
		foreach ($content['SUBCOLUMNS'] as $key => &$myColumn )
		{
			// Set Fieldcaption
			if ( isset($content[ $fields[$key]['FieldCaptionID'] ]) )
				$myColumn['ColCaption'] = $content[ $fields[$key]['FieldCaptionID'] ];
			else
				$myColumn['ColCaption'] = $key;

			// --- Set CSS Class
			if ( $i % 2 == 0 )
				$myColumn['colcssclass'] = "line1";
			else
				$myColumn['colcssclass'] = "line2";
			$i++;
			// --- 
		}
		// --- 

//		print_r ( $content['COLUMNS'] );
	}

	// --- Copy fields data array
	$content['FIELDS'] = $fields; 
	
	// removed already added fields 
	foreach ($content['SUBCOLUMNS'] as $key => &$myColumn )
	{
		if ( isset($content['FIELDS'][$key]) ) 
			unset($content['FIELDS'][$key]);
	}

	// set fieldcaption
	foreach ($content['FIELDS'] as $key => &$myField )
	{
		// Set Fieldcaption
		if ( isset($content[ $myField['FieldCaptionID'] ]) )
			$myField['FieldCaption'] = $content[ $myField['FieldCaptionID'] ];
		else
			$myField['FieldCaption'] = $key;
	}
	// ---

}
// --- 

// --- Process POST Form Data
if ( isset($_POST['op']) )
{
	if ( isset ($_POST['id']) ) { $content['VIEWID'] = DB_RemoveBadChars($_POST['id']); } else {$content['VIEWID'] = ""; }
	if ( isset ($_POST['DisplayName']) ) { $content['DisplayName'] = DB_RemoveBadChars($_POST['DisplayName']); } else {$content['DisplayName'] = ""; }
//	if ( isset ($_POST['SearchQuery']) ) { $content['SearchQuery'] = DB_RemoveBadChars($_POST['SearchQuery']); } else {$content['SearchQuery'] = ""; }

	// User & Group handeled specially
	if ( isset ($_POST['isuseronly']) ) 
	{ 
		$content['userid'] = $content['SESSION_USERID']; 
		$content['groupid'] = "null"; // Either user or group not both!
	} 
	else 
	{
		$content['userid'] = "null"; 
		if ( isset ($_POST['groupid']) && $_POST['groupid'] != -1 ) 
			$content['groupid'] = intval($_POST['groupid']); 
		else 
			$content['groupid'] = "null";
	}

	// --- Check mandotary values
	if ( $content['DisplayName'] == "" )
	{
		$content['ISERROR'] = true;
		$content['ERROR_MSG'] = $content['LN_VIEWS_ERROR_DISPLAYNAMEEMPTY'];
	}
	// --- 
print_r ( $_POST );

	if ( !isset($content['ISERROR']) ) 
	{	
		// Check subop's first!
		if ( isset($_POST['subop']) )
		{
			// Get NewColID
			$szColId = DB_RemoveBadChars($_POST['newcolumn']);
			
			// Add a new Column into our list!
			if ( $_POST['subop'] == $content['LN_VIEWS_ADDCOLUMN'] && isset($_POST['newcolumn']) )
			{
				// Add New entry into columnlist
				$content['SUBCOLUMNS'][$szColId]['ColFieldID'] = $szColId;

				// Set Fieldcaption
				if ( isset($content[ $fields[$szColId]['FieldCaptionID'] ]) )
					$content['SUBCOLUMNS'][$szColId]['ColCaption'] = $content[ $fields[$szColId]['FieldCaptionID'] ];
				else
					$content['SUBCOLUMNS'][$szColId]['ColCaption'] = $szColId;

				// Set CSSClass
				$content['SUBCOLUMNS'][$szColId]['colcssclass'] = count($content['SUBCOLUMNS']) % 2 == 0 ? "line1" : "line2";
				
				// Remove from fields list as well
				if ( isset($content['FIELDS'][$szColId]) ) 
					unset($content['FIELDS'][$szColId]);

			}
		}
		else if ( isset($_POST['subop_delete']) )
		{
			// Get Column ID
			$szColId = DB_RemoveBadChars($_POST['subop_delete']);

			// Remove Entry from Columnslist
			if ( isset($content['SUBCOLUMNS'][$szColId]) )
				unset($content['SUBCOLUMNS'][$szColId]);

			// Add removed entry to field list
			$content['FIELDS'][$szColId] = $fields[$szColId];

			// Set Fieldcaption
			if ( isset($content[ $fields[$szColId]['FieldCaptionID'] ]) )
				$content['FIELDS'][$szColId]['FieldCaption'] = $content[ $fields[$szColId]['FieldCaptionID'] ];
			else
				$content['FIELDS'][$szColId]['FieldCaption'] = $szColId;
		}
		else if ( isset($_POST['subop_moveup']) )
		{
			// Get Column ID
			$szColId = DB_RemoveBadChars($_POST['subop_moveup']);

			// Move Entry one UP in Columnslist

		}
		else if ( isset($_POST['subop_movedown']) )
		{
			// Get Column ID
			$szColId = DB_RemoveBadChars($_POST['subop_movedown']);

			// Move Entry one DOWN in Columnslist

		}
		else // Now SUBOP means normal processing!
		{
			// Everything was alright, so we go to the next step!
			if ( $_POST['op'] == "addnewsearch" )
			{
				// Add custom search now!
				$sqlquery = "INSERT INTO " . DB_SEARCHES . " (DisplayName, SearchQuery, userid, groupid) 
				VALUES ('" . $content['DisplayName'] . "', 
						'" . $content['SearchQuery'] . "',
						" . $content['userid'] . ", 
						" . $content['groupid'] . " 
						)";
				$result = DB_Query($sqlquery);
				DB_FreeQuery($result);
				
				// Do the final redirect
				RedirectResult( GetAndReplaceLangStr( $content['LN_SEARCH_HASBEENADDED'], $content['DisplayName'] ) , "searches.php" );
			}
			else if ( $_POST['op'] == "editsearch" )
			{
				$result = DB_Query("SELECT ID FROM " . DB_SEARCHES . " WHERE ID = " . $content['SEARCHID']);
				$myrow = DB_GetSingleRow($result, true);
				if ( !isset($myrow['ID']) )
				{
					$content['ISERROR'] = true;
					$content['ERROR_MSG'] = GetAndReplaceLangStr( $content['LN_SEARCH_ERROR_IDNOTFOUND'], $content['SEARCHID'] ); 
				}
				else
				{
					// Edit the Search Entry now!
					$result = DB_Query("UPDATE " . DB_SEARCHES . " SET 
						DisplayName = '" . $content['DisplayName'] . "', 
						SearchQuery = '" . $content['SearchQuery'] . "', 
						userid = " . $content['userid'] . ", 
						groupid = " . $content['groupid'] . "
						WHERE ID = " . $content['SEARCHID']);
					DB_FreeQuery($result);

					// Done redirect!
					RedirectResult( GetAndReplaceLangStr( $content['LN_SEARCH_HASBEENEDIT'], $content['DisplayName']) , "searches.php" );
				}
			}
		}
	}
}

if ( !isset($_POST['op']) && !isset($_GET['op']) )
{
	// Default Mode = List Searches
	$content['LISTVIEWS'] = "true";
/*
	// Read all Serverentries
	$sqlquery = "SELECT " . 
				DB_VIEWS . ".ID, " . 
				DB_VIEWS . ".DisplayName, " . 
				DB_VIEWS . ".Columns, " . 
				DB_VIEWS . ".userid, " .
				DB_VIEWS . ".groupid, " .
				DB_USERS . ".username, " .
				DB_GROUPS . ".groupname " .
				" FROM " . DB_VIEWS . 
				" LEFT OUTER JOIN (" . DB_USERS . ", " . DB_GROUPS . 
				") ON (" . 
				DB_VIEWS . ".userid=" . DB_USERS . ".ID AND " . 
				DB_VIEWS . ".groupid=" . DB_GROUPS . ".ID " . 
				") " .
				" ORDER BY " . DB_VIEWS . ".userid, " . DB_VIEWS . ".groupid, " . DB_VIEWS . ".DisplayName";
//echo $sqlquery;
	$result = DB_Query($sqlquery);
	$content['VIEWS'] = DB_GetAllRows($result, true);
*/

	// Copy Views array for further modifications
	$content['VIEWS'] = $content['Views'];

	// --- Process Users
	$i = 0; // Help counter!
	foreach ($content['VIEWS'] as &$myView )
	{
		// So internal Views can not be edited but seen
		if ( is_numeric($myView['ID']) )
		{
			$myView['ActionsAllowed'] = true;

			// --- Set Image for Type
			if ( $myView['userid'] != null )
			{
				$myView['SearchTypeImage'] = $content["MENU_ADMINUSERS"];
				$myView['SearchTypeText'] = $content["LN_GEN_USERONLY"];
			}
			else if ( $myView['groupid'] != null )
			{
				$myView['SearchTypeImage'] = $content["MENU_ADMINGROUPS"];
				$myView['SearchTypeText'] = $content["LN_GEN_GROUPONLY"];
			}
			else
			{
				$myView['SearchTypeImage'] = $content["MENU_GLOBAL"];
				$myView['SearchTypeText'] = $content["LN_GEN_GLOBAL"];
			}
			// ---
		}
		else
		{
			$myView['ActionsAllowed'] = false;

			$myView['SearchTypeImage'] = $content["MENU_INTERNAL"];
			$myView['SearchTypeText'] = $content["LN_GEN_INTERNAL"];
		}

		// --- Add DisplayNames to columns
		$iBegin = true;
		foreach ($myView['Columns'] as $myCol )
		{
			// Get Fieldcaption
			if ( isset($content[ $fields[$myCol]['FieldCaptionID'] ]) )
				$myView['COLUMNS'][$myCol]['FieldCaption'] = $content[ $fields[$myCol]['FieldCaptionID'] ];
			else
				$myView['COLUMNS'][$myCol]['FieldCaption'] = $myCol;
		
			if ( $iBegin )
			{
				$myView['COLUMNS'][$myCol]['FieldCaptionSeperator'] = "";
				$iBegin = false;
			}
			else
				$myView['COLUMNS'][$myCol]['FieldCaptionSeperator'] = ", ";

		}
		// ---

		// --- Set CSS Class
		if ( $i % 2 == 0 )
			$myView['cssclass'] = "line1";
		else
			$myView['cssclass'] = "line2";
		$i++;
		// --- 
	}
	// --- 
}
// --- END Custom Code

// --- BEGIN CREATE TITLE
$content['TITLE'] = InitPageTitle();
$content['TITLE'] .= " :: " . $content['LN_ADMINMENU_VIEWSOPT'];
// --- END CREATE TITLE

// --- Parsen and Output
InitTemplateParser();
$page -> parser($content, "admin/admin_views.html");
$page -> output(); 
// --- 

?>