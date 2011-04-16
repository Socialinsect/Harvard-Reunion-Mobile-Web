{include file="findInclude:common/templates/header.tpl"}

<div class="nonfocal">
  <h3>{$eventTitle}</h3>
</div>

<div id="autoupdateHeader">
  {include file="findInclude:modules/$moduleID/templates/checkinHeaderContent.tpl" state=$state}
</div>

{if !$state['checkedin']}
  <div class="focal checkinForm">
    <form method="get" action="addCheckin">
      <textarea rows="3" name="message" id="messageText" placeholder="Add a shout with this checkin"></textarea>
      <input type="submit" name="submit" value="Check In" />
      {foreach $hiddenArgs as $arg => $value}
        <input type="hidden" name="{$arg}" value="{$value}" />
      {/foreach}
    </form>
  </div>
{/if}

<div id="autoupdateContent">
  {include file="findInclude:modules/$moduleID/templates/checkinContent.tpl" state=$state}
</div>

{include file="findInclude:common/templates/footer.tpl"}
