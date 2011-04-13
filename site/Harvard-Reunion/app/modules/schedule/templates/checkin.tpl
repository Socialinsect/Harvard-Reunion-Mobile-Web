{include file="findInclude:common/templates/header.tpl"}

<div class="nonfocal">
  <h3>{$eventTitle}</h3>
</div>

{if !$state['checkedin']}
  <div class="focal checkinForm">
    Check in to {$eventTitle}
  
    <form method="get" action="addCheckin">
      <textarea rows="3" name="message" id="messageText" placeholder="Add a shout with this checkin"></textarea>
      <input type="submit" name="submit" value="Check In" />
      {foreach $hiddenArgs as $arg => $value}
        <input type="hidden" name="{$arg}" value="{$value}" />
      {/foreach}
    </form>
  </div>
{/if}

<div id="autoupdateContainer">
  {include file="findInclude:modules/$moduleID/templates/checkinContent.tpl" state=$state}
</div>

{include file="findInclude:common/templates/footer.tpl"}
