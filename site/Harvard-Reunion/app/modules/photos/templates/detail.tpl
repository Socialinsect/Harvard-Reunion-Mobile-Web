{include file="findInclude:common/templates/header.tpl" scalable=false}

{if $needsLogin}
  {include file="findInclude:common/templates/needslogin.tpl" service=$service}

{elseif $needsJoinGroup}
  {include file="findInclude:common/templates/needsjoin.tpl" service=$service}
  
{else}
  {capture name="postHTML" assign="postHTML"}
    <div class="photo">
      <img src="{if $photo['img']['src']}{$photo['img']['src']}{else}{$photo['thumbnail']}{/if}" />
    </div>
  {/capture}
  {$photo['html'] = $postHTML}
  
  {include file="findInclude:common/templates/postdetail.tpl" post=$photo}
{/if}

{include file="findInclude:common/templates/footer.tpl"}
