{include file="findInclude:common/templates/header.tpl"}

<div class="nonfocal">
  <h3>{$eventTitle}</h3>
</div>

{if isset($checkinResult['error'])}
  <div class="focal">
    Checkin failed. {$checkinResult['error']}
  </div>
{elseif isset($checkinResult['message']) || isset($checkinResult['points'])}
  <div class="focal smallprint">
    {if $checkinResult['message']}
      {$checkinResult['message']|escape}
    {else}
      You checked in!
    {/if}
    {if $checkinResult['points']}
      You earned {$checkinResult['points']} point{if $checkinResult['points'] > 1}s{/if}!
    {/if}
  </div>
{/if}

<div id="autoupdateHeader">
  {include file="findInclude:modules/$moduleID/templates/checkinHeaderContent.tpl" state=$state}
</div>

{if !count($checkins['self']) && !isset($checkins['error'])}
  <div class="focal checkinForm">
    <form method="get" action="addCheckin">
      <textarea rows="3" name="message" id="messageText" placeholder="Add a shout with this checkin (optional)"></textarea>
      <input type="submit" name="submit" value="Check In" onlick="_gaq.push(['_trackEvent', '{$smarty.const.GA_EVENT_CATEGORY}', 'Foursquare Checkin', '{$eventTitle|escape:'javascript'}']);" />
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
