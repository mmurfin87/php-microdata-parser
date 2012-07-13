<?php

function ParseMicrodata($root)
{		
	$node = $root;
	$item = null;
	$itemList = array();
	$itemStack = array();
	$propertyName = null;
	$propertyValue = null;
	$itemtype = null;
	$itemid = null;
	$itemref = null;
	$id = null;
	
	while ($node != null)
	{		
		// Depth first traversal. If there are children, visit them next
		if ($node->firstChild != null)
		{
			// Ignore #text nodes
			if ($node->nodeType == XML_ELEMENT_NODE)
			{
				// Get the name and value of the property, if one exists
				$propertyName = null;
				if ($node->hasAttribute('itemprop') && ($propertyName = $node->getAttribute('itemprop')) != '')
				{
					switch ($node->nodeName)
					{
					case 'meta':
						$propertyValue = $node->getAttribute('content');
						break;
					case 'audio':
					case 'embed':
					case 'iframe':
					case 'img':
					case 'source':
					case 'video':
						$propertyValue = $node->getAttribute('src');
						break;
					case 'a':
					case 'area':
					case 'link':
						$propertyValue = $node->getAttribute('href');
						break;
					case 'object':
						$propertyValue = $node->getAttribute('data');
						break;
					case 'time':
						$propertyValue = $node->getAttribute('datetime');
						break;
					default:
						$propertyValue = trim($node->textContent);
						break;
					}
				}
				
				// If there is an itemscope, create a new item
				if ($node->hasAttribute('itemscope') == true)
				{
					$itemtype = $node->hasAttribute('itemtype') ? $node->getAttribute('itemtype') : null;
					$itemid = $node->hasAttribute('itemid') ? $node->getAttribute('itemid') : null;
					$itemref = $node->hasAttribute('itemref') ? $node->getAttribtue('itemref') : null;
					$id = $node->hasAttribute('id') ? $node->getAttribute('id') : null;
					
					// If $item is null, this is a top level item (note that we ignore any itemprop tag on top level items)
					if ($item == null)
					{
						$itemList[] = (object)array('itemtype' => $itemtype, 'itemid' => $itemid, 'id' => $id, 'properties' => array(), 'parentItem' => null);
						$item = $itemList[count($itemList)-1];
					}
					else if ($propertyName == null)	// Else this is a top level item but it's inside another item, so deal with that
					{
						$itemList[] = (object)array('itemtype' => $itemtype, 'itemid' => $itemid, 'id' => $id, 'properties' => array(), 'parentItem' => null);
						$itemStack[] = $item;
						$item = $itemList[count($itemList)-1];
					}
					else // Else its a "used item"
					{
						AddProperty($item, $propertyName, (object)array('itemtype' => $itemtype, 'itemid' => $itemid, 'id' => $id, 'properties' => array(), 'parentItem' => null));
						$item = $item->properties[$propertyName];
					}
				}
				else if ($propertyName != null)
					AddProperty($item, $propertyName, $propertyValue);
			}
			$node = $node->firstChild;
		}
		else if ($node->nextSibling != null)
		{
			$node = $node->nextSibling;
		}
		else 	// Otherwise, go back up the tree
		{
			// Get the next sibling, or the closest parent with a sibling, or null if we're done
			while ($node != null && $node->nextSibling == null)
			{
				$node = $node->parentNode;
				
				if ($node != null && $node->nodeType == XML_ELEMENT_NODE && $node->hasAttribute('itemscope') == true && $item != null)
					$item = (count($itemStack) > 0 ? array_pop($itemStack) : $item->parentItem);
			}
				
			// If $node is null, we're done parsing the entire tree
			if ($node != null)
				$node = $node->nextSibling;
		}
	}
	
	return $itemList;
}

function AddProperty(&$item, $name, $value)
{
	if ($item == null)
		return;
		
	if (isset($item->properties[$name]) && is_array($item->properties[$name]))
		$item->properties[$name][] = $value;
	else if (isset($item->properties[$name]))
		$item->properties[$name] = array($item->properties[$name], $value);
	else	
		$item->properties[$name] = $value;
}

?>