{extends file="findExtends:common/templates/footer-touch.tpl"}

{block name="footerNavLinks"}
  {if $moduleID != 'login' && !($moduleID == 'home' && $page == 'index')}
    <div id="footerlinks">
      <a href="#top">Back to top</a> 
      {if $hasHelp} | <a href="help.php">Help</a>{/if}
       | <a href="../home/">{$strings.SITE_NAME} home</a>
    </div>
  {/if}
{/block}

{block name="loginHTML"}
{/block}
