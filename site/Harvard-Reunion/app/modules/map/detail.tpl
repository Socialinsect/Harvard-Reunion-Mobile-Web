{extends file="findExtends:modules/$moduleID/detail.tpl"}

{block name="tabView"}
  <a name="scrolldown"> </a>
  <div class="focal shaded">
    <h2>{$name}</h2>
    <p class="address">{$address|replace:' ':'&shy; '}</p>
    {if $canBookmark|default:true}
      {include file="findInclude:common/bookmark.tpl" name=$cookieName item=$bookmarkItem exdate=$expireDate}
    {/if}
    {include file="findInclude:common/tabs.tpl" tabBodies=$tabBodies}
  </div>
{/block}
