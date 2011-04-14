{extends file="findExtends:modules/$moduleID/templates/facebook.tpl"}

{block name="facebookComment"}
  <label for="messageText">Share an update with the {$groupName} group</label><br/>
  <textarea rows="3" name="message" id="messageText"></textarea><br/>
  <input type="submit" value="Share" />
{/block}
