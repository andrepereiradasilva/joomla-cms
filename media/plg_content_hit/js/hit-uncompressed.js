/**
 * @copyright   Copyright (C) 2005 - 2016 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

/**
 * View Hit javascript behavior
 *
 * Used for making an ajax call to do a view hit.
 *
 * @package  Joomla
 * @since    __DEPLOY_VERSION__
 */
!(function(){
	'use strict';

	document.addEventListener('DOMContentLoaded', function() {

		var hitOptions = Joomla.getOptions('plg_content_hit'),
		    hitAjaxUrl = hitOptions.ajaxurl ? hitOptions.ajaxUrl.replace('&amp;', '') : 'index.php?option=com_ajax&group=content&plugin=contentHit&format=json',
		    hitContext = hitOptions.context ? hitOptions.context : '',
		    hitId      = hitOptions.id ? hitOptions.id : null,
		    hitToken   = hitOptions.token ? hitOptions.token : null;

		if (hitId !== null && hitToken !== null && hitContext !== '')
		{
			Joomla.request({
				url   : hitAjaxUrl,
				method: 'POST',
				data  : 'context=' + hitContext + '&id=' + hitId + '&' + hitToken + '=1',
				onSuccess: function(response, xhr)
				{
					// Do nothing
				},
				onError: function(xhr)
				{
					Joomla.renderMessages(Joomla.ajaxErrorsMessages(xhr));
				}
			});
		}
	});

})(document, Joomla);
