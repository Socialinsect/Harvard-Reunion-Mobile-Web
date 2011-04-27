{extends file="findExtends:modules/$moduleID/templates/detail.tpl"}

{block name="tabView"}
  {if in_array('event', $tabKeys)}
    {capture name="eventPane" assign="eventPane"}
      {block name="eventPane"}
        <p>{$eventDate}</p>
        <p>{$eventLocation}<br/>{$eventAddress}</p>
        {if $eventRegistration}{$eventRegistration}{/if}
        {include file="findInclude:common/templates/navlist.tpl" navlistItems=$eventLinks accessKey=false nested=true}
      {/block}
    {/capture}
    {$oldTabBodies = $tabBodies}
    {$tabBodies = array()}
    {foreach $oldTabBodies as $tabKey => $tabBody}
      {$tabBodies[$tabKey] = $oldTabBodies[$tabKey]}
      {if $tabKey == 'map'}
        {$tabBodies['event'] = $eventPane} {* insert after map tab *}
      {/if}
    {/foreach}   
    {$tabbedView['tabs']['info']['title'] = "Location"}
  {/if}

  <a name="scrolldown"> </a>
  <div class="focal shaded">
    {include file="findInclude:common/templates/bookmark.tpl" name=$cookieName item=$bookmarkItem exdate=$expireDate}
    <h2>{$name}</h2>
    <p class="address">{$address|replace:' ':'&shy; '}</p>
    {include file="findInclude:common/templates/tabs.tpl" tabBodies=$tabBodies}
  </div>
{/block}
