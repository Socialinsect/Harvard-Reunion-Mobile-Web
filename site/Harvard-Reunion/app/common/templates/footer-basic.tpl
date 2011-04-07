{extends file="findExtends:common/templates/footer-basic.tpl"}

{block name="footerNavLinks"}
  <p class="bb"> </p>

  {if $moduleID != 'login' && !($moduleID == 'home' && $page == 'index')}
    {$smarty.block.parent}
  {/if}
{/block}

{block name="loginHTML"}
{/block}
