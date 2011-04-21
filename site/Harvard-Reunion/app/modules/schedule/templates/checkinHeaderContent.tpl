<div class="nonfocal">
  <p>
    {if $state['error']}
      {$state['error']}
    {else}
      {if $state['checkedin']}
        You're here!<br/>
      {/if}
      {if $state['otherCount']}
        {$state['otherCount']} 
        {if $state['checkedin']}other {/if}
        {if $state['otherCount'] > 1}people are{else}person is{/if} here
        {if $state['friendCount']} including {$state['friendCount']} of your friends{/if}
      {else}
        No one {if $state['checkedin']}else {/if}has checked in yet
      {/if}
    {/if}
  </p>
</div>
