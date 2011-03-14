{extends file="findExtends:modules/$moduleID/templates/detail.tpl"}

{block name="tabView"}
  <a name="scrolldown"> </a>
  <div class="focal shaded">
    <h2>{$name}</h2>
    <p class="address">{$address|replace:' ':'&shy; '}</p>
    {if $canBookmark|default:true}
      {include file="findInclude:common/templates/bookmark.tpl" name=$cookieName item=$bookmarkItem exdate=$expireDate}
    {/if}
    {include file="findInclude:common/templates/tabs.tpl" tabBodies=$tabBodies}
  </div>
{/block}
