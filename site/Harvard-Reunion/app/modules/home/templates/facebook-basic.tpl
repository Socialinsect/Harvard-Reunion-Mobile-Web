{extends file="findExtends:modules/$moduleID/templates/facebook.tpl"}

{block name="facebookComment"}
  <label for="messageText">Share an update with the {$groupName} group</label><br/>
  <textarea rows="3" name="message" id="messageText"></textarea><br/>
  <input type="submit" value="Share" />
{/block}

{block name="facebookPost"}
  "{$post['message']}"<br/>
  <span class="smallprint"> - {$post['author']['name']}, {$post['when']['delta']}</span>
  {if !$lastPost}<br/>{/if}
{/block}

{block name="facebookFooter"}
  <p class="nonfocal smallprint">
    Signed in as {$user} (<a href="{$switchUserURL}">change</a>)
    <br/>
    View {$groupName} at <a href="{$groupURL}">facebook.com</a>
  </p>
{/block}
