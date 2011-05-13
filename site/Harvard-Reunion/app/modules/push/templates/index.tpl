{include file="findInclude:common/templates/header.tpl"}

{if $didSend}
<div class="nonfocal">
The following message:
<p>{$messageText}</p>
was sent to {$messageRecipient}
</div>
{/if}

<div class="focal">
  <form method="post" action="sendMessage">
    <textarea rows="3" name="message" placeholder="Type your message"></textarea>

    <p>Select year</p>
    <select id="year" name="year">
      {foreach $years as $aYear}
        <option value="{$aYear}"{if $selectedYear == $aYear} selected="selected"{/if} >
          {$aYear}
        </option>
      {/foreach}
    </select>

    <input type="submit" name="submit" value="Send" />

  </form>
</div>

    
{include file="findInclude:common/templates/footer.tpl"}
