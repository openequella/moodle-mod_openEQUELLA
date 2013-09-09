<?php

// This file is part of the EQUELLA Moodle Integration - https://github.com/equella/moodle-module
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
* This class is a thin wrapper around the PHP SoapClient object.  It is provided for convenience.
*/
class EQUELLA
{
	public $client;

	#endpoint is of the form:  http://myserver/mysint/services/SoapService41
	public function __construct($endpoint)
	{
		$this->client = new SoapClient($endpoint.'?wsdl', array(
			'location' => $endpoint,
			'cache_wsdl' => WSDL_CACHE_BOTH
		));
	}

	public function hasMethod($methodname) {
		$methodname = ' '.$methodname.'(';
		foreach( $this->client->__getFunctions() as $func ) {
			if( strpos($func, $methodname) !== false ) {
				return true;
			}
		}
		return false;
	}

	public function login($username, $password) {
		$this->client->login(array(
			'in0' => $username,
			'in1' => $password
		));
	}

	public function loginWithToken($token) {
		$this->client->loginWithToken(array(
			'in0' => $token
		));
	}

	public function logout() {
		$this->client->logout();
	}

	public function getItem($uuid, $version) {
		return new XMLWrapper($this->client->getItem(array(
			'in0' => $uuid,
			'in1' => $version,
			'in2' => '*'
		))->out);
	}

	/**
	* @return XMLWrapper
	*/
	public function searchItems($query, $collectionUuids, $where, $onlylive, $sorttype, $reversesort, $offset, $maxresults)
	{
		return new XMLWrapper($this->client->searchItems(array(
			'in0' => $query,
			'in1' => $collectionUuids,
			'in2' => $where,
			'in3' => $onlylive,
			'in4' => $sorttype,
			'in5' => $reversesort,
			'in6' => $offset,
			'in7' => $maxresults
		))->out);
	}

	/**
	* @return XMLWrapper
	*/
	public function searchableCollections()
	{
		return new XMLWrapper($this->client->getSearchableCollections()->out);
	}
	
	/**
	* @return XMLWrapper
	*/
	public function contributableCollections()
	{
		return new XMLWrapper($this->client->getContributableCollections()->out);
	}

	public function getTaskFilterCounts($ignoreZero = false)
	{
		return new XMLWrapper($this->client->getTaskFilterCounts(array(
			'in0' => $ignoreZero
		))->out);
	}

	/**
	* @return XMLWrapper
	*/
	public function newItem($collectionUuid)
	{
		return new XMLWrapper($this->client->newItem(array(
			'in0' => $collectionUuid
		))->out);
	}

	/**
	* @param XMLWrapper
	* @param int (boolean)
	*/
	public function saveItem($item, $submit)
	{
		$this->client->saveItem(array(
			'in0' => $item,
			'in1' => $submit
		));
	}

	public function uploadFile($stagingUuid, $serverFilename, $localFilename)
	{
		$fd = fopen($localFilename, 'rb');
		$size = filesize($localFilename);
		$fileData = fread($fd, $size);
		fclose($fd);
		$base64Data = base64_encode($fileData);
		$this->client->uploadFile(array(
			'in0' => $stagingUuid,
			'in1' => $serverFilename,
			'in2' => $base64Data,
			'in3' => '1'
		));
	}

	public function getCollection($collectionUuid)
	{
		return new XMLWrapper($this->client->getCollection(array(
			'in0' => $collectionUuid
		))->out);
	}

	public function getSchema($schemaUuid)
	{
		return new XMLWrapper($this->client->getSchema(array(
			'in0' => $schemaUuid
		))->out);
	}
}

/**
* A wrapper around the DOMDocument and DOMXPath classes.  It is provided for convenience.
*/
class XMLWrapper
{
	private $domDoc;
	private $xpathDoc;

	public function __construct($xmlString)
	{
		$this->domDoc = new DOMDocument();
		$this->domDoc->loadXML($xmlString);
		$this->xpathDoc = new DOMXPath($this->domDoc);
	}

	public function __toString()
	{
		return $this->domDoc->saveXML();
	}

	public function node($xpath, $nodeContext=null)
	{
		if ($nodeContext == null)
		{
			$nodeList = $this->xpathDoc->query($xpath);
		}
		else
		{
			$nodeList = $this->xpathDoc->query($xpath, $nodeContext);
		}
		return $this->singleNodeFromList( $nodeList );
	}

	public function nodeValue($xpath, $nodeContext=null)
	{
		if ($nodeContext == null)
		{
			$nodeList = $this->xpathDoc->query($xpath);
		}
		else
		{
			$nodeList = $this->xpathDoc->query($xpath, $nodeContext);
		}
		return $this->singleNodeValueFromList( $nodeList );
	}

	public function nodeList($xpath)
	{
		return $this->xpathDoc->query($xpath);
	}

	public function setNodeValue($xpath, $value, $createIfNotExists = true)
	{
		$node = $this->singleNodeFromList( $this->nodeList($xpath) );
		if ($node == null)
		{
			$node = $this->createNodeFromXPath($xpath);
		}
		$node->nodeValue = $value;
		return $node;
	}

	public function createNode($parent, $nodeName)
	{
		$node = $this->domDoc->createElement($nodeName);
		$parent->appendChild($node);
		return $node;
	}

	public function createNodeFromXPath($xpath)
	{
		$node = $this->node($xpath);
		if ($node == null)
		{
			$xpathElements = explode('/', $xpath);
			$path = '';
			$node = $this->domDoc->documentElement;
			foreach ($xpathElements as $element)
			{
				if (!empty($element))
				{
					$path = $path.'/'.$element;
					$nextNode = $this->node($path);
					if ($nextNode == null)
					{
						$node = $this->createNode($node, $element);
					}
					else
					{
						$node = $nextNode;
					}
				}
			}
		}
		return $node;
	}

	public function createAttribute($parent, $attrName)
	{
		$node = $this->domDoc->createAttribute($attrName);
		$parent->appendChild($node);
		return $node;
	}

	public function deleteNodeFromXPath($xpath)
	{
		$node = $this->node($xpath);
		if ($node != null)
		{
			$node->parentNode->removeChild($node);
		}
	}

	private function singleNodeValueFromList($nodeList)
	{
		$node = $this->singleNodeFromList($nodeList);
		if ($node == null)
		{
			return '';
		}
		return $node->nodeValue;
	}

	private function singleNodeFromList($nodeList)
	{
		if ($nodeList->length > 0)
		{
			$node = $nodeList->item(0);
			return $node;
		}
		return null;
	}
}
?>
