<?php
/**
 * @package     Joomla.Platform
 * @subpackage  Document
 *
 * @copyright   Copyright (C) 2005 - 2015 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 */

defined('JPATH_PLATFORM') or die;

/**
 * JDocument head renderer
 *
 * @since  11.1
 */
class JDocumentRendererHead extends JDocumentRenderer
{
	/**
	 * Renders the document head and returns the results as a string
	 *
	 * @param   string  $head     (unused)
	 * @param   array   $params   Associative array of values
	 * @param   string  $content  The script
	 *
	 * @return  string  The output of the script
	 *
	 * @since   11.1
	 *
	 * @note    Unused arguments are retained to preserve backward compatibility.
	 */
	public function render($head, $params = array(), $content = null)
	{
		return $this->fetchHead($this->_doc);
	}

	/**
	 * Generates the head HTML and return the results as a string
	 *
	 * @param   JDocument  $document  The document for which the head will be created
	 *
	 * @return  string  The head hTML
	 *
	 * @since   11.1
	 */
	public function fetchHead($document)
	{
		// Convert the tagids to titles
		if (isset($document->_metaTags['standard']['tags']))
		{
			$tagsHelper = new JHelperTags;
			$document->_metaTags['standard']['tags'] = implode(', ', $tagsHelper->getTagNames($document->_metaTags['standard']['tags']));
		}

		// Trigger the onBeforeCompileHead event
		$app = JFactory::getApplication();
		$app->triggerEvent('onBeforeCompileHead');

		// Get line endings
		$lnEnd = $document->_getLineEnd();
		$tab = $document->_getTab();
		$tagEnd = ' />';
		$buffer = '';

		// Generate charset when using HTML5 (should happen first)
		if ($document->isHtml5())
		{
			$buffer .= $tab . '<meta charset="' . $document->getCharset() . '" />' . $lnEnd;
		}

		// Generate base tag (need to happen early)
		$base = $document->getBase();

		if (!empty($base))
		{
			$buffer .= $tab . '<base href="' . $document->getBase() . '" />' . $lnEnd;
		}

		// Generate META tags (needs to happen as early as possible in the head)
		foreach ($document->_metaTags as $type => $tag)
		{
			foreach ($tag as $name => $content)
			{
				if ($type == 'http-equiv' && !($document->isHtml5() && $name == 'content-type'))
				{
					$buffer .= $tab . '<meta http-equiv="' . $name . '" content="' . htmlspecialchars($content) . '" />' . $lnEnd;
				}
				elseif ($type == 'standard' && !empty($content))
				{
					$buffer .= $tab . '<meta name="' . $name . '" content="' . htmlspecialchars($content) . '" />' . $lnEnd;
				}
			}
		}

		// Don't add empty descriptions
		$documentDescription = $document->getDescription();

		if ($documentDescription)
		{
			$buffer .= $tab . '<meta name="description" content="' . htmlspecialchars($documentDescription) . '" />' . $lnEnd;
		}

		// Don't add empty generators
		$generator = $document->getGenerator();

		if ($generator)
		{
			$buffer .= $tab . '<meta name="generator" content="' . htmlspecialchars($generator) . '" />' . $lnEnd;
		}

		$buffer .= $tab . '<title>' . htmlspecialchars($document->getTitle(), ENT_COMPAT, 'UTF-8') . '</title>' . $lnEnd;

		// Generate link declarations
		foreach ($document->_links as $link => $linkAtrr)
		{
			$buffer .= $tab . '<link href="' . $link . '" ' . $linkAtrr['relType'] . '="' . $linkAtrr['relation'] . '"';

			if ($temp = JArrayHelper::toString($linkAtrr['attribs']))
			{
				$buffer .= ' ' . $temp;
			}

			$buffer .= ' />' . $lnEnd;
		}

		// Generate stylesheet links
		foreach ($document->_styleSheets as $strSrc => $strAttr)
		{
			$buffer .= $tab . '<link rel="stylesheet" href="' . $strSrc . '"';

			if (!is_null($strAttr['mime']) && (!$document->isHtml5() || $strAttr['mime'] != 'text/css'))
			{
				$buffer .= ' type="' . $strAttr['mime'] . '"';
			}

			if (!is_null($strAttr['media']))
			{
				$buffer .= ' media="' . $strAttr['media'] . '"';
			}

			if ($temp = JArrayHelper::toString($strAttr['attribs']))
			{
				$buffer .= ' ' . $temp;
			}

			$buffer .= $tagEnd . $lnEnd;
		}

		// Generate stylesheet declarations
		foreach ($document->_style as $type => $content)
		{
			$buffer .= $tab . '<style type="' . $type . '">' . $lnEnd;

			// This is for full XHTML support.
			if ($document->_mime != 'text/html')
			{
				$buffer .= $tab . $tab . '/*<![CDATA[*/' . $lnEnd;
			}

			$buffer .= $content . $lnEnd;

			// See above note
			if ($document->_mime != 'text/html')
			{
				$buffer .= $tab . $tab . '/*]]>*/' . $lnEnd;
			}

			$buffer .= $tab . '</style>' . $lnEnd;
		}

		// Generate script file links (B/C)
		foreach ($document->_scripts as $strSrc => $strAttr)
		{
			$buffer .= $tab . '<script src="' . $strSrc . '"';
			$defaultMimes = array(
				'text/javascript', 'application/javascript', 'text/x-javascript', 'application/x-javascript'
			);

			if (!is_null($strAttr['mime']) && (!$document->isHtml5() || !in_array($strAttr['mime'], $defaultMimes)))
			{
				$buffer .= ' type="' . $strAttr['mime'] . '"';
			}

			if ($strAttr['defer'])
			{
				$buffer .= ' defer="defer"';
			}

			if ($strAttr['async'])
			{
				$buffer .= ' async="async"';
			}

			$buffer .= '></script>' . $lnEnd;

			// Mark as loaded in _scriptsQueue
			$document->_scriptsQueue[$strAttr['mime']][sha1($strSrc)]['loaded'] = true;
		}

		// Generate script declarations (B/C)
		foreach ($document->_script as $type => $content)
		{
			$buffer .= $tab . '<script type="' . $type . '">' . $lnEnd;

			// This is for full XHTML support.
			if ($document->_mime != 'text/html')
			{
				$buffer .= $tab . $tab . '//<![CDATA[' . $lnEnd;
			}

			$buffer .= $content . $lnEnd;

			// See above note
			if ($document->_mime != 'text/html')
			{
				$buffer .= $tab . $tab . '//]]>' . $lnEnd;
			}

			$buffer .= $tab . '</script>' . $lnEnd;

			// Mark as loaded in _scriptsQueue
			$document->_scriptsQueue[$strAttr['mime']][sha1($content)]['loaded'] = true;
		}

		// Generate script tags that are in scripts queue
		foreach ($document->_scriptsQueue as $script_mime => $scripts)
		{
			foreach ($scripts as $script_id => $script)
			{
				$buffer = $this->renderScript($document, $buffer, $script_mime, $script_id);
			}
		}

		// Generate script language declarations.
		if (count(JText::script()))
		{
			$buffer .= $tab . '<script type="text/javascript">' . $lnEnd;

			if ($document->_mime != 'text/html')
			{
				$buffer .= $tab . $tab . '//<![CDATA[' . $lnEnd;
			}

			$buffer .= $tab . $tab . '(function() {' . $lnEnd;
			$buffer .= $tab . $tab . $tab . 'Joomla.JText.load(' . json_encode(JText::script()) . ');' . $lnEnd;
			$buffer .= $tab . $tab . '})();' . $lnEnd;

			if ($document->_mime != 'text/html')
			{
				$buffer .= $tab . $tab . '//]]>' . $lnEnd;
			}

			$buffer .= $tab . '</script>' . $lnEnd;
		}

		// Output the custom tags - array_unique makes sure that we don't output the same tags twice
		foreach (array_unique($document->_custom) as $custom)
		{
			$buffer .= $tab . $custom . $lnEnd;
		}

		return $buffer;
	}

