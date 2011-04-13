<div class="nonfocal">
  <p>
    {if $state['checkedin']}
      You 
      {if $state['otherCount']}
        and {$state['otherCount']} other {if $state['otherCount'] > 1}people{else}person{/if}
      {/if} 
      are checked in
    {elseif $state['otherCount']}
      {$state['otherCount']} {if $state['otherCount'] > 1}people are{else}person is{/if} checked in
    {else}
      No one has checked in yet.
    {/if}
  </p>
</div>

{if count($state['checkins'])}
  <div class="focal">
    {foreach $state['checkins'] as $checkin}
      {$checkin['name']}<span class="smallprint"> - {$checkin['when']['shortDelta']}</span>
      {if !$checkin@last}<br/>{/if}
    {/foreach}
  </div>
{/if}
