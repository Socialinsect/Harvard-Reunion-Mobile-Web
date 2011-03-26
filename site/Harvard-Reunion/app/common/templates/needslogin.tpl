<div class="nonfocal">
  {if $service['type'] == 'facebook'}
    {if $service['items'] == 'videos'}
      <p>Videos are posted to the Facebook group page for each class.  To view and comment on videos, you must sign into Facebook, and you must be a member of the class Facebook group.</p>
    {elseif $service['items'] == 'photos'}
      <p>Photos are posted to the Facebook group page for each class.  To view and comment on photos, you must sign into Facebook, and you must be a member of the class Facebook group.</p>
    {else}
      <p>To view and add updates to the class Facebook group, you must sign into Facebook, and you must be a member of the group</p>
    {/if}
  {else}
    <p>This page requires {$service['name']} access.</p>
  {/if}
  
  <div class="signinbuttons">
    <a class="signinbutton {$service['type']}" href="{$service['url']}">Sign in to {$service['name']}</a>
  </div>
</div>
