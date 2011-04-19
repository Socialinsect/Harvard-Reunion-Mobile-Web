{include file="findInclude:common/templates/header.tpl" customHeader=''}

<div class="nonfocal">

  {include file="findInclude:modules/{$moduleID}/templates/banner.tpl"}

  <p>{if $message}{$message}{else}An unknown error occurred.{/if}</p>
  
  <p class="signinbuttons">
    <a class="signinbutton cancel" href="{$url}">Try Again</a>
  </p>

</div>

{include file="findInclude:common/templates/footer.tpl"}
