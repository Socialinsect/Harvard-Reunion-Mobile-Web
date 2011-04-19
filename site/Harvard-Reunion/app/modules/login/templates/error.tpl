{include file="findInclude:common/templates/header.tpl" customHeader=''}

<div class="nonfocal">

  {include file="findInclude:modules/{$moduleID}/templates/banner.tpl"}

  <h3>Login Failed</h3>
  <p>
    {if $resultCode == $smarty.const.AUTH_USER_DISABLED}
      User account has been disabled
    {elseif $resultCode == $smarty.const.AUTH_ERROR}
      Unable to communicate with login server
    {else}
      An unknown error occurred ({$resultCode})
    {/if}
  </p>
  
  <p class="signinbuttons">
    <a class="signinbutton submit" href="{$continueURL}">Try Again</a>
  </p>

</div>

{include file="findInclude:common/templates/footer.tpl"}
