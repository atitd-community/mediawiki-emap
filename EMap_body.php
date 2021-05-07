<?php


if ( !defined('MEDIAWIKI') )
{
	die( 'This file is a MediaWiki extension, it is not a valid entry point' );
}

class EMap {
		static $mapIdNum = 0;
		static $tale = '1';
		static private $script = NULL;

        public static function onParserSetup( &$parser ){
            $parser->setHook('emap', 'EMap::renderEMap' );
        }

        public static function renderEMap( $input, $params, $parser, $frame ) {

			$parser->getOutput()->updateCacheExpiry();
			global $eMapBasePath;
			$mapID = "map_" . self::$mapIdNum;

			// Parse parameters
			// Start with tale - must be in a predefined list. Default to 8
			$tales = array('1','2','3','4a','4b','5','6','7','8','9');
			self::$tale = 8;
			$tileSource = $eMapBasePath . "/maps/tale8/{z}/{x}/{y}.png";
			if (isset($params['tale'])){
				$found_key = array_search($params['tale'],$tales);
				if($found_key !== FALSE) {
					self::$tale = $tales[$found_key];
					if (preg_replace("/[^0-9,.,\-]/", "", $tales[$found_key]) < 6)
						$tileSource = $eMapBasePath . "/maps/tale" . self::$tale . "/{z}_{x}_{y}.jpg";
					else
						$tileSource = $eMapBasePath . "/maps/tale" . self::$tale . "/{z}/{x}/{y}.png";
				}
			}

			// Zoom levels
			// Valid values for tales 1-4 are: 1-6
			// Valid values for tales 5+ are: 1-8

			$zoom_levels = self::$tale < 5 ? array(1,2,3,4,5,6) : array(1,2,3,4,5,6,7,8);
			$maxZoom = (self::$tale < 5 ? 6 : 8);
			if (isset($params['maxZoom'])){
				$found_key = array_search($params['maxZoom'],$zoom_levels);
				if($found_key !== FALSE) {
					$maxZoom = $zoom_levels[$found_key];
					}
			}

			$minZoom = 1;
			if (isset($params['minZoom'])){
				$found_key = array_search($params['minZoom'],$zoom_levels);
				if($found_key !== FALSE) {
					$minZoom = $zoom_levels[$found_key];
					}
			}

			$zoom = 2;
			if (isset($params['zoom'])){
				$found_key = array_search($params['zoom'],$zoom_levels);
				if($found_key !== FALSE) {
					$zoom = $zoom_levels[$found_key];
					}
				if($zoom < $minZoom)
					$zoom = $minZoom;
				else if($zoom > $maxZoom)
					$zoom = $maxZoom;
			}

			// Control parameters - can be either 'yes' or 'no'
			$showposition = isset($params['showposition']) ? parseControl($params['showposition'], 1) : 1; //Works
			$showfullscreen = isset($params['showfullscreen']) ? parseControl($params['showfullscreen'], 1) : 1; //Works
			$showregion = isset($params['showregion']) ? parseControl($params['showregion'], 1) : 1; //Works
			$showmeasure = isset($params['showmeasure']) ? parseControl($params['showmeasure'], 1) : 1; // Works
			$showzoom = isset($params['showzoom']) ? parseControl($params['showzoom'], 1) : 1; // Works
			$showfullscreen = isset($params['showfullscreen']) ? parseControl($params['showfullscreen'], 1) : 1; //Works
			$autosizeicons = isset($params['autosizeicons']) ? parseControl($params['autosizeicons'], 1) : 1;
			$mouseoverpopup = isset($params['mouseoverpopup']) ? parseControl($params['mouseoverpopup'], 0) : 0;
			$showlayers = isset($params['showlayers']) ? parseControl($params['showlayers'], 1) : 1; //Works

			// We don't need to use the numeric value of self::$tale any more, make it a string
			self::$tale = "'" . self::$tale . "'";


			$style = "";
			$style = $style . "height: " . (isset($params['height']) ? intval($params['height']) : 512) . "px; ";
			$style = $style . "width: " . (isset($params['width']) ? intval($params['width']) : 512) . "px; ";
			$lat = (isset($params['lat']) ? intval($params['lat']) : 1024 );
			$lon = (isset($params['lon']) ? intval($params['lon']) : 0 );

			$script = "";
			$script = $script . "<script>";
			$script = $script . "window.addEventListener('DOMContentLoaded', function() {";
			//Create the map object
			$script = $script . "var " . $mapID ." = L.map(\"" .$mapID . "\", {crs: L.CRS.Simple, center: [0,0],measureControl:true}).setView([0,0]," . $zoom ." );";
			// Create the map helper and initialise
			$script = $script . "var m = new eMap(".$mapID.", " . self::$mapIdNum . ", \"" . $tileSource . "\"" . "," . $minZoom.",".$maxZoom . ",". self::$tale . "); ";
			$script = $script . "m.initIcons();";
			$script .= "m.drawOptions();";

			// Add controls
			$setcontrols = array($showposition, $showregion, $showfullscreen, $showzoom, $showlayers, $showmeasure);
			$script = $script . "m.setControls(" . implode(',',$setcontrols) . ");";

			// Centre the map on the target coords
			$script = $script . "m.centerMap(" . $lat . ", " . $lon . ");";

			$markers = Emap::processLines($input, $mapID, $parser, $frame);

			$script = $script . $markers;

			$script = $script . "});</script>";

			self::$mapIdNum++;

			return "<div id=".$mapID." style=\"". $style . "\">".  "</div>" . $script  ;
			return  true;
			//return $output;
        }


