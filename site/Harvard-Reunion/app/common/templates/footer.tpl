{extends file="findExtends:common/templates/footer.tpl"}

{block name="footerNavLinks"}
  {if $moduleID != 'login' && !($moduleID == 'home' && $page == 'index')}
    <div id="footerlinks">
      <a href="#top">Back to top</a> | <a href="../home/">{$strings.SITE_NAME} home</a>
    </div>
  {/if}
{/block}
