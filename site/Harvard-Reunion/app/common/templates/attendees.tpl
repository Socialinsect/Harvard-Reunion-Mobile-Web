{if $authority == 'harris'}
  <div class="nonfocal">
    <h3>{$eventName}</h3>
    {if $groups}
      <span class="smallprint">Sorted by last name</span>
    {/if}
  </div>
  {if $groups}
    {include file="findInclude:common/templates/navlist.tpl" navlistItems=$groups}
    
  {elseif count($attendees)}
    <div class="focal">
      {foreach $attendees as $attendee}
        {$attendee['title']}{if !$attendee@last}<br/>{/if}
      {/foreach}
    </div>
  {else}
    <div class="focal">
      No one has signed up for {$eventName}.
    </div>
  {/if}
{else}
  <div class="nonfocal">
    <p>In order to see the list of attendees you must sign in.</p>
    <p><a class="signinbutton" href="{$signinURL}"><span>Sign in</span> &gt;</a></p>
  </div>
{/if}