		public static function onParserAfterTidy( Parser &$parser, &$text ) {
		$content_processed =  preg_replace_callback(
			'#!!S!!(.+?)!!E!!#s',
			function($m){
			$m[1] = str_replace('\\',"<br/>",$m[1]);
			return '"' . str_replace('"','\"',$m[1]) . '"';
		},
		$text
		);
		$text = $content_processed;
		return true;
		}

		public static function onBeforePageDisplay( OutputPage &$out, Skin &$skin ) {
		global $wgScriptPath;
			$out->addModuleStyles('ext.emap.styles');
			$out->addScriptFile($wgScriptPath .'/extensions/EMap/modules/ext.emap.leaflet.js');
			$out->addScriptFile($wgScriptPath .'/extensions/EMap/modules/ext.emap.babel.js');
			//$out->addModuleStyles('ext.emap.styles');
			//$out->addModules('ext.emap');
			//$out->addInlineScript($script);
		return true;

		}

		public static function processLines($str, $mapID, $parser, $frame) {
			$line = strtok($str, PHP_EOL);
			$script = "";
			$curMarker = "";
			// Drawing options are arrays, [0] is default [1] is current, [2] is temp
			// This is almost certainly going to need refactoring. Possibly two arrays, one containing names, one containing values?
			$d['stroke'] = [true, true,true];
			$d['color'] = ["'#03f'","'#03f'","'#03f'"];
			$d['weight'] = [5,5,5];
			$d['opacity'] = [0.5,0.5,0.5];
			$d['fill'] = [true,true, true];
			$d['fillColor'] = ["'#03f'","'#03f'","'#03f'"];
			$d['fillOpacity'] = [0.2,0.2,0.2];
			$d['dashArray'] = ["''","''","''"];
			$d['lineCap'] = ["''","''","''"];
			$d['lineJoin'] = ["''","''","''"];
			while ($line !== FALSE) {
				$line = ltrim($line);
				// If the line starts with ! skip it
				if (	(substr($line,0,1) == '!') or
						(substr($line,0,1) == '<') or
						(substr($line,0,1) == '[') or
						(substr($line,0,4) == 'http') or
						(strlen($script) > 109500))
						{
				} else if (	(substr($line,0,4) == "line") or
							(substr($line,0,4) == "poly") or
							(substr($line,0,4) == "rect") or
							(substr($line,0,4) == "circ")
							)	{
					// If the last printing character is a \ then there is a popup on the next line
					$popup = '';
					if (substr(rtrim($line),-1) == '\\'){
						$popup = strtok(PHP_EOL);
						while (substr(rtrim($popup),-1) == '\\')
							$popup .= strtok(PHP_EOL);
					}
					else {
						$popup = "";
					}
					$type = substr($line,0,4);
					$line = ltrim(substr($line,4));
					$lineco = array();
					// Split at the first ' ' only.

					$split = quoted_explode($line," ");
					$coords = explode(":", $split[0]);
					foreach ($coords as $coord) {
						$co = explode(",", $coord);
						array_push($lineco, $co);
					}
					if (sizeof($split) > 1){
						// Either options, radius, or popup.
						$split[1] = ltrim($split[1]);
						if($type == 'circ') {
							//TODO: Make sure only numbers get through
							$radius = $split[1];
							if (sizeof($split) > 2)
								$split[1] = $split[2];
						}
						if(	EMap::isOptions($split[1])) {
							$script .= EMap::processOptions($split[1], $d);
						}
						else {}
					}
					if ((count($lineco)>1) || (count($lineco)>0 && $type == 'circ')) {
						if (strlen($popup) >0)
							$popup = $parser->recursiveTagParse(",!!S!!".  $popup . "!!E!!", $frame);
						else
							$popup = '';

						if($type == 'rect') {
							$script .=  "m.poly([";
							$script .= coordToString($lineco[0][0], $lineco[0][1]) . ",";
							$script .= coordToString($lineco[1][0], $lineco[0][1]) . ",";
							$script .= coordToString($lineco[1][0], $lineco[1][1]) . ",";
							$script .= coordToString($lineco[0][0], $lineco[1][1]) . "]" . $popup;
						} else if ($type == 'circ') {
							$script .=  "m.circ(";
							$script .= coordToString($lineco[0][0], $lineco[0][1]) . ",";
							$script .=  $radius . $popup;


						} else {
							if($type == "line")
								$script = $script . "m.line([";
							else if($type == "poly")
								$script = $script . "m.poly([";
							foreach ($lineco as $item) {
								$script = $script ."[". $item[0] . "," . $item[1] . "], ";
							}
							$script = substr($script,0,-2) . "]" . $popup;
						}
						$script .= ");";

					}
				} else if  (	(substr($line,0,6) == "mlayer") or
								(substr($line,0,6) == "dlayer") ) {
					$type = substr($line,0,6);
					$line = ltrim(substr($line,6));
					$split = quoted_explode($line," ");

					$zindex = (count($split) > 1 ? preg_replace("/[^0-9,.,\-]/", "", $split[1]) : 400);
					$opacity = (count($split) > 2 ? preg_replace("/[^0-9,.,\-]/", "", $split[2]) : 1);
					//$opacity = $split[2];
					if(count($split) > 0) {
						$name = preg_replace("/[^a-zA-Z,0-9]/", "", $split[0]);
						$name = substr($name,0,(strlen($name) < 20 ? strlen($name) : 20));
						if ($type== 'mlayer')
							$script .= "m.setmPane(\"" . $name . "\"," . $zindex . "," . $opacity . ");";
						if ($type== 'dlayer')
							$script .= "m.setdPane(\"" . $name . "\"," . $zindex . "," . $opacity . ");";
					}
				} else if  (	(substr($line,0,9) == "hidelayer") ) {
					$type = substr($line,0,9);
					$line = ltrim(substr($line,9));
					$split = quoted_explode($line," ");
					$name = preg_replace("/[^a-zA-Z,0-9]/", "", $split[0]);
					$script .= "m.hidePane(\"" . $name . "\");";

				} else {
						// If the last printing character is a \ then the text continues onto the next line
						if (substr(rtrim($line),-1) == '\\'){
							$line .= strtok(PHP_EOL);
							while (substr(rtrim($line),-1) == '\\')
								$line .= strtok(PHP_EOL);
						}
						$split = explode(",", $line, 3);
						if(count($split) > 2){
							$temp_lat = preg_replace("/\((.*)\)/", "", $split[0]);
							$lat = preg_replace("/[^0-9,.,\-]/", "", $temp_lat);
							$marker = preg_replace("/[.,\-,\s,(,)]/", "", substr($split[0],0,-1*strlen($lat)));
							if($marker != $curMarker){
								$curMarker = $marker;
								$script = $script . "m.sM(\"" . $marker . "\");";
								}
							$lon = preg_replace("/[^0-9,.,\-]/", "", $split[1]);

							$output = $split[2];

							$script = $script . $parser->recursiveTagParse("m.dm(" . $lat . "," . $lon . ",!!S!!".  $output . "!!E!!);", $frame);
						}
					}



				$line = strtok(PHP_EOL);
			}

			//make sure the tokenizer frees it's memory
			strtok('', '');
			return $script;
		}

