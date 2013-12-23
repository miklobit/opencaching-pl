<?php
	/***************************************************************************
															./lib/search.ovl.inc.php
																-------------------
			begin                : November 5 2005 
			copyright            : (C) 2005 The OpenCaching Group
			forum contact at     : http://www.opencaching.com/phpBB2

		***************************************************************************/

	/***************************************************************************
		*                                         				                                
		*   This program is free software; you can redistribute it and/or modify  	
		*   it under the terms of the GNU General Public License as published by  
		*   the Free Software Foundation; either version 2 of the License, or	    	
		*   (at your option) any later version.
		*
		***************************************************************************/

	/****************************************************************************
		         
		Unicode Reminder ăĄă˘
                                				                                
		ovl search output for TOP25, TOP50 etc.
		
	****************************************************************************/

	global $content, $bUseZip, $sqldebug, $hide_coords, $usr;
	set_time_limit(1800);
	$ovlLine = "[Symbol {symbolnr1}]\r\nTyp=6\r\nGroup=1\r\nWidth=20\r\nHeight=20\r\nDir=100\r\nArt=1\r\nCol=3\r\nZoom=1\r\nSize=103\r\nArea=2\r\nXKoord={lon}\r\nYKoord={lat}\r\n[Symbol {symbolnr2}]\r\nTyp=2\r\nGroup=1\r\nCol=3\r\nArea=1\r\nZoom=1\r\nSize=130\r\nFont=1\r\nDir=100\r\nXKoord={lonname}\r\nYKoord={latname}\r\nText={mod_suffix}{cachename}\r\n";
	$ovlFoot = "[Overlay]\r\nSymbols={symbolscount}\r\n";

	if( $usr || !$hide_coords )
	{
		//prepare the output
		$caches_per_page = 20;
		
		$sql = 'SELECT '; 
		
		if (isset($lat_rad) && isset($lon_rad))
		{
			$sql .= getSqlDistanceFormula($lon_rad * 180 / 3.14159, $lat_rad * 180 / 3.14159, 0, $multiplier[$distance_unit]) . ' `distance`, ';
		}
		else
		{
			if ($usr === false)
			{
				$sql .= '0 distance, ';
			}
			else
			{
				//get the users home coords
				$rs_coords = sql("SELECT `latitude`, `longitude` FROM `user` WHERE `user_id`='&1'", $usr['userid']);
				$record_coords = sql_fetch_array($rs_coords);
				
				if ((($record_coords['latitude'] == NULL) || ($record_coords['longitude'] == NULL)) || (($record_coords['latitude'] == 0) || ($record_coords['longitude'] == 0)))
				{
					$sql .= '0 distance, ';
				}
				else
				{
					//TODO: load from the users-profile
					$distance_unit = 'km';

					$lon_rad = $record_coords['longitude'] * 3.14159 / 180;   
					$lat_rad = $record_coords['latitude'] * 3.14159 / 180; 

					$sql .= getSqlDistanceFormula($record_coords['longitude'], $record_coords['latitude'], 0, $multiplier[$distance_unit]) . ' `distance`, ';
				}
				mysql_free_result($rs_coords);
			}
		}
		$sql .= '`caches`.`cache_id` `cache_id`, `caches`.`longitude` `longitude`, `caches`.`latitude` `latitude`, `caches`.`type` `type`
							FROM `caches`
					WHERE `caches`.`cache_id` IN (' . $sqlFilter . ')';
		
		$sortby = $options['sort'];
		if (isset($lat_rad) && isset($lon_rad) && ($sortby == 'bydistance'))
		{
			$sql .= ' ORDER BY distance ASC';
		}
		else if ($sortby == 'bycreated')
		{
			$sql .= ' ORDER BY date_created DESC';
		}
		else // by name
		{
			$sql .= ' ORDER BY name ASC';
		}

		//startat?
		$startat = isset($_REQUEST['startat']) ? $_REQUEST['startat'] : 0;
		if (!is_numeric($startat)) $startat = 0;
		
		if (isset($_REQUEST['count']))
			$count = $_REQUEST['count'];
		else
			$count = $caches_per_page;
		
		$maxlimit = 1000000000;
		
		if ($count == 'max') $count = $maxlimit;
		if (!is_numeric($count)) $count = 0;
		if ($count < 1) $count = 1;
		if ($count > $maxlimit) $count = $maxlimit;

		$sqlLimit = ' LIMIT ' . $startat . ', ' . $count;

		// temporĂ¤re tabelle erstellen
		sql('CREATE TEMPORARY TABLE `ovlcontent` ' . $sql . $sqlLimit, $sqldebug);

		$rsCount = sql('SELECT COUNT(*) `count` FROM `ovlcontent`');
		$rCount = sql_fetch_array($rsCount);
		mysql_free_result($rsCount);
		
		if ($rCount['count'] == 1)
		{
			$rsName = sql('SELECT `caches`.`wp_oc` `wp_oc` FROM `ovlcontent`, `caches` WHERE `ovlcontent`.`cache_id`=`caches`.`cache_id` LIMIT 1');
			$rName = sql_fetch_array($rsName);
			mysql_free_result($rsName);
			
			$sFilebasename = $rName['wp_oc'];
		}
		else {
			if ($options['searchtype'] == 'bywatched') {
				$sFilebasename = 'watched_caches';
			} elseif ($options['searchtype'] == 'bylist') {
				$sFilebasename = 'cache_list';
			} else {
				$rsName = sql('SELECT `queries`.`name` `name` FROM `queries` WHERE `queries`.`id`= &1 LIMIT 1', $options['queryid']);
				$rName = sql_fetch_array($rsName);
				mysql_free_result($rsName);
				if (isset($rName['name']) && ($rName['name'] != '')) {
					$sFilebasename = trim($rName['name']);
					$sFilebasename = str_replace(" ", "_", $sFilebasename);
				} else {
					$sFilebasename = 'ocpl' . $options['queryid'];
				}
			}
		}
			
		$bUseZip = ($rCount['count'] > 50);
		$bUseZip = $bUseZip || (isset($_REQUEST['zip']) && ($_REQUEST['zip'] == '1'));
		$bUseZip = false;
		if ($bUseZip == true)
		{
			$content = '';
			require_once($rootpath . 'lib/phpzip/ss_zip.class.php');
			$phpzip = new ss_zip('',6);
		}

		// ok, ausgabe starten
		
		if ($sqldebug == false)
		{
			if ($bUseZip == true)
			{
				header("content-type: application/zip");
				header('Content-Disposition: attachment; filename=' . $sFilebasename . '.zip');
			}
			else
			{
				header("Content-type: application/ovl");
				header("Content-Disposition: attachment; filename=" . $sFilebasename . ".ovl");
			}
		}

		// ok, ausgabe ...
		
	/*
		{symbolnr1}
		{lon}
		{lat}
		{symbolnr2}
		{lonname}
		{latname}
		{cachename}
	*/

		$nr = 1;
		$rs = sql('SELECT `ovlcontent`.`cache_id` `cacheid`, `ovlcontent`.`longitude` `longitude`, `ovlcontent`.`latitude` `latitude`, `caches`.`name` `name`, `ovlcontent`.`type` `type` FROM `ovlcontent`, `caches` WHERE `ovlcontent`.`cache_id`=`caches`.`cache_id`');
		while($r = sql_fetch_array($rs))
		{
			$thisline = $ovlLine;
			
			$lat = sprintf('%01.5f', $r['latitude']);
			$thisline = mb_ereg_replace('{lat}', $lat, $thisline);
			$thisline = mb_ereg_replace('{latname}', $lat, $thisline);
			
			$lon = sprintf('%01.5f', $r['longitude']);
			$thisline = mb_ereg_replace('{lon}', $lon, $thisline);
			$thisline = mb_ereg_replace('{lonname}', $lon, $thisline);
			//modified coords
		if ($r['type'] =='7' && $usr!=false) {  //check if quiz (7) and user is logged 
			if (!isset($dbc)) {$dbc = new dataBase();};	
			$mod_coord_sql = 'SELECT cache_id FROM cache_mod_cords
						WHERE cache_id = :v1 AND user_id =:v2';

			$params['v1']['value'] = (integer) $r['cacheid'];
			$params['v1']['data_type'] = 'integer';
			$params['v2']['value'] = (integer) $usr['userid'];
			$params['v2']['data_type'] = 'integer';

			$dbc ->paramQuery($mod_coord_sql,$params);
			Unset($params);				
			

			if ($dbc->rowCount() > 0 )
			{
				$thisline = str_replace('{mod_suffix}', '<F>', $thisline);
			} else {
				$thisline = str_replace('{mod_suffix}', '', $thisline);
			}
		} else {
			$thisline = str_replace('{mod_suffix}', '', $thisline);
		}; 
		
			$thisline = mb_ereg_replace('{cachename}', convert_string($r['name']), $thisline);
			$thisline = mb_ereg_replace('{symbolnr1}', $nr, $thisline);
			$thisline = mb_ereg_replace('{symbolnr2}', $nr + 1, $thisline);

			append_output($thisline);
			ob_flush();
			$nr += 2;
		}
		mysql_free_result($rs);
		unset($dbc);
		$ovlFoot = mb_ereg_replace('{symbolscount}', $nr - 1, $ovlFoot);
		append_output($ovlFoot);
		
		if ($sqldebug == true) sqldbg_end();
		
		// phpzip versenden
		if ($bUseZip == true)
		{
			$phpzip->add_data($sFilebasename . '.ovl', $content);
			echo $phpzip->save($sFilebasename . '.zip', 'b');
		}
	}
	exit;
	
	function convert_string($str)
	{
		$newstr = iconv("UTF-8", "utf-8", $str);
		if ($newstr == false)
			return $str;
		else
			return $newstr;
	}
	
	function xmlentities($str)
	{
		$from[0] = '&'; $to[0] = '&amp;';
		$from[1] = '<'; $to[1] = '&lt;';
		$from[2] = '>'; $to[2] = '&gt;';
		$from[3] = '"'; $to[3] = '&quot;';
		$from[4] = '\''; $to[4] = '&apos;';
		
		for ($i = 0; $i <= 4; $i++)
			$str = mb_ereg_replace($from[$i], $to[$i], $str);
	        	
	        	$str = preg_replace('/[[:cntrl:]]/', '', $str);

		return $str;
	}
	
	function append_output($str)
	{
		global $content, $bUseZip, $sqldebug;
		if ($sqldebug == true) return;

		if ($bUseZip == true)
			$content .= $str;
		else
			echo $str;
	}
?>
