<?php
/**
* Detailed copyright and licensing information can be found
* in the gpl-3.0.txt file which should be included in the distribution.
* 
* @version		1.0 2012-10-10 nuclear-head
* @copyright	2012 nuclear-head
* @license  	GPLv3 Open Source
* @link       	http://jvitals.com
* @since      	File available since initial release
*/

// no direct access
defined('_JEXEC') or die('Restricted access');

jimport('joomla.plugin.plugin');

class plgContentMedia_embed extends JPlugin {
	var $playerGlobalOptions;
	var $web_dir;
	var $declarations;
	var $version;

	public function __construct(&$subject, $config) {
		parent::__construct($subject, $config);
		
		$this->version = '2.0.4.1';

		$this->playerGlobalOptions = array(
			'width' => $this->params->def('width', '290'),
			'animation' => (bool)$this->params->def('animation', 1),
			'remaining' => (bool)$this->params->def('remaining', 0),
			'initialvolume' => $this->params->def('initialvolume', '60'),
			'buffer' => $this->params->def('buffer', '5'),
			'noinfo' => (bool)$this->params->def('noinfo', 0),
			'checkpolicy' => (bool)$this->params->def('checkpolicy', 0),
			'rtl' => (bool)$this->params->def('rtl', 0),
			'bg' => 'E5E5E5',
			'text' => '333333',
			'leftbg' => 'CCCCCC',
			'lefticon' => '333333',
			'volslider' => '666666',
			'voltrack' => 'FFFFFF',
			'rightbg' => 'B4B4B4',
			'rightbghover' => '999999',
			'righticon' => '333333',
			'righticonhover' => 'FFFFFF',
			'track' => 'FFFFFF',
			'loader' => '009900',
			'border' => 'CCCCCC',
			'tracker' => 'DDDDDD',
			'skip' => '666666',
			'pagebg' => 'FFFFFF',
			'transparentpagebg' => true
		);
		
		$this->web_dir = JURI::root(true) . '/plugins/content/media_embed/';
		
		$this->declarations = '';
	}

	function onContentPrepare($context, &$row, &$params, $page = 0) {
	
		if (strpos($row->text, '[audio') !== false) {
		
			$document = JFactory::getDocument();
			$document->addScript($this->web_dir . 'audio-player.js?ver=' . $this->version . '');
			$document->addScriptDeclaration('AudioPlayer.setup("' . $this->web_dir . 'player.swf?ver=' . $this->version . '", ' . $this->php2js($this->playerGlobalOptions) . ');');
			$row->text = preg_replace_callback("/\[audio(([^]]+))\]/i", array(&$this, 'replacePlayer'), $row->text );
			if ($this->declarations) $document->addScriptDeclaration($this->declarations);
			$this->declarations = '';
			
		} elseif (strpos($row->text, '[youtube') !== false) {
		
			$row->text = preg_replace_callback("/\[youtube(([^]]+))\]/i", array(&$this, 'replaceYoutube'), $row->text );
			
		} elseif (strpos($row->text, '[vimeo') !== false) {
		
			$row->text = preg_replace_callback("/\[vimeo(([^]]+))\]/i", array(&$this, 'replaceVimeo'), $row->text );
		}
		
	}

	function replacePlayer($matches) {
		$playerElementID = 'audioplayer_' . md5('audio-' . rand() . '-' . time());
		$files = array();
		$data = array();
		$data = preg_split("/[\|]/", $matches[1]);
		$playerOptions = array();
		
		// files
		foreach (explode(',', trim($data[0])) as $file) {
			$files[] = trim(trim($file, chr(0xC2).chr(0xA0)));
			
		}
		$source = implode(',', $files);
		if (function_exists('html_entity_decode')) $source = html_entity_decode($source);
		
		// options
		for ($i = 1; $i < count($data); $i++) {
			$pair = explode("=", $data[$i]);
			$playerOptions[trim($pair[0])] = trim($pair[1]);
		}
		
		$playerOptions['soundFile'] = $source;
		$playerOptions['titles'] = $playerOptions['title'];
		
		$playerCode = '<p class="audioplayer_container"><span style="display: block; padding: 5px; border:1px solid #dddddd; background: #f8f8f8" id="' . $playerElementID . '">Audio clip: Adobe Flash Player (version 9 or above) is required to play this audio clip. Download the latest version <a href="http://get.adobe.com/flashplayer" title="Download Adobe Flash Player">here</a>. You also need to have JavaScript enabled in your browser.</span></p>';
		$this->declarations .= 'AudioPlayer.embed("' . $playerElementID . '", ' . $this->php2js($playerOptions) . ');';
		$this->declarations .= "\n";

		return $playerCode;
	}
	
	function replaceYoutube($matches) {
		$id = '';
		$ret = '';
		$url = trim(trim($matches[1], chr(0xC2).chr(0xA0)));
		if (!$url) return $ret;
		
		if (strpos($url, 'youtu.be') !== false) {
			$id = str_replace(array('http://', 'https://', 'youtu.be', '/'), '', $url);
		} elseif (strpos($url, 'youtube.com') !== false) {
			$uri = JURI::getInstance($url);
			$query = $uri->getQuery(true);
			if (is_array($query) && count($query) && isset($query['v']) && $query['v']) $id = $query['v'];
		}
		
		if ($id) {
			$width = $this->params->def('youtube_width', 560);
			$height = $this->params->def('youtube_height', 315);
			$ret = '<iframe width="' . $width . '" height="' . $height . '" src="http://www.youtube.com/embed/' . $id . '" frameborder="0" webkitAllowFullScreen mozallowfullscreen allowFullScreen></iframe>';
		}
		
		return $ret;
	}
	
	function replaceVimeo($matches) {
		$id = '';
		$ret = '';
		
		$url = $matches[1];
		if (strpos($url, 'w=') !== false) $url = mb_substr($url, 0, strpos($url, 'w=')-1);
		$url = trim(trim($url, chr(0xC2).chr(0xA0)));
		if (!$url) return $ret;
		
		$id = str_replace(array('http://', 'https://', 'vimeo.com', 'www', '/', '.'), '', $url);
		
		if ($id) {
			$width = $this->params->def('vimeo_width', 500);
			$height = $this->params->def('vimeo_height', 281);
			$ret = '<iframe src="http://player.vimeo.com/video/' . $id . '?title=1&amp;byline=1&amp;portrait=1" width="' . $width . '" height="' . $height . 'height" frameborder="0" webkitAllowFullScreen mozallowfullscreen allowFullScreen></iframe>';
		}
		
		return $ret;
	}

	function php2js($object) {
		$js_options = '{';
		$separator = '';
		$real_separator = ',';
		foreach($object as $key=>$value) {
			// Format booleans
			if (is_bool($value)) $value = $value? 'yes' : 'no';
			else if (in_array($key, array('soundFile', 'titles', 'artists'))) {
				if (in_array($key, array('titles', 'artists'))) {
					// Decode HTML entities in titles and artists
					if (function_exists('html_entity_decode')) {
						$value = html_entity_decode($value);
					}
				}
				$value = rawurlencode($value);
			}
			$js_options .= $separator . $key . ':"' . $value .'"';
			$separator = $real_separator;
		}
		$js_options .= '}';
		
		return $js_options;
	}
}
