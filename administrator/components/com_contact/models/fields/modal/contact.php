<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_contact
 *
 * @copyright   Copyright (C) 2005 - 2016 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('JPATH_BASE') or die;

/**
 * Supports a modal contact picker.
 *
 * @since  1.6
 */
class JFormFieldModal_Contact extends JFormField
{
	/**
	 * The form field type.
	 *
	 * @var     string
	 * @since   1.6
	 */
	protected $type = 'Modal_Contact';

	/**
	 * Method to get the field input markup.
	 *
	 * @return  string  The field input markup.
	 *
	 * @since   1.6
	 */
	protected function getInput()
	{
		$allowEdit  = ((string) $this->element['edit'] == 'true') ? true : false;
		$allowClear = ((string) $this->element['clear'] != 'false') ? true : false;

		// Load language
		JFactory::getLanguage()->load('com_contact', JPATH_ADMINISTRATOR);

		// Build the script.
		$script = array();

		// Select button script
		$script[] = '	function jSelectContact_' . $this->id . '(id, name, object) {';
		$script[] = '		document.getElementById("' . $this->id . '_id").value = id;';
		$script[] = '		document.getElementById("' . $this->id . '_name").value = name;';

		if ($allowEdit)
		{
			$script[] = '		if (id == "' . (int) $this->value . '") {';
			$script[] = '			jQuery("#' . $this->id . '_edit").removeClass("hidden");';
			$script[] = '		} else {';
			$script[] = '			jQuery("#' . $this->id . '_edit").addClass("hidden");';
			$script[] = '		}';
		}

		if ($allowClear)
		{
			$script[] = '		jQuery("#' . $this->id . '_clear").removeClass("hidden");';
		}

		$script[] = '		jQuery("#contactSelect' . $this->id . 'Modal").modal("hide");';

		if ($this->required)
		{
			$script[] = '		document.formvalidator.validate(document.getElementById("' . $this->id . '_id"));';
			$script[] = '		document.formvalidator.validate(document.getElementById("' . $this->id . '_name"));';
		}

		$script[] = '	}';

		// Clear button script
		static $scriptClear;

		if ($allowClear && !$scriptClear)
		{
			$scriptClear = true;

			$script[] = '	function jClearContact(id) {';
			$script[] = '		document.getElementById(id + "_id").value = "";';
			$script[] = '		document.getElementById(id + "_name").value = "'
				. htmlspecialchars(JText::_('COM_CONTACT_SELECT_A_CONTACT', true), ENT_COMPAT, 'UTF-8') . '";';
			$script[] = '		jQuery("#"+id + "_clear").addClass("hidden");';
			$script[] = '		if (document.getElementById(id + "_edit")) {';
			$script[] = '			jQuery("#"+id + "_edit").addClass("hidden");';
			$script[] = '		}';
			$script[] = '		return false;';
			$script[] = '	}';
		}

		// Add the script to the document head.
		JFactory::getDocument()->addScriptDeclaration(implode("\n", $script));

		// Setup variables for display.
		$html = array();

		$linkContacts = 'index.php?option=com_contact&amp;view=contacts&amp;layout=modal&amp;tmpl=component&amp;function=jSelectContact_' . $this->id;
		$linkContact  = 'index.php?option=com_contact&amp;view=contact&amp;layout=modal&amp;tmpl=component&amp;task=contact.edit';

		if (isset($this->element['language']))
		{
			$linkContacts .= '&amp;forcedLanguage=' . $this->element['language'];
			$linkContact  .= '&amp;forcedLanguage=' . $this->element['language'];
		}

		if ((int) $this->value > 0)
		{
			$db    = JFactory::getDbo();
			$query = $db->getQuery(true)
				->select($db->quoteName('name'))
				->from($db->quoteName('#__contact_details'))
				->where($db->quoteName('id') . ' = ' . (int) $this->value);
			$db->setQuery($query);

			try
			{
				$title = $db->loadResult();
			}
			catch (RuntimeException $e)
			{
				JError::raiseWarning(500, $e->getMessage());
			}
		}

		if (empty($title))
		{
			$title = JText::_('COM_CONTACT_SELECT_A_CONTACT');
		}

		$title = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');

		// The active contact id field.
		if (0 == (int) $this->value)
		{
			$value = '';
		}
		else
		{
			$value = (int) $this->value;
		}

		$urlSelect = $linkContacts . '&amp;' . JSession::getFormToken() . '=1';
		$urlEdit   = $linkContact . '&amp;id=' . $value . '&amp;' . JSession::getFormToken() . '=1';

		// The current contact display field.
		$html[] = '<span class="input-append">';
		$html[] = '<input class="input-medium" id="' . $this->id . '_name" type="text" value="' . $title . '" disabled="disabled" size="35" />';

		// Select contact button
		$html[] = '<a'
			. ' class="btn hasTooltip"'
			. ' data-toggle="modal"'
			. ' role="button"'
			. ' href="#contactSelect' . $this->id . 'Modal"'
			. ' title="' . JHtml::tooltipText('COM_CONTACT_CHANGE_CONTACT') . '">'
			. '<span class="icon-file"></span> ' . JText::_('JSELECT')
			. '</a>';

		// Edit contact button
		if ($allowEdit)
		{
			$html[] = '<a'
				. ' class="btn hasTooltip' . ($value ? '' : ' hidden') . '"'
				. ' id="' . $this->id . '_edit"'
				. ' data-toggle="modal"'
				. ' role="button"'
				. ' href="#contactEdit' . $this->id . 'Modal"'
				. ' title="' . JHtml::tooltipText('COM_CONTACT_EDIT_CONTACT') . '">'
				. '<span class="icon-edit"></span> ' . JText::_('JACTION_EDIT')
				. '</a>';
		}

		// Clear contact button
		if ($allowClear)
		{
			$html[] = '<button'
				. ' class="btn' . ($value ? '' : ' hidden') . '"'
				. ' id="' . $this->id . '_clear"'
				. ' onclick="return jClearContact(\'' . $this->id . '\')">'
				. '<span class="icon-remove"></span>' . JText::_('JCLEAR')
				. '</button>';
		}

		$html[] = '</span>';

		// Select contact modal
		$html[] = JHtml::_(
			'bootstrap.renderModal',
			'contactSelect' . $this->id . 'Modal',
			array(
				'url'         => $urlSelect,
				'title'       => JText::_('COM_CONTACT_CHANGE_CONTACT'),
				'width'       => '800px',
				'height'      => '400px',
				'modalWidth'  => '80',
				'bodyHeight'  => '70',
				'footer'      => '<button type="button" class="btn" data-dismiss="modal" aria-hidden="true">'
						. JText::_("JLIB_HTML_BEHAVIOR_CLOSE") . '</button>'
			)
		);

		// Edit contact modal
		$html[] = JHtml::_(
			'bootstrap.renderModal',
			'contactEdit' . $this->id . 'Modal',
			array(
				'url'         => $urlEdit,
				'title'       => JText::_('COM_CONTACT_EDIT_CONTACT'),
				'backdrop'    => 'static',
				'closeButton' => false,
				'width'       => '800px',
				'height'      => '400px',
				'modalWidth'  => '80',
				'bodyHeight'  => '70',
				'footer'      => '<button type="button" class="btn" data-dismiss="modal" aria-hidden="true"'
						. ' onclick="jQuery(\'#contactEdit' . $this->id . 'Modal iframe\').contents().find(\'#closeBtn\').click();">'
						. JText::_("JLIB_HTML_BEHAVIOR_CLOSE") . '</button>'
						. '<button type="button" class="btn btn-primary" data-dismiss="modal" aria-hidden="true"'
						. ' onclick="jQuery(\'#contactEdit' . $this->id . 'Modal iframe\').contents().find(\'#saveBtn\').click();">'
						. JText::_("JSAVE") . '</button>'
						. '<button type="button" class="btn btn-success" aria-hidden="true"'
						. ' onclick="jQuery(\'#contactEdit' . $this->id . 'Modal iframe\').contents().find(\'#applyBtn\').click();">'
						. JText::_("JAPPLY") . '</button>'
			)
		);

		// Note: class='required' for client side validation.
		$class = $this->required ? ' class="required modal-value"' : '';

		$html[] = '<input type="hidden" id="' . $this->id . '_id"' . $class . ' name="' . $this->name . '" value="' . $value . '" />';

		return implode("\n", $html);
	}

	/**
	 * Method to get the field label markup.
	 *
	 * @return  string  The field label markup.
	 *
	 * @since   3.4
	 */
	protected function getLabel()
	{
		return str_replace($this->id, $this->id . '_id', parent::getLabel());
	}
}
