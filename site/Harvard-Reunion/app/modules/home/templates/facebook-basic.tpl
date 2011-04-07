{extends file="findExtends:modules/$moduleID/templates/facebook.tpl"}

{block name="facebookComment"}
  <textarea rows="3" name="message" id="messageText" placeholder="Share an update with the {$groupName} group"></textarea><br/>
  <input type="submit" value="Share" onclick="return validateTextInputForm('messageText', 'Please enter a message to post to the Facebook group.');" />
{/block}

{block name="facebookPost"}
  "{$post['message']}"<br/>
  <span class="smallprint"> - {$post['author']['name']}, {$post['when']['delta']}</span>
  {if !$lastPost}<br/>{/if}
{/block}

{block name="facebookFooter"}
  <p class="nonfocal smallprint">
    Signed in as {$user} (<a href="{$switchUserURL}">change</a>)
    </br>
    View {$groupName} at <a href="{$groupURL}">facebook.com</a>
  </p>
{/block}
