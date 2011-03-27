{include file="findInclude:common/templates/header.tpl"}

{if $authority == 'harris'}
  <div class="nonfocal">
    <h3>{$reunionTitle} Reunion</h3>
  </div>
  {if count($attendees)}
    {include file="findInclude:common/templates/navlist.tpl" navlistItems=$attendees accessKey=false}
  {else}
    <div class="focal">
      No one has signed up for this reunion.
    </div>
  {/if}
{else}
  <div class="nonfocal">
    <p>In order to see the list of attendees you must log in.</p>
  </div>
{/if}

{include file="findInclude:common/templates/footer.tpl"}
