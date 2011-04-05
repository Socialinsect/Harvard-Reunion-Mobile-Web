<div class="focal">
  {foreach $attendees as $attendee}
    {$attendee['title']}{if !$attendee@last}<br/>{/if}
  {/foreach}
</div>