	/**
	 * Generates a script
	 *
	 * @param   JDocument  $document   The document for which the head will be created
	 * @param   string     $buffer     The current buffer
	 * @param   string     $script_id  The script id
	 *
	 * @return  string  buffer
	 *
	 * @since   12.1
	 */
	protected function renderScript($document, $buffer, $script_mime, $script_id)
	{
		$defaultMimes = array(
			'text/javascript', 'application/javascript', 'text/x-javascript', 'application/x-javascript'
		);
		$lnEnd = $document->_getLineEnd();
		$tab = $document->_getTab();

		// Load dependencies if needed
		if (isset($document->_scriptsQueue[$script_mime][$script_id]['dependencies']))
		{
			foreach ($document->_scriptsQueue[$script_mime][$script_id]['dependencies'] as $script_dependency)
			{
				$dependency_id = sha1($script_dependency);
				// Load dependent script
				if (!isset($document->_scriptsQueue[$script_mime][$dependency_id]))
				{
					$document->addScriptToQueue($script_dependency, 'external');
				}

				// Render dependent script if not loaded yet
				if (!$document->_scriptsQueue[$script_mime][$dependency_id]['loaded'])
				{
					$buffer = $this->renderScript($document, $buffer, $script_mime, $dependency_id);
				}
			}
		}

		$buffer .= $tab . '<script';

		// Adds attributes
		if (isset($document->_scriptsQueue[$script_mime][$script_id]['attribs']))
		{
			foreach ($document->_scriptsQueue[$script_mime][$script_id]['attribs'] as $attrib => $attrib_value)
			{
				if ($attrib == 'type')
				{
					if (!$document->isHtml5() || !in_array($attrib_value, $defaultMimes))
					{
						$buffer .= ' type="' . $attrib_value . '"';
					}
				}
				else
				{
					$buffer .= ' ' . $attrib . '=' . (!is_array($attrib_value) ? '"' . $attrib_value . '"' : '\'' . json_encode($attrib_value) . '\'');
				}
			}
		}

		$buffer .= '>';
		// Inline script
		if (isset($document->_scriptsQueue[$script_mime][$script_id]['content']))
		{
			// This is for full XHTML support.
			if ($document->_mime != 'text/html')
			{
				$buffer .= $tab . $tab . '//<![CDATA[' . $lnEnd;
			}

			$buffer .= $document->_scriptsQueue[$script_mime][$script_id]['content'] . $lnEnd;

			// See above note
			if ($document->_mime != 'text/html')
			{
				$buffer .= $tab . $tab . '//]]>' . $lnEnd;
			}
		}

		$buffer .= '</script>' . $lnEnd;

		$document->_scriptsQueue[$script_mime][$script_id]['loaded'] = true;

		return $buffer;
	}
}
