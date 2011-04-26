<div class="nonfocal">
  <p>
    {if $checkins['error']}
      {$checkins['error']}
    {else}
      {$checkedIn = count($checkins['self']) > 0}
      {$nonSelfCount = count($checkins['friends']) + count($checkins['others'])}
      {$totalCount = $nonSelfCount + count($checkins['self'])}
      
      {if $checkedIn}
        {if $nonSelfCount}
          You and 
        {else}
          You're here!<br/>
        {/if}
      {/if}
      {if $nonSelfCount}
        {$nonSelfCount} 
        {if $checkedIn}other {/if}
        {if $nonSelfCount > 1}people{else}person{/if} {if $totalCount > 1}are{else}is{/if} here
      {else}
        No one {if $checkedIn}else {/if}has checked in yet
      {/if}
    {/if}
  </p>
</div>