		public static function isOptions($str){
			if(	(strncasecmp("stroke:",$str,7)==0) or
				(strncasecmp("color:",$str,6)==0) or
				(strncasecmp("weight:",$str,7)==0) or
				(strncasecmp("opacity:",$str,8)==0) or
				(strncasecmp("fill:",$str,5)==0) or
				(strncasecmp("fillColor:",$str,10)==0) or
				(strncasecmp("fillOpacity:",$str,12)==0) or
				(strncasecmp("dashArray:",$str,10)==0) or
				(strncasecmp("lineCap:",$str,8)==0) or
				(strncasecmp("lineJoin:",$str,9)==0))
				return true;
			else
				return false;
		}

		public static function processOptions($str, $d){

		$options = quoted_explode($str);
		$ret = "";
		foreach ($d as $dinst)
			$dinst[2] = $dinst[1];
		foreach ($options as $option) {
			if(strncasecmp("stroke:",$option,7)==0)
				$d['stroke'][2] = substr($option,7);
			else if(strncasecmp("color:",$option,6)==0)
				$d['color'][2] = substr($option,6);
			else if(strncasecmp("weight:",$option,7)==0)
				$d['weight'][2] = substr($option,7);
			else if(strncasecmp("opacity:",$option,8)==0)
				$d['opacity'][2] = substr($option,8);
			else if(strncasecmp("fill:",$option,5)==0)
				$d['fill'][2] = substr($option,5);
			else if(strncasecmp("fillColor:",$option,10)==0)
				$d['fillColor'][2] = substr($option,10);
			else if(strncasecmp("fillOpacity:",$option,12)==0)
				$d['fillOpacity'][2] = substr($option,12);
			else if(strncasecmp("dashArray:",$option,10)==0)
				$d['dashArray'][2] = substr($option,10);
			else if(strncasecmp("lineCap:",$option,8)==0)
				$d['lineCap'][2] = substr($option,8);
			else if(strncasecmp("lineJoin:",$option,9)==0)
				$d['lineJoin'][2] = substr($option,9);
		}
		if ($d['stroke'][2] != $d['stroke'][1] or
			$d['color'][2] != $d['color'][1] or
			$d['weight'][2] != $d['weight'][1] or
			$d['opacity'][2] != $d['opacity'][1] or
			$d['fill'][2] != $d['fill'][1] or
			$d['fillColor'][2] != $d['fillColor'][1] or
			$d['fillOpacity'][2] != $d['fillOpacity'][1] or
			$d['dashArray'][2] != $d['dashArray'][1] or
			$d['lineCap'][2] != $d['lineCap'][1] or
			$d['lineJoin'][2] != $d['lineJoin'][1]) {
				$ret = "m.drawOptions(". $d['stroke'][2] . "," . $d['color'][2] . "," . $d['weight'][2] . "," . $d['opacity'][2] . "," . $d['fill'][2] . "," . $d['fillColor'][2] . "," . $d['fillOpacity'][2] . "," . $d['dashArray'][2] . "," . $d['lineCap'][2] . "," . $d['lineJoin'][2] . ");";
		}
		foreach ($d as $dinst)
			$dinst[1] = $dinst[2];
		return $ret;


		}



}

function coordToString($x,$y) {
	return "[" . $x . "," . $y . "]";
}

function regex_escape($subject) {
    return str_replace(array('\\', '^', '-', ']'), array('\\\\', '\\^', '\\-', '\\]'), $subject);
}

function quoted_explode($subject, $delimiters = ',', $quotes = '\'') {
    $clauses[] = '[^'.regex_escape($delimiters.$quotes).']';
    foreach(str_split($quotes) as $quote) {
        $quote = regex_escape($quote);
        $clauses[] = "[$quote][^$quote]*[$quote]";
    }
    $regex = '(?:'.implode('|', $clauses).')+';
    preg_match_all('/'.str_replace('/', '\\/', $regex).'/', $subject, $matches);
    return $matches[0];
}

function parseControl($control, $default) {
	$options = array('no', 'yes');
	$found_key = array_search($control,$options);
	if($found_key !== FALSE) {
		return $found_key;
		}
	return $default;
}
?>
