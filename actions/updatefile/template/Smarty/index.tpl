{**
 *  \details &copy; 2011  Open Ximdex Evolution SL [http://www.ximdex.org]
 *
 *  Ximdex a Semantic Content Management System (CMS)
 *
 *  This program is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU Affero General Public License as published
 *  by the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU Affero General Public License for more details.
 *
 *  See the Affero GNU General Public License for more details.
 *  You should have received a copy of the Affero GNU General Public License
 *  version 3 along with Ximdex (see LICENSE file).
 *
 *  If not, visit http://gnu.org/licenses/agpl-3.0.html.
 *
 *  @author Ximdex DevTeam <dev@ximdex.com>
 *  @version $Revision$
 *}

<form enctype="multipart/form-data" method="post" name="up_form" id="up_form" action="{$action_url}">
<input type="hidden" name="nodeid" value="{$id_node}">
<fieldset>
	<legend><span>{t}Upload file{/t}</span></legend>
	<p>
			<label for="upload" class="aligned">{t}File{/t}</label>
			<input type="file" name="upload" id="upload" class="cajaxg validable not_empty">
		</p>
</fieldset>
<fieldset class="buttons-form">
			{button label="Upload file" class="validate"}<!--message="Are you sure you want to upload this file?" -->
</fieldset>
