<div class="nonfocal">
  {if $service['type'] == 'facebook'}
    <p>Reunion {$service['items']} are posted to the {$service['name']} group page for each class. To view and share {$service['items']}, you must be signed in to {$service['name']}, and be a member of the class {$service['name']} group.</h3>
  {else}
    <h3>This page requires {$service['name']} access.</h3>
  {/if}
  
  <div class="signinbuttons">
    <a class="signinbutton {$service['type']}" href="{$service['url']}">Sign in to {$service['name']}</a>
  </div>
</div>
